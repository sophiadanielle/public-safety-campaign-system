# Database Synchronization Instructions

## Overview

This guide explains how to synchronize your local `lgu` database with the production `LGU` database at `alertaraqc.com`.

## Important Notes

- **No automatic migration was performed** - You need to manually export from production and import locally
- Production database is at: `alertaraqc.com` (remote server)
- Local database should be: `lgu` (on your XAMPP/localhost)

## Method 1: Using the Export Script (Recommended)

### Step 1: Run Export Script

**Note:** The script connects directly to production - it does NOT modify your `.env` file.

Simply run:

```bash
php export_production_to_local.php
```

This will create `production_lgu_export.sql` containing:
- All table structures (CREATE TABLE statements)
- All data (INSERT statements)

### Step 3: Import into Local Database

**Option A: Via phpMyAdmin**
1. Open phpMyAdmin: `http://localhost/phpmyadmin`
2. Select `lgu` database (or create it if it doesn't exist)
3. Click "Import" tab
4. Choose file: `production_lgu_export.sql`
5. Click "Go"

**Option B: Via MySQL Command Line**
```bash
# Windows (XAMPP)
cd C:\xampp\htdocs\public-safety-campaign-system
C:\xampp\mysql\bin\mysql.exe -u root -p lgu < production_lgu_export.sql

# Linux/Mac
mysql -u root -p lgu < production_lgu_export.sql
```

### Step 4: Verify Import

```sql
USE lgu;
SHOW TABLES;
SELECT COUNT(*) FROM campaigns;
SELECT COUNT(*) FROM users;
```

## Method 2: Using mysqldump (Direct Export)

If you have direct access to the production server:

### Export from Production

```bash
mysqldump -h alertaraqc.com -u root -p'YsqnXk6q#145' \
  --single-transaction \
  --routines \
  --triggers \
  --events \
  LGU > production_lgu_export.sql
```

### Import to Local

```bash
# Create local database if needed
mysql -u root -p -e "CREATE DATABASE IF NOT EXISTS lgu CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"

# Import
mysql -u root -p lgu < production_lgu_export.sql
```

## Method 3: Manual Export via phpMyAdmin (If you have web access)

1. Access production phpMyAdmin (if available)
2. Select `LGU` database
3. Click "Export" tab
4. Choose "Quick" or "Custom" export method
5. Select "SQL" format
6. Click "Go" to download
7. Import the downloaded file into local `lgu` database

## Verification Checklist

After import, verify:

- [ ] All tables exist in local `lgu` database
- [ ] Row counts match production (check key tables)
- [ ] Foreign key relationships are intact
- [ ] Application connects successfully to local database

## Troubleshooting

### Error: "Access denied" when connecting to production

**Solution:** The script uses hardcoded production credentials. If connection fails, verify:
- Production server is accessible: `alertaraqc.com:3306`
- Credentials are correct (check `export_production_to_local.php` if needed)
- Firewall/network allows connection to production server

### Error: "Table already exists" during import

**Solution:** Drop existing tables first:
```sql
USE lgu;
SET FOREIGN_KEY_CHECKS = 0;
-- Drop all tables (be careful!)
SET FOREIGN_KEY_CHECKS = 1;
```

Or use `DROP TABLE IF EXISTS` in the SQL export.

### Error: "Unknown database 'lgu'"

**Solution:** Create the database first:
```sql
CREATE DATABASE IF NOT EXISTS lgu CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
```

## Important Reminders

1. **Backup your local database** before importing production data
2. **Verify APP_ENV** is set correctly before running export script
3. **Check table names** - production uses `LGU`, local should use `lgu` (case may matter on some systems)
4. **Test connection** after import to ensure everything works

## After Synchronization

1. Update `.env` to use local database:
   ```env
   APP_ENV=local
   DB_HOST=localhost
   DB_NAME=lgu
   DB_USER=root
   DB_PASSWORD=your_local_password
   DB_PORT=3306
   ```

2. Test the application to ensure it connects correctly

3. Verify data integrity by checking key tables

