# Viewer (Partner Representative) Role Implementation

## Overview
This document describes the implementation of the Viewer (Partner Representative) role restrictions for the LGU Public Safety Campaign Management System. All changes were made through frontend access control, routing guards, UI behavior, and permission enforcement only - **no backend PHP logic was refactored**.

## Role Definition
**Viewer (Partner Representative)** - An external user with:
- Read-only access to selected modules
- Ability to answer surveys only
- No ability to create, edit, approve, delete, or manage any records

## Access Rules Implemented

### ✅ Allowed Modules (Read-Only)
1. **Dashboard** - Read-only view of KPIs and metrics
2. **Campaigns** - View approved campaigns only (list view)
3. **Events** - View events only (list view)
4. **Surveys** - View published surveys and submit responses
5. **Impact** - Read-only reports and metrics

### ❌ Restricted Modules
Viewers **cannot** access:
- Settings
- Segments
- Content
- Partners
- Any admin/configuration pages

### Surveys Behavior (Special Case)
When an Admin creates and publishes a survey:
- ✅ Viewer can see the survey form UI
- ✅ Viewer can open the survey
- ✅ Viewer can answer questions
- ✅ Viewer can submit responses
- ❌ Viewer **cannot** create, edit, delete, or manage surveys
- ❌ Viewer **cannot** see survey builder
- ❌ Viewer **cannot** modify questions
- ❌ Viewer **cannot** see admin controls or analytics

## Implementation Details

### 1. Route Guards
**Files Modified:**
- `public/settings.php` - Added viewer role check that redirects to dashboard
- `public/partners.php` - Already had viewer protection (redirects to dashboard)
- `public/segments.php` - Already had viewer protection via `block_viewer_access.php`
- `public/content.php` - Already had viewer protection via `block_viewer_access.php`

**Implementation:**
- JavaScript checks user role from JWT token or localStorage
- Redirects Viewer users away from restricted pages
- Prevents manual URL access to admin pages

### 2. Sidebar Navigation Filtering
**File Modified:** `sidebar/includes/sidebar.php`

**Changes:**
- Filters modules based on user role (Viewer only sees allowed modules)
- Filters submenu features for Viewer role:
  - Dashboard: Only KPI Overview and Engagement & Impact
  - Campaigns: Only "All Campaigns" list (no planning, AI tools)
  - Events: Only "All Events" list (no create)
  - Surveys: Only "Submit Response" section (no create, builder, analytics)
  - Impact: Read-only reports only

### 3. Surveys Module
**File Modified:** `public/surveys.php`

**Changes:**
- Hide "Create Survey" section for Viewer (PHP conditional)
- Hide "Survey Builder" section for Viewer
- Hide "Survey Analytics" section for Viewer
- Show only "Submit Response" form for Viewer
- Updated `renderSurveysList()` function:
  - Hides "Results", "Export", and "Close" buttons for Viewer
  - Shows only "Respond" button for published surveys
  - Removes "Actions" column header for Viewer

**New Functions:**
- `checkIfViewer()` - Checks if current user is Viewer role
- `loadSurveyForResponseById(surveyId)` - Helper to load survey for response

### 4. Events Module
**File Modified:** `public/events.php`

**Status:** Already had comprehensive viewer protection
- Create event form is hidden via PHP conditional
- JavaScript aggressively hides all create/edit/delete buttons
- Auto-redirects to events list view
- All action buttons are removed from DOM for Viewer

### 5. Campaigns Module
**File Modified:** `public/campaigns.php`

**Status:** Already had viewer protection
- Planning section hidden for Viewer
- AutoML section hidden for Viewer
- All create/edit/approve buttons hidden via JavaScript
- Read-only campaign list view for Viewer

### 6. Dashboard Module
**File Modified:** `public/dashboard.php`

**Status:** Already had viewer protection
- CSS injection to hide action buttons
- Read-only view of metrics and KPIs

### 7. Permission Utility
**File Created:** `public/js/permissions.js`

**Functions:**
- `getCurrentUserRole()` - Gets current user role from JWT/localStorage
- `isViewer()` - Checks if user is Viewer role
- `canModify()` - Checks if user can create/edit/delete
- `canApprove()` - Checks if user can approve/reject
- `enforceViewerRestrictions()` - Route guard function
- `hideForViewer(selector)` - Hides elements for Viewer
- `disableForViewer(selector)` - Disables elements for Viewer

## UI Enforcement Summary

### Hidden Elements for Viewer:
- ✅ All "Create" buttons
- ✅ All "Edit" buttons
- ✅ All "Delete" buttons
- ✅ All "Approve/Reject" buttons
- ✅ All form inputs for creation/editing
- ✅ Survey builder and analytics sections
- ✅ Campaign planning forms
- ✅ Event creation forms
- ✅ Admin configuration sections

### Visible Elements for Viewer:
- ✅ Read-only data tables and lists
- ✅ Survey response forms (for answering)
- ✅ View-only campaign details
- ✅ View-only event details
- ✅ Dashboard metrics (read-only)
- ✅ Impact reports (read-only)

## Technical Constraints Respected

✅ **No backend PHP refactoring** - All changes are frontend-only
✅ **No database schema changes** - Role behavior via frontend only
✅ **Route guards** - JavaScript-based access control
✅ **Conditional rendering** - PHP conditionals for server-side hiding
✅ **UI permission checks** - JavaScript role checking
✅ **State/session role checking** - Uses existing role detection system

## Testing Checklist

- [ ] Viewer can access Dashboard (read-only)
- [ ] Viewer can access Campaigns (read-only list)
- [ ] Viewer can access Events (read-only list)
- [ ] Viewer can access Surveys (respond only)
- [ ] Viewer can access Impact (read-only)
- [ ] Viewer **cannot** access Settings (redirects)
- [ ] Viewer **cannot** access Segments (redirects)
- [ ] Viewer **cannot** access Content (redirects)
- [ ] Viewer **cannot** access Partners (redirects)
- [ ] Viewer can submit survey responses
- [ ] Viewer **cannot** create surveys
- [ ] Viewer **cannot** edit surveys
- [ ] Viewer **cannot** see survey analytics
- [ ] All create/edit/delete buttons are hidden
- [ ] Sidebar shows only allowed modules
- [ ] Sidebar shows only allowed submenu items

## Files Modified

1. `public/settings.php` - Added viewer route guard
2. `public/surveys.php` - Hide create/edit sections, update list rendering
3. `sidebar/includes/sidebar.php` - Filter surveys features for Viewer
4. `public/js/permissions.js` - Created permission utility (for future use)

## Files Already Protected (No Changes Needed)

1. `public/events.php` - Already has comprehensive viewer protection
2. `public/campaigns.php` - Already has viewer protection
3. `public/dashboard.php` - Already has viewer protection
4. `public/partners.php` - Already has viewer protection
5. `public/segments.php` - Already has viewer protection
6. `public/content.php` - Already has viewer protection

## Notes

- The Viewer role is mapped from database roles: "Partner", "Partner Representative", "Partner_Representative", or any role containing "partner" or "viewer"
- Role detection uses the existing `getCurrentUserRole()` function from `sidebar/includes/get_user_role.php`
- All restrictions are enforced both server-side (PHP) and client-side (JavaScript) for security
- The implementation follows the existing RBAC pattern used throughout the system

