#!/bin/bash
# Decode MIME/Quoted-Printable encoded password
# Input: encoded password from Mac WiFi client
# Output: decoded password

# Read password from stdin or first argument
PASSWORD="${1}"

# First, remove any leading/trailing backslashes that might have been added by shell escaping
# Use tr to remove all backslashes
PASSWORD=$(echo "$PASSWORD" | tr -d '\\')

# Decode quoted-printable encoding
# Common patterns:
#   =3D24 -> $ (This is the specific GTC pattern: =3D is =, 24 is $, together =3D24 becomes =$ but in this context it's $)
#   Actually =3D24 represents a literal $ character in Quoted-Printable
#   =24 -> $
#   =3D -> =
#   =20 -> space
#   =21 -> !
#   =23 -> #
#   =25 -> %
#   =26 -> &
#   =40 -> @

# Process in the correct order to handle =3D24 correctly
# First decode the special pattern =3D24 which represents $
echo "$PASSWORD" | sed -e 's/=3D24/$/g' \
                       -e 's/=24/$/g' \
                       -e 's/=3D/=/g' \
                       -e 's/=20/ /g' \
                       -e 's/=21/!/g' \
                       -e 's/=23/#/g' \
                       -e 's/=25/%/g' \
                       -e 's/=26/\&/g' \
                       -e 's/=2B/+/g' \
                       -e 's/=2C/,/g' \
                       -e 's/=2F/\//g' \
                       -e 's/=3A/:/g' \
                       -e 's/=3B/;/g' \
                       -e 's/=3C/</g' \
                       -e 's/=3E/>/g' \
                       -e 's/=3F/?/g' \
                       -e 's/=40/@/g' \
                       -e 's/=5B/[/g' \
                       -e 's/=5D/]/g' \
                       -e 's/=7B/{/g' \
                       -e 's/=7D/}/g'
