# Password Logging Control

## Overview

The FreeRADIUS authentication system now supports environment variable-controlled password logging for enhanced security. This allows you to hide passwords in production logs while enabling them for development/debugging.

## Environment Variables

Two environment variables control password logging behavior (defined in `.env` file):

### 1. LOG_SENSITIVE_DATA
```bash
LOG_SENSITIVE_DATA=false  # Production (default) - Passwords masked as '***HIDDEN***'
LOG_SENSITIVE_DATA=true   # Development - Full passwords logged
```

### 2. ENVIRONMENT
```bash
ENVIRONMENT=prod   # Production - Passwords masked as '***HIDDEN***'
ENVIRONMENT=dev    # Development - Full passwords logged
```

## Behavior

### Production Mode (Default - Secure)
When `LOG_SENSITIVE_DATA=false` OR `ENVIRONMENT=prod`:
- Passwords in SQL post-auth table show as: `***HIDDEN***`
- Passwords in FreeRADIUS debug logs are still visible (use `LOG_LEVEL=info` to reduce verbosity)
- **Recommended for production environments**

### Development Mode (Debug)
When `LOG_SENSITIVE_DATA=true` OR `ENVIRONMENT=dev`:
- Passwords logged in full: `%{%{User-Password}:-%{Chap-Password}}`
- Useful for troubleshooting authentication issues
- **⚠️ WARNING: Use only in development/test environments!**

## Configuration Files Modified

### 1. `.env`
```bash
# Debug & Security Settings
# Set to 'true' to log usernames and passwords (DEVELOPMENT ONLY!)
# Set to 'false' for production (passwords will be masked in logs)
LOG_SENSITIVE_DATA=false
ENVIRONMENT=prod
```

### 2. `init.sh`
- Added UTF-16 to UTF-8 conversion for `queries.conf`
- Added line ending conversion (CRLF to LF)
- Conditional password value replacement based on environment variables

```bash
if [ "${LOG_SENSITIVE_DATA}" = "true" ] || [ "${ENVIRONMENT}" = "dev" ]; then
    PASSWORD_LOG_VALUE="%{%{User-Password}:-%{Chap-Password}}"
else
    PASSWORD_LOG_VALUE="***HIDDEN***"
fi
```

### 3. `configs/queries.conf`
- Post-auth SQL query modified to use placeholder: `ENV_PASSWORD_LOGGING_PLACEHOLDER`
- Placeholder replaced during container startup by `init.sh`
- Based on the complete original FreeRADIUS queries.conf (not a custom minimal version)

## How It Works

1. **Container Startup**: When the Docker container starts, `init.sh` executes
2. **File Conversion**: `queries.conf` is converted from UTF-16 to UTF-8 (Windows compatibility)
3. **Line Ending Fix**: CRLF line endings converted to LF (Unix format)
4. **Environment Check**: Script checks `LOG_SENSITIVE_DATA` and `ENVIRONMENT` variables
5. **Placeholder Replacement**: `ENV_PASSWORD_LOGGING_PLACEHOLDER` replaced with appropriate value:
   - Production: `'***HIDDEN***'`
   - Development: `'%{%{User-Password}:-%{Chap-Password}}'`
6. **FreeRADIUS Starts**: Configuration loaded with the correct password logging setting

## Database Impact

### radpostauth Table
The `radpostauth` table stores authentication attempts. The `pass` column will contain:

**Production:**
```sql
username     | pass          | reply        | authdate
-------------|---------------|--------------|-------------------
user@yourdomain.com | ***HIDDEN***  | Access-Accept | 2025-11-11 13:45:32
```

**Development:**
```sql
username     | pass                    | reply        | authdate
-------------|-------------------------|--------------|-------------------
user@yourdomain.com | MyPassword123       | Access-Accept | 2025-11-11 13:45:32
```

## Security Recommendations

1. **Always use production mode** (`LOG_SENSITIVE_DATA=false`) in production environments
2. **Rotate database passwords** regularly
3. **Restrict database access** to only necessary users
4. **Monitor log files** for unauthorized access
5. **Use `LOG_LEVEL=info`** instead of `debug` in production to reduce log verbosity

## Testing

### Test Production Mode (Default)
1. Ensure `.env` has `LOG_SENSITIVE_DATA=false`
2. Rebuild: `docker-compose build freeradius`
3. Restart: `docker-compose up -d freeradius`
4. Check logs: `docker logs freeradius-google-ldap 2>&1 | Select-String "Password logging"`
   - Should show: "Password logging is DISABLED (production mode)"
5. Authenticate a user
6. Check database: `SELECT * FROM radpostauth ORDER BY authdate DESC LIMIT 1;`
   - `pass` column should show: `***HIDDEN***`

### Test Development Mode
1. Update `.env`: `LOG_SENSITIVE_DATA=true` or `ENVIRONMENT=dev`
2. Rebuild: `docker-compose build freeradius`
3. Restart: `docker-compose up -d freeradius`
4. Check logs: `docker logs freeradius-google-ldap 2>&1 | Select-String "Password logging"`
   - Should show: "WARNING: Password logging is ENABLED"
5. Authenticate a user
6. Check database: `SELECT * FROM radpostauth ORDER BY authdate DESC LIMIT 1;`
   - `pass` column should show actual password

## Troubleshooting

### Password still showing in logs
- Check FreeRADIUS debug logs (separate from SQL logging):
  - Debug logs (`-X` flag) always show passwords
  - Use `LOG_LEVEL=info` in `.env` to reduce verbosity
  - Or modify `configs/ldap` to disable verbose LDAP logging

### Placeholder not replaced
- Check container logs: `docker logs freeradius-google-ldap`
- Look for: "Configuring password logging"
- Verify file encoding: `docker exec freeradius-google-ldap file /etc/freeradius/mods-config/sql/main/mysql/queries.conf`
  - Should show: "UTF-8 Unicode text"
  - NOT: "UTF-16" or "CRLF"

### Authentication still works?
- Yes! This change only affects SQL logging, not authentication
- LDAP bind authentication uses passwords in memory, not from database
- Passwords are still processed normally for TTLS-PAP authentication

## Implementation Details

### Why UTF-16 Conversion?
The `queries.conf` file was created on Windows and saved as UTF-16 with CRLF line endings. Standard Linux `sed` commands don't work on UTF-16 files. The solution:
1. Use `iconv` to convert UTF-16LE to UTF-8
2. Use `sed` to remove CR characters (CRLF → LF)
3. Then `sed` can successfully replace the placeholder

### Why Two Environment Variables?
- `LOG_SENSITIVE_DATA`: Explicit control (clear intent)
- `ENVIRONMENT`: Broader setting (affects other configurations)
- Either set to development mode enables password logging
- Both must be production mode to mask passwords

## References

- Original Password Logging Issue: User passwords visible in `radpostauth` table
- Solution: Environment-controlled placeholder replacement in `queries.conf`
- Files Modified: `.env`, `init.sh`, `configs/queries.conf`, added `PASSWORD_LOGGING_CONTROL.md`
