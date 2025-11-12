# Aruba Access Point - RADIUS Accounting Configuration

## Problem Detected

Your Aruba AP at **10.10.200.5** is:
- ✅ Sending **authentication** packets to FreeRADIUS (working)
- ❌ **NOT sending accounting** packets to FreeRADIUS (broken)

## Root Cause

The Aruba controller is missing RADIUS accounting configuration for the SSID "IT_TEST_WiFi".

---

## Solution: Enable RADIUS Accounting

### Step 1: SSH to Aruba Controller

```bash
ssh admin@<aruba-controller-ip>
```

### Step 2: Check Current Configuration

```bash
# Check AAA profile used by your SSID
show wlan ssid-profile "IT_TEST_WiFi"

# Check AAA profile configuration
show aaa profile <profile-name>
```

**Look for these lines:**
- `accounting-server-group` - Should be present (if missing, accounting is disabled)
- `accounting-mode` - Should be `start-stop` or `start-interim-stop`

### Step 3: Configure Accounting (if missing)

```bash
# Enter configuration mode
configure terminal

# Configure AAA profile for accounting
aaa-profile <your-profile-name>
  # Use the same server group as authentication
  accounting-server-group <radius-server-group>
  
  # Enable start-stop accounting
  accounting-mode start-stop
  
  # Optional: Enable interim updates every 600 seconds (10 minutes)
  # accounting-mode start-interim-stop
  # interim-accounting-interval 600

# Save configuration
exit
commit apply
```

### Step 4: Verify RADIUS Server Group

```bash
# Check RADIUS server group configuration
show aaa server-group <radius-server-group>
```

**Should show:**
```
Server Group   : <radius-server-group>
  Auth Servers : 172.25.0.4:1812
  Acct Servers : 172.25.0.4:1813   # <-- This must be present!
```

**If accounting server is missing:**
```bash
configure terminal

aaa server-group <radius-server-group>
  # Add accounting server (same as auth server but port 1813)
  accounting-server 172.25.0.4 1813 shared-secret testing123
  
exit
commit apply
```

### Step 5: Apply to SSID

```bash
configure terminal

wlan ssid-profile "IT_TEST_WiFi"
  # Ensure AAA profile is applied
  aaa-profile <your-profile-name>
  
exit
commit apply
```

### Step 6: Verify Configuration

```bash
# Verify AAA profile
show aaa profile <your-profile-name>

# Should show:
#   accounting-server-group : <radius-server-group>
#   accounting-mode         : start-stop

# Verify SSID
show wlan ssid-profile "IT_TEST_WiFi"

# Should show:
#   aaa-profile : <your-profile-name>
```

---

## Testing After Configuration

### Test 1: Connect New Device

1. Connect a device to "IT_TEST_WiFi"
2. Check FreeRADIUS logs:
   ```powershell
   docker logs -f freeradius-google-ldap 2>&1 | Select-String "Accounting"
   ```
3. **Expected**: Should see `Received Accounting-Request ... Acct-Status-Type = Start`

### Test 2: Check Database

```powershell
docker exec radius-mysql mysql -u radius -pRadiusDbPass2024! radius -e "SELECT username, acctstarttime, framedipaddress FROM radacct WHERE nasipaddress='10.10.200.5' ORDER BY radacctid DESC LIMIT 5;"
```

**Expected**: New entries with recent timestamps

### Test 3: Verify Firewall Replication

```powershell
docker logs --tail 50 freeradius-google-ldap 2>&1 | Select-String "Sent Accounting.*to 10.10.10.1"
```

**Expected**: Should see packets being sent to firewall

---

## Common Aruba Accounting Issues

### Issue 1: Accounting Not Enabled

**Symptom**: No accounting packets at all
**Fix**: Add `accounting-server-group` to AAA profile

### Issue 2: Wrong Port

**Symptom**: Authentication works, accounting doesn't
**Fix**: Ensure accounting server uses port 1813 (not 1812)

### Issue 3: Wrong Shared Secret

**Symptom**: FreeRADIUS rejects accounting packets
**Fix**: Ensure accounting server secret matches (should be `testing123`)

### Issue 4: Accounting Mode Missing

**Symptom**: Only authentication works
**Fix**: Set `accounting-mode start-stop` in AAA profile

### Issue 5: Firewall Blocking UDP 1813

**Symptom**: Authentication works, accounting packets don't reach FreeRADIUS
**Fix**: Allow UDP 1813 from Aruba AP (10.10.200.5) to FreeRADIUS (172.25.0.4)

---

## Example Complete Configuration

```bash
# Define RADIUS server group
aaa server-group radius-servers
  auth-server 172.25.0.4 1812 shared-secret testing123
  accounting-server 172.25.0.4 1813 shared-secret testing123

# Create AAA profile
aaa-profile IT-RADIUS-Profile
  authentication-server-group radius-servers
  accounting-server-group radius-servers
  accounting-mode start-stop
  default-user-role authenticated

# Apply to SSID
wlan ssid-profile "IT_TEST_WiFi"
  essid "IT_TEST_WiFi"
  opmode wpa2-aes
  aaa-profile IT-RADIUS-Profile
  vlan 100
```

---

## Verification Commands

### Check if accounting packets are reaching FreeRADIUS:
```powershell
# Watch real-time
docker logs -f freeradius-google-ldap 2>&1 | Select-String "Received Accounting-Request"

# Check last hour
docker logs --since 1h freeradius-google-ldap 2>&1 | Select-String "Received Accounting-Request" | Measure-Object
```

### Check database for new accounting entries:
```powershell
docker exec radius-mysql mysql -u radius -pRadiusDbPass2024! radius -e "SELECT COUNT(*) as total, MAX(acctstarttime) as last_accounting FROM radacct WHERE nasipaddress='10.10.200.5';"
```

### Check if packets are going to firewall:
```powershell
docker logs --tail 100 freeradius-google-ldap 2>&1 | Select-String "10.10.10.1:1813"
```

---

## Next Steps After Configuration

1. **Wait 30 seconds** for configuration to apply to all APs
2. **Connect a test device** to IT_TEST_WiFi
3. **Check FreeRADIUS logs** for Accounting-Start packet
4. **Disconnect device** and check for Accounting-Stop packet
5. **Verify database** entries are created
6. **Check firewall** User-ID mappings

---

## Support

If accounting still doesn't work after configuration:

1. Check Aruba AP system logs: `show log system 100`
2. Check RADIUS server reachability: `ping 172.25.0.4`
3. Check RADIUS port: `telnet 172.25.0.4 1813` (should connect)
4. Enable debug on controller: `logging level debugging user-debug`
5. Check for firewall rules blocking UDP 1813

---

**Last Updated**: November 12, 2025
**FreeRADIUS Version**: 3.0.23
**Issue**: Aruba AP not sending accounting packets
