#!/bin/sh
# ldap-auth.sh - External LDAP authentication for Google Workspace
# OPTIMIZED: Direct bind with email (no search needed!) + credential caching
#
# Google Workspace LDAP accepts email address as bind identity
# No need to search for DN - just bind directly with email + password
#
# Returns: 0 = success (Access-Accept), 1 = failure (Access-Reject)

# Arguments from FreeRADIUS exec module
USER_NAME="$1"
USER_PASSWORD="$2"

# LDAP Configuration for Google Workspace
LDAP_SERVER="ldaps://ldap.google.com:636"
LDAP_CLIENT_CERT="/etc/freeradius/certs/ldap-client.crt"
LDAP_CLIENT_KEY="/etc/freeradius/certs/ldap-client.key"

# Cache configuration (in seconds)
# Use /tmp for cache (writable by freerad user)
CACHE_DIR="/tmp/ldap-auth-cache"
CACHE_TTL=300  # 5 minutes cache

# Log for debugging
LOG_FILE="/var/log/freeradius/ldap-auth.log"

log_debug() {
    echo "[$(date '+%Y-%m-%d %H:%M:%S')] $1" >> "$LOG_FILE" 2>/dev/null
}

# Validate input
if [ -z "$USER_NAME" ] || [ -z "$USER_PASSWORD" ]; then
    log_debug "Missing username or password"
    exit 1
fi

# Decode password if URL-encoded (GTC sends $ as =24, etc.)
USER_PASSWORD=$(printf '%s' "$USER_PASSWORD" | sed 's/=3D24/$/g; s/=3D23/#/g; s/=3D26/\&/g; s/=3D40/@/g')
USER_PASSWORD=$(printf '%s' "$USER_PASSWORD" | sed 's/=24/$/g; s/=23/#/g; s/=26/\&/g; s/=40/@/g; s/=3D/=/g')

# Create cache directory if needed (use /tmp which is always writable)
if [ ! -d "$CACHE_DIR" ]; then
    mkdir -p "$CACHE_DIR" 2>/dev/null
    chmod 700 "$CACHE_DIR" 2>/dev/null
fi

# Generate cache key from username + password hash (for security)
CACHE_KEY=$(printf '%s:%s' "$USER_NAME" "$USER_PASSWORD" | md5sum | cut -d' ' -f1)
CACHE_FILE="$CACHE_DIR/$CACHE_KEY"

# Check cache first
if [ -f "$CACHE_FILE" ]; then
    CACHE_AGE=$(($(date +%s) - $(stat -c %Y "$CACHE_FILE" 2>/dev/null || echo 0)))
    if [ "$CACHE_AGE" -lt "$CACHE_TTL" ]; then
        log_debug "Cache hit for: $USER_NAME (age: ${CACHE_AGE}s)"
        exit 0
    else
        # Cache expired, remove it
        rm -f "$CACHE_FILE" 2>/dev/null
    fi
fi

log_debug "Auth attempt for user: $USER_NAME (direct bind, no search)"

# Set up LDAP TLS environment for client certificate
export LDAPTLS_CERT="$LDAP_CLIENT_CERT"
export LDAPTLS_KEY="$LDAP_CLIENT_KEY"
export LDAPTLS_REQCERT="allow"

# Extract username and domain for building search base
USERNAME=$(echo "$USER_NAME" | sed 's/@.*//')
DOMAIN=$(echo "$USER_NAME" | sed 's/.*@//')
BASE_DN=$(echo "$DOMAIN" | sed 's/\./,dc=/g; s/^/dc=/')

# Direct bind with email address - Google LDAP accepts email as bind identity
# Use password file approach to handle special characters properly
TMPPASS=$(mktemp)
printf '%s' "$USER_PASSWORD" > "$TMPPASS"
chmod 600 "$TMPPASS"

# Bind with email and do a minimal search to verify bind succeeded
# Search for self using mail attribute - this confirms bind worked
BIND_RESULT=$(ldapsearch -H "$LDAP_SERVER" \
    -D "$USER_NAME" \
    -x \
    -y "$TMPPASS" \
    -b "$BASE_DN" \
    -s sub \
    "(mail=$USER_NAME)" \
    dn 2>&1)

BIND_STATUS=$?

# Clean up temp password file
rm -f "$TMPPASS"

# Check bind result - status 0 and finding our own DN means success
if [ $BIND_STATUS -eq 0 ] && echo "$BIND_RESULT" | grep -q "^dn:"; then
    log_debug "Authentication successful for: $USER_NAME"
    # Cache successful auth
    touch "$CACHE_FILE" 2>/dev/null
    chmod 600 "$CACHE_FILE" 2>/dev/null
    exit 0
else
    log_debug "Authentication failed for: $USER_NAME - $BIND_RESULT"
    exit 1
fi
