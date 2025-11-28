#!/bin/bash
# Decode MIME/Quoted-Printable encoded password
# Input: encoded password from Mac WiFi client
# Output: decoded password

# Read password from stdin or first argument
PASSWORD="${1}"

# Decode quoted-printable encoding
# Common patterns:
#   =24 -> $
#   =3D -> =
#   =20 -> space
#   =21 -> !
#   =23 -> #
#   =25 -> %
#   =26 -> &
#   =40 -> @

echo "$PASSWORD" | sed -e 's/=24/$/g' \
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
