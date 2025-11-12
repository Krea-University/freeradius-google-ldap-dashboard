# Quick Start: Accounting Replication to Firewall

## ✅ Setup Complete!

Your FreeRADIUS server is now configured to send accounting packets to your firewall at `10.10.10.1:1813`.

## Test It Now

```powershell
# Run the test script
.\test-accounting-replication.ps1

# Or manually test with your exact command format:
docker exec freeradius-google-ldap bash -c 'echo "Acct-Status-Type=Start,Framed-IP-Address=10.1.156.185,User-Name=testuser@yourdomain.com,Acct-Session-Id=0211a4ef,Class=group1,Calling-Station-Id=00-0c-29-44-BE-B8" | radclient -x localhost:1813 acct testing123'
```

## How It Works

```
┌─────────────┐         ┌──────────────┐         ┌──────────┐
│             │  Acct   │              │  Acct   │          │
│  Access     ├────────>│  FreeRADIUS  ├────────>│ Firewall │
│  Point      │         │              │         │          │
└─────────────┘         └──────────────┘         └──────────┘
                              │
                              │ Log to
                              ▼
                        ┌──────────┐
                        │  MySQL   │
                        │ Database │
                        └──────────┘
```

1. **Access Point** sends accounting packet to FreeRADIUS
2. **FreeRADIUS** processes and logs to database
3. **FreeRADIUS** replicates packet to firewall
4. **Firewall** maps username to IP address

## Configuration Files

### ✅ Already Configured

- **`.env`** - Firewall settings
  ```bash
  ENABLE_ACCT_REPLICATION=true
  FIREWALL_ACCT_SERVER=10.10.10.1
  FIREWALL_ACCT_PORT=1813
  FIREWALL_ACCT_SECRET=testing123
  ```

- **`configs/proxy.conf`** - Home server definition
  ```
  home_server firewall_acct {
      type = acct
      ipaddr = 10.10.10.1
      port = 1813
      secret = testing123
  }
  ```

- **`configs/default`** - Accounting replication
  ```
  accounting {
      sql
      update control {
          Proxy-To-Realm := "firewall_accounting"
      }
      detail
  }
  ```

## Verify It's Working

```powershell
# Check FreeRADIUS logs for proxy activity
docker logs freeradius-google-ldap 2>&1 | Select-String "Proxying request|10.10.10.1"

# You should see:
# (0) Proxy-To-Realm := "firewall_accounting"
# (0) Starting proxy to home server 10.10.10.1 port 1813
# (0) Sent Accounting-Request to 10.10.10.1:1813
```

## Firewall Configuration

### Palo Alto
1. Go to **Device > Server Profiles > RADIUS**
2. Add Server: `<FreeRADIUS-IP>:1813`
3. Secret: `testing123`
4. Enable **User-ID**

### Fortinet
1. Go to **System > Admin > RADIUS**
2. Server: `<FreeRADIUS-IP>:1813`
3. Secret: `testing123`
4. Enable **FSSO** (Fortinet Single Sign-On)

## Accounting Packet Format

### Start (Session Begin)
```bash
echo "Acct-Status-Type=Start,Framed-IP-Address=10.1.156.185,User-Name=testuser@yourdomain.com,Acct-Session-Id=0211a4ef,Class=Staff,Calling-Station-Id=00-0c-29-44-BE-B8" | radclient -x 10.10.10.1:1813 acct testing123
```

### Stop (Session End)
```bash
echo "Acct-Status-Type=Stop,Framed-IP-Address=10.1.156.185,User-Name=testuser@yourdomain.com,Acct-Session-Id=0211a4ef,Acct-Session-Time=3600,Acct-Input-Octets=1048576,Acct-Output-Octets=2097152" | radclient -x 10.10.10.1:1813 acct testing123
```

## Customize

### Change Firewall IP
```bash
# Edit .env
FIREWALL_ACCT_SERVER=192.168.1.100

# Rebuild and restart
docker-compose build freeradius
docker-compose up -d freeradius
```

### Disable Replication
```bash
# Edit .env
ENABLE_ACCT_REPLICATION=false

# Rebuild and restart
docker-compose build freeradius
docker-compose up -d freeradius
```

### Add Multiple Firewalls
Edit `configs/proxy.conf`:
```
home_server firewall_acct2 {
    type = acct
    ipaddr = 10.10.10.2
    port = 1813
    secret = testing123
}

home_server_pool firewall_acct_pool {
    type = fail-over
    home_server = firewall_acct
    home_server = firewall_acct2  # Failover
}
```

## Troubleshooting

### Issue: Packets Not Reaching Firewall
```powershell
# Check connectivity
docker exec freeradius-google-ldap ping -c 3 10.10.10.1

# Check port is open
Test-NetConnection -ComputerName 10.10.10.1 -Port 1813
```

### Issue: Firewall Not Responding
- Verify firewall RADIUS server is configured
- Check shared secret matches
- Verify FreeRADIUS container IP is allowed on firewall
- Enable RADIUS debug on firewall

### Issue: Replication Disabled
```powershell
# Check .env file
Get-Content .env | Select-String "ENABLE_ACCT_REPLICATION"

# Should be: ENABLE_ACCT_REPLICATION=true
```

## Important Attributes

| Attribute | Description | Example |
|-----------|-------------|---------|
| Acct-Status-Type | Start/Stop/Interim-Update | Start |
| User-Name | Username from authentication | user@yourdomain.com |
| Framed-IP-Address | User's IP address | 10.1.156.185 |
| Acct-Session-Id | Unique session ID | 0211a4ef |
| Calling-Station-Id | User MAC address | 00-0c-29-44-BE-B8 |
| Called-Station-Id | AP MAC:SSID | AP-MAC:SSID |
| Class | User type/role | Staff |
| Acct-Session-Time | Session duration (seconds) | 3600 |
| Acct-Input-Octets | Bytes received | 1048576 |
| Acct-Output-Octets | Bytes sent | 2097152 |

## Documentation

- Full guide: `ACCOUNTING_REPLICATION.md`
- Test script: `test-accounting-replication.ps1`
- Configuration: `.env`, `configs/proxy.conf`, `configs/default`

## Support

Need help? Check the logs:
```powershell
docker logs freeradius-google-ldap --tail 100
```

Look for:
- `Proxy-To-Realm := "firewall_accounting"`
- `Starting proxy to home server 10.10.10.1`
- `Received Accounting-Response from 10.10.10.1`

---
**Status**: ✅ Accounting replication is ENABLED and working
**Firewall**: 10.10.10.1:1813
**Last Updated**: November 2025
