# Database Migration Instructions

This guide explains how to run database migrations for the Public Safety Campaign Management System.

## Prerequisites

- **XAMPP** (or any MySQL server) running
- **PHP CLI** (command line interface)
- **MySQL** server accessible

## Method 1: Using the Migration Script (Recommended)

### Step 1: Configure Database Connection

The script uses environment variables. You can either:

**Option A: Set environment variables**
```bash
# Windows (PowerShell)
$env:DB_HOST="127.0.0.1"
$env:DB_DATABASE="LGU"
$env:DB_USERNAME="root"
$env:DB_PASSWORD=""

# Windows (CMD)
set DB_HOST=127.0.0.1
set DB_DATABASE=LGU
set DB_USERNAME=root
set DB_PASSWORD=

# Linux/Mac
export DB_HOST=127.0.0.1
export DB_DATABASE=LGU
export DB_USERNAME=root
export DB_PASSWORD=
```

**Option B: Edit `run_migrations.php`** and change the default values:
```php
$dbHost = '127.0.0.1';
$dbName = 'LGU';
$dbUser = 'root';
$dbPass = '';
```

### Step 2: Run the Migration Script

Open terminal/command prompt in the project root directory and run:

```bash
php run_migrations.php
```

The script will:
1. Create the database if it doesn't exist
2. Run migrations in order:
   - `001_initial_schema.sql` - Base schema
   - `011_complete_schema_update.sql` - Schema enhancements
   - `012_seed_data.sql` - Sample data

### Expected Output

```
✓ Database 'LGU' ready

=== Running Database Migrations ===

→ Running 001_initial_schema.sql...
  ✓ 001_initial_schema.sql completed successfully
→ Running 011_complete_schema_update.sql...
  ✓ 011_complete_schema_update.sql completed successfully
→ Running 012_seed_data.sql...
  ✓ 012_seed_data.sql completed successfully

=== Migration Process Complete ===
✓ Database is ready to use!
```

---

## Method 2: Using MySQL Command Line

### Step 1: Open MySQL Command Line

```bash
# Windows (XAMPP)
cd C:\xampp\mysql\bin
mysql.exe -u root -p

# Linux/Mac
mysql -u root -p
```

### Step 2: Run Migrations Manually

```sql
-- Create database
CREATE DATABASE IF NOT EXISTS LGU CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE LGU;

-- Run migrations
SOURCE C:/xampp/htdocs/public-safety-campaign-system/migrations/001_initial_schema.sql;
SOURCE C:/xampp/htdocs/public-safety-campaign-system/migrations/011_complete_schema_update.sql;
SOURCE C:/xampp/htdocs/public-safety-campaign-system/migrations/012_seed_data.sql;
```

**Note:** Adjust the file paths according to your system.

---

## Method 3: Using phpMyAdmin (GUI)

### Step 1: Open phpMyAdmin

1. Start XAMPP
2. Open browser and go to: `http://localhost/phpmyadmin`
3. Login (usually no password for root user)

### Step 2: Create Database

1. Click "New" in the left sidebar
2. Database name: `LGU`
3. Collation: `utf8mb4_unicode_ci`
4. Click "Create"

### Step 3: Import Migration Files

For each migration file (in order):

1. Select the `LGU` database from the left sidebar
2. Click the "Import" tab
3. Click "Choose File"
4. Select the migration file (e.g., `001_initial_schema.sql`)
5. Click "Go" at the bottom
6. Repeat for:
   - `011_complete_schema_update.sql`
   - `012_seed_data.sql`

---

## Method 4: Using Command Line (One-liner)

### Windows (PowerShell)

```powershell
cd C:\xampp\htdocs\public-safety-campaign-system
C:\xampp\mysql\bin\mysql.exe -u root -e "CREATE DATABASE IF NOT EXISTS LGU CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
C:\xampp\mysql\bin\mysql.exe -u root LGU < migrations\001_initial_schema.sql
C:\xampp\mysql\bin\mysql.exe -u root LGU < migrations\011_complete_schema_update.sql
C:\xampp\mysql\bin\mysql.exe -u root LGU < migrations\012_seed_data.sql
```

### Linux/Mac

```bash
cd /path/to/public-safety-campaign-system
mysql -u root -e "CREATE DATABASE IF NOT EXISTS LGU CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
mysql -u root LGU < migrations/001_initial_schema.sql
mysql -u root LGU < migrations/011_complete_schema_update.sql
mysql -u root LGU < migrations/012_seed_data.sql
```

---

## Verification

After running migrations, verify the database was created correctly:

```sql
USE LGU;

-- Check tables
SHOW TABLES;

-- Check roles
SELECT * FROM roles;

-- Check sample data
SELECT COUNT(*) as campaign_count FROM campaigns;
SELECT COUNT(*) as user_count FROM users;
```

Expected output should show:
- Multiple tables (campaigns, users, roles, etc.)
- 4 roles (Barangay Administrator, Barangay Staff, School Partner, NGO Partner)
- Sample campaigns and users

---

## Troubleshooting

### Error: "Access denied for user"

**Solution:** Check your MySQL username and password. Default XAMPP root user usually has no password.

### Error: "Unknown database"

**Solution:** Make sure the database `LGU` is created first, or use the migration script which creates it automatically.

### Error: "Table already exists"

**Solution:** The migration might have been partially run. You can either:
- Drop the database and re-run: `DROP DATABASE LGU;`
- Or manually check which tables exist and skip those migrations

### Error: "Syntax error" in migration file

**Solution:** Some MySQL versions might not support `IF NOT EXISTS` in `ALTER TABLE`. You may need to modify the migration file or use a newer MySQL version (8.0+).

---

## Migration Files Overview

| File | Purpose |
|------|---------|
| `001_initial_schema.sql` | Base database schema (tables, relationships) |
| `011_complete_schema_update.sql` | Schema enhancements per specification |
| `012_seed_data.sql` | Sample data for testing and development |

---

## Next Steps

After migrations are complete:

1. **Configure Application:**
   - Update `src/Config/db_connect.php` or set environment variables
   - Ensure database credentials match

2. **Test API:**
   - Start your web server
   - Test login endpoint: `POST /api/v1/auth/login`
   - Use credentials from seed data (admin@barangay1.qc.gov.ph / password123)

3. **Access Frontend:**
   - Navigate to `public/campaigns.php` (if using web interface)
   - Or use API endpoints directly

---

## Default Test Credentials

After running seed data, you can login with:

- **Admin:** `admin@barangay1.qc.gov.ph` / `password123`
- **Staff:** `staff@barangay1.qc.gov.ph` / `password123`
- **School Partner:** `school@example.com` / `password123`
- **NGO Partner:** `ngo@example.com` / `password123`

**⚠️ Important:** Change these passwords in production!








