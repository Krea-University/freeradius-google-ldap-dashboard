# FreeRADIUS Authentication & Accounting Monitor
# Monitors and displays RADIUS packets in a neat, formatted way

param(
    [switch]$FollowLogs = $true,
    [int]$TailLines = 0,
    [switch]$ShowTimestamps = $true
)

Write-Host ""
Write-Host "========================================"  -ForegroundColor Cyan
Write-Host "  FreeRADIUS Packet Monitor" -ForegroundColor Cyan
Write-Host "========================================"  -ForegroundColor Cyan
Write-Host ""

Write-Host "Monitoring: " -NoNewline
Write-Host "Authentication " -ForegroundColor Green -NoNewline
Write-Host "& " -NoNewline
Write-Host "Accounting " -ForegroundColor Yellow -NoNewline
Write-Host "packets"
Write-Host ""

Write-Host "Press " -NoNewline
Write-Host "Ctrl+C" -ForegroundColor Red -NoNewline
Write-Host " to stop monitoring"
Write-Host ""

# Colors for different packet types
$colors = @{
    'Auth'      = 'Green'
    'Acct'      = 'Yellow'
    'Accept'    = 'Green'
    'Reject'    = 'Red'
    'Start'     = 'Cyan'
    'Stop'      = 'Magenta'
    'Update'    = 'Blue'
    'User'      = 'White'
    'IP'        = 'Gray'
    'Session'   = 'DarkGray'
    'Firewall'  = 'DarkYellow'
}

# State tracking
$currentPacket = $null
$packetCount = @{
    'AuthRequest' = 0
    'AuthAccept' = 0
    'AuthReject' = 0
    'AcctStart' = 0
    'AcctStop' = 0
    'AcctUpdate' = 0
    'FirewallSent' = 0
}

function Print-PacketHeader {
    param($Type, $Count, $Time)
    
    $separator = "=" * 60
    Write-Host ""
    Write-Host $separator -ForegroundColor DarkGray
    
    if ($Type -eq "Auth-Request") {
        Write-Host "[AUTH #$Count] " -ForegroundColor Green -NoNewline
        Write-Host "Access-Request" -ForegroundColor White -NoNewline
    }
    elseif ($Type -eq "Auth-Accept") {
        Write-Host "[AUTH #$Count] " -ForegroundColor Green -NoNewline
        Write-Host "Access-Accept" -ForegroundColor Green -NoNewline
    }
    elseif ($Type -eq "Auth-Reject") {
        Write-Host "[AUTH #$Count] " -ForegroundColor Green -NoNewline
        Write-Host "Access-Reject" -ForegroundColor Red -NoNewline
    }
    elseif ($Type -eq "Acct-Start") {
        Write-Host "[ACCT #$Count] " -ForegroundColor Yellow -NoNewline
        Write-Host "Accounting-Start" -ForegroundColor Cyan -NoNewline
    }
    elseif ($Type -eq "Acct-Stop") {
        Write-Host "[ACCT #$Count] " -ForegroundColor Yellow -NoNewline
        Write-Host "Accounting-Stop" -ForegroundColor Magenta -NoNewline
    }
    elseif ($Type -eq "Acct-Update") {
        Write-Host "[ACCT #$Count] " -ForegroundColor Yellow -NoNewline
        Write-Host "Accounting-Update" -ForegroundColor Blue -NoNewline
    }
    
    if ($ShowTimestamps -and $Time) {
        Write-Host " at $Time" -ForegroundColor DarkGray
    } else {
        Write-Host ""
    }
}

function Print-PacketDetail {
    param($Label, $Value, $Color = 'White')
    
    Write-Host "  $Label" -NoNewline -ForegroundColor Gray
    Write-Host ": " -NoNewline
    Write-Host $Value -ForegroundColor $Color
}

function Process-Line {
    param($Line)
    
    $timestamp = Get-Date -Format "HH:mm:ss"
    
    # Authentication Request
    if ($Line -match "Received Access-Request.*from ([\d\.:]+)") {
        $packetCount.AuthRequest++
        $source = $matches[1]
        Print-PacketHeader "Auth-Request" $packetCount.AuthRequest $timestamp
        Print-PacketDetail "Source" $source $colors.IP
        $script:currentPacket = "auth"
    }
    
    # Authentication Accept
    elseif ($Line -match "Sent Access-Accept") {
        $packetCount.AuthAccept++
        Print-PacketHeader "Auth-Accept" $packetCount.AuthAccept $timestamp
        $script:currentPacket = $null
    }
    
    # Authentication Reject
    elseif ($Line -match "Sent Access-Reject") {
        $packetCount.AuthReject++
        Print-PacketHeader "Auth-Reject" $packetCount.AuthReject $timestamp
        $script:currentPacket = $null
    }
    
    # Accounting Request - Start
    elseif ($Line -match "Received Accounting-Request.*from ([\d\.:]+)") {
        $source = $matches[1]
        $script:currentPacket = "acct"
        $script:acctSource = $source
    }
    
    # Accounting Status Type
    elseif ($Line -match "Acct-Status-Type = (\w+)" -and $script:currentPacket -eq "acct") {
        $statusType = $matches[1]
        
        if ($statusType -eq "Start") {
            $packetCount.AcctStart++
            Print-PacketHeader "Acct-Start" $packetCount.AcctStart $timestamp
            Print-PacketDetail "Source" $script:acctSource $colors.IP
        }
        elseif ($statusType -eq "Stop") {
            $packetCount.AcctStop++
            Print-PacketHeader "Acct-Stop" $packetCount.AcctStop $timestamp
            Print-PacketDetail "Source" $script:acctSource $colors.IP
        }
        elseif ($statusType -match "Interim-Update|Alive") {
            $packetCount.AcctUpdate++
            Print-PacketHeader "Acct-Update" $packetCount.AcctUpdate $timestamp
            Print-PacketDetail "Source" $script:acctSource $colors.IP
        }
        
        Print-PacketDetail "Status" $statusType $colors.Acct
    }
    
    # User-Name
    elseif ($Line -match 'User-Name = "([^"]+)"') {
        $username = $matches[1]
        Print-PacketDetail "User" $username $colors.User
    }
    
    # Framed-IP-Address
    elseif ($Line -match "Framed-IP-Address = ([\d\.]+)") {
        $ip = $matches[1]
        Print-PacketDetail "Client IP" $ip $colors.IP
    }
    
    # Session ID
    elseif ($Line -match 'Acct-Session-Id = "([^"]+)"') {
        $sessionId = $matches[1]
        Print-PacketDetail "Session ID" $sessionId $colors.Session
    }
    
    # NAS IP
    elseif ($Line -match "NAS-IP-Address = ([\d\.]+)") {
        $nasIp = $matches[1]
        Print-PacketDetail "NAS (AP)" $nasIp $colors.IP
    }
    
    # Called-Station-Id (SSID/BSSID)
    elseif ($Line -match 'Called-Station-Id = "([^"]+)"') {
        $calledStation = $matches[1]
        Print-PacketDetail "SSID/AP MAC" $calledStation $colors.Session
    }
    
    # Calling-Station-Id (Client MAC)
    elseif ($Line -match 'Calling-Station-Id = "([^"]+)"') {
        $callingStation = $matches[1]
        Print-PacketDetail "Client MAC" $callingStation $colors.Session
    }
    
    # Firewall Replication
    elseif ($Line -match "Sent Accounting-Request.*to 10\.10\.10\.1") {
        $packetCount.FirewallSent++
        Print-PacketDetail "-> Firewall" "Sent to 10.10.10.1:1813" $colors.Firewall
    }
    
    # Firewall Response
    elseif ($Line -match "Received Accounting-Response.*from 10\.10\.10\.1") {
        Print-PacketDetail "<- Firewall" "Response received [OK]" $colors.Firewall
        $script:currentPacket = $null
    }
    
    # Session Time (for Stop packets)
    elseif ($Line -match "Acct-Session-Time = (\d+)") {
        $sessionTime = [int]$matches[1]
        $hours = [Math]::Floor($sessionTime / 3600)
        $minutes = [Math]::Floor(($sessionTime % 3600) / 60)
        $seconds = $sessionTime % 60
        $duration = "{0:D2}h {1:D2}m {2:D2}s" -f $hours, $minutes, $seconds
        Print-PacketDetail "Duration" $duration $colors.Session
    }
    
    # Input/Output Octets (for Stop packets)
    elseif ($Line -match "Acct-Input-Octets = (\d+)") {
        $bytes = [long]$matches[1]
        $mb = [Math]::Round($bytes / 1MB, 2)
        Print-PacketDetail "Downloaded" "$mb MB" $colors.Session
    }
    elseif ($Line -match "Acct-Output-Octets = (\d+)") {
        $bytes = [long]$matches[1]
        $mb = [Math]::Round($bytes / 1MB, 2)
        Print-PacketDetail "Uploaded" "$mb MB" $colors.Session
    }
}

function Print-Statistics {
    Write-Host ""
    Write-Host ""
    Write-Host "========================================" -ForegroundColor Cyan
    Write-Host "  Session Statistics" -ForegroundColor Cyan
    Write-Host "========================================" -ForegroundColor Cyan
    
    Write-Host ""
    Write-Host "Authentication:" -ForegroundColor Green
    Write-Host "  Requests : $($packetCount.AuthRequest)"
    Write-Host "  Accepted : " -NoNewline
    Write-Host "$($packetCount.AuthAccept)" -ForegroundColor Green
    Write-Host "  Rejected : " -NoNewline
    Write-Host "$($packetCount.AuthReject)" -ForegroundColor Red
    
    Write-Host ""
    Write-Host "Accounting:" -ForegroundColor Yellow
    Write-Host "  Start    : " -NoNewline
    Write-Host "$($packetCount.AcctStart)" -ForegroundColor Cyan
    Write-Host "  Stop     : " -NoNewline
    Write-Host "$($packetCount.AcctStop)" -ForegroundColor Magenta
    Write-Host "  Update   : " -NoNewline
    Write-Host "$($packetCount.AcctUpdate)" -ForegroundColor Blue
    
    Write-Host ""
    Write-Host "Firewall Replication:" -ForegroundColor DarkYellow
    Write-Host "  Packets Sent: $($packetCount.FirewallSent)"
    
    Write-Host ""
}

# Catch Ctrl+C to show statistics
$null = Register-EngineEvent -SourceIdentifier PowerShell.Exiting -Action {
    Print-Statistics
}

try {
    # Build docker logs command
    $dockerCmd = "docker logs"
    if ($FollowLogs) {
        $dockerCmd += " -f"
    }
    $dockerCmd += " --tail $TailLines freeradius-google-ldap 2>&1"
    
    # Start monitoring
    Invoke-Expression $dockerCmd | ForEach-Object {
        Process-Line $_
    }
}
catch {
    Write-Host ""
    Write-Host "Monitoring stopped." -ForegroundColor Yellow
}
finally {
    Print-Statistics
}
