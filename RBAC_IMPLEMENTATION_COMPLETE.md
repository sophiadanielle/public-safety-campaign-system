# Complete RBAC Backend Enforcement Implementation

## SUMMARY

Backend RBAC enforcement has been implemented across all controllers. The system now enforces role-based permissions at the API level, not just UI hiding.

## FILES MODIFIED

### 1. CampaignController.php
**Lines Changed:**
- **26-30:** Added authentication check to `index()`
- **226-231:** Added authentication check to `show()`
- **65-79:** Enhanced `store()` with viewer read-only check
- **235-245:** Enhanced `update()` with viewer read-only check and proper LGU role validation

**Before:**
```php
public function index(?array $user, array $params = []): array
{
    try {
        $sql = 'SELECT ... FROM campaign_department_campaigns ...';
```

**After:**
```php
public function index(?array $user, array $params = []): array
{
    // RBAC: All authenticated users can view campaigns (read access)
    if (!$user) {
        http_response_code(401);
        return ['error' => 'Authentication required'];
    }
    // ... rest of method
```

**Enforcement:**
- ✅ Viewer cannot create campaigns (403 error)
- ✅ Viewer cannot update campaigns (403 error)
- ✅ Only authorized LGU roles (staff, secretary, kagawad, captain, admin) can create/update
- ✅ Workflow enforcement already exists in `update()` method (lines 267-374)

### 2. SegmentController.php
**Lines Changed:**
- **7:** Added `use App\Middleware\RoleMiddleware;`
- **52-58:** Added authentication check to `index()`
- **71-77:** Added authentication check to `show()`
- **99-120:** Added RBAC enforcement to `store()` with viewer read-only check
- **219-240:** Added RBAC enforcement to `update()` with viewer read-only check

**Enforcement:**
- ✅ Viewer cannot create segments (403 error)
- ✅ Viewer cannot update segments (403 error)
- ✅ Only authorized LGU roles can create/update segments

### 3. EventController.php
**Lines Changed:**
- **116-122:** Added authentication check to `show()`
- **217-228:** Enhanced `store()` with viewer read-only check and LGU role support
- **383-395:** Enhanced `update()` with viewer read-only check and LGU role support

**Enforcement:**
- ✅ Viewer cannot create events (403 error)
- ✅ Viewer cannot update events (403 error)
- ✅ Only authorized LGU roles can create/update events

### 4. SurveyController.php
**Lines Changed:**
- **73-100:** Added RBAC enforcement to `store()` with viewer read-only check

**Enforcement:**
- ✅ Viewer cannot create surveys (403 error)
- ✅ Only authorized LGU roles can create surveys

### 5. PartnerController.php
**Lines Changed:**
- **7:** Added `use App\Middleware\RoleMiddleware;`
- **21-27:** Added authentication check to `index()`
- **27-50:** Added RBAC enforcement to `store()` with viewer read-only check
- **51-75:** Added RBAC enforcement to `engage()` with viewer read-only check

**Enforcement:**
- ✅ Viewer cannot create partners (403 error)
- ✅ Viewer cannot engage partners (403 error)
- ✅ Only authorized LGU roles can create/engage partners

### 6. ContentController.php
**Lines Changed:**
- **36-42:** Added authentication check to `index()`
- **498-504:** Added authentication check to `show()`
- **Note:** `store()`, `update()`, and `updateApproval()` already had role checks (lines 254-268, 584-598, 689-703)

**Enforcement:**
- ✅ All read operations require authentication
- ✅ Write operations already had role checks (no changes needed)

### 7. AuthController.php (Signup)
**Lines Changed:**
- **129-176:** Complete rewrite of `register()` method to require role selection

**Before:**
```php
// Default new signups to staff role (entry-level LGU role)
$staffRoleStmt = $this->pdo->prepare('SELECT id FROM campaign_department_roles WHERE name IN ("staff", ...)');
// Auto-assigns role
```

**After:**
```php
$roleName = isset($input['role']) ? trim((string) $input['role']) : '';

// RBAC: Role selection is REQUIRED - no auto-assignment
if (!$roleName) {
    http_response_code(422);
    return ['error' => 'Role selection is required. Please select your role: staff, secretary, kagawad, captain, partner, or viewer.'];
}

// Validate role name against allowed LGU roles
$allowedRoles = ['staff', 'secretary', 'kagawad', 'captain', 'partner', 'viewer'];
$normalizedRoleName = strtolower($roleName);
if (!in_array($normalizedRoleName, $allowedRoles, true)) {
    http_response_code(422);
    return ['error' => 'Invalid role. Allowed roles: staff, secretary, kagawad, captain, partner, viewer.'];
}

// Get role ID from selected role name
$roleStmt = $this->pdo->prepare('SELECT id FROM campaign_department_roles WHERE LOWER(name) = :role_name LIMIT 1');
$roleStmt->execute(['role_name' => $normalizedRoleName]);
$role = $roleStmt->fetch();

if (!$role) {
    http_response_code(422);
    return ['error' => 'Selected role does not exist in the system. Please contact administrator.'];
}

$roleId = (int) $role['id'];
```

**Enforcement:**
- ✅ Signup now REQUIRES role selection (no auto-assignment)
- ✅ Only valid LGU roles accepted: staff, secretary, kagawad, captain, partner, viewer
- ✅ Role must exist in database

### 8. public/signup.php
**Lines Changed:**
- **223-230:** Added role selection dropdown to signup form
- **300-318:** Updated `signup()` JavaScript function to include role in API call

**Before:**
```html
<input id="password" type="password" ...>
<button class="btn btn-primary" onclick="signup()">Sign Up</button>
```

**After:**
```html
<input id="password" type="password" ...>
<label for="role">Role <span style="color: #dc2626;">*</span></label>
<select id="role" name="role" required ...>
    <option value="">Select your role</option>
    <option value="staff">Staff - Create campaign drafts</option>
    <option value="secretary">Secretary - Review and route drafts</option>
    <option value="kagawad">Kagawad - Review and recommend approval</option>
    <option value="captain">Captain - Final approval authority</option>
    <option value="partner">Partner - External partner access</option>
    <option value="viewer">Viewer - Read-only access</option>
</select>
<button class="btn btn-primary" onclick="signup()">Sign Up</button>
```

### 9. index.php
**Lines Changed:**
- **639-650:** Added role selection dropdown to signup panel
- **947-970:** Updated `signup()` JavaScript function to include role in API call

## PROOF OF ENFORCEMENT

### Test Cases:

1. **Viewer attempts to create campaign:**
   - **Request:** `POST /api/v1/campaigns` with viewer JWT token
   - **Expected:** `403 Forbidden` with message: "Viewer role is read-only. You cannot create campaigns."
   - **Implementation:** `CampaignController::store()` lines 67-74

2. **Viewer attempts to update campaign:**
   - **Request:** `PUT /api/v1/campaigns/{id}` with viewer JWT token
   - **Expected:** `403 Forbidden` with message: "Viewer role is read-only. You cannot update campaigns."
   - **Implementation:** `CampaignController::update()` lines 240-245

3. **Staff attempts to approve campaign (bypass workflow):**
   - **Request:** `PUT /api/v1/campaigns/{id}` with status="approved" and staff JWT token
   - **Expected:** `403 Forbidden` with message: "Staff can only create and edit drafts. Status changes require review."
   - **Implementation:** `CampaignController::update()` lines 314-320

4. **Secretary attempts to finalize approval:**
   - **Request:** `PUT /api/v1/campaigns/{id}` with status="approved" and secretary JWT token (from pending_review)
   - **Expected:** `403 Forbidden` with message: "Secretary can only mark drafts as Pending Review."
   - **Implementation:** `CampaignController::update()` lines 323-331

5. **Kagawad attempts to finalize approval:**
   - **Request:** `PUT /api/v1/campaigns/{id}` with status="approved" and kagawad JWT token (from pending_review)
   - **Expected:** `403 Forbidden` with message: "Kagawad can only recommend campaigns for approval (Pending Review → For Approval)."
   - **Implementation:** `CampaignController::update()` lines 334-342

6. **Captain approves campaign:**
   - **Request:** `PUT /api/v1/campaigns/{id}` with status="approved" and captain JWT token (from for_approval)
   - **Expected:** `200 OK` - Campaign status updated to "approved"
   - **Implementation:** `CampaignController::update()` lines 345-355

7. **Signup without role:**
   - **Request:** `POST /api/v1/auth/register` with `{name, email, password}` (no role)
   - **Expected:** `422 Unprocessable Entity` with message: "Role selection is required. Please select your role: staff, secretary, kagawad, captain, partner, or viewer."
   - **Implementation:** `AuthController::register()` lines 140-144

8. **Signup with invalid role:**
   - **Request:** `POST /api/v1/auth/register` with `{name, email, password, role: "invalid"}`
   - **Expected:** `422 Unprocessable Entity` with message: "Invalid role. Allowed roles: staff, secretary, kagawad, captain, partner, viewer."
   - **Implementation:** `AuthController::register()` lines 146-151

## ROLE PERMISSIONS SUMMARY

| Role | Create Campaigns | Update Campaigns | Approve Campaigns | Create Segments | Create Events | Create Surveys | Create Partners |
|------|------------------|------------------|-------------------|-----------------|---------------|----------------|-----------------|
| **Admin** | ✅ | ✅ | ✅ (override) | ✅ | ✅ | ✅ | ✅ |
| **Captain** | ✅ | ✅ | ✅ (final authority) | ✅ | ✅ | ✅ | ✅ |
| **Kagawad** | ✅ | ✅ | ❌ (can recommend only) | ✅ | ✅ | ✅ | ✅ |
| **Secretary** | ✅ | ✅ | ❌ (can route only) | ✅ | ✅ | ✅ | ✅ |
| **Staff** | ✅ | ✅ | ❌ (drafts only) | ✅ | ✅ | ✅ | ✅ |
| **Partner** | ❌ | ❌ | ❌ | ❌ | ❌ | ❌ | ❌ (can engage) |
| **Viewer** | ❌ | ❌ | ❌ | ❌ | ❌ | ❌ | ❌ |

## VERIFICATION STEPS

1. **Test Viewer Read-Only:**
   ```bash
   # Login as viewer
   curl -X POST http://localhost/api/v1/auth/login \
     -H "Content-Type: application/json" \
     -d '{"email":"viewer@barangay1.qc.gov.ph","password":"pass123"}'
   # Save token, then:
   curl -X POST http://localhost/api/v1/campaigns \
     -H "Authorization: Bearer {token}" \
     -H "Content-Type: application/json" \
     -d '{"title":"Test Campaign"}'
   # Expected: 403 Forbidden
   ```

2. **Test Staff Cannot Approve:**
   ```bash
   # Login as staff, create draft, then try to approve
   # Expected: 403 Forbidden with workflow message
   ```

3. **Test Signup Requires Role:**
   ```bash
   curl -X POST http://localhost/api/v1/auth/register \
     -H "Content-Type: application/json" \
     -d '{"name":"Test User","email":"test@example.com","password":"test123"}'
   # Expected: 422 Unprocessable Entity - Role required
   ```

4. **Test Captain Can Approve:**
   ```bash
   # Login as captain, approve campaign in "for_approval" status
   # Expected: 200 OK - Campaign approved
   ```

## NOTES

- All enforcement happens at the **backend API level**, not just UI hiding
- Viewer role is **strictly read-only** - all write operations return 403
- Signup **requires explicit role selection** - no auto-assignment
- Workflow enforcement (staff → secretary → kagawad → captain) is already implemented in `CampaignController::update()`
- All controllers now check authentication before allowing any operation
- Role names are case-insensitive (normalized to lowercase for comparison)

