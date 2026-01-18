# RBAC UI Module Visibility Implementation

## SUMMARY

Implemented role-based sidebar module filtering to match research defense requirements. The sidebar now dynamically shows/hides modules based on user role.

## FILES MODIFIED

### 1. sidebar/includes/get_user_role.php (NEW)
**Purpose:** Helper function to get user role from JWT token in PHP

**Function:** `getCurrentUserRole(): ?string`
- Extracts JWT from Authorization header
- Decodes JWT to get user ID
- Queries database for role name
- Returns lowercase role name or null

### 2. sidebar/includes/sidebar.php
**Lines Changed:**

#### Line 38-41: Get user role
```php
// Get current user role for module filtering
require_once __DIR__ . '/get_user_role.php';
$currentUserRole = getCurrentUserRole();
```

#### Line 137-157: PHP-based module filtering
```php
// RBAC: Filter modules based on user role
// Define which modules each role can access
$roleModulePermissions = [
    'viewer' => ['dashboard.php', 'campaigns.php', 'events.php', 'surveys.php', 'impact.php'],
    'staff' => ['dashboard.php', 'campaigns.php', 'content.php', 'segments.php', 'events.php', 'surveys.php', 'impact.php'],
    'secretary' => ['dashboard.php', 'campaigns.php', 'content.php', 'segments.php', 'events.php', 'surveys.php', 'impact.php'],
    'kagawad' => ['dashboard.php', 'campaigns.php', 'events.php', 'impact.php', 'content.php'],
    'captain' => ['dashboard.php', 'campaigns.php', 'content.php', 'segments.php', 'events.php', 'surveys.php', 'impact.php', 'partners.php'],
    'admin' => ['dashboard.php', 'campaigns.php', 'content.php', 'segments.php', 'events.php', 'surveys.php', 'impact.php', 'partners.php'],
    // Legacy role support
    'barangay administrator' => [...],
    'barangay staff' => [...],
];

// Filter modules based on role (if role is available)
if ($currentUserRole && isset($roleModulePermissions[$currentUserRole])) {
    $allowedModules = $roleModulePermissions[$currentUserRole];
    $modules = array_filter($modules, function($key) use ($allowedModules) {
        return in_array($key, $allowedModules, true);
    }, ARRAY_FILTER_USE_KEY);
}
```

#### Line 484-563: JavaScript-based fallback filtering
```javascript
// RBAC: Client-side module filtering based on user role from localStorage
// This provides fallback if PHP role detection fails
function filterSidebarByRole() {
    // Gets currentUser from localStorage
    // Maps role/role_id to effective role
    // Filters sidebar items based on roleModulePermissions
    // Hides modules not in allowed list
}
```

## MODULE VISIBILITY BY ROLE

### VIEWER (Partner Representative)
**Visible Modules:**
- ✅ Dashboard
- ✅ Campaigns (view only)
- ✅ Events (view only)
- ✅ Surveys (respond only)
- ✅ Impact (view reports)

**Hidden Modules:**
- ❌ Content
- ❌ Segments
- ❌ Partners management
- ❌ AI features (shown but blocked by backend)

### STAFF (BDRRMO/Admin Staff)
**Visible Modules:**
- ✅ Dashboard
- ✅ Campaigns (create/edit drafts only)
- ✅ Content
- ✅ Segments
- ✅ Events
- ✅ Surveys
- ✅ Impact

**Hidden Modules:**
- ❌ Partners management (admin/captain only)
- ❌ User management (admin only)

### SECRETARY
**Visible Modules:**
- ✅ Dashboard
- ✅ Campaigns (coordination/scheduling)
- ✅ Content
- ✅ Segments
- ✅ Events
- ✅ Surveys
- ✅ Impact

**Hidden Modules:**
- ❌ Partners management
- ❌ User management

### KAGAWAD (Public Safety Committee)
**Visible Modules:**
- ✅ Dashboard
- ✅ Campaigns (view/review only)
- ✅ Events (view)
- ✅ Impact (reports)
- ✅ Content (view)

**Hidden Modules:**
- ❌ Segments (encoding tool)
- ❌ Create/edit forms (read-only)
- ❌ Partners management

### CAPTAIN (Barangay Captain)
**Visible Modules:**
- ✅ Dashboard
- ✅ Campaigns (full access, can approve)
- ✅ Content
- ✅ Segments
- ✅ Events
- ✅ Surveys
- ✅ Impact
- ✅ Partners (can manage)

**Hidden Modules:**
- ❌ User management (admin only)
- ❌ System configuration (admin only)

### ADMIN (Admin Staff)
**Visible Modules:**
- ✅ ALL MODULES (full access)
- ✅ User management (if implemented)
- ✅ System configuration (if implemented)

## ENFORCEMENT MECHANISM

### Two-Layer Protection:

1. **PHP Server-Side Filtering** (Primary):
   - Decodes JWT from Authorization header
   - Queries database for role
   - Filters `$modules` array before rendering
   - Modules not in allowed list never appear in HTML

2. **JavaScript Client-Side Filtering** (Fallback):
   - Reads `currentUser` from localStorage
   - Filters sidebar items after page load
   - Provides backup if PHP filtering fails
   - Ensures no unauthorized modules are visible

### Why Both?

- **PHP filtering**: Prevents unauthorized modules from being rendered in HTML (more secure)
- **JavaScript filtering**: Works even if JWT not in header (handles edge cases, provides instant UI update)

## VERIFICATION

To verify module visibility:

1. **Login as Viewer:**
   - Sidebar should show: Dashboard, Campaigns, Events, Surveys, Impact
   - Should NOT show: Content, Segments, Partners

2. **Login as Staff:**
   - Sidebar should show: Dashboard, Campaigns, Content, Segments, Events, Surveys, Impact
   - Should NOT show: Partners

3. **Login as Captain:**
   - Sidebar should show: All modules including Partners
   - Should NOT show: User management (if exists)

4. **Check Browser Console:**
   - Should see: `RBAC: Sidebar filtered for role: [role] Allowed modules: [list]`

## HOW IT WORKS

1. **Page Load:**
   - PHP tries to get role from JWT header
   - Filters modules array
   - Renders only allowed modules

2. **After DOM Ready:**
   - JavaScript reads `currentUser` from localStorage
   - Hides any sidebar items not in allowed list
   - Provides fallback if PHP filtering missed anything

3. **Dynamic Updates:**
   - If `currentUser` changes in localStorage, sidebar re-filters
   - Ensures UI stays in sync with user role

## BACKEND RBAC (Already Implemented)

Backend enforcement (HTTP 403) already prevents unauthorized actions:
- Viewer cannot create/update/delete (returns 403)
- Staff cannot approve (workflow enforced)
- Secretary cannot finalize (workflow enforced)
- Kagawad cannot finalize (workflow enforced)
- Captain can approve (final authority)

**See:** `RBAC_ENFORCEMENT_COMPLETE.md` for backend proof

## FILES SUMMARY

**Modified:**
- `sidebar/includes/sidebar.php` - Added PHP and JavaScript filtering

**Created:**
- `sidebar/includes/get_user_role.php` - Helper to get role from JWT

**No Changes:**
- Database schema (uses existing `campaign_department_roles` table)
- Business logic (only visibility filtering)
- UI styling (only show/hide via `display: none`)
- Controller logic (backend RBAC already enforced)

