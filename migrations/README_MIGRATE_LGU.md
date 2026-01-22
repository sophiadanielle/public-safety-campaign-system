# Database Migration: lgu → LGU

This migration copies all tables, data, views, and structure from the `lgu` database to the `LGU` database.

## Prerequisites

- MySQL root access
- Both databases should exist (or `LGU` will be created)
- Backup of existing `LGU` database if it contains important data (this migration will overwrite it)

## Method 1: SQL Script (Recommended)

Run the SQL migration script:

```bash
mysql -u root -p < migrations/999_migrate_lgu_to_uppercase.sql
```

Or in MySQL command line:
```sql
SOURCE migrations/999_migrate_lgu_to_uppercase.sql;
```

## Method 2: mysqldump (Alternative - More Reliable)

This method uses mysqldump to export and import, which preserves everything more reliably:

### Step 1: Export from lgu
```bash
mysqldump -u root -p --single-transaction --routines --triggers lgu > lgu_backup.sql
```

### Step 2: Modify database name in dump file
```bash
# On Windows PowerShell:
(Get-Content lgu_backup.sql) -replace 'CREATE DATABASE.*`lgu`', 'CREATE DATABASE IF NOT EXISTS `LGU`' -replace 'USE `lgu`', 'USE `LGU`' -replace '`lgu`.', '`LGU`.' | Set-Content lgu_backup_modified.sql

# On Linux/Mac:
sed -i 's/`lgu`/`LGU`/g' lgu_backup.sql
sed -i 's/USE `lgu`/USE `LGU`/g' lgu_backup.sql
```

### Step 3: Import into LGU
```bash
mysql -u root -p < lgu_backup_modified.sql
```

## What Gets Migrated

- ✅ All base tables (37 tables)
- ✅ All table data (preserves AUTO_INCREMENT values)
- ✅ All indexes and constraints
- ✅ All foreign keys
- ✅ All views (3 views: campaign_engagement_summary, timing_effectiveness, participation_history)
- ✅ Character sets and collations
- ✅ Table structures (columns, data types, defaults)

## Verification

After migration, verify both databases have the same structure:

```sql
-- Count tables
SELECT 
    'LGU' AS database_name,
    COUNT(*) AS table_count
FROM information_schema.TABLES 
WHERE TABLE_SCHEMA = 'LGU' AND TABLE_TYPE = 'BASE TABLE'
UNION ALL
SELECT 
    'lgu' AS database_name,
    COUNT(*) AS table_count
FROM information_schema.TABLES 
WHERE TABLE_SCHEMA = 'lgu' AND TABLE_TYPE = 'BASE TABLE';

-- Verify data counts match for a sample table
SELECT 
    'LGU' AS db,
    COUNT(*) AS campaign_count
FROM LGU.campaigns
UNION ALL
SELECT 
    'lgu' AS db,
    COUNT(*) AS campaign_count
FROM lgu.campaigns;
```

## Important Notes

1. **This migration will DROP existing tables in LGU** - backup first if needed
2. **Foreign key checks are disabled** during migration for faster execution
3. **Views are recreated** with updated table references (`lgu` → `LGU`)
4. **AUTO_INCREMENT values are preserved** by using CREATE TABLE ... LIKE
5. **All data is copied** using INSERT ... SELECT

## Troubleshooting

If you encounter foreign key errors:
- The script disables foreign key checks, but if issues persist, run tables in dependency order
- Check that all referenced tables exist in LGU before creating foreign keys

If views fail to create:
- Check that all referenced tables exist in LGU
- Verify view definitions don't reference tables that don't exist





