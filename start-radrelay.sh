#!/bin/bash
#
# Start radrelay daemon for firewall accounting replication
# This script is called from init.sh after FreeRADIUS configuration
#

# Only start if replication is enabled
if [ "${ENABLE_ACCT_REPLICATION}" != "true" ]; then
    echo "Accounting replication disabled, not starting radrelay"
    exit 0
fi

# Create directory for detail files
mkdir -p /var/log/freeradius/firewall
chown freerad:freerad /var/log/freeradius/firewall
chmod 0700 /var/log/freeradius/firewall

echo "Starting radrelay daemon for firewall accounting replication..."
echo "  Target: ${FIREWALL_ACCT_SERVER}:${FIREWALL_ACCT_PORT}"
echo "  Detail file: /var/log/freeradius/firewall/detail"

# Start radrelay in background
# -f: run in foreground (we'll background it ourselves)
# -d: detail file directory
# -n: name (for logging)
# -s: RADIUS server
# -p: port
# -S: shared secret file
echo "${FIREWALL_ACCT_SECRET}" > /tmp/firewall_secret
chmod 0600 /tmp/firewall_secret
chown freerad:freerad /tmp/firewall_secret

# Run as freerad user, in background
su -s /bin/bash freerad -c "radrelay \
    -d /etc/freeradius \
    -n radrelay \
    -D /var/log/freeradius/firewall \
    -a ${FIREWALL_ACCT_SERVER}:${FIREWALL_ACCT_PORT} \
    -s /tmp/firewall_secret \
    -f" >> /var/log/freeradius/radrelay.log 2>&1 &

RADRELAY_PID=$!
echo "radrelay started with PID ${RADRELAY_PID}"
echo ${RADRELAY_PID} > /var/run/radrelay.pid

# Give it a moment to start
sleep 1

# Check if still running
if kill -0 ${RADRELAY_PID} 2>/dev/null; then
    echo "✓ radrelay daemon is running"
else
    echo "✗ radrelay failed to start, check /var/log/freeradius/radrelay.log"
    cat /var/log/freeradius/radrelay.log
    exit 1
fi
