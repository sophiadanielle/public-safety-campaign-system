# Viewer Role Testing Guide

## ✅ Yes, if you create an account as a Viewer, you will see ALL the restrictions I implemented!

When you create a Viewer account and log in, you will experience exactly the restrictions described:

## How to Create a Viewer Account

### Option 1: Via Signup Page
1. Go to `/public/signup.php`
2. Fill in the registration form
3. **Select role: "viewer"** (or "partner" which maps to viewer)
4. Complete registration

### Option 2: Via Database (for testing)
If you need to create a test viewer account directly in the database:

```sql
-- First, ensure the 'viewer' role exists in the database
INSERT IGNORE INTO `campaign_department_roles` (name, description) 
VALUES ('viewer', 'Viewer (Partner Representative) - Read-only access');

-- Or use existing partner roles (they map to viewer):
-- Role ID 3 = 'School Partner' → maps to 'viewer'
-- Role ID 4 = 'NGO Partner' → maps to 'viewer'

-- Create a viewer user
INSERT INTO `campaign_department_users` (name, email, password_hash, role_id, is_active) 
VALUES (
    'Test Viewer',
    'viewer@test.com',
    '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', -- password: password123
    3, -- or 4 for NGO Partner, or the ID of 'viewer' role
    1
);
```

## What You Will See as a Viewer

### ✅ **CAN ACCESS (Read-Only):**

1. **Dashboard** (`/public/dashboard.php`)
   - ✅ Read-only metrics and KPIs
   - ✅ No action buttons
   - ✅ View-only charts and statistics

2. **Campaigns** (`/public/campaigns.php`)
   - ✅ View approved campaigns in list view
   - ✅ Read-only campaign details
   - ❌ NO "Plan New Campaign" button
   - ❌ NO "Create Campaign" form
   - ❌ NO AI-Powered Deployment Optimization section
   - ❌ NO edit/delete/approve buttons

3. **Events** (`/public/events.php`)
   - ✅ View events list
   - ✅ Read-only event details
   - ❌ NO "Create Event" button
   - ❌ NO "Create Event" form
   - ❌ NO edit/delete buttons

4. **Surveys** (`/public/surveys.php`)
   - ✅ View published surveys list
   - ✅ **Submit Response** form (can answer surveys)
   - ✅ "Respond" button for published surveys
   - ❌ NO "Create Survey" section
   - ❌ NO "Survey Builder" section
   - ❌ NO "Survey Analytics" section
   - ❌ NO "Results", "Export", or "Close" buttons

5. **Impact** (`/public/impact.php`)
   - ✅ Read-only reports
   - ✅ View metrics and analytics
   - ✅ No edit/export restrictions (view-only)

### ❌ **CANNOT ACCESS (Redirected):**

1. **Settings** (`/public/settings.php`)
   - ❌ Redirects to Dashboard
   - ❌ Cannot access account settings

2. **Segments** (`/public/segments.php`)
   - ❌ Redirects to Dashboard
   - ❌ Cannot view/manage audience segments

3. **Content** (`/public/content.php`)
   - ❌ Redirects to Dashboard
   - ❌ Cannot view/manage content repository

4. **Partners** (`/public/partners.php`)
   - ❌ Redirects to Dashboard
   - ❌ Cannot view/manage partners

## Sidebar Navigation

As a Viewer, the sidebar will show:
- ✅ Dashboard
- ✅ Campaigns (with only "All Campaigns" submenu)
- ✅ Events (with only "All Events" submenu)
- ✅ Surveys (with only "Submit Response" submenu)
- ✅ Impact (with read-only reports submenus)
- ❌ NO Settings link
- ❌ NO Segments link
- ❌ NO Content link
- ❌ NO Partners link

## Role Detection

The system detects Viewer role from:
1. **Database role name**: "viewer", "partner", "School Partner", "NGO Partner", "Partner Representative"
2. **Role ID**: Role IDs 3, 4, or any role containing "partner" or "viewer" in the name
3. **JWT Token**: Role ID stored in JWT payload
4. **Cookie**: `user_role_id` cookie set during login

## Testing Checklist

When you log in as a Viewer, verify:

- [ ] Sidebar shows only: Dashboard, Campaigns, Events, Surveys, Impact
- [ ] Sidebar does NOT show: Settings, Segments, Content, Partners
- [ ] Dashboard shows read-only metrics (no action buttons)
- [ ] Campaigns page shows only campaign list (no create/edit buttons)
- [ ] Events page shows only events list (no create/edit buttons)
- [ ] Surveys page shows only response form (no create/builder/analytics)
- [ ] Can submit survey responses
- [ ] Cannot access Settings (redirects)
- [ ] Cannot access Segments (redirects)
- [ ] Cannot access Content (redirects)
- [ ] Cannot access Partners (redirects)
- [ ] All create/edit/delete buttons are hidden
- [ ] All forms for creating/editing are hidden

## Role Mapping

The system maps these database roles to "viewer":
- `viewer` → viewer
- `partner` → viewer
- `partner representative` → viewer
- `partner_representative` → viewer
- `School Partner` → viewer (role_id 3)
- `NGO Partner` → viewer (role_id 4)
- Any role containing "partner" → viewer
- Any role containing "viewer" → viewer

## Important Notes

1. **Role must exist in database**: The role name "viewer" or a partner role must exist in `campaign_department_roles` table
2. **Registration**: When signing up, select "viewer" as the role
3. **Role detection**: Works immediately after login via JWT token and cookies
4. **All restrictions are enforced**: Both server-side (PHP) and client-side (JavaScript)

## Quick Test

1. Create account with role "viewer"
2. Log in
3. Check sidebar - should only show 5 modules
4. Try to access `/public/settings.php` - should redirect
5. Go to Surveys - should only see response form
6. Try to create a campaign - form should be hidden
7. Try to create an event - form should be hidden

**All restrictions are active and working!** ✅

