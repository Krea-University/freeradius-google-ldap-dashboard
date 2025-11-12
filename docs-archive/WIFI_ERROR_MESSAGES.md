# WiFi Authentication Error Messages

## Overview

When users fail to connect to **IT_TEST_WiFi**, they will now see **helpful error messages** explaining WHY authentication failed, instead of generic "Unable to connect" messages.

---

## Error Messages by Failure Type

### 1. **Wrong Password or Username**
**Trigger**: User enters incorrect password or username doesn't exist  
**User Sees**:
```
Authentication failed: Invalid username or password. 
Please check your credentials and try again.
```

**Common Causes**:
- Typo in email address
- Wrong password
- CapsLock enabled
- Using old/expired password

---

### 2. **User Account Not Found**
**Trigger**: Email address not in Google LDAP  
**User Sees**:
```
Authentication failed: User account not found. 
Please contact IT support.
```

**Common Causes**:
- New employee not yet added to system
- Account deactivated/suspended
- Using personal email instead of institutional

---

### 3. **LDAP Server Unreachable**
**Trigger**: Google LDAP server timeout or connection failure  
**User Sees**:
```
Authentication failed: Unable to reach authentication server. 
Please try again later.
```

**Common Causes**:
- Internet connectivity issues
- Google LDAP service down
- Firewall blocking ldaps://ldap.google.com:636

---

### 4. **Certificate Error**
**Trigger**: TLS/certificate validation issues  
**User Sees**:
```
Authentication failed: Certificate error. 
Please check your device security settings.
```

**Common Causes**:
- Device date/time incorrect
- Root certificate expired
- Corporate certificate not installed

---

### 5. **Unsupported Domain**
**Trigger**: User tries to connect with non-whitelisted email domain  
**User Sees**:
```
Authentication failed: Email domain not supported. 
Use your institutional email address.
```

**Common Causes**:
- Using @gmail.com or other personal email
- Using subdomain not configured (e.g., @dept.example.com)
- Typo in domain name

---

### 6. **Generic Failure**
**Trigger**: Any other authentication failure  
**User Sees**:
```
Authentication failed: Invalid credentials. 
Please verify your username and password.
```

**Common Causes**:
- Catch-all for undefined errors
- Network issues
- Configuration problems

---

## How It Works

### Technical Flow:

1. **User tries to connect** → FreeRADIUS receives Access-Request
2. **Authentication fails** → Module-Failure-Message is set
3. **Post-Auth-Type REJECT runs** → Checks failure reason
4. **Reply-Message added** → Specific error message based on failure type
5. **Access-Reject sent** → User device shows the Reply-Message

### Configuration Locations:

- **Inner-Tunnel** (for EAP-TTLS/PEAP): `configs/inner-tunnel` line ~393
- **Outer Server** (for direct auth): `configs/default` line ~939

---

## Testing Error Messages

### Test Wrong Password:
```powershell
# Connect to WiFi with correct username but WRONG password
# Expected: "Invalid username or password"
```

### Test Non-Existent User:
```powershell
# Try connecting with: nonexistent@yourdomain.com
# Expected: "User account not found"
```

### Test Wrong Domain:
```powershell
# Try connecting with: user@gmail.com
# Expected: "Email domain not supported"
```

---

## User-Facing Documentation

### For End Users:

**WiFi Connection Troubleshooting**

If you see an error message when connecting to your WiFi network:

1. **"Invalid username or password"**
   - Check your spelling carefully
   - Make sure CapsLock is OFF
   - Use your full institutional email (user@yourdomain.com)
   - Reset your password if needed

2. **"User account not found"**
   - Contact IT Support
   - Your account may need to be activated

3. **"Unable to reach authentication server"**
   - Check your internet connection
   - Try again in a few minutes
   - Contact IT if problem persists

4. **"Certificate error"**
   - Check your device date/time is correct
   - Update your device OS
   - Contact IT Support for certificate installation

5. **"Email domain not supported"**
   - Use your institutional email address
   - Don't use personal email addresses

---

## Monitoring Error Messages

### View in Logs:
```powershell
# See what error messages are being sent
docker logs -f freeradius-google-ldap 2>&1 | Select-String "Reply-Message"
```

### Example Log Output:
```
(0) update reply {
(0)   Reply-Message := "Authentication failed: Invalid username or password. Please check your credentials and try again."
(0) }
```

---

## Customizing Messages

To change error messages, edit:

1. **`configs/inner-tunnel`** - Line ~393 (Post-Auth-Type REJECT)
2. **`configs/default`** - Line ~939 (Post-Auth-Type REJECT)

Then rebuild:
```powershell
docker-compose up -d --build freeradius
```

---

## Platform-Specific Behavior

### Windows 10/11:
- Shows Reply-Message in notification bubble
- Also shows in WiFi settings → Connection properties

### macOS:
- Shows Reply-Message in WiFi menu
- May show "Authentication failed" with details in Console app

### iOS/iPadOS:
- Shows Reply-Message in Settings → WiFi
- May truncate long messages

### Android:
- Shows Reply-Message in notification
- Varies by manufacturer (Samsung, Google, etc.)

### Linux:
- Varies by desktop environment
- Usually shows in network manager applet

---

**Last Updated**: November 12, 2025  
**FreeRADIUS Version**: 3.0.23
