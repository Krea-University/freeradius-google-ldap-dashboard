# Sync Active Accounting Sessions to Firewall
# This script sends Accounting-Start packets to the firewall for all currently active sessions
# Use this after enabling firewall replication to sync existing sessions

Write-Host "Syncing active accounting sessions to firewall at 10.10.10.1:1813..." -ForegroundColor Cyan

# Get all active sessions from database
$sessions = docker exec radius-mysql mysql -u radius -pRadiusDbPass2024! radius -e `
    "SELECT username, acctsessionid, framedipaddress, callingstationid, nasipaddress FROM radacct WHERE acctstoptime IS NULL" `
    --batch --skip-column-names 2>$null

if ($LASTEXITCODE -ne 0) {
    Write-Host "ERROR: Failed to query database" -ForegroundColor Red
    exit 1
}

$sessionCount = 0
$successCount = 0
$failCount = 0

foreach ($line in $sessions -split "`n") {
    if ([string]::IsNullOrWhiteSpace($line)) { continue }
    
    $fields = $line -split "`t"
    if ($fields.Count -lt 5) { continue }
    
    $username = $fields[0]
    $sessionId = $fields[1]
    $framedIp = $fields[2]
    $callingStationId = $fields[3]
    $nasIp = $fields[4]
    
    $sessionCount++
    
    Write-Host "`n[$sessionCount] Sending: $username @ $framedIp (Session: $sessionId)" -ForegroundColor Yellow
    
    # Build accounting packet
    $packet = "Acct-Status-Type=Start,User-Name=$username,Framed-IP-Address=$framedIp,Acct-Session-Id=$sessionId,Calling-Station-Id=$callingStationId,NAS-IP-Address=$nasIp"
    
    # Send to firewall
    $result = echo $packet | docker exec -i freeradius-google-ldap radclient -x 10.10.10.1:1813 acct testing123 2>&1
    
    if ($LASTEXITCODE -eq 0 -and $result -match "Received Accounting-Response") {
        Write-Host "  Success: Firewall acknowledged" -ForegroundColor Green
        $successCount++
    }
    else {
        Write-Host "  Failed: No response from firewall" -ForegroundColor Red
        $failCount++
    }
}

Write-Host "`n================================================" -ForegroundColor Cyan
Write-Host "Sync Complete:" -ForegroundColor Cyan
Write-Host "  Total Sessions: $sessionCount" -ForegroundColor White
Write-Host "  Successful:     $successCount" -ForegroundColor Green
Write-Host "  Failed:         $failCount" -ForegroundColor Red
Write-Host "================================================" -ForegroundColor Cyan

if ($failCount -gt 0) {
    Write-Host "WARNING: Some sessions failed to sync. Check firewall connectivity." -ForegroundColor Yellow
    exit 1
}

Write-Host "All active sessions synced to firewall successfully!" -ForegroundColor Green
exit 0
