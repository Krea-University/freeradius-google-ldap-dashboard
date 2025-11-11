-- Performance optimization for FreeRADIUS accounting
-- Adds indexes and optimizations for faster accounting operations

USE radius;

-- Optimize radacct table with composite indexes for common queries
-- Add indexes if they don't exist (ignore errors if they already exist)
CREATE INDEX idx_session_lookup ON radacct (acctsessionid, username);
CREATE INDEX idx_active_sessions ON radacct (username, acctstoptime);
CREATE INDEX idx_time_range ON radacct (acctstarttime, acctstoptime);

-- Optimize the acctuniqueid for faster duplicate detection
-- Already has UNIQUE KEY, but ensure it's optimized
ANALYZE TABLE radacct;

-- Optimize radpostauth for faster authentication logging
CREATE INDEX idx_user_time ON radpostauth (username, authdate);

ANALYZE TABLE radpostauth;

-- Set table engine optimizations
ALTER TABLE radacct ENGINE=InnoDB ROW_FORMAT=DYNAMIC;
ALTER TABLE radpostauth ENGINE=InnoDB ROW_FORMAT=DYNAMIC;

-- Update table statistics
ANALYZE TABLE radacct, radpostauth, radcheck, radreply, radusergroup;

COMMIT;
