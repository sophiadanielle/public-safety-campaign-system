# RBAC System Audit Report

## AUDIT RESULT

### Existing auth system: **YES** (but incomplete)

### Existing roles found:
1. **role_id 1**: "Barangay Administrator" (admin-equivalent)
2. **role_id 2**: "Barangay Staff" (staff-equivalent)  
3. **role_id 3**: "School Partner" (viewer-like, limited permissions)
4. **role_id 4**: "NGO Partner" (viewer-like, limited permissions)

**Note**: Additional roles exist in migration files (system_admin, barangay_admin, etc.) but the base seed uses the above 4 roles.

### Actual enforcement present: **PARTIAL**

#### Evidence:

**✅ What EXISTS:**
1. **RoleMiddleware class** (`src/Middleware/RoleMiddleware.php`)
   - `requireRole()` method exists
   - `requirePermission()` method exists
   - `getUserRole()` method exists

2. **JWT Authentication** (`src/Middleware/JWTMiddleware.php`)
   - All API routes require JWT token
   - User data includes `role_id` in JWT

3. **Partial Controller Enforcement:**
   - `AutoMLController::startTraining()` - Line 130: Requires admin role
   - `AutoMLController::deployModel()` - Line 202: Requires admin role

**❌ What is MISSING:**
1. **Signup defaults to ADMIN** (`src/Controllers/AuthController.php:153`)
   - New users get `role_id = 1` (Barangay Administrator)
   - **CRITICAL SECURITY ISSUE**

2. **No "viewer" role exists**
   - System has admin/staff/partner roles but no explicit "viewer" role
   - Need to create viewer role or map existing role

3. **Routes don't enforce roles**
   - All routes in `src/Routes/*.php` only use `JWTMiddleware`
   - No role-based restrictions at route level
   - Example: `src/Routes/campaigns.php` - all routes only check JWT, not roles

4. **Controllers don't enforce roles**
   - `CampaignController` - No role checks
   - `ContentController` - No role checks  
   - `EventController` - Only gets role name, doesn't restrict access
   - `SurveyController` - Only gets role name, doesn't restrict access

5. **Frontend has no role checks**
   - `public/dashboard.php` - Only checks for JWT token
   - `public/campaigns.php` - Only checks for JWT token
   - No role-based UI restrictions or redirects

## CRITICAL ISSUES

1. **Signup creates admin users** - Anyone can sign up and get admin privileges
2. **No backend enforcement** - Staff/viewer can access admin-only endpoints via direct API calls
3. **No role separation** - All authenticated users have same access level

## REQUIRED FIXES

1. Create "viewer" role (or use existing lowest-privilege role)
2. Fix signup to default to viewer role
3. Add role enforcement to critical routes/controllers
4. Ensure admin/staff/viewer separation

