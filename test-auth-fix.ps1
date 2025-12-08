# Test script for PEAP authentication with Google Workspace LDAP
# This script tests the fixed inner-tunnel authentication

Write-Host "==================================================" -ForegroundColor Cyan
Write-Host "FreeRADIUS PEAP Authentication Test" -ForegroundColor Cyan
Write-Host "==================================================" -ForegroundColor Cyan
Write-Host ""

# Check if containers are running
Write-Host "1. Checking container status..." -ForegroundColor Yellow
$containers = docker ps | Select-String "freeradius-google-ldap|radius-mysql"
if ($containers.Count -ge 2) {
    Write-Host "✓ Containers are running" -ForegroundColor Green
} else {
    Write-Host "ERROR: Containers not running. Start with: docker-compose up -d" -ForegroundColor Red
    exit 1
}
Write-Host ""

# Check FreeRADIUS logs for errors
Write-Host "2. Checking for configuration errors..." -ForegroundColor Yellow
$errors = docker logs freeradius-google-ldap 2>&1 | Select-String "error" | Select-String -NotMatch "VLAN attribute" | Select-Object -First 5
if ($errors) {
    Write-Host "⚠️  WARNING: Found errors in FreeRADIUS logs:" -ForegroundColor Yellow
    $errors | ForEach-Object { Write-Host "   $_" }
} else {
    Write-Host "✓ No configuration errors found" -ForegroundColor Green
}
Write-Host ""

# Verify inner-tunnel configuration
Write-Host "3. Verifying inner-tunnel EAP-Type detection..." -ForegroundColor Yellow
$eapCheck = docker exec freeradius-google-ldap grep -A 5 'if (\&EAP-Type)' /etc/freeradius/sites-enabled/inner-tunnel 2>&1
if ($eapCheck -and $eapCheck -notmatch "cannot access") {
    Write-Host "✓ EAP-Type detection found in inner-tunnel" -ForegroundColor Green
    Write-Host ""
    docker exec freeradius-google-ldap sh -c "sed -n '188,198p' /etc/freeradius/sites-enabled/inner-tunnel"
} else {
    Write-Host "❌ ERROR: EAP-Type detection NOT found in inner-tunnel" -ForegroundColor Red
    Write-Host "The fix may not have been applied correctly" -ForegroundColor Red
    exit 1
}
Write-Host ""

# Check PEAP configuration
Write-Host "4. Verifying PEAP is configured with GTC..." -ForegroundColor Yellow
$peapCheck = docker exec freeradius-google-ldap grep -A 5 'peap {' /etc/freeradius/mods-enabled/eap 2>&1 | Select-String "default_eap_type = gtc"
if ($peapCheck) {
    Write-Host "✓ PEAP configured to use GTC (Generic Token Card)" -ForegroundColor Green
} else {
    Write-Host "⚠️  WARNING: PEAP may not be configured with GTC" -ForegroundColor Yellow
}
Write-Host ""

# Check LDAP configuration
Write-Host "5. Verifying LDAP authentication is available..." -ForegroundColor Yellow
$ldapCheck = docker exec freeradius-google-ldap grep -A 5 'Auth-Type LDAP' /etc/freeradius/sites-enabled/inner-tunnel 2>&1
if ($ldapCheck -and $ldapCheck -notmatch "cannot access") {
    Write-Host "✓ LDAP authentication module configured" -ForegroundColor Green
} else {
    Write-Host "❌ ERROR: LDAP authentication not configured in inner-tunnel" -ForegroundColor Red
    exit 1
}
Write-Host ""

# Check VLAN configuration
Write-Host "6. Verifying VLAN assignment configuration..." -ForegroundColor Yellow
$vlanCount = docker exec freeradius-google-ldap grep -c 'Tunnel-Private-Group-Id' /etc/freeradius/sites-enabled/inner-tunnel 2>&1
if ($vlanCount -and $vlanCount -match "^\d+$" -and [int]$vlanCount -gt 0) {
    Write-Host "✓ VLAN attributes configured in inner-tunnel" -ForegroundColor Green
    Write-Host "  Found $vlanCount VLAN assignments"
} else {
    Write-Host "⚠️  WARNING: VLAN configuration not found" -ForegroundColor Yellow
}
Write-Host ""

Write-Host "7. Test recommendations:" -ForegroundColor Yellow
Write-Host "   a) Connect a PEAP-capable client (Windows, Mac, or Aruba AP)"
Write-Host "   b) Use credentials: <username>@krea.edu.in"
Write-Host "   c) Monitor logs: docker logs -f freeradius-google-ldap 2>&1 | Select-String 'Access-Accept|Access-Reject|EAP'"
Write-Host "   d) Check database results after authentication attempt"
Write-Host ""

Write-Host "==================================================" -ForegroundColor Cyan
Write-Host "Configuration verification complete!" -ForegroundColor Green
Write-Host "==================================================" -ForegroundColor Cyan
