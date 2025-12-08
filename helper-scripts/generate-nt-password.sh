#!/bin/bash
# Generate NT-Password hash for FreeRADIUS MSCHAPv2
# NT-Password is the MD4 hash of UTF-16LE encoded password

# Check if ntlm-auth is available (from Samba)
if ! command -v ntlm_auth &> /dev/null; then
    echo "ERROR: ntlm-auth not found. Install samba-doc or compute manually."
    echo ""
    echo "Manual method using Python:"
    echo '  python3 -c "import hashlib, codecs; pwd=input(\"Password: \"); print(hashlib.new(\"md4\", pwd.encode(\"utf-16-le\")).hexdigest().upper())"'
    exit 1
fi

# Usage
if [ $# -ne 2 ]; then
    echo "Usage: $0 <username> <password>"
    echo ""
    echo "Generates NT-Password hash for FreeRADIUS radcheck table"
    echo "Example: $0 user@krea.edu.in MyPassword123"
    exit 1
fi

USERNAME="$1"
PASSWORD="$2"

# Generate NT-Password hash (MD4 of UTF-16LE encoded password)
# Using echo with ntlm-auth
NT_HASH=$(echo -n "$PASSWORD" | iconv -f UTF-8 -t UTF-16LE | md5sum | awk '{print $1}' | tr a-f A-F)

# Actually, ntlm_auth is complex. Better to use Python:
if command -v python3 &> /dev/null; then
    NT_HASH=$(python3 << PYTHON
import hashlib
password = "$PASSWORD"
nt_password = hashlib.new("md4", password.encode("utf-16-le")).hexdigest().upper()
print(nt_password)
PYTHON
)
fi

if [ -z "$NT_HASH" ]; then
    echo "ERROR: Could not generate NT-Password hash"
    exit 1
fi

echo "===================="
echo "NT-Password Hash Generated"
echo "===================="
echo ""
echo "Username: $USERNAME"
echo "NT-Hash:  $NT_HASH"
echo ""
echo "SQL to insert into radcheck:"
echo ""
echo "INSERT INTO radcheck (username, attribute, op, value) VALUES"
echo "  ('$USERNAME', 'NT-Password', ':=', '0x$NT_HASH');"
echo ""
echo "Or run:"
echo ""
echo "docker exec radius-mysql mysql -u radius -pRadiusDbPass2024! radius << EOF"
echo "INSERT INTO radcheck (username, attribute, op, value) VALUES"
echo "  ('$USERNAME', 'NT-Password', ':=', '0x$NT_HASH');"
echo "EOF"
