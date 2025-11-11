-- Add IPv6 support columns to radacct table
-- This migration adds the missing IPv6 columns that FreeRADIUS 3.0.23 requires

USE radius;

-- Add IPv6 columns to radacct table
-- Check and add columns only if they don't exist
SET @preparedStatement = (SELECT IF(
  (
    SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
    WHERE table_schema=DATABASE()
    AND table_name='radacct'
    AND column_name='framedipv6address'
  ) > 0,
  "SELECT 1",
  "ALTER TABLE radacct 
   ADD COLUMN framedipv6address varchar(45) NOT NULL default '' AFTER framedipaddress,
   ADD COLUMN framedipv6prefix varchar(45) NOT NULL default '' AFTER framedipv6address,
   ADD COLUMN framedinterfaceid varchar(44) NOT NULL default '' AFTER framedipv6prefix,
   ADD COLUMN delegatedipv6prefix varchar(45) NOT NULL default '' AFTER framedinterfaceid"
));
PREPARE alterStatement FROM @preparedStatement;
EXECUTE alterStatement;
DEALLOCATE PREPARE alterStatement;

-- Add index for IPv6 address lookups
SET @preparedStatement = (SELECT IF(
  (
    SELECT COUNT(*) FROM INFORMATION_SCHEMA.STATISTICS
    WHERE table_schema=DATABASE()
    AND table_name='radacct'
    AND index_name='framedipv6address'
  ) > 0,
  "SELECT 1",
  "ALTER TABLE radacct ADD INDEX framedipv6address (framedipv6address)"
));
PREPARE alterStatement FROM @preparedStatement;
EXECUTE alterStatement;
DEALLOCATE PREPARE alterStatement;

COMMIT;
