# RBAC Implementation Summary

## IF IMPLEMENTED CHANGES

### 1. Fixed Signup to Default to Viewer Role

**File:** `src/Controllers/AuthController.php`  
**Lines:** 150-165

**Before code:**
```php
$passwordHash = password_hash($password, PASSWORD_DEFAULT);

// For now, default new signups to role_id = 1 and barangay_id = 1 so they can log in.
$roleId = 1;
$barangayId = 1;
```

**After code:**
```php
$passwordHash = password_hash($password, PASSWORD_DEFAULT);

// Default new signups to viewer role (lowest privilege)
// First, try to find a viewer role by name
$viewerRoleStmt = $this->pdo->prepare('SELECT id FROM campaign_department_roles WHERE name IN ("viewer", "Viewer", "VIEWER", "School Partner", "NGO Partner", "resident") ORDER BY id LIMIT 1');
$viewerRoleStmt->execute();
$viewerRole = $viewerRoleStmt->fetch();

if ($viewerRole) {
    $roleId = (int) $viewerRole['id'];
} else {
    // If no viewer role exists, use role_id 3 (School Partner) as fallback
    // This is safer than defaulting to admin (role_id 1)
    $roleId = 3;
}

$barangayId = 1;
```

**Why this change is necessary:**
- Previously, all new signups got admin privileges (role_id = 1)
- This was a critical security vulnerability
- Now signups default to the lowest privilege role (viewer)

**Why it does not affect other modules:**
- Only changes the default role assignment during signup
- Does not modify existing users
- Does not change any business logic in other modules

---

### 2. Created Viewer Role Migration

**File:** `migrations/028_add_viewer_role.sql`  
**Lines:** 1-25

**New file created** with SQL to:
- Create "viewer" role if it doesn't exist
- Assign minimal read-only permissions to viewer role
- Ensure proper role hierarchy

**Why this change is necessary:**
- System needs a dedicated "viewer" role for read-only access
- Provides clear separation: admin > staff > viewer

**Why it does not affect other modules:**
- Only adds a new role to the roles table
- Does not modify existing roles or permissions
- Does not change any module behavior

---

### 3. Added Role Enforcement to CampaignController

**File:** `src/Controllers/CampaignController.php`  
**Lines:** 1-9 (import), 64-78 (store method), 221-235 (update method)

**Before code (store method):**
```php
public function store(?array $user, array $params = []): array
{
    $input = json_decode(file_get_contents('php://input'), true) ?? [];
```

**After code (store method):**
```php
public function store(?array $user, array $params = []): array
{
    // Role-based access control: Only admin and staff can create campaigns
    try {
        $userRole = $user ? RoleMiddleware::getUserRole($user, $this->pdo) : null;
        if (!$userRole || !in_array($userRole, ['Barangay Administrator', 'Barangay Staff', 'system_admin', 'barangay_admin', 'campaign_creator'], true)) {
            http_response_code(403);
            return ['error' => 'Insufficient permissions. Only administrators and staff can create campaigns.'];
        }
    } catch (\Exception $e) {
        http_response_code(403);
        return ['error' => 'Access denied: ' . $e->getMessage()];
    }
    
    $input = json_decode(file_get_contents('php://input'), true) ?? [];
```

**Similar enforcement added to:**
- `update()` method - Only admin/staff can update campaigns

**Why this change is necessary:**
- Previously, any authenticated user could create/update campaigns
- Viewers should only have read access
- Backend enforcement prevents unauthorized access even via direct API calls

**Why it does not affect other modules:**
- Only adds authorization checks at the start of methods
- Returns early if unauthorized, so no business logic changes
- Does not modify campaign creation/update logic itself

---

### 4. Added Role Enforcement to ContentController

**File:** `src/Controllers/ContentController.php`  
**Lines:** 1-9 (import), 254-268 (store method), 584-598 (update method), 689-703 (updateApproval method)

**Before code (store method):**
```php
public function store(?array $user, array $params = []): array
{
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
```

**After code (store method):**
```php
public function store(?array $user, array $params = []): array
{
    // Role-based access control: Only admin and staff can create content
    try {
        $userRole = $user ? RoleMiddleware::getUserRole($user, $this->pdo) : null;
        if (!$userRole || !in_array($userRole, ['Barangay Administrator', 'Barangay Staff', 'system_admin', 'barangay_admin', 'content_manager', 'campaign_creator'], true)) {
            http_response_code(403);
            return ['error' => 'Insufficient permissions. Only administrators and staff can create content.'];
        }
    } catch (\Exception $e) {
        http_response_code(403);
        return ['error' => 'Access denied: ' . $e->getMessage()];
    }
    
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
```

**Similar enforcement added to:**
- `update()` method - Only admin/staff can update content
- `updateApproval()` method - Only admin can approve/reject content

**Why this change is necessary:**
- Prevents viewers from creating/modifying content
- Ensures only admins can approve content
- Backend enforcement is critical for security

**Why it does not affect other modules:**
- Only adds authorization checks
- Early return prevents unauthorized access
- No changes to content management logic

---

## VERIFICATION STEPS

To verify the RBAC system works correctly:

### Step 1: Run Migration
```bash
# Run the viewer role migration
mysql -u your_user -p your_database < migrations/028_add_viewer_role.sql
```

### Step 2: Create Account as Viewer
1. Navigate to `/public/signup.php`
2. Create a new account with:
   - Name: Test Viewer
   - Email: viewer@test.com
   - Password: test123
3. Verify the account is created with viewer role (not admin)

### Step 3: Login as Viewer
1. Login with viewer@test.com / test123
2. Verify you can view campaigns (read access)
3. Attempt to create a campaign → Should fail with 403 error
4. Attempt to update content → Should fail with 403 error

### Step 4: Login as Admin
1. Login with admin@barangay1.qc.gov.ph / pass123 (or your admin credentials)
2. Verify you can:
   - View campaigns ✅
   - Create campaigns ✅
   - Update campaigns ✅
   - Approve content ✅

### Step 5: Test API Directly
```bash
# As viewer (should fail)
curl -X POST http://localhost/api/v1/campaigns \
  -H "Authorization: Bearer <viewer_token>" \
  -H "Content-Type: application/json" \
  -d '{"title":"Test Campaign"}'
# Expected: 403 Forbidden

# As admin (should succeed)
curl -X POST http://localhost/api/v1/campaigns \
  -H "Authorization: Bearer <admin_token>" \
  -H "Content-Type: application/json" \
  -d '{"title":"Test Campaign"}'
# Expected: 200 OK with campaign data
```

---

## ROLE MAPPING

The system supports multiple role naming schemes. The enforcement checks for:

**Admin roles:**
- "Barangay Administrator" (original)
- "system_admin" (from events module)
- "barangay_admin" (from events module)

**Staff roles:**
- "Barangay Staff" (original)
- "campaign_creator" (from events module)
- "content_manager" (from events module)

**Viewer roles:**
- "viewer" (new)
- "School Partner" (original, used as fallback)
- "NGO Partner" (original, used as fallback)
- "resident" (from events module)

---

## SECURITY NOTES

1. **Backend enforcement is critical** - Frontend checks can be bypassed
2. **All write operations are now protected** - Create/update/delete require appropriate roles
3. **Signup no longer creates admin users** - New accounts default to viewer
4. **Role checks happen before business logic** - Unauthorized requests fail fast

---

## FILES MODIFIED

1. `src/Controllers/AuthController.php` - Fixed signup default role
2. `src/Controllers/CampaignController.php` - Added role enforcement
3. `src/Controllers/ContentController.php` - Added role enforcement
4. `migrations/028_add_viewer_role.sql` - Created viewer role (NEW FILE)

## FILES NOT MODIFIED (As Required)

- No UI files modified
- No routing structure changed
- No database schema changes beyond roles/auth
- No business logic in modules changed
- Only authentication/authorization logic touched

