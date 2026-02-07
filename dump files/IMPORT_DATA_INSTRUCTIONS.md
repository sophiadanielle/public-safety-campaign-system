# How to Import Data into lgu Database

Your `lgu` database now has the correct schema (43 tables with `campaign_department_` prefix) but is empty. Here are three ways to populate it with data:

---

## Option 1: Import from Production Database (Recommended)

This will copy all data from the production `LGU` database at `alertaraqc.com` into your local `lgu` database.

### Step 1: Export from Production

Run the export script (it connects directly to production, doesn't modify .env):

```bash
php export_production_to_local.php
```

**Note:** This requires network access to `alertaraqc.com:3306`. If connection fails, see troubleshooting below.

### Step 2: Import into Local lgu Database

After the export completes, you'll have a file like `production_lgu_export.sql`.

**Via phpMyAdmin:**
1. Open phpMyAdmin: `http://localhost/phpmyadmin`
2. Select `lgu` database from left sidebar
3. Click "Import" tab
4. Click "Choose File" and select `production_lgu_export.sql`
5. Click "Go" at the bottom
6. Wait for import to complete

**Via MySQL Command Line:**
```bash
# Windows (XAMPP)
cd C:\xampp\htdocs\public-safety-campaign-system
C:\xampp\mysql\bin\mysql.exe -u root -pPhiarren@182212 lgu < production_lgu_export.sql

# Or if you prefer to be prompted for password:
C:\xampp\mysql\bin\mysql.exe -u root -p lgu < production_lgu_export.sql
```

### Troubleshooting Production Export

If `export_production_to_local.php` fails to connect:

**Option A: Use SSH Tunnel (if you have SSH access)**
1. Create SSH tunnel: `ssh -L 3306:localhost:3306 user@alertaraqc.com`
2. Modify `export_production_to_local.php` to use `localhost:3306` instead of `alertaraqc.com:3306`
3. Run the export script

**Option B: Manual Export via phpMyAdmin (if you have web access)**
1. Access production phpMyAdmin (if available)
2. Select `LGU` database
3. Click "Export" tab
4. Choose "Quick" or "Custom" method
5. Format: SQL
6. Click "Go" to download
7. Import the downloaded file into local `lgu` database

**Option C: Use mysqldump from Server (if you have server access)**
```bash
mysqldump -h alertaraqc.com -u root -p'YsqnXk6q#145' \
  --single-transaction \
  --routines \
  --triggers \
  --events \
  LGU > production_lgu_export.sql
```

---

## Option 2: Seed Data from Migrations

If you want to start with sample/seed data instead of production data:

### Step 1: Run Seed Migrations

```bash
# Windows (XAMPP)
cd C:\xampp\htdocs\public-safety-campaign-system
C:\xampp\php\php.exe -r "
require 'src/Config/db_connect.php';
\$pdo->exec('USE lgu');
\$sql = file_get_contents('migrations/012_seed_data.sql');
\$sql = str_replace('USE `LGU`;', 'USE `lgu`;', \$sql);
\$pdo->exec(\$sql);
echo 'Seed data imported successfully';
"
```

**Or manually via MySQL:**
```bash
C:\xampp\mysql\bin\mysql.exe -u root -pPhiarren@182212 lgu < migrations/012_seed_data.sql
```

**Note:** You may need to modify the seed file to use `lgu` instead of `LGU` if it has `USE LGU;` statements.

### Step 2: Run Additional Seed Files (if available)

```bash
# QC Reference Data
C:\xampp\mysql\bin\mysql.exe -u root -pPhiarren@182212 lgu < migrations/013_seed_qc_reference_data.sql

# Content Repository Seed
C:\xampp\mysql\bin\mysql.exe -u root -pPhiarren@182212 lgu < migrations/015_content_repository_seed.sql
```

---

## Option 3: Manual Import from SQL File

If you have an existing SQL backup file:

### Via phpMyAdmin:
1. Open phpMyAdmin: `http://localhost/phpmyadmin`
2. Select `lgu` database
3. Click "Import" tab
4. Choose your SQL file
5. Click "Go"

### Via MySQL Command Line:
```bash
C:\xampp\mysql\bin\mysql.exe -u root -pPhiarren@182212 lgu < your_backup_file.sql
```

---

## Verification After Import

After importing data, verify it worked:

```sql
USE lgu;

-- Check table counts
SELECT COUNT(*) as user_count FROM campaign_department_users;
SELECT COUNT(*) as campaign_count FROM campaign_department_campaigns;
SELECT COUNT(*) as role_count FROM campaign_department_roles;

-- List all tables with row counts
SELECT 
    table_name,
    table_rows
FROM information_schema.TABLES
WHERE TABLE_SCHEMA = 'lgu'
AND TABLE_TYPE = 'BASE TABLE'
ORDER BY table_name;
```

---

## Quick Start: Try Production Export First

The easiest approach is Option 1. Run this command:

```bash
php export_production_to_local.php
```

If it succeeds, you'll get `production_lgu_export.sql`. Then import it:

```bash
C:\xampp\mysql\bin\mysql.exe -u root -pPhiarren@182212 lgu < production_lgu_export.sql
```

If production export fails due to network issues, use Option 2 (seed data) to at least get sample data for testing.

---

## After Import: Update Application Configuration

Once data is imported, make sure your application connects to `lgu`:

1. Check `.env` file has:
   ```env
   APP_ENV=local
   DB_HOST=localhost
   DB_NAME=lgu
   DB_USER=root
   DB_PASSWORD=Phiarren@182212
   DB_PORT=3306
   ```

2. Test the connection by accessing your application

---

## Need Help?

- If production export fails: Check network/firewall settings or use Option 2 (seed data)
- If import fails: Check file size limits in phpMyAdmin or use command line
- If tables are empty after import: Verify the SQL file contains INSERT statements





