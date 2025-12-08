# Generate NT-Password hash for FreeRADIUS MSCHAPv2
# NT-Password is the MD4 hash of UTF-16LE encoded password

param(
    [Parameter(Mandatory=$true)]
    [string]$Username,
    
    [Parameter(Mandatory=$true)]
    [string]$Password
)

# Function to compute MD4 hash
function Get-MD4Hash {
    param([string]$InputString)
    
    # Convert string to UTF-16LE bytes (as required for NT-Password)
    $encoder = New-Object System.Text.UnicodeEncoding
    $bytes = $encoder.GetBytes($InputString)
    
    # Create MD4 hash
    $md4 = New-Object System.Security.Cryptography.MD4CryptoServiceProvider
    $hashBytes = $md4.ComputeHash($bytes)
    
    # Convert to hex string
    return ($hashBytes | ForEach-Object { "{0:X2}" -f $_ }) -join ""
}

# Generate NT-Password hash
$NTHash = Get-MD4Hash $Password

Write-Host "====================" -ForegroundColor Cyan
Write-Host "NT-Password Hash Generated" -ForegroundColor Cyan
Write-Host "====================" -ForegroundColor Cyan
Write-Host ""
Write-Host "Username: $Username" -ForegroundColor Yellow
Write-Host "NT-Hash:  $NTHash" -ForegroundColor Green
Write-Host ""
Write-Host "SQL to insert into radcheck:" -ForegroundColor Cyan
Write-Host ""
Write-Host "INSERT INTO radcheck (username, attribute, op, value) VALUES" -ForegroundColor Gray
Write-Host "  ('$Username', 'NT-Password', ':=', '0x$NTHash');" -ForegroundColor Gray
Write-Host ""
Write-Host "Or run this PowerShell command:" -ForegroundColor Cyan
Write-Host ""

$sqlCommand = @"
docker exec radius-mysql mysql -u radius -pRadiusDbPass2024! radius -e `
"INSERT INTO radcheck (username, attribute, op, value) VALUES ('$Username', 'NT-Password', ':=', '0x$NTHash');`
SELECT * FROM radcheck WHERE username = '$Username';"
"@

Write-Host $sqlCommand -ForegroundColor Gray
