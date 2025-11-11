# Cache Testing Script for Windows PowerShell
# This script tests LDAP credential caching performance

Write-Host "========================================" -ForegroundColor Cyan
Write-Host "LDAP Credential Cache Performance Test" -ForegroundColor Cyan
Write-Host "========================================" -ForegroundColor Cyan
Write-Host ""

# Test credentials (update with valid credentials)
$username = "erpadmin@krea.edu.in"
$password = "SenthilNasa005`$`$`$"  # Escaped $ for PowerShell
$radiusSecret = "testing123"

Write-Host "Testing user: $username" -ForegroundColor White
Write-Host ""

# Check if cache module is loaded
Write-Host "1. Checking cache module status..." -ForegroundColor Yellow
$cacheEnabled = docker exec freeradius-google-ldap test -L /etc/freeradius/mods-enabled/cache
if ($LASTEXITCODE -eq 0) {
    Write-Host "   ✓ Cache module is ENABLED" -ForegroundColor Green
} else {
    Write-Host "   ✗ Cache module is NOT enabled" -ForegroundColor Red
}

# Check cache configuration
Write-Host ""
Write-Host "2. Cache configuration:" -ForegroundColor Yellow
$cacheTTL = docker exec freeradius-google-ldap grep "ttl = " /etc/freeradius/mods-available/cache 2>$null | Select-Object -First 1
Write-Host "   $cacheTTL" -ForegroundColor White

# Clear cache
Write-Host ""
Write-Host "3. Clearing cache (restarting FreeRADIUS)..." -ForegroundColor Yellow
docker-compose restart freeradius 2>&1 | Out-Null
Start-Sleep -Seconds 5
Write-Host "   ✓ Cache cleared" -ForegroundColor Green

# Test 1: First authentication
Write-Host ""
Write-Host "Test 1: First Authentication (Cache MISS)" -ForegroundColor Cyan
Write-Host "   Expected: Slow (~3-10 seconds) - Full LDAP query" -ForegroundColor Gray
Write-Host "   Testing... " -NoNewline

$time1 = Measure-Command {
    $result1 = docker exec freeradius-google-ldap radtest $username $password localhost 0 $radiusSecret 2>&1
}

if ($result1 -match "Access-Accept") {
    Write-Host "✓ SUCCESS" -ForegroundColor Green
    Write-Host "   Time: $($time1.TotalSeconds.ToString('F3')) seconds" -ForegroundColor Cyan
} else {
    Write-Host "✗ FAILED" -ForegroundColor Red
    Write-Host "   Check credentials and try again" -ForegroundColor Yellow
    Write-Host "   Result: $result1" -ForegroundColor Gray
    exit 1
}

# Wait a moment
Start-Sleep -Seconds 1

# Test 2: Second authentication
Write-Host ""
Write-Host "Test 2: Second Authentication (Cache HIT)" -ForegroundColor Cyan
Write-Host "   Expected: Fast (<1 second) - From cache" -ForegroundColor Gray
Write-Host "   Testing... " -NoNewline

$time2 = Measure-Command {
    $result2 = docker exec freeradius-google-ldap radtest $username $password localhost 0 $radiusSecret 2>&1
}

if ($result2 -match "Access-Accept") {
    Write-Host "✓ SUCCESS" -ForegroundColor Green
    Write-Host "   Time: $($time2.TotalSeconds.ToString('F3')) seconds" -ForegroundColor Cyan
} else {
    Write-Host "✗ FAILED" -ForegroundColor Red
}

# Test 3: Third authentication (verify cache persistence)
Write-Host ""
Write-Host "Test 3: Third Authentication (Verify Cache)" -ForegroundColor Cyan
Write-Host "   Testing... " -NoNewline

$time3 = Measure-Command {
    $result3 = docker exec freeradius-google-ldap radtest $username $password localhost 0 $radiusSecret 2>&1
}

if ($result3 -match "Access-Accept") {
    Write-Host "✓ SUCCESS" -ForegroundColor Green
    Write-Host "   Time: $($time3.TotalSeconds.ToString('F3')) seconds" -ForegroundColor Cyan
}

# Performance comparison
Write-Host ""
Write-Host "========================================" -ForegroundColor Cyan
Write-Host "Performance Comparison:" -ForegroundColor Cyan
Write-Host "========================================" -ForegroundColor Cyan
Write-Host "First auth (no cache):  $($time1.TotalSeconds.ToString('F3')) seconds" -ForegroundColor White
Write-Host "Second auth (cached):   $($time2.TotalSeconds.ToString('F3')) seconds" -ForegroundColor White
Write-Host "Third auth (cached):    $($time3.TotalSeconds.ToString('F3')) seconds" -ForegroundColor White

$improvement = [math]::Round((($time1.TotalSeconds - $time2.TotalSeconds) / $time1.TotalSeconds * 100), 1)
$avgCached = ($time2.TotalSeconds + $time3.TotalSeconds) / 2

Write-Host ""
if ($time2.TotalSeconds -lt $time1.TotalSeconds) {
    Write-Host "Performance improvement: $improvement%" -ForegroundColor Green
    Write-Host "Average cached time: $($avgCached.ToString('F3')) seconds" -ForegroundColor Cyan
    
    if ($improvement -gt 50) {
        Write-Host "✓ Cache is working effectively!" -ForegroundColor Green
    } elseif ($improvement -gt 20) {
        Write-Host "⚠ Cache is working but could be better" -ForegroundColor Yellow
    } else {
        Write-Host "⚠ Cache might not be working optimally" -ForegroundColor Yellow
    }
} else {
    Write-Host "⚠ No improvement detected - cache may not be active" -ForegroundColor Red
}

# Check logs
Write-Host ""
Write-Host "========================================" -ForegroundColor Cyan
Write-Host "Recent FreeRADIUS Cache Activity:" -ForegroundColor Cyan
Write-Host "========================================" -ForegroundColor Cyan
docker logs freeradius-google-ldap --tail 50 2>&1 | Select-String -Pattern "ldap_cache|Cache" | Select-Object -Last 10

Write-Host ""
Write-Host "Test complete!" -ForegroundColor Green
Write-Host ""
Write-Host "Note: If cache is not showing in logs, it may not be properly configured." -ForegroundColor Yellow
Write-Host "Check the authorize section in /etc/freeradius/sites-enabled/default" -ForegroundColor Yellow
