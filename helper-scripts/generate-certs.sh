#!/bin/bash
#
# FreeRADIUS Certificate Generation Script
# Generates new SSL certificates for EAP-TLS/PEAP/TTLS authentication
#

set -e

# Colors for output
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
RED='\033[0;31m'
NC='\033[0m' # No Color

echo -e "${GREEN}=== FreeRADIUS Certificate Generation Script ===${NC}"
echo ""

# Get certificate information from user
echo -e "${YELLOW}Please provide the following information for your certificates:${NC}"
echo ""

read -p "Country Code (2 letters) [US]: " COUNTRY
COUNTRY=${COUNTRY:-US}

read -p "State or Province [California]: " STATE
STATE=${STATE:-California}

read -p "City [San Francisco]: " CITY
CITY=${CITY:-San Francisco}

read -p "Organization Name [Your Organization]: " ORG
ORG=${ORG:-Your Organization}

read -p "Common Name for CA [Your Organization Certificate Authority]: " CA_CN
CA_CN=${CA_CN:-Your Organization Certificate Authority}

read -p "Common Name for Server [radius.yourdomain.com]: " SERVER_CN
SERVER_CN=${SERVER_CN:-radius.yourdomain.com}

read -p "Email Address [admin@yourdomain.com]: " EMAIL
EMAIL=${EMAIL:-admin@yourdomain.com}

read -p "Certificate Validity (days) [3650]: " VALIDITY
VALIDITY=${VALIDITY:-3650}

echo ""
echo -e "${GREEN}Generating certificates with the following details:${NC}"
echo "  Country: $COUNTRY"
echo "  State: $STATE"
echo "  City: $CITY"
echo "  Organization: $ORG"
echo "  CA Common Name: $CA_CN"
echo "  Server Common Name: $SERVER_CN"
echo "  Email: $EMAIL"
echo "  Validity: $VALIDITY days"
echo ""

read -p "Continue? (y/n) [y]: " CONFIRM
CONFIRM=${CONFIRM:-y}

if [[ ! $CONFIRM =~ ^[Yy]$ ]]; then
    echo -e "${RED}Certificate generation cancelled.${NC}"
    exit 1
fi

# Create certs directory if it doesn't exist
CERTS_DIR="./certs"
mkdir -p "$CERTS_DIR"

# Backup LDAP client certificates if they exist (these are from Google Admin Console)
if [ -f "$CERTS_DIR/ldap-client.crt" ]; then
    echo ""
    echo -e "${YELLOW}Backing up LDAP client certificates...${NC}"
    cp "$CERTS_DIR/ldap-client.crt" "$CERTS_DIR/ldap-client.crt.backup"
fi
if [ -f "$CERTS_DIR/ldap-client.key" ]; then
    cp "$CERTS_DIR/ldap-client.key" "$CERTS_DIR/ldap-client.key.backup"
fi

# Clean up old certificates (but preserve LDAP client certs)
echo ""
echo -e "${YELLOW}Cleaning up old RADIUS certificates...${NC}"
rm -f "$CERTS_DIR"/ca.pem "$CERTS_DIR"/ca.key "$CERTS_DIR"/ca.der "$CERTS_DIR"/ca.srl \
      "$CERTS_DIR"/server.pem "$CERTS_DIR"/server.key "$CERTS_DIR"/server.crt "$CERTS_DIR"/server.csr "$CERTS_DIR"/server.der "$CERTS_DIR"/server.p12 \
      "$CERTS_DIR"/*.attr "$CERTS_DIR"/index.txt* "$CERTS_DIR"/serial* "$CERTS_DIR"/*.cnf "$CERTS_DIR"/dh

# Restore LDAP client certificates
if [ -f "$CERTS_DIR/ldap-client.crt.backup" ]; then
    mv "$CERTS_DIR/ldap-client.crt.backup" "$CERTS_DIR/ldap-client.crt"
fi
if [ -f "$CERTS_DIR/ldap-client.key.backup" ]; then
    mv "$CERTS_DIR/ldap-client.key.backup" "$CERTS_DIR/ldap-client.key"
fi

# Create OpenSSL configuration for CA
echo ""
echo -e "${YELLOW}Creating CA configuration...${NC}"
cat > "$CERTS_DIR/ca.cnf" <<EOF
[ req ]
default_bits            = 2048
input_password          = whatever
output_password         = whatever
distinguished_name      = req_distinguished_name
prompt                  = no

[ req_distinguished_name ]
countryName             = $COUNTRY
stateOrProvinceName     = $STATE
localityName            = $CITY
organizationName        = $ORG
emailAddress            = $EMAIL
commonName              = $CA_CN

[ v3_ca ]
subjectKeyIdentifier    = hash
authorityKeyIdentifier  = keyid:always,issuer:always
basicConstraints        = CA:true
keyUsage                = keyCertSign, cRLSign
EOF

# Create OpenSSL configuration for Server with Windows-compatible extensions
echo ""
echo -e "${YELLOW}Creating Server configuration...${NC}"
cat > "$CERTS_DIR/server.cnf" <<EOF
[ req ]
default_bits            = 2048
input_password          = whatever
output_password         = whatever
distinguished_name      = req_distinguished_name
prompt                  = no
req_extensions          = v3_req

[ req_distinguished_name ]
countryName             = $COUNTRY
stateOrProvinceName     = $STATE
localityName            = $CITY
organizationName        = $ORG
emailAddress            = $EMAIL
commonName              = $SERVER_CN

[ v3_req ]
basicConstraints        = CA:FALSE
keyUsage                = nonRepudiation, digitalSignature, keyEncipherment
extendedKeyUsage        = serverAuth, 1.3.6.1.5.5.8.2.2
subjectAltName          = @alt_names

[ alt_names ]
DNS.1                   = $SERVER_CN
DNS.2                   = localhost
IP.1                    = 127.0.0.1
EOF

# Generate CA private key
echo ""
echo -e "${YELLOW}Generating CA private key...${NC}"
openssl genrsa -out "$CERTS_DIR/ca.key" 2048

# Generate CA certificate
echo ""
echo -e "${YELLOW}Generating CA certificate...${NC}"
openssl req -new -x509 -days "$VALIDITY" -key "$CERTS_DIR/ca.key" -out "$CERTS_DIR/ca.pem" -config "$CERTS_DIR/ca.cnf" -extensions v3_ca

# Generate server private key
echo ""
echo -e "${YELLOW}Generating server private key...${NC}"
openssl genrsa -out "$CERTS_DIR/server.key" 2048

# Generate server certificate signing request
echo ""
echo -e "${YELLOW}Generating server CSR...${NC}"
openssl req -new -key "$CERTS_DIR/server.key" -out "$CERTS_DIR/server.csr" -config "$CERTS_DIR/server.cnf"

# Sign server certificate with CA (with Windows-compatible extensions)
echo ""
echo -e "${YELLOW}Signing server certificate...${NC}"
openssl x509 -req -in "$CERTS_DIR/server.csr" -CA "$CERTS_DIR/ca.pem" -CAkey "$CERTS_DIR/ca.key" -CAcreateserial -out "$CERTS_DIR/server.crt" -days "$VALIDITY" -extfile "$CERTS_DIR/server.cnf" -extensions v3_req

# Combine server certificate and key into single PEM file
echo ""
echo -e "${YELLOW}Creating combined server.pem...${NC}"
cat "$CERTS_DIR/server.crt" "$CERTS_DIR/server.key" > "$CERTS_DIR/server.pem"

# Generate DH parameters (this takes a while)
echo ""
echo -e "${YELLOW}Generating DH parameters (this may take several minutes)...${NC}"
openssl dhparam -out "$CERTS_DIR/dh" 2048

# Set proper permissions
echo ""
echo -e "${YELLOW}Setting permissions...${NC}"
chmod 640 "$CERTS_DIR"/*.key
chmod 644 "$CERTS_DIR"/*.pem "$CERTS_DIR"/*.crt "$CERTS_DIR"/dh

# Display certificate information
echo ""
echo -e "${GREEN}=== Certificate Generated Successfully ===${NC}"
echo ""
echo -e "${YELLOW}CA Certificate Details:${NC}"
openssl x509 -in "$CERTS_DIR/ca.pem" -noout -subject -issuer -dates
echo ""
echo -e "${YELLOW}Server Certificate Details:${NC}"
openssl x509 -in "$CERTS_DIR/server.pem" -noout -subject -issuer -dates
echo ""
echo -e "${YELLOW}Certificate Extensions (Windows compatibility):${NC}"
openssl x509 -in "$CERTS_DIR/server.pem" -noout -text | grep -A 10 "X509v3 extensions"

echo ""
echo -e "${GREEN}Certificates have been generated in: $CERTS_DIR${NC}"
echo ""
echo -e "${YELLOW}Next steps:${NC}"
echo "  1. Certificates are ready in the certs/ folder"
echo "  2. Rebuilding and restarting Docker containers..."
echo ""

# Rebuild and restart FreeRADIUS container
echo -e "${YELLOW}Stopping containers...${NC}"
docker-compose down

echo ""
echo -e "${YELLOW}Rebuilding FreeRADIUS container...${NC}"
docker-compose build freeradius

echo ""
echo -e "${YELLOW}Starting containers...${NC}"
docker-compose up -d

echo ""
echo -e "${YELLOW}Waiting for containers to be healthy...${NC}"
sleep 10

# Check container status
if docker ps | grep -q "freeradius-google-ldap.*healthy"; then
    echo -e "${GREEN}✓ FreeRADIUS container is healthy!${NC}"
else
    echo -e "${YELLOW}⚠ Waiting for FreeRADIUS container to become healthy...${NC}"
    sleep 10
fi

echo ""
echo -e "${GREEN}=== Setup Complete! ===${NC}"
echo ""
echo -e "${YELLOW}Certificate Details:${NC}"
echo "  CA Certificate: $CERTS_DIR/ca.pem"
echo "  Server Certificate: $CERTS_DIR/server.pem"
echo "  Server Key: $CERTS_DIR/server.key"
echo "  DH Parameters: $CERTS_DIR/dh"
echo ""
echo -e "${YELLOW}For Windows clients:${NC}"
echo "  1. Import ca.pem as a Trusted Root Certificate"
echo "  2. Configure WiFi to use PEAP with MSCHAPv2"
echo "  3. Validate server certificate: $SERVER_CN"
echo ""
echo -e "${GREEN}Windows users should now be able to connect to WiFi!${NC}"
