@echo off
REM FreeRADIUS Certificate Generation Script for Windows
REM Generates new SSL certificates for EAP-TLS/PEAP/TTLS authentication

setlocal enabledelayedexpansion

echo ===================================
echo FreeRADIUS Certificate Generation
echo ===================================
echo.

REM Get certificate information from user
echo Please provide the following information for your certificates:
echo.

set /p COUNTRY="Country Code (2 letters) [US]: "
if "%COUNTRY%"=="" set COUNTRY=US

set /p STATE="State or Province [California]: "
if "%STATE%"=="" set STATE=California

set /p CITY="City [San Francisco]: "
if "%CITY%"=="" set CITY=San Francisco

set /p ORG="Organization Name [KREA University]: "
if "%ORG%"=="" set ORG=KREA University

set /p CA_CN="Common Name for CA [KREA Certificate Authority]: "
if "%CA_CN%"=="" set CA_CN=KREA Certificate Authority

set /p SERVER_CN="Common Name for Server [radius.krea.edu.in]: "
if "%SERVER_CN%"=="" set SERVER_CN=radius.krea.edu.in

set /p EMAIL="Email Address [admin@krea.edu.in]: "
if "%EMAIL%"=="" set EMAIL=admin@krea.edu.in

set /p VALIDITY="Certificate Validity (days) [3650]: "
if "%VALIDITY%"=="" set VALIDITY=3650

echo.
echo Generating certificates with the following details:
echo   Country: %COUNTRY%
echo   State: %STATE%
echo   City: %CITY%
echo   Organization: %ORG%
echo   CA Common Name: %CA_CN%
echo   Server Common Name: %SERVER_CN%
echo   Email: %EMAIL%
echo   Validity: %VALIDITY% days
echo.

set /p CONFIRM="Continue? (y/n) [y]: "
if "%CONFIRM%"=="" set CONFIRM=y
if /i not "%CONFIRM%"=="y" (
    echo Certificate generation cancelled.
    exit /b 1
)

REM Create certs directory if it doesn't exist
if not exist "certs" mkdir certs

REM Clean up old certificates
echo.
echo Cleaning up old certificates...
del /q certs\*.pem certs\*.der certs\*.csr certs\*.key certs\*.p12 certs\*.cnf certs\*.attr certs\index.txt* certs\serial* 2>nul

REM Create OpenSSL configuration for CA
echo.
echo Creating CA configuration...
(
echo [req]
echo default_bits            = 2048
echo input_password          = whatever
echo output_password         = whatever
echo distinguished_name      = req_distinguished_name
echo prompt                  = no
echo.
echo [req_distinguished_name]
echo countryName             = %COUNTRY%
echo stateOrProvinceName     = %STATE%
echo localityName            = %CITY%
echo organizationName        = %ORG%
echo emailAddress            = %EMAIL%
echo commonName              = %CA_CN%
echo.
echo [v3_ca]
echo subjectKeyIdentifier    = hash
echo authorityKeyIdentifier  = keyid:always,issuer:always
echo basicConstraints        = CA:true
echo keyUsage                = keyCertSign, cRLSign
) > certs\ca.cnf

REM Create OpenSSL configuration for Server
echo.
echo Creating Server configuration...
(
echo [req]
echo default_bits            = 2048
echo input_password          = whatever
echo output_password         = whatever
echo distinguished_name      = req_distinguished_name
echo prompt                  = no
echo req_extensions          = v3_req
echo.
echo [req_distinguished_name]
echo countryName             = %COUNTRY%
echo stateOrProvinceName     = %STATE%
echo localityName            = %CITY%
echo organizationName        = %ORG%
echo emailAddress            = %EMAIL%
echo commonName              = %SERVER_CN%
echo.
echo [v3_req]
echo basicConstraints        = CA:FALSE
echo keyUsage                = nonRepudiation, digitalSignature, keyEncipherment
echo extendedKeyUsage        = serverAuth, 1.3.6.1.5.5.8.2.2
echo subjectAltName          = @alt_names
echo.
echo [alt_names]
echo DNS.1                   = %SERVER_CN%
echo DNS.2                   = localhost
echo IP.1                    = 127.0.0.1
) > certs\server.cnf

REM Generate CA private key
echo.
echo Generating CA private key...
openssl genrsa -out certs\ca.key 2048

REM Generate CA certificate
echo.
echo Generating CA certificate...
openssl req -new -x509 -days %VALIDITY% -key certs\ca.key -out certs\ca.pem -config certs\ca.cnf -extensions v3_ca

REM Generate server private key
echo.
echo Generating server private key...
openssl genrsa -out certs\server.key 2048

REM Generate server certificate signing request
echo.
echo Generating server CSR...
openssl req -new -key certs\server.key -out certs\server.csr -config certs\server.cnf

REM Sign server certificate with CA
echo.
echo Signing server certificate...
openssl x509 -req -in certs\server.csr -CA certs\ca.pem -CAkey certs\ca.key -CAcreateserial -out certs\server.crt -days %VALIDITY% -extfile certs\server.cnf -extensions v3_req

REM Combine server certificate and key into single PEM file
echo.
echo Creating combined server.pem...
type certs\server.crt certs\server.key > certs\server.pem

REM Generate DH parameters
echo.
echo Generating DH parameters (this may take several minutes^)...
openssl dhparam -out certs\dh 2048

REM Display certificate information
echo.
echo ===================================
echo Certificate Generated Successfully
echo ===================================
echo.
echo CA Certificate Details:
openssl x509 -in certs\ca.pem -noout -subject -issuer -dates
echo.
echo Server Certificate Details:
openssl x509 -in certs\server.pem -noout -subject -issuer -dates

echo.
echo Certificates have been generated in: certs\
echo.
echo Next steps:
echo   1. Certificates are ready in the certs\ folder
echo   2. Rebuilding and restarting Docker containers...
echo.

REM Rebuild and restart FreeRADIUS container
echo Stopping containers...
docker-compose down

echo.
echo Rebuilding FreeRADIUS container...
docker-compose build freeradius

echo.
echo Starting containers...
docker-compose up -d

echo.
echo Waiting for containers to be healthy...
timeout /t 10 /nobreak >nul

REM Check container status
docker ps | findstr /C:"freeradius-google-ldap" | findstr /C:"healthy" >nul
if %errorlevel%==0 (
    echo [32m✓ FreeRADIUS container is healthy![0m
) else (
    echo [33m⚠ Waiting for FreeRADIUS container to become healthy...[0m
    timeout /t 10 /nobreak >nul
)

echo.
echo ===================================
echo Setup Complete!
echo ===================================
echo.
echo Certificate Details:
echo   CA Certificate: certs\ca.pem
echo   Server Certificate: certs\server.pem
echo   Server Key: certs\server.key
echo   DH Parameters: certs\dh
echo.
echo For Windows clients:
echo   1. Import ca.pem as a Trusted Root Certificate
echo   2. Configure WiFi to use PEAP with MSCHAPv2
echo   3. Validate server certificate: %SERVER_CN%
echo.
echo Windows users should now be able to connect to WiFi!

endlocal
