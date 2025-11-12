# Firewall Setup Checklist for RADIUS Accounting

## Current Status

✅ **FreeRADIUS is WORKING** - Successfully sending accounting packets to `10.10.10.1:1813`

⚠️ **Firewall is NOT responding consistently** - Only first packet got a response

## Issue Analysis

From your logs:
```
(0) Received Accounting-Response Id 8 from 10.10.10.1:1813  ← SUCCESS!
(1) ERROR: Failing proxied request, no response from 10.10.10.1 ← TIMEOUT
(2) ERROR: Failing proxied request, no response from 10.10.10.1 ← TIMEOUT
Marking home server 10.10.10.1 port 1813 as zombie
Marking home server 10.10.10.1 port 1813 as dead
```

**Root Cause:** Firewall at `10.10.10.1` is not responding to RADIUS accounting packets.

## Firewall Configuration Steps

### Option 1: Configure Real Firewall (Recommended)

#### For Palo Alto Firewall:

1. **Add RADIUS Server Profile**
   ```
   Device > Server Profiles > RADIUS
   - Click "Add"
   - Name: FreeRADIUS-Accounting
   - Server: <FreeRADIUS-Container-IP> (get from step 2)
   - Port: 1813
   - Secret: testing123
   - Timeout: 3 seconds
   - Retries: 3
   ```

2. **Get FreeRADIUS Container IP**
   ```powershell
   docker inspect freeradius-google-ldap | Select-String "IPAddress" | Select-Object -First 1
   
   # Or use the host IP if firewall is external
   # Firewall should allow: <Docker-Host-IP> → 10.10.10.1:1813
   ```

3. **Enable User-ID**
   ```
   Device > User Identification > User Mapping
   - Enable "Enable User Identification"
   - Add RADIUS Accounting
   - Select: FreeRADIUS-Accounting profile
   ```

4. **Configure Firewall Rules to Allow RADIUS**
   ```
   Source: <FreeRADIUS-IP>
   Destination: 10.10.10.1
   Service: radius-acct (UDP 1813)
   Action: Allow
   ```

#### For Fortinet FortiGate:

1. **Configure RADIUS Server**
   ```
   System > Admin > RADIUS
   - Name: FreeRADIUS-Accounting
   - Primary Server IP: <FreeRADIUS-Container-IP>
   - Primary Server Port: 1813
   - Primary Server Secret: testing123
   - Authentication Method: PAP
   ```

2. **Enable FSSO (Fortinet Single Sign-On)**
   ```
   User & Device > FSSO
   - Enable RADIUS Accounting
   - Add Accounting Agent: FreeRADIUS-Accounting
   ```

3. **Configure Firewall Policy**
   ```
   Source: <FreeRADIUS-IP>
   Destination: 10.10.10.1
   Service: RADIUS-Accounting
   Action: ACCEPT
   ```

#### For Cisco/Other Firewalls:

Configure RADIUS accounting client with:
- **Client IP**: FreeRADIUS container IP
- **Shared Secret**: `testing123`
- **Accounting Port**: 1813
- **Timeout**: 20 seconds
- **Retries**: 3

### Option 2: Test with Local RADIUS Server (For Testing)

If you don't have access to configure the real firewall yet, you can test with a local RADIUS server:

```powershell
# Run a test RADIUS server in Docker (accepts all accounting packets)
docker run -d --name test-radius-server `
  -p 1813:1813/udp `
  freeradius/freeradius-server:latest `
  freeradius -X

# Update .env to point to test server
# FIREWALL_ACCT_SERVER=host.docker.internal  # For Docker Desktop
# or
# FIREWALL_ACCT_SERVER=172.17.0.1  # For Linux Docker

# Rebuild and restart
docker-compose build freeradius
docker-compose up -d freeradius
```

### Option 3: Disable Accounting Replication (Temporary)

If you're not ready to configure the firewall yet:

```bash
# Edit .env
ENABLE_ACCT_REPLICATION=false

# Rebuild and restart
docker-compose build freeradius
docker-compose up -d freeradius
```

## Verification Steps

### 1. Check Firewall Connectivity

```powershell
# Get FreeRADIUS container IP
docker inspect freeradius-google-ldap | Select-String '"IPAddress"' | Select-Object -First 1

# Test ping from container to firewall
docker exec freeradius-google-ldap ping -c 3 10.10.10.1

# Test UDP port 1813 (from Windows host)
Test-NetConnection -ComputerName 10.10.10.1 -Port 1813
```

### 2. Test RADIUS Accounting from Command Line

```powershell
# From FreeRADIUS container (should work - logs to SQL)
docker exec freeradius-google-ldap bash -c 'echo "Acct-Status-Type=Start,User-Name=testuser@krea.edu.in,Framed-IP-Address=10.1.1.100,Acct-Session-Id=TEST-001" | radclient -x localhost:1813 acct testing123'

# Direct to firewall (requires firewall to be configured)
docker exec freeradius-google-ldap bash -c 'echo "Acct-Status-Type=Start,User-Name=testuser,Framed-IP-Address=10.1.1.100,Acct-Session-Id=TEST-001" | radclient -x 10.10.10.1:1813 acct testing123'
```

### 3. Check Firewall RADIUS Logs

**Palo Alto:**
```
Monitor > System > RADIUS Authentication
Monitor > User-ID > IP Address to User Mappings
```

**Fortinet:**
```
Log & Report > Security Events > User Activity
User & Device > User Monitor
```

### 4. Enable RADIUS Debug on Firewall

**Palo Alto:**
```
> debug user-id on debug
> debug user-id on info
> tail follow yes mp-log useridd.log
```

**Fortinet:**
```
diagnose debug enable
diagnose debug application radiusd -1
diagnose debug application fnbamd -1
```

## Common Issues & Solutions

### Issue 1: "Marking home server as zombie/dead"

**Cause:** Firewall not responding to RADIUS packets

**Solutions:**
- ✅ Configure firewall RADIUS server (see above)
- ✅ Verify shared secret matches: `testing123`
- ✅ Check firewall allows UDP 1813 from FreeRADIUS IP
- ✅ Enable RADIUS debug on firewall to see if packets arrive

### Issue 2: "No response from home server"

**Cause:** Network connectivity or firewall blocking

**Solutions:**
```powershell
# Check firewall reachability
docker exec freeradius-google-ldap ping -c 3 10.10.10.1

# Check if port is open
Test-NetConnection -ComputerName 10.10.10.1 -Port 1813

# Check FreeRADIUS can send UDP
docker exec freeradius-google-ldap bash -c 'echo "test" | nc -u 10.10.10.1 1813'
```

### Issue 3: First packet succeeds, others fail

**Cause:** This is actually what's happening! Firewall responded once, then stopped.

**Possible Reasons:**
1. Firewall rate-limiting RADIUS requests
2. Shared secret mismatch on firewall
3. Firewall RADIUS server not properly configured
4. Firewall expecting different packet format

**Solution:**
- Check firewall RADIUS configuration matches exactly:
  - Port: 1813
  - Secret: `testing123`
  - Protocol: UDP
  - Allow source IP: FreeRADIUS container IP

### Issue 4: "Duplicate proxied request"

**Cause:** FreeRADIUS retrying because firewall not responding

**Normal Behavior:** 
- FreeRADIUS waits 20 seconds for response
- Retries 3 times if no response
- Marks server as "zombie" then "dead"

**Solution:** Fix firewall configuration to respond to packets

## Success Criteria

You'll know it's working when you see in logs:

```
✓ (0) Starting proxy to home server 10.10.10.1 port 1813
✓ (0) Sent Accounting-Request Id 8 to 10.10.10.1:1813
✓ (0) Received Accounting-Response Id 8 from 10.10.10.1:1813
✓ (1) Sent Accounting-Request Id 9 to 10.10.10.1:1813
✓ (1) Received Accounting-Response Id 9 from 10.10.10.1:1813
```

**NOT:**
```
✗ ERROR: Failing proxied request, no response from home server
✗ Marking home server as zombie
✗ Marking home server as dead
```

## Quick Fix Commands

### Check Current Status
```powershell
# Check if replication is enabled
Get-Content .env | Select-String "ENABLE_ACCT_REPLICATION|FIREWALL"

# Check recent accounting activity
docker logs freeradius-google-ldap --tail 50 | Select-String "10.10.10.1|Proxying"

# Check home server status
docker logs freeradius-google-ldap 2>&1 | Select-String "zombie|dead|alive"
```

### Restart FreeRADIUS (Resets "zombie/dead" status)
```powershell
docker-compose restart freeradius
Start-Sleep -Seconds 8
docker logs freeradius-google-ldap --tail 20
```

### Change Firewall IP
```bash
# Edit .env
FIREWALL_ACCT_SERVER=192.168.1.100  # Your firewall's real IP

# Rebuild and restart
docker-compose build freeradius
docker-compose up -d freeradius
```

## What's Working vs What's Not

### ✅ Working:
1. FreeRADIUS accounting replication is configured
2. FreeRADIUS is sending packets to 10.10.10.1:1813
3. Network connectivity exists (first packet got response)
4. Accounting is being logged to MySQL database
5. Configuration is dynamically generated correctly

### ⚠️ Needs Configuration:
1. **Firewall at 10.10.10.1** needs RADIUS accounting configured
2. **Shared secret** on firewall must be set to `testing123`
3. **Firewall rules** must allow UDP 1813 from FreeRADIUS
4. **RADIUS service** on firewall must be enabled and listening

## Next Actions (In Priority Order)

1. **Verify firewall IP** - Is `10.10.10.1` the correct firewall IP?
2. **Check firewall access** - Do you have admin access to configure it?
3. **Configure RADIUS on firewall** - Follow steps above for your firewall type
4. **Test connectivity** - Run the verification commands
5. **Check firewall logs** - Verify packets are arriving
6. **Re-test** - Run `.\test-accounting-replication.ps1`

## Support Contact

If you need help configuring your specific firewall model:
1. Identify firewall vendor and model
2. Check firewall documentation for "RADIUS Accounting" or "User-ID"
3. Consult firewall admin or vendor support

---

**Current Status Summary:**
- ✅ FreeRADIUS: WORKING - Sending packets
- ⚠️ Firewall (10.10.10.1): NOT CONFIGURED - Not responding
- 📝 Action Required: Configure RADIUS on firewall or change `FIREWALL_ACCT_SERVER` to correct IP
