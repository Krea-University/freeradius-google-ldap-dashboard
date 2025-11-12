# FreeRADIUS Live Monitoring & Debugging Guide

## Quick Start - Live Monitoring

### Option 1: Using the Monitoring Script (Recommended)
```powershell
.\monitor-radius.ps1
```
**Shows:** Color-coded authentication, accounting packets, firewall replication, with statistics

---

### Option 2: Raw Docker Logs (Most Detailed)
```powershell
# Follow ALL logs in real-time
docker logs -f freeradius-google-ldap

# Follow last 20 lines then stream new
docker logs -f --tail 20 freeradius-google-ldap
```

---

### Option 3: Filtered Logs (Specific Debugging)

**Authentication Only:**
```powershell
docker logs -f freeradius-google-ldap 2>&1 | Select-String "Received Access-Request|Sent Access-Accept|Sent Access-Reject|User-Name|Auth-Type"
```

**Accounting Only:**
```powershell
docker logs -f freeradius-google-ldap 2>&1 | Select-String "Received Accounting-Request|Acct-Status-Type|Acct-Session-Id|Framed-IP-Address"
```

**Firewall Replication Only:**
```powershell
docker logs -f freeradius-google-ldap 2>&1 | Select-String "10.10.10.1|Proxy-To-Realm|firewall_accounting|Sent Accounting.*to|Received Accounting.*from"
```

**User Group Mapping (Filter-Id):**
```powershell
docker logs -f freeradius-google-ldap 2>&1 | Select-String "Filter-Id|Class|User-Name :="
```

**Specific User:**
```powershell
docker logs -f freeradius-google-ldap 2>&1 | Select-String "user1|user2"
```

---

## Debugging Specific Issues

### Check Container Status
```powershell
# List containers
docker ps -a | Select-String "freeradius"

# Check if healthy
docker inspect freeradius-google-ldap | Select-String "Status|Health"
```

### Check Recent Errors
```powershell
# Last 50 lines with errors
docker logs --tail 50 freeradius-google-ldap 2>&1 | Select-String "error|failed|warning" -CaseSensitive:$false

# Configuration errors
docker logs --tail 100 freeradius-google-ldap 2>&1 | Select-String "configuration|syntax|invalid"
```

### Test Authentication
```powershell
# From within container
docker exec freeradius-google-ldap radtest user@yourdomain.com <password> localhost 0 testing123

# From host (if radclient installed)
echo "User-Name=user@yourdomain.com,User-Password=<password>" | radclient -x 172.25.0.4:1812 auth testing123
```

### Test Accounting
```powershell
# Send test accounting packet
docker exec freeradius-google-ldap bash -c 'echo "Acct-Status-Type=Start,User-Name=test@yourdomain.com,Acct-Session-Id=TEST-001,Framed-IP-Address=10.10.156.99,NAS-IP-Address=10.10.200.5" | radclient -x localhost:1813 acct testing123'
```

---

## Database Debugging

### Check Active Sessions
```powershell
docker exec radius-mysql mysql -u radius -pRadiusDbPass2024! radius -e "SELECT username, framedipaddress, acctstarttime, CASE WHEN acctstoptime IS NULL THEN 'ACTIVE' ELSE 'CLOSED' END as status FROM radacct WHERE nasipaddress='10.10.200.5' ORDER BY radacctid DESC LIMIT 10;"
```

### Check Authentication Logs
```powershell
docker exec radius-mysql mysql -u radius -pRadiusDbPass2024! radius -e "SELECT username, reply, authdate FROM radpostauth ORDER BY authdate DESC LIMIT 10;"
```

### Check Duplicate Inserts
```powershell
docker exec radius-mysql mysql -u radius -pRadiusDbPass2024! radius -e "SELECT username, COUNT(*) as count, authdate FROM radpostauth GROUP BY username, authdate HAVING count > 1 ORDER BY authdate DESC;"
```

---

## Real-Time Monitoring with Context

### Show packet details with surrounding context
```powershell
# Show 2 lines before and after each match
docker logs -f freeradius-google-ldap 2>&1 | Select-String "Received Accounting-Request" -Context 2,2
```

### Watch specific attributes
```powershell
docker logs -f freeradius-google-ldap 2>&1 | Select-String "User-Name =|NAS-IP-Address =|Framed-IP-Address =|Filter-Id :=|Class :="
```

---

## Performance Debugging

### Check LDAP queries
```powershell
docker logs -f freeradius-google-ldap 2>&1 | Select-String "ldap|bind|search|cache"
```

### Check SQL queries
```powershell
docker logs -f freeradius-google-ldap 2>&1 | Select-String "sql|INSERT|UPDATE|SELECT" -CaseSensitive:$false
```

### Check timing
```powershell
docker logs -f freeradius-google-ldap 2>&1 | Select-String "Finished request|Processing time"
```

---

## Troubleshooting

### Container keeps restarting
```powershell
# View init script errors
docker logs freeradius-google-ldap 2>&1 | Select-String "python3|error|failed|exit" -Context 3

# Check configuration syntax
docker exec freeradius-google-ldap radiusd -XC

# Rebuild without cache
docker-compose down
docker-compose build --no-cache freeradius
docker-compose up -d
```

### No accounting packets from Aruba AP
1. Check ARUBA_ACCOUNTING_SETUP.md
2. Verify AP sends accounting:
   ```powershell
   docker logs --since 5m freeradius-google-ldap 2>&1 | Select-String "10.10.200.5"
   ```
3. Check AP configuration on Aruba controller

### Firewall not receiving packets
```powershell
# Check if proxy is enabled
docker exec freeradius-google-ldap grep "Proxy-To-Realm" /etc/freeradius/sites-available/default

# Check firewall status
docker logs freeradius-google-ldap 2>&1 | Select-String "10.10.10.1" | Select-Object -Last 10

# Test firewall manually
docker exec freeradius-google-ldap bash -c 'echo "Acct-Status-Type=Start,User-Name=test@krea.edu.in" | radclient -x 10.10.10.1:1813 acct testing123'
```

---

## Advanced: Debug Mode

### Run FreeRADIUS in debug mode (temporary)
```powershell
# Stop container
docker-compose stop freeradius

# Run in debug mode (verbose output)
docker-compose run --rm freeradius radiusd -X
```

**Warning:** This will show VERY detailed output including passwords if LOG_SENSITIVE_DATA=true

---

## Quick Reference

| What to Monitor | Command |
|----------------|---------|
| Everything | `docker logs -f freeradius-google-ldap` |
| Auth only | `docker logs -f freeradius-google-ldap 2>&1 \| Select-String "Access-Request\|Access-Accept"` |
| Acct only | `docker logs -f freeradius-google-ldap 2>&1 \| Select-String "Accounting-Request\|Acct-Status-Type"` |
| Firewall | `docker logs -f freeradius-google-ldap 2>&1 \| Select-String "10.10.10.1"` |
| Errors | `docker logs --tail 100 freeradius-google-ldap 2>&1 \| Select-String "error"` |
| User Groups | `docker logs -f freeradius-google-ldap 2>&1 \| Select-String "Filter-Id"` |
| Specific User | `docker logs -f freeradius-google-ldap 2>&1 \| Select-String "username@domain"` |

---

## Notes

- Press **Ctrl+C** to stop following logs
- Use `--since 10m` to show last 10 minutes only
- Use `--tail 50` to show last 50 lines
- `2>&1` redirects stderr to stdout for PowerShell filtering
- Add `-Context X,Y` to show X lines before and Y lines after match

---

**Last Updated:** November 12, 2025
