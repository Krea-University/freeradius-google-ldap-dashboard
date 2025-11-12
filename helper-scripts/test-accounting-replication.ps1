# FreeRADIUS Accounting Packet Test Script
# Tests accounting packet replication to firewall

Write-Host "`n========================================" -ForegroundColor Cyan
Write-Host "FreeRADIUS Accounting Replication Test" -ForegroundColor Cyan
Write-Host "========================================`n" -ForegroundColor Cyan

# Check if accounting replication is enabled
$envContent = Get-Content .env | Select-String "ENABLE_ACCT_REPLICATION"
Write-Host "Configuration:" -ForegroundColor Yellow
Get-Content .env | Select-String "ENABLE_ACCT_REPLICATION|FIREWALL_ACCT" | ForEach-Object { Write-Host "  $_" }
Write-Host ""

# Test 1: Accounting-Start
Write-Host "[Test 1] Sending Accounting-Start packet..." -ForegroundColor Green
$startCmd = 'echo "Acct-Status-Type=Start,Framed-IP-Address=10.1.156.185,User-Name=testuser@yourdomain.com,Acct-Session-Id=0211a4ef,Class=Staff,Calling-Station-Id=00-0c-29-44-BE-B8" | radclient -x localhost:1813 acct testing123'
$result1 = docker exec freeradius-google-ldap bash -c $startCmd 2>&1 | Select-String "Received Accounting-Response"

if ($result1) {
    Write-Host "  [OK] Accounting-Start sent successfully" -ForegroundColor Green
} else {
    Write-Host "  [FAIL] Accounting-Start failed" -ForegroundColor Red
}

Start-Sleep -Seconds 2

# Test 2: Accounting-Interim-Update
Write-Host "`n[Test 2] Sending Accounting-Interim-Update packet..." -ForegroundColor Green
$interimCmd = 'echo "Acct-Status-Type=Interim-Update,Framed-IP-Address=10.1.156.185,User-Name=testuser@yourdomain.com,Acct-Session-Id=0211a4ef,Acct-Session-Time=1800,Acct-Input-Octets=1048576,Acct-Output-Octets=2097152" | radclient -x localhost:1813 acct testing123'
$result2 = docker exec freeradius-google-ldap bash -c $interimCmd 2>&1 | Select-String "Received Accounting-Response"

if ($result2) {
    Write-Host "  [OK] Accounting-Interim-Update sent successfully" -ForegroundColor Green
} else {
    Write-Host "  [FAIL] Accounting-Interim-Update failed" -ForegroundColor Red
}

Start-Sleep -Seconds 2

# Test 3: Accounting-Stop
Write-Host "`n[Test 3] Sending Accounting-Stop packet..." -ForegroundColor Green
$stopCmd = 'echo "Acct-Status-Type=Stop,Framed-IP-Address=10.1.156.185,User-Name=testuser@yourdomain.com,Acct-Session-Id=0211a4ef,Acct-Session-Time=3600,Acct-Input-Octets=10485760,Acct-Output-Octets=20971520,Acct-Terminate-Cause=User-Request" | radclient -x localhost:1813 acct testing123'
$result3 = docker exec freeradius-google-ldap bash -c $stopCmd 2>&1 | Select-String "Received Accounting-Response"

if ($result3) {
    Write-Host "  [OK] Accounting-Stop sent successfully" -ForegroundColor Green
} else {
    Write-Host "  [FAIL] Accounting-Stop failed" -ForegroundColor Red
}

# Check logs for proxy activity
Write-Host "`n[Test 4] Checking FreeRADIUS logs for firewall proxy..." -ForegroundColor Yellow
Start-Sleep -Seconds 1
$proxyLogs = docker logs freeradius-google-ldap 2>&1 | Select-String -Pattern "Proxying request|10.10.10.1" | Select-Object -Last 5

if ($proxyLogs) {
    Write-Host "  [OK] Found proxy activity to firewall:" -ForegroundColor Green
    $proxyLogs | ForEach-Object { Write-Host "    $_" -ForegroundColor Gray }
} else {
    Write-Host "  [FAIL] No proxy activity found" -ForegroundColor Red
}

# Check for firewall responses
Write-Host "`n[Test 5] Checking for firewall responses..." -ForegroundColor Yellow
$firewallResponses = docker logs freeradius-google-ldap 2>&1 | Select-String -Pattern "from 10.10.10.1" | Select-Object -Last 3

if ($firewallResponses) {
    Write-Host "  [OK] Firewall is responding:" -ForegroundColor Green
    $firewallResponses | ForEach-Object { Write-Host "    $_" -ForegroundColor Gray }
} else {
    Write-Host "  [WARN] No responses from firewall (this is normal if firewall is not configured yet)" -ForegroundColor Yellow
    Write-Host "    FreeRADIUS will still send packets even if firewall does not respond" -ForegroundColor Gray
}

# Summary
Write-Host "`n========================================" -ForegroundColor Cyan
Write-Host "Test Summary" -ForegroundColor Cyan
Write-Host "========================================" -ForegroundColor Cyan

$passCount = 0
if ($result1) { $passCount++ }
if ($result2) { $passCount++ }
if ($result3) { $passCount++ }

Write-Host "Tests Passed: $passCount/3" -ForegroundColor $(if($passCount -eq 3){'Green'}else{'Yellow'})

if ($proxyLogs) {
    Write-Host "[OK] Accounting replication is WORKING" -ForegroundColor Green
    Write-Host "  Packets are being sent to firewall at 10.10.10.1:1813" -ForegroundColor Green
} else {
    Write-Host "[FAIL] Accounting replication is NOT working" -ForegroundColor Red
    Write-Host "  Check ENABLE_ACCT_REPLICATION in .env file" -ForegroundColor Yellow
}

Write-Host "`nNext Steps:" -ForegroundColor Cyan
Write-Host "1. Configure your firewall to accept RADIUS accounting from FreeRADIUS" -ForegroundColor White
Write-Host "2. Set FIREWALL_ACCT_SERVER to your firewalls IP in .env" -ForegroundColor White
Write-Host "3. Verify firewalls RADIUS accounting is enabled and shared secret matches" -ForegroundColor White
Write-Host "4. Check firewalls User-ID logs to see the user-to-IP mappings" -ForegroundColor White
Write-Host ""
