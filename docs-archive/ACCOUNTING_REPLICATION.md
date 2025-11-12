# Accounting Packet Replication to Firewall

This guide explains how to configure FreeRADIUS to send accounting packets to your firewall or external RADIUS accounting server.

## Overview

Accounting packet replication allows FreeRADIUS to forward a copy of all accounting packets (Start, Stop, Interim-Update) to an external RADIUS server such as a firewall. This is useful for:

- **Firewall integration**: Send user session data to firewalls (Palo Alto, Fortinet, etc.)
- **User-ID mapping**: Map IP addresses to usernames on the firewall
- **Session tracking**: Monitor user sessions across multiple systems
- **Compliance**: Centralized accounting for audit purposes

## How It Works

1. **Access Point → FreeRADIUS**: AP sends accounting packets to FreeRADIUS
2. **FreeRADIUS Processing**: 
   - Logs accounting to SQL database
   - Creates detail files
   - Updates user session tracking
3. **FreeRADIUS → Firewall**: Replicates accounting packet to firewall
4. **Firewall**: Receives accounting data and maps user to IP address

## Configuration

### 1. Environment Variables

Add these variables to your `.env` file:

```bash
# Accounting Replication to Firewall
ENABLE_ACCT_REPLICATION=true           # Set to 'true' to enable
FIREWALL_ACCT_SERVER=10.10.10.1        # Your firewall IP address
FIREWALL_ACCT_PORT=1813                # RADIUS accounting port (usually 1813)
FIREWALL_ACCT_SECRET=testing123        # Shared secret for firewall
```

### 2. Firewall Configuration

Configure your firewall to accept RADIUS accounting packets from FreeRADIUS:

#### Palo Alto Firewall Example:
```
Device > Server Profiles > RADIUS
  - Add Server: <FreeRADIUS-IP>
  - Port: 1813
  - Secret: testing123
  - Enable User-ID
```

#### Fortinet FortiGate Example:
```
System > Admin > RADIUS
  - Name: FreeRADIUS
  - Primary Server IP: <FreeRADIUS-IP>
  - Port: 1813
  - Secret: testing123
  - Authentication Method: PAP
```

### 3. Enable Replication

```bash
# Edit .env file
ENABLE_ACCT_REPLICATION=true
FIREWALL_ACCT_SERVER=10.10.10.1
FIREWALL_ACCT_PORT=1813
FIREWALL_ACCT_SECRET=YourSharedSecret

# Rebuild and restart
docker-compose build freeradius
docker-compose up -d freeradius
```

## Testing

### Test Accounting Packet Replication

```powershell
# Send accounting Start packet
docker exec freeradius-google-ldap bash -c 'echo "Acct-Status-Type=Start,Framed-IP-Address=10.1.156.185,User-Name=testuser@yourdomain.com,Acct-Session-Id=0211a4ef,Class=group1,Calling-Station-Id=00-0c-29-44-BE-B8" | radclient -x localhost:1813 acct testing123'

# Send accounting Stop packet
docker exec freeradius-google-ldap bash -c 'echo "Acct-Status-Type=Stop,Framed-IP-Address=10.1.156.185,User-Name=testuser@yourdomain.com,Acct-Session-Id=0211a4ef,Acct-Session-Time=3600,Acct-Input-Octets=1048576,Acct-Output-Octets=2097152" | radclient -x localhost:1813 acct testing123'
```

### Check Logs

```powershell
# Check FreeRADIUS logs for proxying
docker logs freeradius-google-ldap 2>&1 | Select-String "Proxying|firewall|acct"

# Check if packets are being sent
docker logs freeradius-google-ldap 2>&1 | Select-String "Acct-Status-Type"
```

### Verify on Firewall

Check your firewall's User-ID logs:
- **Palo Alto**: Monitor > Traffic > User Activity
- **Fortinet**: User & Device > User Monitor

## Troubleshooting

### 1. Accounting Packets Not Reaching Firewall

**Check FreeRADIUS logs:**
```powershell
docker logs freeradius-google-ldap --tail 100 | Select-String "firewall_accounting|Proxy-To-Realm"
```

**Verify firewall is reachable:**
```powershell
docker exec freeradius-google-ldap ping -c 3 10.10.10.1
```

**Check firewall port is open:**
```powershell
Test-NetConnection -ComputerName 10.10.10.1 -Port 1813
```

### 2. Shared Secret Mismatch

Error: `No reply from firewall`

**Solution:**
- Verify `FIREWALL_ACCT_SECRET` matches firewall configuration
- Check firewall RADIUS server settings

### 3. Firewall Not Receiving Packets

**Check firewall configuration:**
- Verify RADIUS server is enabled
- Check allowed client IP (FreeRADIUS container IP)
- Enable RADIUS debug logging on firewall

**Get container IP:**
```powershell
docker inspect freeradius-google-ldap | Select-String "IPAddress"
```

### 4. Disable Replication

```bash
# Edit .env file
ENABLE_ACCT_REPLICATION=false

# Rebuild and restart
docker-compose build freeradius
docker-compose up -d freeradius
```

## Accounting Packet Format

### Accounting-Start Packet
Sent when user session begins:
```
Acct-Status-Type = Start
User-Name = "user@yourdomain.com"
Framed-IP-Address = 10.1.156.185
Acct-Session-Id = "0211a4ef"
NAS-IP-Address = 10.10.10.50
Calling-Station-Id = "00-0c-29-44-BE-B8"  # User MAC address
Called-Station-Id = "AP-MAC:SSID"          # AP MAC and SSID
Class = "Staff"                            # User type/role
```

### Accounting-Stop Packet
Sent when user session ends:
```
Acct-Status-Type = Stop
User-Name = "user@yourdomain.com"
Framed-IP-Address = 10.1.156.185
Acct-Session-Id = "0211a4ef"
Acct-Session-Time = 3600                   # Session duration in seconds
Acct-Input-Octets = 1048576                # Bytes received
Acct-Output-Octets = 2097152               # Bytes sent
Acct-Terminate-Cause = User-Request
```

### Accounting-Interim-Update
Sent periodically during active session (if configured on AP):
```
Acct-Status-Type = Interim-Update
User-Name = "user@yourdomain.com"
Framed-IP-Address = 10.1.156.185
Acct-Session-Id = "0211a4ef"
Acct-Session-Time = 1800
Acct-Input-Octets = 524288
Acct-Output-Octets = 1048576
```

## Advanced Configuration

### Conditional Replication

To replicate only specific users or VLANs, modify `/etc/freeradius/sites-available/default`:

```
accounting {
    sql
    
    # Only replicate Staff users
    if (&Class == "Staff") {
        update control {
            Proxy-To-Realm := "firewall_accounting"
        }
    }
    
    detail
}
```

### Multiple Firewall Destinations

Add additional home servers in `proxy.conf`:

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
    home_server = firewall_acct2  # Failover to second firewall
}
```

## Performance Considerations

- **Network latency**: Accounting replication adds minimal latency (~5-10ms)
- **Firewall response**: FreeRADIUS waits for firewall response (default 20 seconds timeout)
- **Connection limits**: Default 65536 outstanding packets to firewall
- **Zombie detection**: If firewall is down, packets are dropped after 40 seconds

## Security Best Practices

1. **Strong shared secret**: Use a complex shared secret (32+ characters)
2. **Firewall rules**: Restrict RADIUS accounting to FreeRADIUS IP only
3. **Encrypted transport**: Consider IPsec tunnel between FreeRADIUS and firewall
4. **Separate VLAN**: Place RADIUS servers and firewalls on management VLAN
5. **Monitor logs**: Regular review of accounting logs for anomalies

## Integration Examples

### Palo Alto User-ID

```
Device > User Identification > User Mapping
  - Enable User Identification
  - Add RADIUS Accounting Server: <FreeRADIUS-IP>:1813
  - Configure User-ID Agent (optional)
  
Security Policy:
  - Source User: DOMAIN\username
  - Destination Zone: Internet
  - Application: web-browsing
  - Action: Allow
```

### Fortinet FSSO

```
System > Admin > RADIUS
  - Enable RADIUS Accounting
  - Enable FSSO (Fortinet Single Sign-On)
  
User & Device > FSSO
  - Add RADIUS Accounting Collector
  - Server: <FreeRADIUS-IP>
  - Port: 1813
  - Secret: testing123
```

## References

- [RFC 2866 - RADIUS Accounting](https://tools.ietf.org/html/rfc2866)
- [FreeRADIUS Proxy Configuration](https://wiki.freeradius.org/config/Proxy)
- [Palo Alto User-ID](https://docs.paloaltonetworks.com/pan-os/9-1/pan-os-admin/user-id)
- [Fortinet FSSO](https://docs.fortinet.com/document/fortigate/7.0.0/administration-guide/628678/fsso)

## Support

If you encounter issues with accounting replication:

1. Check FreeRADIUS logs: `docker logs freeradius-google-ldap --tail 100`
2. Verify firewall RADIUS configuration
3. Test connectivity: `docker exec freeradius-google-ldap radclient -x <firewall-ip>:1813 acct <secret>`
4. Review this documentation: `ACCOUNTING_REPLICATION.md`

---

**Last Updated**: November 2025
**Version**: 1.0
