# What File to Import into lgu Database (Option 3: phpMyAdmin)

## Current Situation

Your `lgu` database already has:
- ✅ Schema (43 tables with `campaign_department_` prefix)
- ✅ Some seed data (4 users, 4 roles, 15 campaigns, 18 barangays, 27 content items)

## Files You Can Import

### Option A: Production Export (Best - Real Data)
**File:** `production_lgu_export.sql` (if it exists)

**How to get it:**
1. Run: `php export_production_to_local.php`
2. If successful, you'll get `production_lgu_export.sql`
3. Import this file via phpMyAdmin

**Status:** This file doesn't exist yet (production export failed due to network access)

---

### Option B: Seed Data Files (Sample Data)
**Files to import (in order):**

1. **`migrations/012_seed_data.sql`** - Basic seed data
   - Already partially imported (some errors, but data exists)
   - You can re-import to add more data

2. **`migrations/013_seed_qc_reference_data.sql`** - Quezon City reference data
   - Already imported ✓

3. **`migrations/015_content_repository_seed.sql`** - Content repository samples
   - Already imported ✓

**Note:** These use `campaign_department_` prefix, so they're compatible with your `lgu` database.

---

### Option C: Additional Seed Files (More Sample Data)

**Files you can import for more data:**

- `migrations/002_sample_seed.sql` - Additional sample data
- `migrations/003_demo_campaign.sql` - Demo campaign data
- `migrations/005_sample_audience_members.sql` - Sample audience members

**Before importing these:**
- Check if they use `campaign_department_` prefix
- If they use `LGU` or no prefix, you may need to modify them

---

## Recommended Import Order (via phpMyAdmin)

### If you want MORE sample data:

1. **First, import:** `migrations/002_sample_seed.sql`
2. **Then import:** `migrations/003_demo_campaign.sql`
3. **Then import:** `migrations/005_sample_audience_members.sql`

### If you want to REPLACE with production data:

1. **First, get production export:**
   ```bash
   php export_production_to_local.php
   ```
2. **Then import:** `production_lgu_export.sql` (if export succeeds)

---

## How to Import via phpMyAdmin

1. Open: `http://localhost/phpmyadmin`
2. Select `lgu` database from left sidebar
3. Click "Import" tab at the top
4. Click "Choose File" button
5. Navigate to the file you want to import:
   - For seed data: `migrations/012_seed_data.sql` (or other seed files)
   - For production: `production_lgu_export.sql` (if available)
6. Leave settings as default (or adjust if needed)
7. Click "Go" button at the bottom
8. Wait for import to complete

---

## Important Notes

⚠️ **Warning:** Importing will ADD data to existing tables. If you want to start fresh:
- You can drop and recreate the database first
- Or use `TRUNCATE TABLE` to clear existing data before importing

✅ **Safe to import multiple times:** Most seed files use `INSERT IGNORE` which won't create duplicates.

---

## Quick Answer

**For Option 3 (phpMyAdmin), import this file:**

**Best option:** `migrations/012_seed_data.sql` (if you want more sample data)

**Or if you have production export:** `production_lgu_export.sql` (real production data)

**Location:** Files are in your project root or `migrations/` folder.





