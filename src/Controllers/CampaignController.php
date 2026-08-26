<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Middleware\RoleMiddleware;
use App\Services\AutoMLService;
use PDO;
use RuntimeException;

class CampaignController
{
    private AutoMLService $autoMLService;
    private static bool $columnsChecked = false;
    private static bool $hasStartTime = true;
    private static bool $hasEndTime = true;

    public function __construct(
        private PDO $pdo,
        private string $jwtSecret,
        private string $jwtIssuer,
        private string $jwtAudience,
        private int $jwtExpirySeconds
    ) {
        $this->autoMLService = new AutoMLService($pdo);
        $this->ensureBudgetWorkflowSchema();
    }

    private function ensureBudgetWorkflowSchema(): void
    {
        try {
            $this->pdo->exec("ALTER TABLE campaign_budgets ADD COLUMN budget_destination VARCHAR(255) NULL AFTER related_action");
        } catch (\PDOException $e) {
            // Already migrated.
        }
        try {
            $this->pdo->exec("
                CREATE TABLE IF NOT EXISTS campaign_budget_workflows (
                    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
                    campaign_id INT UNSIGNED NOT NULL,
                    planning_status ENUM('draft','approved','finalized') NOT NULL DEFAULT 'draft',
                    review_status ENUM('none','pending','approved','rejected') NOT NULL DEFAULT 'none',
                    rejection_reason TEXT NULL,
                    pre_edit_snapshot LONGTEXT NULL,
                    approved_by INT UNSIGNED NULL,
                    approved_at DATETIME NULL,
                    finalized_by INT UNSIGNED NULL,
                    finalized_at DATETIME NULL,
                    reviewed_by INT UNSIGNED NULL,
                    reviewed_at DATETIME NULL,
                    created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
                    updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                    PRIMARY KEY (id),
                    UNIQUE KEY uq_campaign_budget_workflow_campaign (campaign_id),
                    KEY idx_budget_workflow_planning (planning_status),
                    KEY idx_budget_workflow_review (review_status),
                    CONSTRAINT fk_budget_workflow_campaign FOREIGN KEY (campaign_id)
                        REFERENCES campaign_department_campaigns(id) ON DELETE CASCADE ON UPDATE CASCADE
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
            ");
        } catch (\PDOException $e) {
            error_log('CampaignController::ensureBudgetWorkflowSchema - ' . $e->getMessage());
        }
    }
    
    /**
     * Ensure start_time and end_time columns exist - cached to avoid slow INFORMATION_SCHEMA queries
     */
    private function ensureTimeColumns(): void
    {
        // Skip if already checked in this request
        if (self::$columnsChecked) {
            return;
        }
        self::$columnsChecked = true;
        
        try {
            $checkStmt = $this->pdo->query("SELECT COUNT(*) as cnt FROM INFORMATION_SCHEMA.COLUMNS 
                WHERE TABLE_SCHEMA = DATABASE() 
                AND TABLE_NAME = 'campaign_department_campaigns' 
                AND COLUMN_NAME = 'start_time'");
            self::$hasStartTime = $checkStmt->fetch(PDO::FETCH_ASSOC)['cnt'] > 0;
        } catch (\Exception $e) {
            self::$hasStartTime = true; // Assume exists to avoid errors
        }
        
        try {
            $checkStmt = $this->pdo->query("SELECT COUNT(*) as cnt FROM INFORMATION_SCHEMA.COLUMNS 
                WHERE TABLE_SCHEMA = DATABASE() 
                AND TABLE_NAME = 'campaign_department_campaigns' 
                AND COLUMN_NAME = 'end_time'");
            self::$hasEndTime = $checkStmt->fetch(PDO::FETCH_ASSOC)['cnt'] > 0;
        } catch (\Exception $e) {
            self::$hasEndTime = true; // Assume exists to avoid errors
        }
        
        // Auto-apply migration if columns don't exist
        if (!self::$hasStartTime) {
            try {
                $this->pdo->exec("ALTER TABLE `campaign_department_campaigns` ADD COLUMN start_time TIME NULL AFTER start_date");
                self::$hasStartTime = true;
                error_log('CampaignController: Auto-applied migration - added start_time column');
            } catch (\Exception $e) {
                error_log('CampaignController: Failed to add start_time column: ' . $e->getMessage());
            }
        }
        
        if (!self::$hasEndTime) {
            try {
                $this->pdo->exec("ALTER TABLE `campaign_department_campaigns` ADD COLUMN end_time TIME NULL AFTER end_date");
                self::$hasEndTime = true;
                error_log('CampaignController: Auto-applied migration - added end_time column');
            } catch (\Exception $e) {
                error_log('CampaignController: Failed to add end_time column: ' . $e->getMessage());
            }
        }
    }

    public function index(?array $user, array $params = []): array
    {
        // RBAC: All authenticated users can view campaigns (read access)
        // Viewer role is allowed for read operations
        if (!$user) {
            http_response_code(401);
            return ['error' => 'Authentication required'];
        }
        
        try {
            $sql = '
                SELECT id, title, description, category, geographic_scope, status, 
                       start_date, end_date, draft_schedule_datetime, ai_recommended_datetime, 
                       final_schedule_datetime, owner_id, created_at, objectives, location, 
                       assigned_staff, barangay_target_zones, budget, staff_count, materials_json 
                FROM campaign_department_campaigns 
                ORDER BY created_at DESC
            ';
            error_log('CRITICAL: CampaignController::index - Executing SQL: ' . $sql);
            $stmt = $this->pdo->query($sql);
            
            if ($stmt === false) {
                throw new \RuntimeException('Failed to execute query');
            }
            
            $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Ensure all data is properly formatted for JSON
            // Note: null values are fine for JSON, no conversion needed
            // Just ensure the data structure is valid
            
            return ['data' => $data];
        } catch (\PDOException $e) {
            error_log('CRITICAL ERROR: CampaignController::index - Database error: ' . $e->getMessage());
            error_log('CRITICAL ERROR: CampaignController::index - SQL State: ' . $e->getCode());
            error_log('CRITICAL ERROR: CampaignController::index - Error Info: ' . json_encode($stmt->errorInfo() ?? []));
            http_response_code(500);
            return ['error' => 'Database error: ' . $e->getMessage()];
        } catch (\Exception $e) {
            error_log('CampaignController::index - Error: ' . $e->getMessage());
            error_log('CampaignController::index - Stack trace: ' . $e->getTraceAsString());
            http_response_code(500);
            return ['error' => 'Failed to load campaigns: ' . $e->getMessage()];
        }
    }

    public function store(?array $user, array $params = []): array
    {
        // RBAC: Only authorized LGU roles can create campaigns (viewer cannot)
        if (!$user) {
            http_response_code(401);
            return ['error' => 'Authentication required'];
        }
        
        try {
            $userRole = RoleMiddleware::getUserRole($user, $this->pdo);
            $userRoleName = $userRole ? strtolower($userRole) : '';
            
            // Viewer is read-only - cannot create anything
            if ($userRoleName === 'viewer') {
                http_response_code(403);
                return ['error' => 'Viewer role is read-only. You cannot create campaigns.'];
            }
            
            // Allowed roles: admin, staff, secretary, kagawad, captain (and legacy roles for compatibility)
            $allowedRoles = ['admin', 'staff', 'secretary', 'kagawad', 'captain', 'barangay administrator', 'barangay staff', 'system_admin', 'barangay_admin', 'campaign_creator'];
            if (!$userRole || !in_array($userRoleName, $allowedRoles, true)) {
                http_response_code(403);
                return ['error' => 'Insufficient permissions. Only authorized LGU personnel can create campaigns.'];
            }
        } catch (\Exception $e) {
            http_response_code(403);
            return ['error' => 'Access denied: ' . $e->getMessage()];
        }
        
        $input = json_decode(file_get_contents('php://input'), true) ?? [];
        
        // Log received input for debugging
        error_log('Campaign creation - Received input: ' . json_encode($input));
        
        // Use ONLY the values from input - no hardcoded defaults except for status
        $title = isset($input['title']) ? trim((string)$input['title']) : '';
        $description = isset($input['description']) ? trim((string)$input['description']) : null;
        $status = isset($input['status']) ? trim((string)$input['status']) : 'draft';
        
        // LGU GOVERNANCE WORKFLOW ENFORCEMENT: Staff can ONLY create Draft campaigns
        // Workflow: Staff creates → Draft, Secretary forwards → Pending, Captain approves → Approved
        // No role can skip steps - Staff cannot set Approved, Secretary cannot set Approved, etc.
        if ($status !== 'draft') {
            $userRoleName = $userRole ? strtolower($userRole) : '';
            $isAdmin = in_array($userRoleName, ['admin', 'barangay administrator', 'system_admin'], true);
            $isStaff = in_array($userRoleName, ['staff', 'barangay staff'], true);
            
            // Staff MUST create Draft campaigns only
            if ($isStaff) {
                http_response_code(403);
                return ['error' => 'Staff can only create campaigns with Draft status. Status changes must follow the approval workflow.'];
            }
            
            // Non-admin non-staff roles also cannot create non-draft campaigns
            if (!$isAdmin) {
                http_response_code(403);
                return ['error' => 'New campaigns must be created as drafts. Only administrators can create campaigns with other statuses.'];
            }
        }
        
        $startDate = isset($input['start_date']) && $input['start_date'] ? trim((string)$input['start_date']) : null;
        $startTime = isset($input['start_time']) && $input['start_time'] ? trim((string)$input['start_time']) : null;
        $endDate = isset($input['end_date']) && $input['end_date'] ? trim((string)$input['end_date']) : null;
        $endTime = isset($input['end_time']) && $input['end_time'] ? trim((string)$input['end_time']) : null;
        $ownerId = $user['id'] ?? null;

        if (!$title) {
            http_response_code(422);
            return ['error' => 'Title is required'];
        }

        // Validate date range: start_date must not be later than end_date
        if ($startDate && $endDate) {
            $startTimestamp = strtotime($startDate);
            $endTimestamp = strtotime($endDate);
            if ($startTimestamp > $endTimestamp) {
                http_response_code(422);
                return ['error' => 'Start date must not be later than end date'];
            }
        }

        $allowedStatus = ['draft','pending','approved','ongoing','completed','scheduled','published','active','archived'];
        if (!in_array($status, $allowedStatus, true)) {
            http_response_code(422);
            return ['error' => 'Invalid status'];
        }

        // Use actual input values - no defaults
        // FIX: Removed hardcoded category validation - category is VARCHAR(100) in database
        // Categories should be flexible to support real-world barangay public safety operations
        // Examples: "General", "Community Preparedness", "Training", "Orientation", etc.
        $category = isset($input['category']) && $input['category'] ? trim((string)$input['category']) : null;
        // Basic validation: ensure category doesn't exceed database column length (VARCHAR(100))
        if ($category && strlen($category) > 100) {
            http_response_code(422);
            return ['error' => 'Category must not exceed 100 characters'];
        }
        $geographicScope = isset($input['geographic_scope']) && $input['geographic_scope'] ? trim((string)$input['geographic_scope']) : null;
        $objectives = isset($input['objectives']) && $input['objectives'] ? trim((string)$input['objectives']) : null;
        $location = isset($input['location']) && $input['location'] ? trim((string)$input['location']) : null;
        $assignedStaff = isset($input['assigned_staff']) && !empty($input['assigned_staff']) ? json_encode($input['assigned_staff']) : null;
        $barangayTargetZones = isset($input['barangay_target_zones']) && !empty($input['barangay_target_zones']) ? json_encode($input['barangay_target_zones']) : null;
        
        // Validate geographic scope is Quezon City only (soft validation - assumes all barangays are QC)
        // In production, validate against a barangay database to ensure they belong to Quezon City
        if ($geographicScope && stripos($geographicScope, 'quezon city') === false && stripos($geographicScope, 'qc') === false) {
            // Allow barangay names but note they should be validated against QC barangay list
        }
        // Use actual input values - convert to null if empty/zero
        $budget = isset($input['budget']) && $input['budget'] !== null && $input['budget'] !== '' ? (float) $input['budget'] : null;
        $staffCount = isset($input['staff_count']) && $input['staff_count'] !== null && $input['staff_count'] !== '' ? (int) $input['staff_count'] : null;
        $materialsJson = isset($input['materials_json']) && !empty($input['materials_json']) ? json_encode($input['materials_json']) : null;
        // NOTE: draft_schedule_datetime is NOT set during initial creation per sequence diagram
        // Schedule must be set via AI recommendation flow (Steps 3-9) - user requests prediction, then confirms
        // Ignore draft_schedule_datetime if provided during creation to enforce proper flow
        $draftSchedule = null;

        // FIX: Auto-apply migration 032 if start_time and end_time columns don't exist
        // Use cached check to avoid slow INFORMATION_SCHEMA queries on every request
        $this->ensureTimeColumns();
        
        $stmt = $this->pdo->prepare('
            INSERT INTO campaign_department_campaigns (
                title, description, category, geographic_scope, status, 
                start_date, start_time, end_date, end_time, draft_schedule_datetime, owner_id, 
                objectives, location, assigned_staff, barangay_target_zones, 
                budget, staff_count, materials_json
            ) VALUES (
                :title, :description, :category, :geographic_scope, :status,
                :start_date, :start_time, :end_date, :end_time, :draft_schedule_datetime, :owner_id,
                :objectives, :location, :assigned_staff, :barangay_target_zones,
                :budget, :staff_count, :materials_json
            )
        ');
        $stmt->execute([
            'title' => $title,
            'description' => $description ?: null,
            'category' => $category ?: null,
            'geographic_scope' => $geographicScope ?: null,
            'status' => $status,
            'start_date' => $startDate ?: null,
            'start_time' => $startTime ?: null,
            'end_date' => $endDate ?: null,
            'end_time' => $endTime ?: null,
            'draft_schedule_datetime' => null, // Schedule must be set via AI recommendation flow (Steps 3-9)
            'owner_id' => $ownerId,
            'objectives' => $objectives ?: null,
            'location' => $location ?: null,
            'assigned_staff' => $assignedStaff,
            'barangay_target_zones' => $barangayTargetZones,
            'budget' => $budget,
            'staff_count' => $staffCount,
            'materials_json' => $materialsJson,
        ]);

        $campaignId = (int) $this->pdo->lastInsertId();

        // Log integrations to internal and external subsystems
        $this->logCampaignIntegrations($campaignId, [
            'title' => $title,
            'description' => $description,
            'category' => $category,
            'geographic_scope' => $geographicScope,
            'status' => $status,
            'start_date' => $startDate,
            'end_date' => $endDate,
            'draft_schedule_datetime' => $draftSchedule,
            'objectives' => $objectives,
            'location' => $location,
            'assigned_staff' => $assignedStaff ? json_decode($assignedStaff, true) : [],
            'barangay_target_zones' => $barangayTargetZones ? json_decode($barangayTargetZones, true) : [],
            'budget' => $budget,
            'staff_count' => $staffCount,
            'materials_json' => $materialsJson ? json_decode($materialsJson, true) : [],
        ]);

        // Log audit entry
        $this->logAudit($ownerId, 'campaign', 'create', $campaignId, ['title' => $title, 'status' => $status]);

        // Create notification for campaign creator
        try {
            \App\Controllers\NotificationController::create(
                $this->pdo,
                $ownerId,
                'campaign',
                'Campaign Created',
                "Campaign '{$title}' has been created successfully.",
                '/public/campaigns.php#list-section',
                'fas fa-bullhorn'
            );
        } catch (\Exception $e) {
            error_log('Failed to create notification: ' . $e->getMessage());
        }

        return ['id' => $campaignId, 'message' => 'Campaign created'];
    }

    public function show(?array $user, array $params = []): array
    {
        // RBAC: All authenticated users can view campaigns (read access)
        if (!$user) {
            http_response_code(401);
            return ['error' => 'Authentication required'];
        }
        
        $id = (int) ($params['id'] ?? 0);
        $campaign = $this->findCampaign($id);
        return ['data' => $campaign];
    }

    public function update(?array $user, array $params = []): array
    {
        // RBAC: Only authorized LGU roles can update campaigns (viewer cannot)
        if (!$user) {
            http_response_code(401);
            return ['error' => 'Authentication required'];
        }
        
        // FIX: Ensure start_time and end_time columns exist (same auto-migration as store method)
        // Use cached check to avoid slow INFORMATION_SCHEMA queries on every request
        $this->ensureTimeColumns();
        
        try {
            $userRole = RoleMiddleware::getUserRole($user, $this->pdo);
            $userRoleName = $userRole ? strtolower($userRole) : '';
            
            // Viewer is read-only - cannot update anything
            if ($userRoleName === 'viewer') {
                http_response_code(403);
                return ['error' => 'Viewer role is read-only. You cannot modify campaigns.'];
            }
            
            // Allowed roles: admin, staff, secretary, kagawad, captain (and legacy roles for compatibility)
            $allowedRoles = ['admin', 'staff', 'secretary', 'kagawad', 'captain', 'barangay administrator', 'barangay staff', 'system_admin', 'barangay_admin', 'campaign_creator'];
            if (!$userRole || !in_array($userRoleName, $allowedRoles, true)) {
                http_response_code(403);
                return ['error' => 'Insufficient permissions. Only authorized LGU personnel can update campaigns.'];
            }
        } catch (\Exception $e) {
            http_response_code(403);
            return ['error' => 'Access denied: ' . $e->getMessage()];
        }
        
        $id = (int) ($params['id'] ?? 0);
        $currentCampaign = $this->findCampaign($id); // ensure exists and get current state

        $input = json_decode(file_get_contents('php://input'), true) ?? [];
        $fields = [];
        $bindings = ['id' => $id];

        $allowedStatus = ['draft','pending','approved','ongoing','completed','scheduled','published','active','archived'];

        if (isset($input['title'])) {
            $fields[] = 'title = :title';
            $bindings['title'] = trim($input['title']);
        }
        if (isset($input['description'])) {
            $fields[] = 'description = :description';
            $bindings['description'] = trim((string) $input['description']) ?: null;
        }
        // LGU GOVERNANCE WORKFLOW ENFORCEMENT (STRICT)
        // Required workflow: Staff → Draft | Secretary → Pending | Captain → Approved
        // Staff CANNOT set Approved | Secretary CANNOT set Approved | Kagawad CANNOT change status
        // No skipping steps: Draft → Approved is forbidden
        if (isset($input['status'])) {
            $newStatus = trim((string)$input['status']);
            if (!in_array($newStatus, $allowedStatus, true)) {
                http_response_code(422);
                return ['error' => 'Invalid status'];
            }
            
            // Get current campaign status from already-fetched campaign
            $currentStatus = $currentCampaign['status'] ?? 'draft';
            
            // Get user's role
            $userRoleName = $userRole ? strtolower($userRole) : '';
            $isAdmin = in_array($userRoleName, ['admin', 'barangay administrator', 'system_admin'], true);
            $isCaptain = in_array($userRoleName, ['captain'], true);
            $isKagawad = in_array($userRoleName, ['kagawad'], true);
            $isSecretary = in_array($userRoleName, ['secretary'], true);
            $isStaff = in_array($userRoleName, ['staff', 'barangay staff'], true);
            
            // Normalize status (map 'pending_review' to 'pending' for consistency)
            $normalizedCurrent = strtolower($currentStatus);
            $normalizedNew = strtolower($newStatus);
            if ($normalizedCurrent === 'pending_review') $normalizedCurrent = 'pending';
            if ($normalizedNew === 'pending_review') $normalizedNew = 'pending';
            
            // Role-based status change restrictions (STRICT ENFORCEMENT)
            $canChangeStatus = false;
            $errorMessage = '';
            
            // Staff: Can submit Draft → Pending (to Secretary), or edit draft content
            if ($isStaff) {
                if ($normalizedCurrent === 'draft' && $normalizedNew === 'pending') {
                    $canChangeStatus = true; // Staff can submit to Secretary
                } elseif ($normalizedCurrent === 'draft' && $normalizedNew === 'draft') {
                    $canChangeStatus = true; // Can update draft content, but status remains draft
                } else {
                    $canChangeStatus = false;
                    $errorMessage = 'Staff can only create/edit Draft campaigns or submit Draft → Pending to Secretary.';
                }
            }
            // Secretary: Can forward Draft → Pending, return Pending → Draft for revision, CANNOT approve
            elseif ($isSecretary) {
                if ($normalizedCurrent === 'draft' && $normalizedNew === 'pending') {
                    $canChangeStatus = true; // Secretary forwards for approval
                } elseif ($normalizedCurrent === 'pending' && $normalizedNew === 'draft') {
                    $canChangeStatus = true; // Secretary can return for revision
                } elseif ($normalizedCurrent === $normalizedNew && $normalizedCurrent === 'pending') {
                    $canChangeStatus = true; // Can update pending campaign content
                } elseif ($normalizedCurrent === $normalizedNew && $normalizedCurrent === 'draft') {
                    $canChangeStatus = true; // Can update draft campaign content
                } else {
                    $canChangeStatus = false;
                    $errorMessage = 'Secretary can forward Draft → Pending, return Pending → Draft for revision, or update content. Secretary cannot approve campaigns.';
                }
            }
            // Kagawad: CANNOT change status - can only review/recommend
            elseif ($isKagawad) {
                if ($normalizedCurrent === $normalizedNew) {
                    $canChangeStatus = true; // Can update same status content, but cannot change status
                } else {
                    $canChangeStatus = false;
                    $errorMessage = 'Kagawad cannot change campaign status. Kagawad can only review and add recommendations.';
                }
            }
            // Captain: Can ONLY approve Pending → Approved (final authority)
            elseif ($isCaptain) {
                if ($normalizedCurrent === 'pending' && $normalizedNew === 'approved') {
                    $canChangeStatus = true; // Captain approves pending campaigns
                } elseif ($normalizedCurrent === 'approved' && in_array($normalizedNew, ['ongoing', 'completed', 'scheduled'], true)) {
                    $canChangeStatus = true; // Can manage approved campaigns
                } elseif ($normalizedCurrent === 'scheduled' && in_array($normalizedNew, ['ongoing', 'completed'], true)) {
                    $canChangeStatus = true; // Can close scheduled campaigns
                } elseif ($normalizedCurrent === $normalizedNew) {
                    $canChangeStatus = true; // Can update same status
                } else {
                    $canChangeStatus = false;
                    $errorMessage = 'Captain can only approve campaigns from Pending to Approved status. Current status: ' . $currentStatus;
                }
            }
            // Admin: Can override (with audit logging)
            elseif ($isAdmin) {
                $canChangeStatus = true;
                // Log admin override
                try {
                    if (method_exists($this, 'logAudit')) {
                        $this->logAudit($user['id'] ?? null, 'campaign', 'status_override', $id, [
                            'from' => $currentStatus,
                            'to' => $newStatus,
                            'reason' => 'Admin override - workflow bypass'
                        ]);
                    }
                } catch (\Exception $e) {
                    error_log('CampaignController::update - Failed to log admin override: ' . $e->getMessage());
                }
            }
            // Unauthorized role
            else {
                $canChangeStatus = false;
                $errorMessage = 'Insufficient permissions to change campaign status.';
            }
            
            // Block unauthorized status changes
            if (!$canChangeStatus) {
                http_response_code(403);
                return ['error' => $errorMessage ?: 'You do not have permission to change campaign status from ' . $currentStatus . ' to ' . $newStatus];
            }
            
            $fields[] = 'status = :status';
            $bindings['status'] = $newStatus;
        }
        if (isset($input['start_date'])) {
            $fields[] = 'start_date = :start_date';
            $bindings['start_date'] = $input['start_date'] ?: null;
        }
        if (isset($input['start_time'])) {
            $fields[] = 'start_time = :start_time';
            $bindings['start_time'] = $input['start_time'] ?: null;
        }
        if (isset($input['end_date'])) {
            $fields[] = 'end_date = :end_date';
            $bindings['end_date'] = $input['end_date'] ?: null;
        }
        if (isset($input['end_time'])) {
            $fields[] = 'end_time = :end_time';
            $bindings['end_time'] = $input['end_time'] ?: null;
        }
        
        // Validate date/time range if both dates are provided
        if (isset($input['start_date']) && isset($input['end_date']) && $input['start_date'] && $input['end_date']) {
            $startTimestamp = strtotime($input['start_date']);
            $endTimestamp = strtotime($input['end_date']);
            if ($startTimestamp > $endTimestamp) {
                http_response_code(422);
                return ['error' => 'Start date must not be later than end date'];
            }
            // If same date, validate time range
            if ($startTimestamp === $endTimestamp && isset($input['start_time']) && isset($input['end_time']) && $input['start_time'] && $input['end_time']) {
                $startDateTime = strtotime($input['start_date'] . ' ' . $input['start_time']);
                $endDateTime = strtotime($input['end_date'] . ' ' . $input['end_time']);
                if ($startDateTime >= $endDateTime) {
                    http_response_code(422);
                    return ['error' => 'Start time must be earlier than end time when dates are the same'];
                }
            }
        }
        if (isset($input['objectives'])) {
            $fields[] = 'objectives = :objectives';
            $bindings['objectives'] = trim((string) $input['objectives']) ?: null;
        }
        if (isset($input['location'])) {
            $fields[] = 'location = :location';
            $bindings['location'] = trim((string) $input['location']) ?: null;
        }
        if (isset($input['assigned_staff'])) {
            $fields[] = 'assigned_staff = :assigned_staff';
            $bindings['assigned_staff'] = json_encode($input['assigned_staff']);
        }
        if (isset($input['barangay_target_zones'])) {
            $fields[] = 'barangay_target_zones = :barangay_target_zones';
            $bindings['barangay_target_zones'] = json_encode($input['barangay_target_zones']);
        }
        if (isset($input['budget'])) {
            $fields[] = 'budget = :budget';
            $bindings['budget'] = (float) $input['budget'];
        }
        if (isset($input['staff_count'])) {
            $fields[] = 'staff_count = :staff_count';
            $bindings['staff_count'] = (int) $input['staff_count'];
        }
        if (isset($input['materials_json'])) {
            $fields[] = 'materials_json = :materials_json';
            $bindings['materials_json'] = json_encode($input['materials_json']);
        }
        if (isset($input['category'])) {
            // FIX: Removed hardcoded category validation - category is VARCHAR(100) in database
            // Categories should be flexible to support real-world barangay public safety operations
            // Examples: "General", "Community Preparedness", "Training", "Orientation", etc.
            $category = trim($input['category']);
            // Basic validation: ensure category doesn't exceed database column length (VARCHAR(100))
            if ($category && strlen($category) > 100) {
                http_response_code(422);
                return ['error' => 'Category must not exceed 100 characters'];
            }
            $fields[] = 'category = :category';
            $bindings['category'] = $category ?: null;
        }
        if (isset($input['geographic_scope'])) {
            $fields[] = 'geographic_scope = :geographic_scope';
            $bindings['geographic_scope'] = trim($input['geographic_scope']) ?: null;
        }
        if (isset($input['draft_schedule_datetime'])) {
            $fields[] = 'draft_schedule_datetime = :draft_schedule_datetime';
            $bindings['draft_schedule_datetime'] = $input['draft_schedule_datetime'] ?: null;
        }

        if (empty($fields)) {
            return ['message' => 'Nothing to update'];
        }

        $sql = 'UPDATE campaign_department_campaigns SET ' . implode(', ', $fields) . ', updated_at = CURRENT_TIMESTAMP WHERE id = :id';
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($bindings);

        // Log audit entry
        $this->logAudit($user['id'] ?? null, 'campaign', 'update', $id, ['fields_updated' => array_keys($input)]);

        return ['message' => 'Campaign updated', 'id' => $id];
    }

    /**
     * List content items linked to a campaign
     */
    public function listContent(?array $user, array $params = []): array
    {
        $campaignId = (int) ($params['id'] ?? 0);
        $this->findCampaign($campaignId);

        $stmt = $this->pdo->prepare('
            SELECT ci.id, ci.title, ci.body, ci.content_type, ci.created_at
            FROM `campaign_department_content_items` ci
            WHERE ci.campaign_id = :cid
            ORDER BY ci.created_at DESC
        ');
        $stmt->execute(['cid' => $campaignId]);
        return ['data' => $stmt->fetchAll()];
    }

    public function addSchedule(?array $user, array $params = []): array
    {
        // RBAC: Only authorized LGU roles can add schedules (viewer cannot)
        if (!$user) {
            http_response_code(401);
            return ['error' => 'Authentication required'];
        }
        
        try {
            $userRole = RoleMiddleware::getUserRole($user, $this->pdo);
            $userRoleName = $userRole ? strtolower($userRole) : '';
            
            if ($userRoleName === 'viewer') {
                http_response_code(403);
                return ['error' => 'Viewer role is read-only. You cannot add schedules.'];
            }
            
            $allowedRoles = ['admin', 'staff', 'secretary', 'kagawad', 'captain', 'barangay administrator', 'barangay staff', 'system_admin', 'barangay_admin', 'campaign_creator'];
            if (!$userRole || !in_array($userRoleName, $allowedRoles, true)) {
                http_response_code(403);
                return ['error' => 'Insufficient permissions. Only authorized LGU personnel can add schedules.'];
            }
        } catch (\Exception $e) {
            http_response_code(403);
            return ['error' => 'Access denied: ' . $e->getMessage()];
        }
        
        $campaignId = (int) ($params['id'] ?? 0);
        $this->findCampaign($campaignId);

        $input = json_decode(file_get_contents('php://input'), true) ?? [];
        $scheduledAt = $input['scheduled_at'] ?? null;
        $channel = trim($input['channel'] ?? '');
        $notes = trim($input['notes'] ?? '');

        if (!$scheduledAt || !$channel) {
            http_response_code(422);
            return ['error' => 'scheduled_at and channel are required'];
        }

        // Insert schedule with status 'pending'
        $stmt = $this->pdo->prepare('INSERT INTO `campaign_department_campaign_schedules` (campaign_id, scheduled_at, channel, notes, status) VALUES (:campaign_id, :scheduled_at, :channel, :notes, :status)');
        $stmt->execute([
            'campaign_id' => $campaignId,
            'scheduled_at' => $scheduledAt,
            'channel' => $channel,
            'notes' => $notes ?: null,
            'status' => 'pending',
        ]);

        $scheduleId = (int) $this->pdo->lastInsertId();
        
        // Log audit entry
        $this->logAudit($user['id'] ?? null, 'campaign_schedule', 'create', $scheduleId, ['campaign_id' => $campaignId, 'scheduled_at' => $scheduledAt]);

        return ['id' => $scheduleId, 'message' => 'Schedule created'];
    }

    public function listSchedules(?array $user, array $params = []): array
    {
        $campaignId = (int) ($params['id'] ?? 0);
        $this->findCampaign($campaignId);

        // Get schedules with status and last posting attempt from notification_logs
        $stmt = $this->pdo->prepare('
            SELECT 
                cs.id, 
                cs.scheduled_at, 
                cs.channel, 
                cs.notes, 
                cs.status,
                cs.created_at,
                MAX(nl.created_at) as last_posting_attempt
            FROM `campaign_department_campaign_schedules` cs
            LEFT JOIN `campaign_department_notification_logs` nl ON nl.campaign_id = cs.campaign_id 
                AND nl.channel = cs.channel 
                AND DATE(nl.created_at) = DATE(cs.scheduled_at)
            WHERE cs.campaign_id = :campaign_id 
            GROUP BY cs.id, cs.scheduled_at, cs.channel, cs.notes, cs.status, cs.created_at
            ORDER BY cs.scheduled_at ASC
        ');
        $stmt->execute(['campaign_id' => $campaignId]);
        return ['data' => $stmt->fetchAll()];
    }

    public function sendSchedule(?array $user, array $params = []): array
    {
        // RBAC: Only authorized LGU roles can send schedules (viewer cannot)
        if (!$user) {
            http_response_code(401);
            return ['error' => 'Authentication required'];
        }
        
        try {
            $userRole = RoleMiddleware::getUserRole($user, $this->pdo);
            $userRoleName = $userRole ? strtolower($userRole) : '';
            
            if ($userRoleName === 'viewer') {
                http_response_code(403);
                return ['error' => 'Viewer role is read-only. You cannot send schedules.'];
            }
            
            $allowedRoles = ['admin', 'staff', 'secretary', 'kagawad', 'captain', 'barangay administrator', 'barangay staff', 'system_admin', 'barangay_admin', 'campaign_creator'];
            if (!$userRole || !in_array($userRoleName, $allowedRoles, true)) {
                http_response_code(403);
                return ['error' => 'Insufficient permissions. Only authorized LGU personnel can send schedules.'];
            }
        } catch (\Exception $e) {
            http_response_code(403);
            return ['error' => 'Access denied: ' . $e->getMessage()];
        }
        
        $campaignId = (int) ($params['id'] ?? 0);
        $scheduleId = (int) ($params['sid'] ?? 0);

        $this->findCampaign($campaignId);
        $schedule = $this->findSchedule($campaignId, $scheduleId);

        try {
            // Simulate sending by inserting a notification_log entry and integration log
            $stmt = $this->pdo->prepare('INSERT INTO `campaign_department_notification_logs` (campaign_id, audience_member_id, channel, status, response_message) VALUES (:campaign_id, NULL, :channel, :status, :response_message)');
            $stmt->execute([
                'campaign_id' => $campaignId,
                'channel' => $schedule['channel'],
                'status' => 'sent',
                'response_message' => 'delivered',
            ]);

            $payload = [
                'campaign_id' => $campaignId,
                'schedule_id' => $scheduleId,
                'channel' => $schedule['channel'],
                'scheduled_at' => $schedule['scheduled_at'],
            ];
            $log = $this->pdo->prepare('INSERT INTO `campaign_department_integration_logs` (source, payload, status) VALUES (:source, :payload, :status)');
            $log->execute([
                'source' => 'notification_dispatch',
                'payload' => json_encode($payload),
                'status' => 'queued',
            ]);

            // Fire outbound webhook if configured
            $webhookUrl = getenv('NOTIFY_WEBHOOK_URL') ?: null;
            $webhookSuccess = true;
            if ($webhookUrl) {
                $webhookSuccess = $this->dispatchWebhook($webhookUrl, $payload);
            }

            // Update schedule status to 'sent' on success, 'failed' on failure
            $scheduleStatus = $webhookSuccess ? 'sent' : 'failed';
            $updateStmt = $this->pdo->prepare('UPDATE `campaign_department_campaign_schedules` SET status = :status WHERE id = :id');
            $updateStmt->execute([
                'id' => $scheduleId,
                'status' => $scheduleStatus,
            ]);

            // Log audit entry
            $this->logAudit($user['id'] ?? null, 'campaign_schedule', 'send', $scheduleId, ['campaign_id' => $campaignId, 'status' => $scheduleStatus]);

            return [
                'message' => $scheduleStatus === 'sent' ? 'Schedule sent successfully' : 'Schedule sent but webhook failed',
                'status' => $scheduleStatus,
                'notification_log_id' => (int) $this->pdo->lastInsertId(),
                'integration_log_id' => (int) $this->pdo->lastInsertId(),
            ];
        } catch (\Exception $e) {
            // Update schedule status to 'failed' on error
            $updateStmt = $this->pdo->prepare('UPDATE `campaign_department_campaign_schedules` SET status = :status WHERE id = :id');
            $updateStmt->execute([
                'id' => $scheduleId,
                'status' => 'failed',
            ]);

            http_response_code(500);
            return ['error' => 'Failed to send schedule: ' . $e->getMessage(), 'status' => 'failed'];
        }
    }

    public function listSegments(?array $user, array $params = []): array
    {
        $campaignId = (int) ($params['id'] ?? 0);
        $this->findCampaign($campaignId);

        $stmt = $this->pdo->prepare('
            SELECT s.id, s.name, s.criteria, s.created_at
            FROM `campaign_department_campaign_audience` ca
            INNER JOIN `campaign_department_audience_segments` s ON s.id = ca.segment_id
            WHERE ca.campaign_id = :cid
            ORDER BY s.created_at DESC
        ');
        $stmt->execute(['cid' => $campaignId]);
        return ['data' => $stmt->fetchAll()];
    }

    public function syncSegments(?array $user, array $params = []): array
    {
        // RBAC: Only authorized LGU roles can sync segments (viewer cannot)
        if (!$user) {
            http_response_code(401);
            return ['error' => 'Authentication required'];
        }
        
        try {
            $userRole = RoleMiddleware::getUserRole($user, $this->pdo);
            $userRoleName = $userRole ? strtolower($userRole) : '';
            
            if ($userRoleName === 'viewer') {
                http_response_code(403);
                return ['error' => 'Viewer role is read-only. You cannot sync segments.'];
            }
            
            $allowedRoles = ['admin', 'staff', 'secretary', 'kagawad', 'captain', 'barangay administrator', 'barangay staff', 'system_admin', 'barangay_admin', 'campaign_creator'];
            if (!$userRole || !in_array($userRoleName, $allowedRoles, true)) {
                http_response_code(403);
                return ['error' => 'Insufficient permissions. Only authorized LGU personnel can sync segments.'];
            }
        } catch (\Exception $e) {
            http_response_code(403);
            return ['error' => 'Access denied: ' . $e->getMessage()];
        }
        
        $campaignId = (int) ($params['id'] ?? 0);
        $this->findCampaign($campaignId);

        $input = json_decode(file_get_contents('php://input'), true) ?? [];
        $segments = $input['segment_ids'] ?? null;
        if (!is_array($segments)) {
            http_response_code(422);
            return ['error' => 'segment_ids array is required'];
        }
        $segmentIds = array_values(array_unique(array_map('intval', $segments)));
        if (empty($segmentIds)) {
            http_response_code(422);
            return ['error' => 'At least one segment id is required'];
        }

        $this->assertSegments($segmentIds);

        $this->pdo->beginTransaction();
        try {
            $del = $this->pdo->prepare('DELETE FROM `campaign_department_campaign_audience` WHERE campaign_id = :cid');
            $del->execute(['cid' => $campaignId]);

            $ins = $this->pdo->prepare('INSERT INTO `campaign_department_campaign_audience` (campaign_id, segment_id) VALUES (:cid, :sid)');
            foreach ($segmentIds as $sid) {
                $ins->execute(['cid' => $campaignId, 'sid' => $sid]);
            }
            $this->pdo->commit();
        } catch (\Throwable $e) {
            $this->pdo->rollBack();
            http_response_code(500);
            return ['error' => 'Failed to sync segments'];
        }

        return ['message' => 'Segments synced', 'count' => count($segmentIds)];
    }

    /**
     * Request AI-recommended posting time for a campaign
     */
    public function requestAIRecommendation(?array $user, array $params = []): array
    {
        // RBAC: Only authorized LGU roles can request AI recommendations (viewer cannot)
        if (!$user) {
            http_response_code(401);
            return ['error' => 'Authentication required'];
        }
        
        try {
            $userRole = RoleMiddleware::getUserRole($user, $this->pdo);
            $userRoleName = $userRole ? strtolower($userRole) : '';
            
            if ($userRoleName === 'viewer') {
                http_response_code(403);
                return ['error' => 'Viewer role is read-only. You cannot request AI recommendations.'];
            }
            
            $allowedRoles = ['admin', 'staff', 'secretary', 'kagawad', 'captain', 'barangay administrator', 'barangay staff', 'system_admin', 'barangay_admin', 'campaign_creator'];
            if (!$userRole || !in_array($userRoleName, $allowedRoles, true)) {
                http_response_code(403);
                return ['error' => 'Insufficient permissions. Only authorized LGU personnel can request AI recommendations.'];
            }
        } catch (\Exception $e) {
            http_response_code(403);
            return ['error' => 'Access denied: ' . $e->getMessage()];
        }
        
        $campaignId = (int) ($params['id'] ?? 0);
        $campaign = $this->findCampaign($campaignId);

        $input = json_decode(file_get_contents('php://input'), true) ?? [];
        $features = $input['features'] ?? [];

        try {
            error_log("CampaignController::requestAIRecommendation - Campaign ID: $campaignId");
            error_log("CampaignController::requestAIRecommendation - Features: " . json_encode($features));
            
            $prediction = $this->autoMLService->predict($campaignId, $features);
            
            error_log("CampaignController::requestAIRecommendation - Prediction received:");
            error_log("  - Model Source: " . ($prediction['model_source'] ?? 'unknown'));
            error_log("  - Suggested DateTime: " . ($prediction['suggested_datetime'] ?? 'N/A'));
            error_log("  - Confidence Score: " . ($prediction['confidence_score'] ?? 'N/A'));
            error_log("  - AutoML Configured: " . (isset($prediction['automl_configured']) ? ($prediction['automl_configured'] ? 'YES' : 'NO') : 'UNKNOWN'));
            
            // Save AI recommendation to campaign
            $stmt = $this->pdo->prepare('
                UPDATE campaign_department_campaigns 
                SET ai_recommended_datetime = :ai_recommended_datetime 
                WHERE id = :id
            ');
            $stmt->execute([
                'id' => $campaignId,
                'ai_recommended_datetime' => $prediction['suggested_datetime'],
            ]);

            // Save prediction record
            try {
                $predictionId = $this->autoMLService->savePrediction($campaignId, $prediction);
            } catch (\Exception $e) {
                error_log("CampaignController::requestAIRecommendation - Failed to save prediction record: " . $e->getMessage());
                $predictionId = null;
            }

            $message = 'AI recommendation generated';
            if (isset($prediction['model_source'])) {
                if ($prediction['model_source'] === 'google_automl') {
                    $message = 'Google AutoML recommendation generated successfully';
                } elseif (isset($prediction['automl_configured']) && !$prediction['automl_configured']) {
                    $message = 'Heuristic recommendation generated (Google AutoML not configured)';
                } elseif (isset($prediction['fallback_reason'])) {
                    $message = 'Heuristic recommendation generated (Google AutoML unavailable)';
                }
            }

            return [
                'prediction_id' => $predictionId,
                'prediction' => $prediction,
                'message' => $message,
            ];
        } catch (RuntimeException $e) {
            error_log("CampaignController::requestAIRecommendation - RuntimeException: " . $e->getMessage());
            http_response_code(400);
            return ['error' => $e->getMessage()];
        } catch (\Exception $e) {
            error_log("CampaignController::requestAIRecommendation - Exception: " . $e->getMessage());
            error_log("CampaignController::requestAIRecommendation - Stack trace: " . $e->getTraceAsString());
            http_response_code(500);
            return ['error' => 'Failed to generate AI recommendation: ' . $e->getMessage()];
        }
    }

    /**
     * Accept or override AI recommendation
     */
    public function setFinalSchedule(?array $user, array $params = []): array
    {
        // RBAC: Only authorized LGU roles can set final schedule (viewer cannot)
        if (!$user) {
            http_response_code(401);
            return ['error' => 'Authentication required'];
        }
        
        try {
            $userRole = RoleMiddleware::getUserRole($user, $this->pdo);
            $userRoleName = $userRole ? strtolower($userRole) : '';
            
            if ($userRoleName === 'viewer') {
                http_response_code(403);
                return ['error' => 'Viewer role is read-only. You cannot set final schedule.'];
            }
            
            $allowedRoles = ['admin', 'staff', 'secretary', 'kagawad', 'captain', 'barangay administrator', 'barangay staff', 'system_admin', 'barangay_admin', 'campaign_creator'];
            if (!$userRole || !in_array($userRoleName, $allowedRoles, true)) {
                http_response_code(403);
                return ['error' => 'Insufficient permissions. Only authorized LGU personnel can set final schedule.'];
            }
        } catch (\Exception $e) {
            http_response_code(403);
            return ['error' => 'Access denied: ' . $e->getMessage()];
        }
        
        $campaignId = (int) ($params['id'] ?? 0);
        $this->findCampaign($campaignId);

        $input = json_decode(file_get_contents('php://input'), true) ?? [];
        $finalSchedule = $input['final_schedule_datetime'] ?? null;
        $useAIRecommendation = $input['use_ai_recommendation'] ?? false;
        $finalize = $input['finalize'] ?? false; // Flag to actually finalize and change status to scheduled

        if ($useAIRecommendation) {
            // Use AI recommendation
            // LGU Workflow: Draft -> Pending (after AI acceptance) -> Approved (by Captain) -> Scheduled (via Finalize button only)
            // NOTE: Status should NOT change to "scheduled" here - only the Finalize button should do that
            $stmt = $this->pdo->prepare('
                UPDATE campaign_department_campaigns 
                SET final_schedule_datetime = ai_recommended_datetime,
                    draft_schedule_datetime = ai_recommended_datetime,
                    status = CASE 
                        WHEN status = "draft" THEN "pending"
                        ELSE status
                    END
                WHERE id = :id AND ai_recommended_datetime IS NOT NULL
            ');
            $stmt->execute(['id' => $campaignId]);
            
            if ($stmt->rowCount() === 0) {
                http_response_code(400);
                return ['error' => 'No AI recommendation available. Request one first.'];
            }
        } elseif ($finalSchedule) {
            if ($finalize) {
                // FINALIZE: Change status to "scheduled" - this is the actual finalize action
                // Only Captain/Admin should call this with finalize=true
                $stmt = $this->pdo->prepare('
                    UPDATE campaign_department_campaigns 
                    SET final_schedule_datetime = :final_schedule_datetime,
                        draft_schedule_datetime = :final_schedule_datetime,
                        status = "scheduled"
                    WHERE id = :id AND status = "approved"
                ');
                $stmt->execute([
                    'id' => $campaignId,
                    'final_schedule_datetime' => $finalSchedule,
                ]);
                
                if ($stmt->rowCount() === 0) {
                    http_response_code(400);
                    return ['error' => 'Campaign must be in Approved status to finalize.'];
                }
            } else {
                // Override with manual schedule (NOT finalizing - just setting the schedule)
                // LGU Workflow: Draft -> Pending (after schedule set) -> Approved (by Captain) -> Scheduled (via Finalize button only)
                $stmt = $this->pdo->prepare('
                    UPDATE campaign_department_campaigns 
                    SET final_schedule_datetime = :final_schedule_datetime,
                        draft_schedule_datetime = :final_schedule_datetime,
                        status = CASE 
                            WHEN status = "draft" THEN "pending"
                            ELSE status
                        END
                    WHERE id = :id
                ');
                $stmt->execute([
                    'id' => $campaignId,
                    'final_schedule_datetime' => $finalSchedule,
                ]);
            }
        } else {
            http_response_code(422);
            return ['error' => 'Either use_ai_recommendation or final_schedule_datetime is required'];
        }

        // Log audit entry for schedule approval
        $this->logAudit($user['id'] ?? null, 'campaign', 'schedule_approved', $campaignId, [
            'final_schedule_datetime' => $finalSchedule ?? 'ai_recommended',
            'use_ai_recommendation' => $useAIRecommendation
        ]);

        return ['message' => 'Final schedule set successfully'];
    }

    /**
     * Get calendar view of campaigns
     */
    public function calendar(?array $user, array $params = []): array
    {
        $startDate = $_GET['start'] ?? date('Y-m-01');
        $endDate = $_GET['end'] ?? date('Y-m-t');

        // FIX: More inclusive query to show all campaigns with any date information
        // Show campaigns that:
        // 1. Have start_date or end_date in the range
        // 2. Have any schedule datetime in the range
        // 3. Have start_date/end_date that span the range
        // 4. OR have no dates but exist (for debugging - will show as events without dates)
        $stmt = $this->pdo->prepare('
            SELECT 
                id, title, description, status, category,
                start_date, end_date, 
                draft_schedule_datetime, ai_recommended_datetime, final_schedule_datetime,
                location, geographic_scope, budget
            FROM campaign_department_campaigns
            WHERE 
                (start_date IS NOT NULL AND start_date BETWEEN :start AND :end)
                OR (end_date IS NOT NULL AND end_date BETWEEN :start AND :end)
                OR (start_date IS NOT NULL AND end_date IS NOT NULL AND start_date <= :start AND end_date >= :end)
                OR (draft_schedule_datetime IS NOT NULL AND DATE(draft_schedule_datetime) BETWEEN :start AND :end)
                OR (ai_recommended_datetime IS NOT NULL AND DATE(ai_recommended_datetime) BETWEEN :start AND :end)
                OR (final_schedule_datetime IS NOT NULL AND DATE(final_schedule_datetime) BETWEEN :start AND :end)
                OR (start_date IS NOT NULL AND start_date <= :end AND (end_date IS NULL OR end_date >= :start))
            ORDER BY COALESCE(final_schedule_datetime, ai_recommended_datetime, draft_schedule_datetime, start_date) ASC
        ');
        $stmt->execute(['start' => $startDate, 'end' => $endDate]);
        $campaigns = $stmt->fetchAll(PDO::FETCH_ASSOC);

        error_log('CampaignController::calendar - Date range: ' . $startDate . ' to ' . $endDate . ', Found campaigns: ' . count($campaigns));

        return ['data' => $campaigns];
    }

    /**
     * Check for scheduling conflicts with events
     */
    public function checkConflicts(?array $user, array $params = []): array
    {
        $campaignId = (int) ($params['id'] ?? 0);
        $input = json_decode(file_get_contents('php://input'), true) ?? [];
        $proposedDatetime = $input['proposed_datetime'] ?? null;

        if (!$proposedDatetime) {
            http_response_code(422);
            return ['error' => 'proposed_datetime is required'];
        }

        $campaign = $this->findCampaign($campaignId);
        $proposedDate = date('Y-m-d', strtotime($proposedDatetime));

        // Check for conflicts with other campaigns
        $stmt = $this->pdo->prepare('
            SELECT id, title, final_schedule_datetime, location
            FROM campaign_department_campaigns
            WHERE id != :id
              AND final_schedule_datetime IS NOT NULL
              AND DATE(final_schedule_datetime) = :proposed_date
              AND status IN ("scheduled", "approved", "ongoing")
        ');
        $stmt->execute(['id' => $campaignId, 'proposed_date' => $proposedDate]);
        $campaignConflicts = $stmt->fetchAll();

        // Check for conflicts with events and seminars
        // Use 'event_date' and 'event_time' columns (the actual columns in the events table)
        $stmt = $this->pdo->prepare('
            SELECT e.id, e.name as event_name, e.name as event_title, 
                   e.event_type, e.event_date, e.event_time, e.venue
            FROM `campaign_department_events` e
            WHERE e.event_date = :proposed_date
              AND e.status IN ("scheduled", "ongoing")
        ');
        $stmt->execute(['proposed_date' => $proposedDate]);
        $eventConflicts = $stmt->fetchAll();
        
        // Format event conflicts to include event_name as 'name' for frontend compatibility
        $eventConflicts = array_map(function($event) {
            $event['name'] = $event['event_name'] ?? $event['event_title'] ?? 'Untitled Event';
            $event['date'] = $event['event_date'] ?? null;
            $event['start_time'] = $event['event_time'] ?? null;
            $event['event_time'] = $event['event_time'] ?? '';
            return $event;
        }, $eventConflicts);

        $hasConflicts = !empty($campaignConflicts) || !empty($eventConflicts);

        return [
            'has_conflicts' => $hasConflicts,
            'campaign_conflicts' => $campaignConflicts,
            'event_conflicts' => $eventConflicts,
            'proposed_datetime' => $proposedDatetime,
        ];
    }

    private function findCampaign(int $id): array
    {
        $stmt = $this->pdo->prepare('
            SELECT id, title, description, category, geographic_scope, status, 
                   start_date, start_time, end_date, end_time, draft_schedule_datetime, 
                   ai_recommended_datetime, final_schedule_datetime, 
                   owner_id, objectives, location, assigned_staff, 
                   barangay_target_zones, budget, staff_count, materials_json 
            FROM campaign_department_campaigns 
            WHERE id = :id LIMIT 1
        ');
        $stmt->execute(['id' => $id]);
        $campaign = $stmt->fetch();
        if (!$campaign) {
            http_response_code(404);
            throw new RuntimeException('Campaign not found');
        }
        return $campaign;
    }

    private function findSchedule(int $campaignId, int $scheduleId): array
    {
        $stmt = $this->pdo->prepare('SELECT id, campaign_id, scheduled_at, channel, notes, status FROM `campaign_department_campaign_schedules` WHERE id = :sid AND campaign_id = :cid LIMIT 1');
        $stmt->execute(['sid' => $scheduleId, 'cid' => $campaignId]);
        $schedule = $stmt->fetch();
        if (!$schedule) {
            http_response_code(404);
            throw new RuntimeException('Schedule not found');
        }
        return $schedule;
    }

    /**
     * Re-send a failed schedule
     */
    public function resendSchedule(?array $user, array $params = []): array
    {
        // RBAC: Only authorized LGU roles can resend schedules (viewer cannot)
        if (!$user) {
            http_response_code(401);
            return ['error' => 'Authentication required'];
        }
        
        try {
            $userRole = RoleMiddleware::getUserRole($user, $this->pdo);
            $userRoleName = $userRole ? strtolower($userRole) : '';
            
            if ($userRoleName === 'viewer') {
                http_response_code(403);
                return ['error' => 'Viewer role is read-only. You cannot resend schedules.'];
            }
            
            $allowedRoles = ['admin', 'staff', 'secretary', 'kagawad', 'captain', 'barangay administrator', 'barangay staff', 'system_admin', 'barangay_admin', 'campaign_creator'];
            if (!$userRole || !in_array($userRoleName, $allowedRoles, true)) {
                http_response_code(403);
                return ['error' => 'Insufficient permissions. Only authorized LGU personnel can resend schedules.'];
            }
        } catch (\Exception $e) {
            http_response_code(403);
            return ['error' => 'Access denied: ' . $e->getMessage()];
        }
        
        $campaignId = (int) ($params['id'] ?? 0);
        $scheduleId = (int) ($params['sid'] ?? 0);

        $this->findCampaign($campaignId);
        $schedule = $this->findSchedule($campaignId, $scheduleId);

        // Only allow re-sending failed schedules
        if ($schedule['status'] !== 'failed') {
            http_response_code(422);
            return ['error' => 'Can only re-send failed schedules. Current status: ' . $schedule['status']];
        }

        // Reset status to pending and call sendSchedule logic
        $updateStmt = $this->pdo->prepare('UPDATE `campaign_department_campaign_schedules` SET status = :status WHERE id = :id');
        $updateStmt->execute([
            'id' => $scheduleId,
            'status' => 'pending',
        ]);

        // Call sendSchedule logic
        return $this->sendSchedule($user, $params);
    }

    private function assertSegments(array $ids): void
    {
        if (empty($ids)) {
            return;
        }
        $placeholders = implode(',', array_fill(0, count($ids), '?'));
        $stmt = $this->pdo->prepare("SELECT COUNT(*) FROM `campaign_department_audience_segments` WHERE id IN ($placeholders)");
        $stmt->execute($ids);
        $found = (int) $stmt->fetchColumn();
        if ($found !== count($ids)) {
            throw new RuntimeException('One or more segments not found');
        }
    }

    private function dispatchWebhook(string $url, array $payload): bool
    {
        $secret = getenv('NOTIFY_WEBHOOK_SECRET') ?: 'demo_secret';
        $json = json_encode($payload);
        $signature = hash_hmac('sha256', $json, $secret);

        $opts = [
            'http' => [
                'method' => 'POST',
                'header' => "Content-Type: application/json\r\nX-Signature: {$signature}\r\n",
                'content' => $json,
                'timeout' => 5,
            ],
        ];
        $ctx = stream_context_create($opts);
        try {
            $result = @file_get_contents($url, false, $ctx);
            return $result !== false;
        } catch (\Throwable $e) {
            // swallow to avoid breaking flow; logged via integration_logs
            return false;
        }
    }

    /**
     * Log audit entry for campaign operations
     */
    private function logAudit(?int $userId, string $entityType, string $action, int $entityId, array $details = []): void
    {
        try {
            // Create campaign_department_audit_logs table if it doesn't exist
            $this->pdo->exec("
                CREATE TABLE IF NOT EXISTS `campaign_department_audit_logs` (
                    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                    user_id INT UNSIGNED NULL,
                    action VARCHAR(150) NOT NULL,
                    entity_type VARCHAR(50) NOT NULL,
                    entity_id INT UNSIGNED NULL,
                    details TEXT NULL,
                    metadata JSON NULL,
                    ip_address VARCHAR(45) NULL,
                    user_agent TEXT NULL,
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    INDEX idx_user_id (user_id),
                    INDEX idx_entity (entity_type, entity_id),
                    INDEX idx_created_at (created_at)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
            ");

            $ipAddress = $_SERVER['REMOTE_ADDR'] ?? null;
            $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? null;

            $stmt = $this->pdo->prepare('
                INSERT INTO campaign_department_audit_logs (user_id, entity_type, action, entity_id, details, ip_address, user_agent, created_at)
                VALUES (:user_id, :entity_type, :action, :entity_id, :details, :ip_address, :user_agent, NOW())
            ');
            $stmt->execute([
                'user_id' => $userId,
                'entity_type' => $entityType,
                'action' => $action,
                'entity_id' => $entityId,
                'details' => json_encode($details),
                'ip_address' => $ipAddress,
                'user_agent' => $userAgent,
            ]);
        } catch (\Throwable $e) {
            // Fail silently - audit logging should not break main operations
            error_log('Audit log failed: ' . $e->getMessage());
        }
    }

    /**
     * Record integration events for newly created campaigns.
     * This captures both internal module coordination and
     * external systems such as notification and training.
     */
    private function logCampaignIntegrations(int $campaignId, array $payload): void
    {
        // Fail-safe: if integration_logs table is missing or any error occurs,
        // we don't want to block campaign creation. Just log and return.
        try {
            // Ensure integration_logs table exists
            $check = $this->pdo->query("SHOW TABLES LIKE 'campaign_department_integration_logs'");
            if (!$check || $check->rowCount() === 0) {
                return;
            }

            $stmt = $this->pdo->prepare('INSERT INTO `campaign_department_integration_logs` (source, payload, status) VALUES (:source, :payload, :status)');
        } catch (\Throwable $e) {
            error_log('logCampaignIntegrations init failed: ' . $e->getMessage());
            return;
        }

        // Core internal subsystems
        $sources = [
            // Content Repository (materials, themes, hazard type)
            'content_repository',
            // Target Audience Segmentation (risk profiles, segments)
            'target_audience_segmentation',
            // Event & Seminar Management (implementation logistics)
            'event_seminar_management',
            // School & NGO Collaboration (partner engagement)
            'school_ngo_collaboration',
            // Emergency Communication System (mass notification)
            'emergency_communication_system',
            // Disaster Preparedness Training and Simulation
            'training_and_simulation',
            // Community Policing & Surveillance (volunteers)
            'community_policing_surveillance',
            // Traffic & Transport Management (permits)
            'traffic_transport_management',
            // Fire & Rescue Services Management (scheduling)
            'fire_rescue_management',
        ];

        foreach ($sources as $source) {
            try {
                $stmt->execute([
                    'source' => $source,
                    'payload' => json_encode([
                        'campaign_id' => $campaignId,
                        'source' => $source,
                        'campaign' => $payload,
                    ]),
                    'status' => 'queued',
                ]);
            } catch (\Throwable $e) {
                // Log but don't interrupt main flow
                error_log('logCampaignIntegrations insert failed for source ' . $source . ': ' . $e->getMessage());
            }
        }
    }

    /**
     * Delete a campaign
     */
    public function destroy(?array $user, array $params = []): array
    {
        // RBAC: Only authorized LGU roles can delete campaigns (viewer cannot)
        if (!$user) {
            http_response_code(401);
            return ['error' => 'Authentication required'];
        }
        
        try {
            $userRole = RoleMiddleware::getUserRole($user, $this->pdo);
            $userRoleName = $userRole ? strtolower($userRole) : '';
            
            // Viewer is read-only - cannot delete anything
            if ($userRoleName === 'viewer') {
                http_response_code(403);
                return ['error' => 'Viewer role is read-only. You cannot delete campaigns.'];
            }
            
            // Only admin and captain can delete campaigns
            $allowedRoles = ['admin', 'captain', 'barangay administrator', 'system_admin', 'barangay_admin'];
            if (!$userRole || !in_array($userRoleName, $allowedRoles, true)) {
                http_response_code(403);
                return ['error' => 'Insufficient permissions. Only administrators and captains can delete campaigns.'];
            }
        } catch (\Exception $e) {
            http_response_code(403);
            return ['error' => 'Access denied: ' . $e->getMessage()];
        }
        
        $id = (int) ($params['id'] ?? 0);
        $campaign = $this->findCampaign($id);
        
        // Check if campaign can be deleted
        // Archived campaigns CAN be deleted (they're already removed from active view)
        // Completed campaigns should be archived first before deletion
        $status = strtolower($campaign['status'] ?? '');
        if ($status === 'completed') {
            http_response_code(422);
            return ['error' => 'Cannot delete completed campaigns directly. Please archive them first.'];
        }
        
        // Delete related records first (foreign key constraints)
        $this->pdo->beginTransaction();
        try {
            // Delete campaign schedules
            $stmt = $this->pdo->prepare('DELETE FROM `campaign_department_campaign_schedules` WHERE campaign_id = :id');
            $stmt->execute(['id' => $id]);
            
            // Delete campaign audience segments
            $stmt = $this->pdo->prepare('DELETE FROM `campaign_department_campaign_audience` WHERE campaign_id = :id');
            $stmt->execute(['id' => $id]);
            
            // Delete campaign content links (set campaign_id to NULL instead of deleting content)
            $stmt = $this->pdo->prepare('UPDATE `campaign_department_content_items` SET campaign_id = NULL WHERE campaign_id = :id');
            $stmt->execute(['id' => $id]);
            
            // Delete campaign budget items (Financial & Budgeting data)
            try {
                $stmt = $this->pdo->prepare('DELETE FROM `campaign_budgets` WHERE campaign_id = :id');
                $stmt->execute(['id' => $id]);
            } catch (\PDOException $e) {
                // Table might not exist, log and continue
                error_log('CampaignController::destroy - campaign_budgets delete failed: ' . $e->getMessage());
            }
            
            // Delete campaign content usage records
            try {
                $stmt = $this->pdo->prepare('DELETE FROM `campaign_department_content_usage` WHERE campaign_id = :id');
                $stmt->execute(['id' => $id]);
            } catch (\PDOException $e) {
                // Table might not exist or column might not exist, log and continue
                error_log('CampaignController::destroy - content_usage delete failed: ' . $e->getMessage());
            }
            
            // Delete the campaign
            $stmt = $this->pdo->prepare('DELETE FROM `campaign_department_campaigns` WHERE id = :id');
            $stmt->execute(['id' => $id]);
            
            $this->pdo->commit();
            
            // Log audit entry
            $this->logAudit($user['id'] ?? null, 'campaign', 'delete', $id, ['title' => $campaign['title'] ?? '']);
            
            return ['message' => 'Campaign deleted successfully'];
        } catch (\Throwable $e) {
            $this->pdo->rollBack();
            error_log('CampaignController::destroy - Error: ' . $e->getMessage());
            http_response_code(500);
            return ['error' => 'Failed to delete campaign: ' . $e->getMessage()];
        }
    }

    public function manualPlanningOptions(?array $user, array $params = []): array
    {
        if (!$user) {
            http_response_code(401);
            return ['error' => 'Authentication required'];
        }
        try {
            $staff = $this->pdo->query('SELECT id, name, role, qty FROM campaign_department_reference_staff ORDER BY name ASC')->fetchAll(PDO::FETCH_ASSOC);
            $partners = $this->pdo->query("SELECT id, name, organization_type FROM campaign_department_partners WHERE COALESCE(status,'active') <> 'archived' ORDER BY name ASC")->fetchAll(PDO::FETCH_ASSOC);
            $segments = $this->pdo->query('SELECT id, segment_name, geographic_scope, location_reference, sector_type, risk_level, is_archived FROM campaign_department_audience_segments ORDER BY is_archived ASC, segment_name ASC')->fetchAll(PDO::FETCH_ASSOC);
            return ['data' => ['reference_staff' => $staff, 'available_partners' => $partners, 'audience_segments' => $segments]];
        } catch (\PDOException $e) {
            error_log('CampaignController::manualPlanningOptions - ' . $e->getMessage());
            http_response_code(500);
            return ['error' => 'Unable to load manual planner reference data'];
        }
    }

    /**
     * Return all data used by the 8-step manual campaign planner.
     */
    public function manualPlanning(?array $user, array $params = []): array
    {
        if (!$user) {
            http_response_code(401);
            return ['error' => 'Authentication required'];
        }

        $campaignId = (int)($params['id'] ?? 0);
        $this->findCampaign($campaignId);

        try {
            $reportsStmt = $this->pdo->prepare('SELECT * FROM campaign_department_campaign_reports WHERE campaign_id = :cid ORDER BY created_at DESC, id DESC');
            $reportsStmt->execute(['cid' => $campaignId]);
            $reports = $reportsStmt->fetchAll(PDO::FETCH_ASSOC);

            $budgetsStmt = $this->pdo->prepare("SELECT *, (quantity * unit_cost * COALESCE(NULLIF(sessions_or_days, 0), 1)) AS total_cost FROM campaign_budgets WHERE campaign_id = :cid AND COALESCE(is_archived,0)=0 ORDER BY sort_order ASC, id ASC");
            $budgetsStmt->execute(['cid' => $campaignId]);
            $budgets = $budgetsStmt->fetchAll(PDO::FETCH_ASSOC);

            $audienceStmt = $this->pdo->prepare('SELECT ca.segment_id, s.segment_name, s.sector_type, s.location_reference FROM campaign_department_campaign_audience ca LEFT JOIN campaign_department_audience_segments s ON s.id = ca.segment_id WHERE ca.campaign_id = :cid ORDER BY s.segment_name ASC');
            $audienceStmt->execute(['cid' => $campaignId]);
            $audiences = $audienceStmt->fetchAll(PDO::FETCH_ASSOC);

            $participantsStmt = $this->pdo->prepare('SELECT * FROM campaign_department_campaign_participants WHERE campaign_id = :cid ORDER BY id ASC');
            $participantsStmt->execute(['cid' => $campaignId]);
            $participants = $participantsStmt->fetchAll(PDO::FETCH_ASSOC);

            // Accepted AI recommendations keep their source engagement type as ai_recommended,
            // but they must also appear in the same Partners tab used by manual campaigns.
            $partnersStmt = $this->pdo->prepare("SELECT pe.id, pe.partner_id, pe.engagement_type, pe.notes, p.name AS partner_name, p.organization_type FROM campaign_department_partner_engagements pe LEFT JOIN campaign_department_partners p ON p.id = pe.partner_id WHERE pe.campaign_id = :cid AND pe.engagement_type IN ('manual_planner','ai_recommended') ORDER BY CASE WHEN pe.engagement_type = 'manual_planner' THEN 0 ELSE 1 END, pe.id ASC");
            $partnersStmt->execute(['cid' => $campaignId]);
            $partnerRows = $partnersStmt->fetchAll(PDO::FETCH_ASSOC);
            $partners = [];
            $seenPartnerIds = [];
            foreach ($partnerRows as $row) {
                $partnerId = (int)($row['partner_id'] ?? 0);
                if ($partnerId > 0 && isset($seenPartnerIds[$partnerId])) {
                    continue;
                }
                if ($partnerId > 0) {
                    $seenPartnerIds[$partnerId] = true;
                }
                $meta = [];
                $rawNotes = (string)($row['notes'] ?? '');
                if ($rawNotes !== '') {
                    $decoded = json_decode($rawNotes, true);
                    if (is_array($decoded)) $meta = $decoded;
                }
                $isAiPartner = ($row['engagement_type'] ?? '') === 'ai_recommended';
                $partners[] = [
                    'id' => (int)$row['id'],
                    'partner_id' => (int)$row['partner_id'],
                    'partner_name' => $row['partner_name'],
                    'organization_type' => $row['organization_type'],
                    'role' => $meta['role'] ?? ($isAiPartner ? 'AI Recommended Partner' : ''),
                    'engagement_type' => $meta['engagement_type'] ?? 'collaboration',
                    'notes' => $meta['notes'] ?? ($isAiPartner ? $rawNotes : ''),
                    'source' => $meta['source'] ?? ($isAiPartner ? 'ai_recommendation' : 'manual'),
                ];
            }

            $phasesStmt = $this->pdo->prepare('SELECT * FROM campaign_department_campaign_schedule_phases WHERE campaign_id = :cid ORDER BY sort_order ASC, sprint_number ASC, id ASC');
            $phasesStmt->execute(['cid' => $campaignId]);
            $phases = $phasesStmt->fetchAll(PDO::FETCH_ASSOC);

            foreach ($phases as &$phase) {
                foreach (['activities','assigned_staff','assigned_partners','locations','dependencies'] as $jsonField) {
                    if (isset($phase[$jsonField]) && is_string($phase[$jsonField])) {
                        $decoded = json_decode($phase[$jsonField], true);
                        $phase[$jsonField] = is_array($decoded) ? $decoded : [];
                    }
                }
            }
            unset($phase);

            $staff = $this->pdo->query('SELECT id, name, role, qty FROM campaign_department_reference_staff ORDER BY name ASC')->fetchAll(PDO::FETCH_ASSOC);
            $availablePartners = $this->pdo->query("SELECT id, name, organization_type FROM campaign_department_partners WHERE COALESCE(status,'active') <> 'archived' ORDER BY name ASC")->fetchAll(PDO::FETCH_ASSOC);
            $segments = $this->pdo->query('SELECT id, segment_name, geographic_scope, location_reference, sector_type, risk_level, is_archived FROM campaign_department_audience_segments ORDER BY is_archived ASC, segment_name ASC')->fetchAll(PDO::FETCH_ASSOC);

            $workflowStmt = $this->pdo->prepare('SELECT * FROM campaign_budget_workflows WHERE campaign_id = :cid LIMIT 1');
            $workflowStmt->execute(['cid' => $campaignId]);
            $budgetWorkflow = $workflowStmt->fetch(PDO::FETCH_ASSOC) ?: [
                'campaign_id' => $campaignId,
                'planning_status' => count($budgets) > 0 ? 'finalized' : 'draft',
                'review_status' => 'none',
                'rejection_reason' => null,
            ];

            return ['data' => [
                'reports' => $reports,
                'budget_items' => $budgets,
                'budget_workflow' => $budgetWorkflow,
                'audiences' => $audiences,
                'participants' => $participants,
                'partners' => $partners,
                'schedule_phases' => $phases,
                'reference_staff' => $staff,
                'available_partners' => $availablePartners,
                'audience_segments' => $segments,
            ]];
        } catch (\PDOException $e) {
            error_log('CampaignController::manualPlanning - ' . $e->getMessage());
            http_response_code(500);
            return ['error' => 'Manual campaign planner database tables are missing or unavailable. Apply manual_campaign_planner.sql first.'];
        }
    }

    /** Save Steps 3-7 of the manual planner in one transaction. */
    public function saveManualPlanning(?array $user, array $params = []): array
    {
        if (!$user) {
            http_response_code(401);
            return ['error' => 'Authentication required'];
        }

        $campaignId = (int)($params['id'] ?? 0);
        $this->findCampaign($campaignId);
        $input = json_decode(file_get_contents('php://input'), true) ?? [];
        $budgetItems = is_array($input['budget_items'] ?? null) ? $input['budget_items'] : [];
        $segmentIds = is_array($input['segment_ids'] ?? null) ? $input['segment_ids'] : [];
        $participants = is_array($input['participants'] ?? null) ? $input['participants'] : [];
        $partners = is_array($input['partners'] ?? null) ? $input['partners'] : [];
        $phases = is_array($input['schedule_phases'] ?? null) ? $input['schedule_phases'] : [];
        $userId = isset($user['id']) ? (int)$user['id'] : null;

        try {
            $this->pdo->beginTransaction();

            // Target audience: the planner is authoritative for campaign audience assignments.
            $stmt = $this->pdo->prepare('DELETE FROM campaign_department_campaign_audience WHERE campaign_id = :cid');
            $stmt->execute(['cid' => $campaignId]);
            $insertAudience = $this->pdo->prepare('INSERT INTO campaign_department_campaign_audience (campaign_id, segment_id) VALUES (:cid, :sid)');
            foreach (array_values(array_unique(array_filter(array_map('intval', $segmentIds)))) as $segmentId) {
                if ($segmentId > 0) $insertAudience->execute(['cid' => $campaignId, 'sid' => $segmentId]);
            }

            // Budget workflow: manual planning is Draft until explicitly approved and finalized.
            // If a finalized budget is edited through the campaign planner, preserve the last
            // finalized values so Financial & Budgeting can review the revision as Pending.
            $workflowStmt = $this->pdo->prepare('SELECT * FROM campaign_budget_workflows WHERE campaign_id = :cid LIMIT 1 FOR UPDATE');
            $workflowStmt->execute(['cid' => $campaignId]);
            $budgetWorkflow = $workflowStmt->fetch(PDO::FETCH_ASSOC) ?: null;
            $wasFinalized = ($budgetWorkflow['planning_status'] ?? '') === 'finalized';
            $preEditSnapshot = (string)($budgetWorkflow['pre_edit_snapshot'] ?? '');
            if ($wasFinalized && (($budgetWorkflow['review_status'] ?? 'none') !== 'pending' || $preEditSnapshot === '')) {
                $snapStmt = $this->pdo->prepare("SELECT * FROM campaign_budgets WHERE campaign_id = :cid AND COALESCE(is_archived,0)=0 ORDER BY sort_order ASC, id ASC");
                $snapStmt->execute(['cid' => $campaignId]);
                $preEditSnapshot = json_encode($snapStmt->fetchAll(PDO::FETCH_ASSOC), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?: '[]';
            }

            // Replace manual budget items only; AI-converted rows are preserved.
            $stmt = $this->pdo->prepare('DELETE FROM campaign_budgets WHERE campaign_id = :cid AND source_recommendation_id IS NULL');
            $stmt->execute(['cid' => $campaignId]);
            $insertBudget = $this->pdo->prepare('INSERT INTO campaign_budgets (campaign_id,item_name,item_type,quantity,unit_cost,funding_source,notes,created_by,is_archived,category,item_description,sessions_or_days,unit_label,related_action,budget_destination,is_estimate,sort_order) VALUES (:cid,:name,:type,:qty,:cost,:funding,:notes,:uid,0,:category,:description,:sessions,:unit_label,:related_action,:budget_destination,0,:sort_order)');
            $totalBudget = 0.0;
            $sort = 0;
            foreach ($budgetItems as $item) {
                $name = trim((string)($item['item_name'] ?? ''));
                if ($name === '') continue;
                $qty = max(1, (int)($item['quantity'] ?? 1));
                $cost = max(0, (float)($item['unit_cost'] ?? 0));
                $sessions = max(1, (int)($item['sessions_or_days'] ?? 1));
                $sort++;
                $totalBudget += $qty * $cost * $sessions;
                $insertBudget->execute([
                    'cid' => $campaignId,
                    'name' => $name,
                    'type' => trim((string)($item['item_type'] ?? 'consumable')) ?: 'consumable',
                    'qty' => $qty,
                    'cost' => $cost,
                    'funding' => trim((string)($item['funding_source'] ?? 'government_allocated')) ?: 'government_allocated',
                    'notes' => trim((string)($item['notes'] ?? '')) ?: null,
                    'uid' => $userId,
                    'category' => trim((string)($item['category'] ?? '')) ?: null,
                    'description' => trim((string)($item['item_description'] ?? '')) ?: null,
                    'sessions' => $sessions,
                    'unit_label' => trim((string)($item['unit_label'] ?? '')) ?: null,
                    'related_action' => trim((string)($item['related_action'] ?? '')) ?: null,
                    'budget_destination' => trim((string)($item['budget_destination'] ?? '')) ?: null,
                    'sort_order' => $sort,
                ]);
            }

            // Include preserved AI-converted rows in the campaign-level compatibility total.
            $totalStmt = $this->pdo->prepare('SELECT COALESCE(SUM(quantity * unit_cost * COALESCE(NULLIF(sessions_or_days,0),1)),0) FROM campaign_budgets WHERE campaign_id = :cid AND COALESCE(is_archived,0)=0');
            $totalStmt->execute(['cid' => $campaignId]);
            $totalBudget = (float)$totalStmt->fetchColumn();

            if ($budgetWorkflow) {
                if ($wasFinalized) {
                    $stmt = $this->pdo->prepare("UPDATE campaign_budget_workflows SET planning_status='finalized', review_status='pending', rejection_reason=NULL, pre_edit_snapshot=:snapshot, reviewed_by=NULL, reviewed_at=NULL WHERE campaign_id=:cid");
                    $stmt->execute(['snapshot' => $preEditSnapshot, 'cid' => $campaignId]);
                } else {
                    // Any edit before finalization returns the budget plan to Draft for re-approval.
                    $stmt = $this->pdo->prepare("UPDATE campaign_budget_workflows SET planning_status='draft', review_status='none', rejection_reason=NULL, approved_by=NULL, approved_at=NULL WHERE campaign_id=:cid");
                    $stmt->execute(['cid' => $campaignId]);
                }
            } else {
                $stmt = $this->pdo->prepare("INSERT INTO campaign_budget_workflows (campaign_id,planning_status,review_status) VALUES (:cid,'draft','none')");
                $stmt->execute(['cid' => $campaignId]);
            }

            // Manual participants.
            $stmt = $this->pdo->prepare('DELETE FROM campaign_department_campaign_participants WHERE campaign_id = :cid');
            $stmt->execute(['cid' => $campaignId]);
            $insertParticipant = $this->pdo->prepare('INSERT INTO campaign_department_campaign_participants (campaign_id,staff_id,staff_name_snapshot,staff_role_snapshot,selected_qty,assigned_activity,deployment_location,notes,created_by) VALUES (:cid,:staff_id,:name,:role,:qty,:activity,:location,:notes,:uid)');
            $staffNames = [];
            $staffCount = 0;
            foreach ($participants as $participant) {
                $staffId = (int)($participant['staff_id'] ?? 0);
                if ($staffId <= 0) continue;
                $staffStmt = $this->pdo->prepare('SELECT name, role, qty FROM campaign_department_reference_staff WHERE id = :id LIMIT 1');
                $staffStmt->execute(['id' => $staffId]);
                $staffRow = $staffStmt->fetch(PDO::FETCH_ASSOC);
                if (!$staffRow) continue;
                $selectedQty = max(1, (int)($participant['selected_qty'] ?? 1));
                $availableQty = max(1, (int)($staffRow['qty'] ?? 1));
                $selectedQty = min($selectedQty, $availableQty);
                $staffCount += $selectedQty;
                $staffNames[] = $staffRow['name'];
                $insertParticipant->execute([
                    'cid' => $campaignId,
                    'staff_id' => $staffId,
                    'name' => $staffRow['name'],
                    'role' => $staffRow['role'],
                    'qty' => $selectedQty,
                    'activity' => trim((string)($participant['assigned_activity'] ?? '')) ?: null,
                    'location' => trim((string)($participant['deployment_location'] ?? '')) ?: null,
                    'notes' => trim((string)($participant['notes'] ?? '')) ?: null,
                    'uid' => $userId,
                ]);
            }

            // Manual partner selections are stored in the existing engagements table and tagged safely.
            $stmt = $this->pdo->prepare("DELETE FROM campaign_department_partner_engagements WHERE campaign_id = :cid AND engagement_type = 'manual_planner'");
            $stmt->execute(['cid' => $campaignId]);
            $insertPartner = $this->pdo->prepare("INSERT INTO campaign_department_partner_engagements (partner_id,campaign_id,event_id,engagement_type,notes) VALUES (:pid,:cid,NULL,'manual_planner',:notes)");
            foreach ($partners as $partner) {
                $partnerId = (int)($partner['partner_id'] ?? 0);
                if ($partnerId <= 0) continue;
                $meta = [
                    'role' => trim((string)($partner['role'] ?? '')),
                    'engagement_type' => trim((string)($partner['engagement_type'] ?? 'collaboration')) ?: 'collaboration',
                    'notes' => trim((string)($partner['notes'] ?? '')),
                ];
                $insertPartner->execute(['pid' => $partnerId, 'cid' => $campaignId, 'notes' => json_encode($meta, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)]);
            }

            // Manual Date Sprint phases.
            $stmt = $this->pdo->prepare('DELETE FROM campaign_department_campaign_schedule_phases WHERE campaign_id = :cid');
            $stmt->execute(['cid' => $campaignId]);
            $insertPhase = $this->pdo->prepare('INSERT INTO campaign_department_campaign_schedule_phases (campaign_id,sprint_number,sprint_title,start_date,end_date,duration_days,objectives,activities,assigned_staff,assigned_partners,locations,phase_budget,outputs,completion_criteria,dependencies,status,sort_order,created_by) VALUES (:cid,:sprint,:title,:start_date,:end_date,:duration,:objectives,:activities,:staff,:partners,:locations,:budget,:outputs,:criteria,:dependencies,:status,:sort_order,:uid)');
            $phaseStartDates = [];
            $phaseEndDates = [];
            $sort = 0;
            foreach ($phases as $phase) {
                $title = trim((string)($phase['sprint_title'] ?? ''));
                $start = trim((string)($phase['start_date'] ?? ''));
                $end = trim((string)($phase['end_date'] ?? ''));
                if ($title === '' || $start === '' || $end === '') continue;
                if (strtotime($start) > strtotime($end)) {
                    throw new RuntimeException('A Date Sprint phase has a start date after its end date.');
                }
                $sort++;
                $sprint = $sort; // Keep sprint numbers unique and ordered per campaign.
                $duration = max(1, (int)floor((strtotime($end) - strtotime($start)) / 86400) + 1);
                $phaseStartDates[] = $start;
                $phaseEndDates[] = $end;
                $json = static fn($v) => json_encode(is_array($v) ? array_values($v) : [], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
                $insertPhase->execute([
                    'cid' => $campaignId,
                    'sprint' => $sprint,
                    'title' => $title,
                    'start_date' => $start,
                    'end_date' => $end,
                    'duration' => $duration,
                    'objectives' => trim((string)($phase['objectives'] ?? '')) ?: null,
                    'activities' => $json($phase['activities'] ?? []),
                    'staff' => $json($phase['assigned_staff'] ?? []),
                    'partners' => $json($phase['assigned_partners'] ?? []),
                    'locations' => $json($phase['locations'] ?? []),
                    'budget' => max(0, (float)($phase['phase_budget'] ?? 0)),
                    'outputs' => trim((string)($phase['outputs'] ?? '')) ?: null,
                    'criteria' => trim((string)($phase['completion_criteria'] ?? '')) ?: null,
                    'dependencies' => $json($phase['dependencies'] ?? []),
                    'status' => in_array(($phase['status'] ?? 'planned'), ['planned','confirmed','completed'], true) ? $phase['status'] : 'planned',
                    'sort_order' => $sort,
                    'uid' => $userId,
                ]);
            }

            $campaignUpdate = ['budget' => $totalBudget, 'staff_count' => $staffCount, 'assigned_staff' => $staffNames ? json_encode(array_values(array_unique($staffNames))) : null, 'cid' => $campaignId];
            $dateSql = '';
            if ($phaseStartDates && $phaseEndDates) {
                sort($phaseStartDates);
                sort($phaseEndDates);
                $campaignUpdate['start_date'] = $phaseStartDates[0];
                $campaignUpdate['end_date'] = $phaseEndDates[count($phaseEndDates)-1];
                $dateSql = ', start_date = :start_date, end_date = :end_date';
            }
            $stmt = $this->pdo->prepare('UPDATE campaign_department_campaigns SET budget = :budget, staff_count = :staff_count, assigned_staff = :assigned_staff' . $dateSql . ', updated_at = CURRENT_TIMESTAMP WHERE id = :cid');
            $stmt->execute($campaignUpdate);

            $this->pdo->commit();
            $this->logAudit($userId, 'campaign', 'manual_plan_update', $campaignId, ['budget_items' => count($budgetItems), 'participants' => count($participants), 'partners' => count($partners), 'schedule_phases' => count($phases)]);
            return ['message' => 'Manual campaign plan saved', 'campaign_id' => $campaignId, 'budget_total' => $totalBudget, 'staff_count' => $staffCount];
        } catch (\Throwable $e) {
            if ($this->pdo->inTransaction()) $this->pdo->rollBack();
            error_log('CampaignController::saveManualPlanning - ' . $e->getMessage());
            http_response_code(500);
            return ['error' => $e instanceof RuntimeException ? $e->getMessage() : 'Failed to save manual campaign plan: ' . $e->getMessage()];
        }
    }

    /** Upload or replace a supporting report for Step 2. */
    public function uploadManualReport(?array $user, array $params = []): array
    {
        if (!$user) {
            http_response_code(401);
            return ['error' => 'Authentication required'];
        }
        $campaignId = (int)($params['id'] ?? 0);
        $this->findCampaign($campaignId);
        $reportId = isset($params['rid']) ? (int)$params['rid'] : 0;
        $title = trim((string)($_POST['report_title'] ?? ''));
        $type = trim((string)($_POST['report_type'] ?? 'Other')) ?: 'Other';
        $reportDate = trim((string)($_POST['report_date'] ?? '')) ?: null;
        $location = trim((string)($_POST['location'] ?? '')) ?: null;
        $description = trim((string)($_POST['description'] ?? '')) ?: null;
        if ($title === '') {
            http_response_code(422);
            return ['error' => 'Report title is required'];
        }

        $existing = null;
        if ($reportId > 0) {
            $stmt = $this->pdo->prepare('SELECT * FROM campaign_department_campaign_reports WHERE id = :rid AND campaign_id = :cid LIMIT 1');
            $stmt->execute(['rid' => $reportId, 'cid' => $campaignId]);
            $existing = $stmt->fetch(PDO::FETCH_ASSOC);
            if (!$existing) {
                http_response_code(404);
                return ['error' => 'Report not found'];
            }
        }

        $hasFile = isset($_FILES['report_file']) && is_array($_FILES['report_file']) && ($_FILES['report_file']['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE;
        if (!$existing && !$hasFile) {
            http_response_code(422);
            return ['error' => 'A report file is required'];
        }

        $fileMeta = $existing ?: [];
        if ($hasFile) {
            $file = $_FILES['report_file'];
            if (($file['error'] ?? UPLOAD_ERR_OK) !== UPLOAD_ERR_OK) {
                http_response_code(422);
                return ['error' => 'File upload failed'];
            }
            if (($file['size'] ?? 0) > 10 * 1024 * 1024) {
                http_response_code(422);
                return ['error' => 'Report file must be 10 MB or smaller'];
            }
            $original = basename((string)$file['name']);
            $ext = strtolower(pathinfo($original, PATHINFO_EXTENSION));
            $allowed = ['pdf','doc','docx','xls','xlsx','csv','jpg','jpeg','png'];
            if (!in_array($ext, $allowed, true)) {
                http_response_code(422);
                return ['error' => 'Unsupported report file type'];
            }
            $mime = function_exists('mime_content_type') ? (mime_content_type($file['tmp_name']) ?: ($file['type'] ?? 'application/octet-stream')) : ($file['type'] ?? 'application/octet-stream');
            $stored = date('Ymd_His') . '_' . bin2hex(random_bytes(8)) . '.' . $ext;
            $relativeDir = 'public/uploads/campaign_reports/campaign_' . $campaignId;
            $absoluteDir = dirname(__DIR__, 2) . '/' . $relativeDir;
            if (!is_dir($absoluteDir) && !mkdir($absoluteDir, 0775, true) && !is_dir($absoluteDir)) {
                throw new RuntimeException('Unable to create report upload directory');
            }
            $absolutePath = $absoluteDir . '/' . $stored;
            if (!move_uploaded_file($file['tmp_name'], $absolutePath)) {
                throw new RuntimeException('Unable to save uploaded report');
            }
            if ($existing && !empty($existing['file_path'])) {
                $old = dirname(__DIR__, 2) . '/' . ltrim((string)$existing['file_path'], '/');
                if (is_file($old)) @unlink($old);
            }
            $fileMeta = [
                'original_file_name' => $original,
                'stored_file_name' => $stored,
                'file_path' => $relativeDir . '/' . $stored,
                'mime_type' => $mime,
                'file_size' => (int)$file['size'],
            ];
        }

        if ($existing) {
            $stmt = $this->pdo->prepare('UPDATE campaign_department_campaign_reports SET report_title=:title, report_type=:type, report_date=:report_date, location=:location, description=:description, original_file_name=:original, stored_file_name=:stored, file_path=:path, mime_type=:mime, file_size=:size, updated_at=CURRENT_TIMESTAMP WHERE id=:rid AND campaign_id=:cid');
            $stmt->execute([
                'title'=>$title,'type'=>$type,'report_date'=>$reportDate,'location'=>$location,'description'=>$description,
                'original'=>$fileMeta['original_file_name'],'stored'=>$fileMeta['stored_file_name'],'path'=>$fileMeta['file_path'],'mime'=>$fileMeta['mime_type'] ?? null,'size'=>$fileMeta['file_size'] ?? 0,
                'rid'=>$reportId,'cid'=>$campaignId
            ]);
        } else {
            $stmt = $this->pdo->prepare('INSERT INTO campaign_department_campaign_reports (campaign_id,report_title,report_type,report_date,location,description,original_file_name,stored_file_name,file_path,mime_type,file_size,uploaded_by) VALUES (:cid,:title,:type,:report_date,:location,:description,:original,:stored,:path,:mime,:size,:uid)');
            $stmt->execute([
                'cid'=>$campaignId,'title'=>$title,'type'=>$type,'report_date'=>$reportDate,'location'=>$location,'description'=>$description,
                'original'=>$fileMeta['original_file_name'],'stored'=>$fileMeta['stored_file_name'],'path'=>$fileMeta['file_path'],'mime'=>$fileMeta['mime_type'] ?? null,'size'=>$fileMeta['file_size'] ?? 0,'uid'=>$user['id'] ?? null
            ]);
            $reportId = (int)$this->pdo->lastInsertId();
        }

        $stmt = $this->pdo->prepare('SELECT * FROM campaign_department_campaign_reports WHERE id = :rid');
        $stmt->execute(['rid' => $reportId]);
        return ['message' => $existing ? 'Report updated' : 'Report uploaded', 'data' => $stmt->fetch(PDO::FETCH_ASSOC)];
    }

    public function deleteManualReport(?array $user, array $params = []): array
    {
        if (!$user) {
            http_response_code(401);
            return ['error' => 'Authentication required'];
        }
        $campaignId = (int)($params['id'] ?? 0);
        $reportId = (int)($params['rid'] ?? 0);
        $this->findCampaign($campaignId);
        $stmt = $this->pdo->prepare('SELECT file_path FROM campaign_department_campaign_reports WHERE id=:rid AND campaign_id=:cid LIMIT 1');
        $stmt->execute(['rid'=>$reportId,'cid'=>$campaignId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$row) {
            http_response_code(404);
            return ['error' => 'Report not found'];
        }
        $stmt = $this->pdo->prepare('DELETE FROM campaign_department_campaign_reports WHERE id=:rid AND campaign_id=:cid');
        $stmt->execute(['rid'=>$reportId,'cid'=>$campaignId]);
        $path = dirname(__DIR__, 2) . '/' . ltrim((string)$row['file_path'], '/');
        if (is_file($path)) @unlink($path);
        return ['message' => 'Report deleted'];
    }

}


