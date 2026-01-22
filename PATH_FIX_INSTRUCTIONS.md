# CSS/Asset Path Fix for Production Deployment

## Problem
CSS and static assets fail to load on production (https://campaign.alertaraqc.com/) with 404 errors, while working correctly on localhost.

## Root Cause
The base path was hardcoded to `/public-safety-campaign-system`, which works for localhost but fails on production where the app is deployed at root or a different subdomain.

## Solution Applied

### 1. Updated `header/includes/path_helper.php`
- Changed from hardcoded `/public-safety-campaign-system` to dynamic detection
- Added support for `BASE_PATH` environment variable override
- Auto-detects base path from file system location

### 2. Updated `sidebar/includes/sidebar.php`
- Changed fallback from hardcoded path to empty string

## Quick Fix for Production

**Option 1: Quick Override (Easiest - Recommended)**

Edit `header/includes/path_helper.php` and change line 17:

```php
// Change this line:
$basePathOverride = null;

// To this (for root deployment):
$basePathOverride = '';
```

This will force the base path to be empty, making all asset paths work from root (e.g., `/header/css/global.css` instead of `/public-safety-campaign-system/header/css/global.css`).

**Option 2: Set Environment Variable**

Add to your `.env` file or server configuration:
```bash
BASE_PATH=
```
(Empty string for root deployment at https://campaign.alertaraqc.com/)

**Option 3: Let Auto-Detection Work**

The auto-detection should work automatically, but if it doesn't, use Option 1.

## Verification Steps

1. **Check current base path:**
   - Open browser console on https://campaign.alertaraqc.com/
   - Look for: `BASE PATH: ...`
   - Should show empty string `BASE PATH: ` (nothing after colon) for root deployment

2. **Check CSS loading:**
   - Open DevTools → Network tab
   - Reload page
   - CSS files should load from `/header/css/global.css` (not `/public-safety-campaign-system/header/css/global.css`)
   - No 404 errors

3. **Visual check:**
   - Login page should be fully styled
   - All pages should render correctly with proper CSS

## Files Changed

1. `header/includes/path_helper.php` - Dynamic base path detection
2. `sidebar/includes/sidebar.php` - Removed hardcoded fallback

## Expected Behavior

- **Localhost:** `BASE PATH: /public-safety-campaign-system` → CSS from `/public-safety-campaign-system/header/css/...`
- **Production (root):** `BASE PATH: ` (empty) → CSS from `/header/css/...`
- **Production (subdir):** `BASE PATH: /subdir` → CSS from `/subdir/header/css/...`

