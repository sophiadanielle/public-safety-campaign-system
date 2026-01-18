# Complete RBAC Backend Enforcement Audit

## AUDIT RESULT

### Where roles are stored:
- **Table:** `campaign_department_users`
- **Column:** `role_id` (INT UNSIGNED, foreign key to `campaign_department_roles.id`)
- **Role names:** Stored in `campaign_department_roles.name`

### How roles are loaded during login:
1. User logs in → `AuthController::login()` queries `campaign_department_users` table
2. Returns `role_id` in JWT token payload
3. `JWTMiddleware::authenticate()` validates token and queries database for full user record with `role_id`
4. User array passed to controllers contains `role_id`

### Current Enforcement Status:

#### ✅ HAS Enforcement:
- `CampaignController::store()` - Lines 67-79: Checks role before creating campaigns
- `CampaignController::update()` - Lines 235-245, 267-374: Has role checks and workflow enforcement
- `ContentController::store()` - Lines 254-268: Checks role before creating content
- `ContentController::update()` - Lines 584-598: Checks role before updating content
- `ContentController::updateApproval()` - Lines 689-703: Checks role (admin only)
- `AutoMLController::startTraining()` - Line 130: Admin only
- `AutoMLController::deployModel()` - Line 202: Admin only

#### ❌ MISSING Enforcement:
- `CampaignController::index()` - NO role check (anyone can view all campaigns)
- `CampaignController::show()` - NO role check (anyone can view any campaign)
- `SegmentController::store()` - NO role check (anyone can create segments)
- `SegmentController::update()` - NO role check (anyone can update segments)
- `SegmentController::delete()` - NO role check (anyone can delete segments)
- `EventController::store()` - NO role check (anyone can create events)
- `EventController::update()` - NO role check (anyone can update events)
- `SurveyController::store()` - NO role check (only checks if user exists)
- `SurveyController::update()` - NO role check
- `PartnerController::store()` - NO role check (anyone can create partners)
- `PartnerController::engage()` - NO role check (anyone can engage partners)
- `DashboardController::index()` - NO role check
- `ImpactController` - NO role checks
- `MessageController` - NO role checks
- `NotificationController` - NO role checks

### Signup Process:
- **Current:** Auto-assigns "staff" role (line 155-174 in AuthController.php)
- **Problem:** No role selection, user cannot choose their role
- **Required:** Must include role selection dropdown

## CRITICAL ISSUES

1. **Viewer role can create/modify data** - No enforcement on write operations
2. **Staff can approve campaigns** - Workflow enforcement exists but initial role check is wrong
3. **No read-only enforcement** - Viewer can access all modules
4. **Signup auto-assigns role** - No user selection

