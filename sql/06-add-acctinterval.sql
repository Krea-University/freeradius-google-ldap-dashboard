-- Add missing acctinterval column for interim updates
-- This column tracks the time between accounting update packets

USE radius;

-- Add acctinterval column
ALTER TABLE radacct 
ADD COLUMN acctinterval int(12) DEFAULT NULL AFTER acctsessiontime;

-- Add index for performance
CREATE INDEX idx_acctinterval ON radacct (acctinterval);

COMMIT;
