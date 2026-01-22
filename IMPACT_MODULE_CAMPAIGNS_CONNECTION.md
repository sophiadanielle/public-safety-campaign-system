# Impact Module - Campaigns Connection Implementation

## Overview
Connected all campaigns from the Campaigns module to all sections in the Impact module. All "Select Campaign" dropdowns now show a complete list of all campaigns.

## Implementation

### Function Added: `loadAllCampaigns()`
**Location:** `public/impact.php`

**Purpose:**
- Loads all campaigns from the API endpoint `/api/v1/campaigns`
- Populates all 5 campaign dropdowns in the Impact module

**Dropdowns Populated:**
1. `campaign_id` - Campaign Impact Dashboard section
2. `report_campaign_id` - Evaluation Reports section
3. `metrics_campaign_id` - Metrics Overview section
4. `analysis_campaign_id` - Performance Analysis section
5. `export_campaign_id` - Export Data section

**How It Works:**
1. Fetches all campaigns from `/api/v1/campaigns` API endpoint
2. For each dropdown, clears existing options
3. Adds placeholder option: "-- Select a campaign --"
4. Adds all campaigns with format: "ID {id} - {title}"
5. Shows "-- No campaigns available --" if no campaigns exist

**Execution:**
- Runs automatically on page load
- Waits 300ms to ensure JWT token is available
- Handles both DOM ready and already-loaded states

## Campaign Data Source

**API Endpoint:** `GET /api/v1/campaigns`
- Returns all campaigns from the database
- No filtering applied (shows all campaigns)
- Viewer role can see all campaigns in dropdowns (read-only access)

## Sections Connected

### 1. Campaign Impact Dashboard
- **Dropdown ID:** `campaign_id`
- **Button:** "View Campaign Performance"
- **Function:** `loadImpact()`
- **Data Source:** `/api/v1/campaigns/{id}/impact`

### 2. Evaluation Reports
- **Dropdown ID:** `report_campaign_id`
- **Button:** "Create Evaluation Report" (hidden for Viewer)
- **Function:** `generateReport()`
- **Data Source:** `/api/v1/reports/generate/{id}`

### 3. Metrics Overview
- **Dropdown ID:** `metrics_campaign_id`
- **Button:** "View Key Metrics"
- **Function:** `loadMetricsOverview()`
- **Data Source:** `/api/v1/campaigns/{id}/impact`

### 4. Performance Analysis
- **Dropdown ID:** `analysis_campaign_id`
- **Button:** "Analyze Performance"
- **Function:** `loadPerformanceAnalysis()`
- **Data Source:** `/api/v1/campaigns/{id}/impact`

### 5. Export Data
- **Dropdown ID:** `export_campaign_id`
- **Button:** "Download Data (CSV)"
- **Function:** `exportImpactData()`
- **Data Source:** `/api/v1/campaigns/{id}/impact`

## Viewer Role Restrictions

**Note:** The "Create Evaluation Report" button is automatically hidden for Viewer role by `viewer-restrictions.js` because it contains the word "Create" in its text.

**Viewer Can:**
- ✅ See all campaigns in all dropdowns
- ✅ View Campaign Performance (read-only)
- ✅ View Key Metrics (read-only)
- ✅ Analyze Performance (read-only)
- ✅ Download Data (CSV) - read-only export

**Viewer Cannot:**
- ❌ Create Evaluation Report (button hidden)

## Testing

1. **Load Impact Page:**
   - All 5 dropdowns should be populated with campaigns
   - Format: "ID {id} - {title}"

2. **Select Campaign:**
   - Select a campaign from any dropdown
   - Click the corresponding action button
   - Data should load for that campaign

3. **Viewer Role:**
   - All dropdowns show all campaigns
   - "Create Evaluation Report" button is hidden
   - All other buttons work (read-only operations)

## Files Modified

- `public/impact.php` - Added `loadAllCampaigns()` function and initialization code

## No Changes to Campaigns Module

✅ **No modifications made to campaigns module** - All changes are in Impact module only.

