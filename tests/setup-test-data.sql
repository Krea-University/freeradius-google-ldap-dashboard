-- ==================================================
-- Test Data Setup for RADIUS Testing
-- ==================================================
-- This script creates comprehensive test data for
-- testing RADIUS authentication and the web dashboard
-- ==================================================

-- ==================================================
-- Test Users in radcheck table
-- ==================================================

-- Test User 1: Valid credentials (Cleartext-Password)
INSERT INTO radcheck (username, attribute, op, value)
VALUES
    ('testuser1@example.com', 'Cleartext-Password', ':=', 'password123')
ON DUPLICATE KEY UPDATE value = VALUES(value);

-- Test User 2: Valid credentials (different password)
INSERT INTO radcheck (username, attribute, op, value)
VALUES
    ('testuser2@example.com', 'Cleartext-Password', ':=', 'testpass456')
ON DUPLICATE KEY UPDATE value = VALUES(value);

-- Test User 3: For SSL/TLS testing
INSERT INTO radcheck (username, attribute, op, value)
VALUES
    ('testuser3@example.com', 'Cleartext-Password', ':=', 'ssltest789')
ON DUPLICATE KEY UPDATE value = VALUES(value);

-- Test User 4: Student account
INSERT INTO radcheck (username, attribute, op, value)
VALUES
    ('student@example.com', 'Cleartext-Password', ':=', 'student123')
ON DUPLICATE KEY UPDATE value = VALUES(value);

-- Test User 5: Staff account
INSERT INTO radcheck (username, attribute, op, value)
VALUES
    ('staff@example.com', 'Cleartext-Password', ':=', 'staff123')
ON DUPLICATE KEY UPDATE value = VALUES(value);

-- ==================================================
-- Test User Groups
-- ==================================================

-- Assign users to groups
INSERT INTO radusergroup (username, groupname, priority)
VALUES
    ('testuser1@example.com', 'student', 1),
    ('testuser2@example.com', 'student', 1),
    ('testuser3@example.com', 'staff', 1),
    ('student@example.com', 'student', 1),
    ('staff@example.com', 'staff', 1)
ON DUPLICATE KEY UPDATE priority = VALUES(priority);

-- ==================================================
-- Test NAS (Network Access Servers / Access Points)
-- ==================================================

-- Test AP 1: Main Building
INSERT INTO nas (nasname, shortname, type, ports, secret, server, community, description)
VALUES
    ('172.30.0.100', 'test-ap-01', 'other', NULL, 'testing123', NULL, NULL, 'Test Access Point 01 - Main Building')
ON DUPLICATE KEY UPDATE description = VALUES(description);

-- Test AP 2: Library
INSERT INTO nas (nasname, shortname, type, ports, secret, server, community, description)
VALUES
    ('172.30.0.101', 'test-ap-02', 'other', NULL, 'testing123', NULL, NULL, 'Test Access Point 02 - Library')
ON DUPLICATE KEY UPDATE description = VALUES(description);

-- Test AP 3: Dormitory
INSERT INTO nas (nasname, shortname, type, ports, secret, server, community, description)
VALUES
    ('172.30.0.102', 'test-ap-03', 'other', NULL, 'testing123', NULL, NULL, 'Test Access Point 03 - Dormitory')
ON DUPLICATE KEY UPDATE description = VALUES(description);

-- Test AP 4: Admin Building
INSERT INTO nas (nasname, shortname, type, ports, secret, server, community, description)
VALUES
    ('172.30.0.103', 'test-ap-04', 'other', NULL, 'testing123', NULL, NULL, 'Test Access Point 04 - Admin Building')
ON DUPLICATE KEY UPDATE description = VALUES(description);

-- ==================================================
-- Test Accounting Data (radacct)
-- ==================================================

-- Active sessions (no stop time)
INSERT INTO radacct (
    acctsessionid, acctuniqueid, username,
    nasipaddress, nasportid, nasporttype,
    acctstarttime, acctupdatetime,
    acctsessiontime, acctinputoctets, acctoutputoctets,
    calledstationid, callingstationid,
    servicetype, framedipaddress, framedprotocol
) VALUES
    -- User 1: Active session
    (
        'session-001', 'unique-001', 'testuser1@example.com',
        '172.30.0.100', '1', 'Wireless-802.11',
        DATE_SUB(NOW(), INTERVAL 2 HOUR), NOW(),
        7200, 1048576000, 524288000,
        'AP01-WiFi', 'AA:BB:CC:DD:EE:01',
        'Framed-User', '10.10.1.101', 'PPP'
    ),
    -- User 2: Active session
    (
        'session-002', 'unique-002', 'testuser2@example.com',
        '172.30.0.101', '2', 'Wireless-802.11',
        DATE_SUB(NOW(), INTERVAL 1 HOUR), NOW(),
        3600, 524288000, 262144000,
        'AP02-WiFi', 'AA:BB:CC:DD:EE:02',
        'Framed-User', '10.10.1.102', 'PPP'
    ),
    -- User 3: Active session
    (
        'session-003', 'unique-003', 'staff@example.com',
        '172.30.0.103', '3', 'Wireless-802.11',
        DATE_SUB(NOW(), INTERVAL 30 MINUTE), NOW(),
        1800, 104857600, 52428800,
        'AP04-WiFi', 'AA:BB:CC:DD:EE:03',
        'Framed-User', '10.10.1.103', 'PPP'
    )
ON DUPLICATE KEY UPDATE acctupdatetime = VALUES(acctupdatetime);

-- Completed sessions (with stop time)
INSERT INTO radacct (
    acctsessionid, acctuniqueid, username,
    nasipaddress, nasportid, nasporttype,
    acctstarttime, acctstoptime, acctupdatetime,
    acctsessiontime, acctinputoctets, acctoutputoctets,
    acctterminatecause,
    calledstationid, callingstationid,
    servicetype, framedipaddress, framedprotocol
) VALUES
    -- Yesterday's sessions
    (
        'session-101', 'unique-101', 'testuser1@example.com',
        '172.30.0.100', '1', 'Wireless-802.11',
        DATE_SUB(NOW(), INTERVAL 1 DAY), DATE_SUB(NOW(), INTERVAL 22 HOUR), DATE_SUB(NOW(), INTERVAL 22 HOUR),
        7200, 2097152000, 1048576000,
        'User-Request',
        'AP01-WiFi', 'AA:BB:CC:DD:EE:01',
        'Framed-User', '10.10.1.101', 'PPP'
    ),
    (
        'session-102', 'unique-102', 'testuser2@example.com',
        '172.30.0.101', '2', 'Wireless-802.11',
        DATE_SUB(NOW(), INTERVAL 1 DAY), DATE_SUB(NOW(), INTERVAL 20 HOUR), DATE_SUB(NOW(), INTERVAL 20 HOUR),
        14400, 3145728000, 1572864000,
        'Session-Timeout',
        'AP02-WiFi', 'AA:BB:CC:DD:EE:02',
        'Framed-User', '10.10.1.102', 'PPP'
    ),
    (
        'session-103', 'unique-103', 'student@example.com',
        '172.30.0.102', '3', 'Wireless-802.11',
        DATE_SUB(NOW(), INTERVAL 1 DAY), DATE_SUB(NOW(), INTERVAL 18 HOUR), DATE_SUB(NOW(), INTERVAL 18 HOUR),
        21600, 5242880000, 2621440000,
        'User-Request',
        'AP03-WiFi', 'AA:BB:CC:DD:EE:04',
        'Framed-User', '10.10.1.104', 'PPP'
    ),
    -- Two days ago
    (
        'session-201', 'unique-201', 'staff@example.com',
        '172.30.0.103', '4', 'Wireless-802.11',
        DATE_SUB(NOW(), INTERVAL 2 DAY), DATE_SUB(NOW(), INTERVAL 46 HOUR), DATE_SUB(NOW(), INTERVAL 46 HOUR),
        3600, 1048576000, 524288000,
        'Lost-Carrier',
        'AP04-WiFi', 'AA:BB:CC:DD:EE:03',
        'Framed-User', '10.10.1.103', 'PPP'
    )
ON DUPLICATE KEY UPDATE acctterminatecause = VALUES(acctterminatecause);

-- ==================================================
-- Test Authentication Log Data (radpostauth)
-- ==================================================

-- Successful authentications
INSERT INTO radpostauth (
    username, pass, reply, reply_message, error_type,
    authdate, authdate_utc
) VALUES
    ('testuser1@example.com', 'password123', 'Access-Accept', 'Login OK', NULL, NOW(), UTC_TIMESTAMP()),
    ('testuser2@example.com', 'testpass456', 'Access-Accept', 'Login OK', NULL, DATE_SUB(NOW(), INTERVAL 1 HOUR), DATE_SUB(UTC_TIMESTAMP(), INTERVAL 1 HOUR)),
    ('staff@example.com', 'staff123', 'Access-Accept', 'Login OK', NULL, DATE_SUB(NOW(), INTERVAL 2 HOUR), DATE_SUB(UTC_TIMESTAMP(), INTERVAL 2 HOUR)),
    ('student@example.com', 'student123', 'Access-Accept', 'Login OK', NULL, DATE_SUB(NOW(), INTERVAL 3 HOUR), DATE_SUB(UTC_TIMESTAMP(), INTERVAL 3 HOUR));

-- Failed authentications - Password wrong
INSERT INTO radpostauth (
    username, pass, reply, reply_message, error_type,
    authdate, authdate_utc
) VALUES
    ('testuser1@example.com', 'wrongpass', 'Access-Reject', 'Authentication failed: Invalid username or password. Please verify your credentials and try again.', 'password_wrong', DATE_SUB(NOW(), INTERVAL 30 MINUTE), DATE_SUB(UTC_TIMESTAMP(), INTERVAL 30 MINUTE)),
    ('testuser2@example.com', 'badpassword', 'Access-Reject', 'Authentication failed: Invalid username or password. Please verify your credentials and try again.', 'password_wrong', DATE_SUB(NOW(), INTERVAL 1 HOUR), DATE_SUB(UTC_TIMESTAMP(), INTERVAL 1 HOUR)),
    ('staff@example.com', 'incorrect', 'Access-Reject', 'Authentication failed: Invalid username or password. Please verify your credentials and try again.', 'password_wrong', DATE_SUB(NOW(), INTERVAL 2 HOUR), DATE_SUB(UTC_TIMESTAMP(), INTERVAL 2 HOUR));

-- Failed authentications - User not found
INSERT INTO radpostauth (
    username, pass, reply, reply_message, error_type,
    authdate, authdate_utc
) VALUES
    ('nonexistent@example.com', 'anypass', 'Access-Reject', 'Authentication failed: User not found. Please contact your administrator if you believe this is an error.', 'user_not_found', DATE_SUB(NOW(), INTERVAL 4 HOUR), DATE_SUB(UTC_TIMESTAMP(), INTERVAL 4 HOUR)),
    ('unknown@example.com', 'testpass', 'Access-Reject', 'Authentication failed: User not found. Please contact your administrator if you believe this is an error.', 'user_not_found', DATE_SUB(NOW(), INTERVAL 5 HOUR), DATE_SUB(UTC_TIMESTAMP(), INTERVAL 5 HOUR));

-- Failed authentications - LDAP connection failed
INSERT INTO radpostauth (
    username, pass, reply, reply_message, error_type,
    authdate, authdate_utc
) VALUES
    ('testuser3@example.com', 'ssltest789', 'Access-Reject', 'Authentication failed: Unable to connect to authentication server. Please try again later or contact support.', 'ldap_connection_failed', DATE_SUB(NOW(), INTERVAL 6 HOUR), DATE_SUB(UTC_TIMESTAMP(), INTERVAL 6 HOUR));

-- Failed authentications - SSL certificate error
INSERT INTO radpostauth (
    username, pass, reply, reply_message, error_type,
    authdate, authdate_utc
) VALUES
    ('testuser3@example.com', 'ssltest789', 'Access-Reject', 'Authentication failed: SSL certificate verification failed. This is a server configuration issue. Please contact your network administrator.', 'ssl_certificate_error', DATE_SUB(NOW(), INTERVAL 7 HOUR), DATE_SUB(UTC_TIMESTAMP(), INTERVAL 7 HOUR));

-- Failed authentications - Authentication failed (generic)
INSERT INTO radpostauth (
    username, pass, reply, reply_message, error_type,
    authdate, authdate_utc
) VALUES
    ('testuser1@example.com', 'password123', 'Access-Reject', 'Authentication failed: An unexpected error occurred during authentication. Please try again or contact support.', 'authentication_failed', DATE_SUB(NOW(), INTERVAL 8 HOUR), DATE_SUB(UTC_TIMESTAMP(), INTERVAL 8 HOUR));

-- ==================================================
-- User Information (userinfo table)
-- ==================================================

INSERT INTO userinfo (
    username, firstname, lastname, email, department,
    company, workphone, mobilephone,
    creationdate, creationby
) VALUES
    ('testuser1@example.com', 'Test', 'User One', 'testuser1@example.com', 'Engineering', 'Test Company', '555-0101', '555-0201', NOW(), 'admin'),
    ('testuser2@example.com', 'Test', 'User Two', 'testuser2@example.com', 'Marketing', 'Test Company', '555-0102', '555-0202', NOW(), 'admin'),
    ('testuser3@example.com', 'Test', 'User Three', 'testuser3@example.com', 'IT', 'Test Company', '555-0103', '555-0203', NOW(), 'admin'),
    ('student@example.com', 'Test', 'Student', 'student@example.com', 'Computer Science', 'University', '555-0104', '555-0204', NOW(), 'admin'),
    ('staff@example.com', 'Test', 'Staff', 'staff@example.com', 'Administration', 'University', '555-0105', '555-0205', NOW(), 'admin')
ON DUPLICATE KEY UPDATE email = VALUES(email);

-- ==================================================
-- Display Summary
-- ==================================================

SELECT '========================================' AS '';
SELECT 'Test Data Setup Complete!' AS 'Status';
SELECT '========================================' AS '';

SELECT 'Test Users Created:' AS '';
SELECT COUNT(*) AS 'Total Users' FROM radcheck WHERE username LIKE '%@example.com';

SELECT '' AS '';
SELECT 'Test NAS/Access Points Created:' AS '';
SELECT COUNT(*) AS 'Total NAS' FROM nas WHERE nasname LIKE '172.30.0.%';

SELECT '' AS '';
SELECT 'Test Accounting Records:' AS '';
SELECT COUNT(*) AS 'Active Sessions' FROM radacct WHERE acctstoptime IS NULL;
SELECT COUNT(*) AS 'Completed Sessions' FROM radacct WHERE acctstoptime IS NOT NULL;

SELECT '' AS '';
SELECT 'Test Authentication Log:' AS '';
SELECT reply, COUNT(*) AS 'Count' FROM radpostauth GROUP BY reply;

SELECT '' AS '';
SELECT 'Error Types Distribution:' AS '';
SELECT
    COALESCE(error_type, 'SUCCESS') AS 'Error Type',
    COUNT(*) AS 'Count'
FROM radpostauth
GROUP BY error_type
ORDER BY COUNT(*) DESC;

SELECT '========================================' AS '';
