<?php

declare(strict_types=1);

namespace App\Controllers;

use PDO;
use RuntimeException;
use App\Middleware\RoleMiddleware;

class EventController
{
    public function __construct(
        private PDO $pdo,
        private string $jwtSecret,
        private string $jwtIssuer,
        private string $jwtAudience,
        private int $jwtExpirySeconds
    ) {
        // Auto-migration: Ensure status ENUM includes 'archived' and audit log table exists
        $this->ensureArchivedStatus();
        $this->ensureAuditLogTable();
    }
    
    private function ensureArchivedStatus(): void
    {
        try {
            $checkStmt = $this->pdo->query("SELECT COLUMN_TYPE FROM INFORMATION_SCHEMA.COLUMNS 
                WHERE TABLE_SCHEMA = DATABASE() 
                AND TABLE_NAME = 'campaign_department_events' 
                AND COLUMN_NAME = 'status'");
            $columnType = $checkStmt->fetchColumn();
            
            if ($columnType && strpos($columnType, 'archived') === false) {
                error_log('EventController: Updating status column to include archived');
                $this->pdo->exec("ALTER TABLE `campaign_department_events` 
                    MODIFY COLUMN `status` ENUM('scheduled','ongoing','completed','cancelled','archived') NOT NULL DEFAULT 'scheduled'");
            }
        } catch (\Throwable $e) {
            error_log('EventController::ensureArchivedStatus error: ' . $e->getMessage());
        }
    }
    
    private function ensureAuditLogTable(): void
    {
        try {
            // Check if audit log table exists
            $checkTable = $this->pdo->query("SHOW TABLES LIKE 'campaign_department_event_audit_log'");
            if ($checkTable->rowCount() === 0) {
                error_log('EventController: Creating event_audit_log table');
                $this->pdo->exec("
                    CREATE TABLE IF NOT EXISTS `campaign_department_event_audit_log` (
                        id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                        event_id INT UNSIGNED NOT NULL,
                        user_id INT UNSIGNED NULL,
                        action_type VARCHAR(50) NOT NULL,
                        field_name VARCHAR(100) NULL,
                        old_value TEXT NULL,
                        new_value TEXT NULL,
                        change_details TEXT NULL,
                        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                        INDEX idx_event_audit_event (event_id),
                        INDEX idx_event_audit_action (action_type)
                    ) ENGINE=InnoDB
                ");
            } else {
                // Check if action_type column exists
                $checkCol = $this->pdo->query("SELECT COUNT(*) as cnt FROM INFORMATION_SCHEMA.COLUMNS 
                    WHERE TABLE_SCHEMA = DATABASE() 
                    AND TABLE_NAME = 'campaign_department_event_audit_log' 
                    AND COLUMN_NAME = 'action_type'");
                $hasCol = $checkCol->fetch(\PDO::FETCH_ASSOC)['cnt'] > 0;
                
                if (!$hasCol) {
                    error_log('EventController: Adding action_type column to audit_log');
                    $this->pdo->exec("ALTER TABLE `campaign_department_event_audit_log` 
                        ADD COLUMN `action_type` VARCHAR(50) NOT NULL DEFAULT 'updated' AFTER `user_id`");
                }
            }
        } catch (\Throwable $e) {
            error_log('EventController::ensureAuditLogTable error: ' . $e->getMessage());
        }
    }

    /**
     * List events with filtering and pagination
     * Role: All authenticated users can view finalized events
     */
    public function index(?array $user, array $params = []): array
    {
        try {
            $filters = [];
            $where = [];
            $queryParams = [];

            // Check if table exists first
            try {
                $checkTable = $this->pdo->query("SHOW TABLES LIKE 'campaign_department_events'");
                if ($checkTable->rowCount() === 0) {
                    error_log('EventController::index - Table campaign_department_events does not exist');
                    return ['data' => []];
                }
            } catch (\PDOException $e) {
                error_log('EventController::index - Error checking table existence: ' . $e->getMessage());
                return ['data' => []];
            }

            // Apply filters
            if (isset($_GET['date'])) {
                $where[] = 'e.event_date = :filter_date';
                $queryParams['filter_date'] = $_GET['date'];
            }
            if (isset($_GET['campaign_id'])) {
                $where[] = 'e.linked_campaign_id = :filter_campaign_id';
                $queryParams['filter_campaign_id'] = (int) $_GET['campaign_id'];
            }
            if (isset($_GET['event_type'])) {
                $where[] = 'e.event_type = :filter_event_type';
                $queryParams['filter_event_type'] = $_GET['event_type'];
            }
            if (isset($_GET['event_status']) && $_GET['event_status'] !== '') {
                $where[] = 'e.status = :filter_event_status';
                $queryParams['filter_event_status'] = $_GET['event_status'];
            } else {
                // By default, exclude archived events unless specifically requested
                $where[] = "e.status != 'archived'";
            }
            if (isset($_GET['hazard_focus'])) {
                $where[] = 'e.hazard_focus = :filter_hazard_focus';
                $queryParams['filter_hazard_focus'] = $_GET['hazard_focus'];
            }

            // Role-based filtering: Viewers only see finalized events, LGU roles see all
            $userRole = null;
            try {
                $userRole = $user ? RoleMiddleware::getUserRole($user, $this->pdo) : null;
            } catch (\Throwable $e) {
                error_log('EventController::index - Error getting user role: ' . $e->getMessage());
            }
            $userRoleName = $userRole ? strtolower($userRole) : '';
            $isViewer = in_array($userRoleName, ['viewer', 'partner'], true);
            $isLGUStaff = in_array($userRoleName, ['admin', 'staff', 'secretary', 'kagawad', 'captain', 'barangay administrator', 'barangay staff', 'system_admin', 'barangay_admin', 'campaign_creator'], true);
            
            // Viewers can only see ongoing/completed events (read-only)
            if ($isViewer && !$isLGUStaff) {
                $where[] = "e.status IN ('ongoing', 'completed')";
            }

            $whereClause = !empty($where) ? 'WHERE ' . implode(' AND ', $where) : '';

            $sql = "
                SELECT 
                    e.id as event_id,
                    e.name as event_title,
                    e.name as event_name,
                    e.event_type,
                    e.description as event_description,
                    e.event_date as date,
                    e.event_time as start_time,
                    e.event_time as end_time,
                    e.venue,
                    e.location,
                    e.status as event_status,
                    e.created_at,
                    c.title as campaign_title
                FROM `campaign_department_events` e
                LEFT JOIN `campaign_department_campaigns` c ON c.id = e.linked_campaign_id
                {$whereClause}
                ORDER BY e.event_date DESC, e.event_time DESC
                LIMIT 100
            ";

            try {
                error_log('EventController::index SQL: ' . $sql);
                error_log('EventController::index Params: ' . json_encode($queryParams));
                
                $stmt = $this->pdo->prepare($sql);
                if ($stmt === false) {
                    throw new \RuntimeException('Failed to prepare SQL statement');
                }
                $stmt->execute($queryParams);
                $events = $stmt->fetchAll(PDO::FETCH_ASSOC);
                
                error_log('EventController::index Events count: ' . count($events));
                error_log('EventController::index Events: ' . json_encode($events));

                // Format dates and times
                foreach ($events as &$event) {
                    if ($event['date']) {
                        $event['date_formatted'] = date('Y-m-d', strtotime($event['date']));
                    }
                    if ($event['start_time']) {
                        $event['start_time_formatted'] = date('H:i', strtotime($event['start_time']));
                    }
                    if ($event['end_time']) {
                        $event['end_time_formatted'] = date('H:i', strtotime($event['end_time']));
                    }
                }

                return ['data' => $events ?: []];
            } catch (\PDOException $e) {
                error_log('EventController::index PDO error: ' . $e->getMessage());
                error_log('EventController::index SQL: ' . $sql);
                return ['data' => []];
            }
        } catch (\Throwable $e) {
            error_log('EventController::index error: ' . $e->getMessage());
            error_log('EventController::index stack: ' . $e->getTraceAsString());
            return ['data' => []];
        }
    }

    /**
     * Get single event details with all related data
     */
    public function show(?array $user, array $params = []): array
    {
        // RBAC: All authenticated users can view events (read access)
        if (!$user) {
            http_response_code(401);
            return ['error' => 'Authentication required'];
        }
        
        $eventId = (int) ($params['id'] ?? 0);
        if ($eventId <= 0) {
            http_response_code(422);
            return ['error' => 'Invalid event ID'];
        }

        // Get event
        $stmt = $this->pdo->prepare('
            SELECT 
                e.id,
                e.name,
                e.name as event_name,
                e.name as event_title,
                e.event_type,
                e.description,
                e.description as event_description,
                e.event_date,
                e.event_date as date,
                e.event_time,
                e.event_time as start_time,
                e.venue,
                e.location,
                e.status,
                e.status as event_status,
                e.campaign_id,
                e.linked_campaign_id,
                e.hazard_focus,
                e.target_audience_profile_id,
                e.transport_requirements,
                e.trainer_requirements,
                e.equipment_requirements,
                e.volunteer_requirements,
                e.post_event_notes,
                e.starts_at,
                e.ends_at,
                e.created_at,
                c.title as campaign_title
            FROM `campaign_department_events` e
            LEFT JOIN `campaign_department_campaigns` c ON c.id = e.linked_campaign_id
            WHERE e.id = :id
        ');
        $stmt->execute(['id' => $eventId]);
        $event = $stmt->fetch(PDO::FETCH_ASSOC);
        
        // Add end_time calculation from starts_at/ends_at if available
        if ($event && $event['ends_at']) {
            $event['end_time'] = date('H:i', strtotime($event['ends_at']));
        }

        if (!$event) {
            http_response_code(404);
            return ['error' => 'Event not found'];
        }

        // Get facilitators
        $stmt = $this->pdo->prepare('
            SELECT u.id, u.name, u.email
            FROM `campaign_department_event_facilitators` ef
            JOIN `campaign_department_users` u ON u.id = ef.user_id
            WHERE ef.event_id = :id
        ');
        $stmt->execute(['id' => $eventId]);
        $facilitators = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Get audience segments
        $stmt = $this->pdo->prepare('
            SELECT a.id, a.segment_name as name, a.risk_level
            FROM `campaign_department_event_audience_segments` eas
            JOIN `campaign_department_audience_segments` a ON a.id = eas.segment_id
            WHERE eas.event_id = :id
        ');
        $stmt->execute(['id' => $eventId]);
        $audienceSegments = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Get agency coordination
        $stmt = $this->pdo->prepare('
            SELECT * FROM `campaign_department_event_agency_coordination`
            WHERE event_id = :id
            ORDER BY agency_type, requested_at
        ');
        $stmt->execute(['id' => $eventId]);
        $agencyCoordination = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Get conflicts
        $stmt = $this->pdo->prepare('
            SELECT * FROM `campaign_department_event_conflicts`
            WHERE event_id = :id AND resolved = FALSE
        ');
        $stmt->execute(['id' => $eventId]);
        $conflicts = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Get attendance summary
        $stmt = $this->pdo->prepare('
            SELECT 
                COUNT(*) as total_attendance,
                SUM(CASE WHEN checkin_method = "QR" THEN 1 ELSE 0 END) as qr_checkins,
                SUM(CASE WHEN checkin_method = "manual" THEN 1 ELSE 0 END) as manual_checkins
            FROM `campaign_department_attendance`
            WHERE event_id = :id
        ');
        $stmt->execute(['id' => $eventId]);
        $attendanceSummary = $stmt->fetch(PDO::FETCH_ASSOC);

        return [
            'event' => $event,
            'facilitators' => $facilitators,
            'audience_segments' => $audienceSegments,
            'agency_coordination' => $agencyCoordination,
            'conflicts' => $conflicts,
            'attendance_summary' => $attendanceSummary ?: ['total_attendance' => 0, 'qr_checkins' => 0, 'manual_checkins' => 0]
        ];
    }

    /**
     * Create new event
     * Role: Admin, Campaign Manager
     */
    public function store(?array $user, array $params = []): array
    {
        // Check permissions
        if (!$user) {
            http_response_code(401);
            return ['error' => 'Authentication required'];
        }

        $userRole = RoleMiddleware::getUserRole($user, $this->pdo);
        $userRoleName = $userRole ? strtolower($userRole) : '';
        
        // Viewer is read-only
        if ($userRoleName === 'viewer') {
            http_response_code(403);
            return ['error' => 'Viewer role is read-only. You cannot create events.'];
        }
        
        // Allowed roles: admin, staff, secretary, kagawad, captain (and legacy roles for compatibility)
        $allowedRoles = ['admin', 'staff', 'secretary', 'kagawad', 'captain', 'barangay administrator', 'barangay staff', 'system_admin', 'barangay_admin', 'campaign_creator'];
        if (!$userRole || !in_array($userRoleName, $allowedRoles, true)) {
            http_response_code(403);
            return ['error' => 'Insufficient permissions. Only authorized LGU personnel can create events.'];
        }

        $input = json_decode(file_get_contents('php://input'), true) ?? [];
        
        // Extract all fields
        $eventTitle = trim($input['event_title'] ?? $input['event_name'] ?? $input['name'] ?? '');
        $eventType = $input['event_type'] ?? 'seminar';
        $eventDescription = $input['event_description'] ?? $input['description'] ?? null;
        $hazardFocus = $input['hazard_focus'] ?? null;
        $targetAudienceProfileId = isset($input['target_audience_profile_id']) ? (int) $input['target_audience_profile_id'] : null;
        $linkedCampaignId = isset($input['linked_campaign_id']) ? (int) $input['linked_campaign_id'] : null;
        $date = $input['date'] ?? $input['event_date'] ?? null;
        $startTime = $input['start_time'] ?? $input['event_time'] ?? null;
        $endTime = $input['end_time'] ?? null;
        $venue = $input['venue'] ?? $input['location'] ?? null;
        $location = $input['location'] ?? null;
        $eventStatus = $input['event_status'] ?? $input['status'] ?? 'scheduled';
        $transportRequirements = $input['transport_requirements'] ?? null;
        $trainerRequirements = $input['trainer_requirements'] ?? null;
        $equipmentRequirements = $input['equipment_requirements'] ?? null;
        $volunteerRequirements = $input['volunteer_requirements'] ?? null;

        // Validation
        if (!$eventTitle) {
            http_response_code(422);
            return ['error' => 'event_title is required'];
        }

        if (!in_array($eventType, ['seminar', 'drill', 'workshop', 'orientation', 'meeting', 'other'], true)) {
            http_response_code(422);
            return ['error' => 'Invalid event_type'];
        }

        // Database ENUM only allows: scheduled, ongoing, completed, cancelled
        if (!in_array($eventStatus, ['scheduled', 'ongoing', 'completed', 'cancelled'], true)) {
            http_response_code(422);
            return ['error' => 'Invalid event_status. Must be: scheduled, ongoing, completed, or cancelled'];
        }

        // Build starts_at and ends_at from date and times
        $startsAt = null;
        $endsAt = null;
        if ($date && $startTime) {
            $startsAt = $date . ' ' . $startTime . ':00';
        }
        if ($date && $endTime) {
            $endsAt = $date . ' ' . $endTime . ':00';
        }

        // Check for conflicts (but don't block creation, just warn)
        $conflicts = $this->checkConflicts($date, $startTime, $endTime, $venue, null);

        // Start transaction
        $this->pdo->beginTransaction();
        try {
            // Insert event - only use columns that exist in database schema
            $stmt = $this->pdo->prepare('
                INSERT INTO `campaign_department_events` (
                    campaign_id, linked_campaign_id, name, event_type, description,
                    event_date, event_time, venue, location, status, starts_at, ends_at
                ) VALUES (
                    :campaign_id, :linked_campaign_id, :name, :event_type, :description,
                    :event_date, :event_time, :venue, :location, :status, :starts_at, :ends_at
                )
            ');
            $stmt->execute([
                'campaign_id' => $linkedCampaignId ?: null,
                'linked_campaign_id' => $linkedCampaignId ?: null,
                'name' => $eventTitle,
                'event_type' => $eventType,
                'description' => $eventDescription,
                'event_date' => $date,
                'event_time' => $startTime,
                'venue' => $venue,
                'location' => $location ?: $venue,
                'status' => $eventStatus,
                'starts_at' => $startsAt,
                'ends_at' => $endsAt,
            ]);

            $eventId = (int) $this->pdo->lastInsertId();

            // Note: Facilitators and segments tables don't exist in current schema
            // These features can be added later when the tables are created

            $this->pdo->commit();

            return [
                'id' => $eventId,
                'event_id' => $eventId,
                'message' => 'Event created successfully',
                'conflicts' => $conflicts,
                'warning' => !empty($conflicts) ? 'Scheduling conflicts detected - see conflicts array' : null
            ];
        } catch (\Exception $e) {
            $this->pdo->rollBack();
            http_response_code(500);
            return ['error' => 'Failed to create event: ' . $e->getMessage()];
        }
    }

    /**
     * Update event
     * Role: Admin, Campaign Manager (for their own events)
     */
    public function update(?array $user, array $params = []): array
    {
        if (!$user) {
            http_response_code(401);
            return ['error' => 'Authentication required'];
        }

        $eventId = (int) ($params['id'] ?? 0);
        $event = $this->findEvent($eventId);

        // RBAC: Check permissions - viewer cannot update
        $userRole = RoleMiddleware::getUserRole($user, $this->pdo);
        $userRoleName = $userRole ? strtolower($userRole) : '';
        
        // Viewer is read-only
        if ($userRoleName === 'viewer') {
            http_response_code(403);
            return ['error' => 'Viewer role is read-only. You cannot update events.'];
        }
        
        $isAdmin = in_array($userRoleName, ['admin', 'system_admin', 'barangay_admin', 'barangay administrator'], true);
        $isOwner = $event['created_by'] == $user['id'];
        $allowedRoles = ['admin', 'staff', 'secretary', 'kagawad', 'captain', 'barangay administrator', 'barangay staff', 'system_admin', 'barangay_admin', 'campaign_creator'];
        $hasAllowedRole = in_array($userRoleName, $allowedRoles, true);

        if (!$isAdmin && !$isOwner && !$hasAllowedRole) {
            http_response_code(403);
            return ['error' => 'Insufficient permissions to update this event'];
        }

        $input = json_decode(file_get_contents('php://input'), true) ?? [];
        
        // Build update query dynamically
        $updates = [];
        $updateParams = ['event_id' => $eventId];
        $oldValues = [];

        // Map frontend field names to database column names
        $fieldMapping = [
            'event_title' => 'name',
            'event_name' => 'name',
            'event_type' => 'event_type',
            'event_description' => 'description',
            'hazard_focus' => 'hazard_focus',
            'target_audience_profile_id' => 'target_audience_profile_id',
            'linked_campaign_id' => 'linked_campaign_id',
            'date' => 'event_date',
            'start_time' => 'event_time',
            'venue' => 'venue',
            'location' => 'location',
            'event_status' => 'status',
            'transport_requirements' => 'transport_requirements',
            'trainer_requirements' => 'trainer_requirements',
            'equipment_requirements' => 'equipment_requirements',
            'volunteer_requirements' => 'volunteer_requirements',
            'post_event_notes' => 'post_event_notes'
        ];

        foreach ($fieldMapping as $inputField => $dbColumn) {
            if (isset($input[$inputField])) {
                $oldValue = $event[$dbColumn] ?? $event[$inputField] ?? null;
                $newValue = $input[$inputField];
                
                if ($oldValue != $newValue) {
                    $updates[] = "{$dbColumn} = :{$dbColumn}";
                    $updateParams[$dbColumn] = $newValue;
                    $oldValues[$inputField] = $oldValue;
                }
            }
        }

        // Handle starts_at and ends_at
        if (isset($input['date']) || isset($input['start_time'])) {
            $date = $input['date'] ?? $event['event_date'] ?? $event['date'];
            $startTime = $input['start_time'] ?? $event['event_time'] ?? $event['start_time'];
            if ($date && $startTime) {
                $startsAt = $date . ' ' . $startTime . ':00';
                $updates[] = "starts_at = :starts_at";
                $updateParams['starts_at'] = $startsAt;
            }
        }
        if (isset($input['date']) || isset($input['end_time'])) {
            $date = $input['date'] ?? $event['event_date'] ?? $event['date'];
            $endTime = $input['end_time'] ?? null;
            if ($date && $endTime) {
                $endsAt = $date . ' ' . $endTime . ':00';
                $updates[] = "ends_at = :ends_at";
                $updateParams['ends_at'] = $endsAt;
            }
        }

        if (empty($updates)) {
            return ['message' => 'No changes detected', 'event_id' => $eventId];
        }

        // Check for conflicts if date/time/venue changed
        if (isset($input['date']) || isset($input['start_time']) || isset($input['venue'])) {
            $checkDate = $input['date'] ?? $event['date'];
            $checkStartTime = $input['start_time'] ?? $event['start_time'];
            $checkEndTime = $input['end_time'] ?? $event['end_time'];
            $checkVenue = $input['venue'] ?? $event['venue'];
            $conflicts = $this->checkConflicts($checkDate, $checkStartTime, $checkEndTime, $checkVenue, $eventId);
            if (!empty($conflicts)) {
                return [
                    'error' => 'Scheduling conflicts detected',
                    'conflicts' => $conflicts,
                    'warning' => 'Update can still proceed, but conflicts exist'
                ];
            }
        }

        $this->pdo->beginTransaction();
        try {
            $sql = 'UPDATE `campaign_department_events` SET ' . implode(', ', $updates) . ' WHERE id = :event_id';
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($updateParams);

            // Log audit for each changed field
            foreach ($oldValues as $field => $oldValue) {
                $this->logAudit($eventId, $user['id'], 'updated', $field, $oldValue, $updateParams[$field] ?? null);
            }

            // Handle status change
            if (isset($input['event_status']) && $input['event_status'] != $event['event_status']) {
                $this->logAudit($eventId, $user['id'], 'status_changed', 'event_status', $event['event_status'], $input['event_status']);
            }

            $this->pdo->commit();

            return ['message' => 'Event updated successfully', 'event_id' => $eventId];
        } catch (\Exception $e) {
            $this->pdo->rollBack();
            http_response_code(500);
            return ['error' => 'Failed to update event: ' . $e->getMessage()];
        }
    }

    /**
     * Check for scheduling conflicts (public endpoint)
     */
    public function checkConflictsEndpoint(?array $user, array $params = []): array
    {
        $date = $_GET['date'] ?? null;
        $startTime = $_GET['start_time'] ?? null;
        $endTime = $_GET['end_time'] ?? null;
        $venue = $_GET['venue'] ?? null;
        $excludeEventId = isset($_GET['exclude_event_id']) ? (int) $_GET['exclude_event_id'] : null;
        
        $conflicts = $this->checkConflicts($date, $startTime, $endTime, $venue, $excludeEventId);
        
        return ['conflicts' => $conflicts];
    }

    /**
     * Check for scheduling conflicts (private helper)
     */
    private function checkConflicts(?string $date, ?string $startTime, ?string $endTime, ?string $venue, ?int $excludeEventId = null): array
    {
        $conflicts = [];

        if (!$date || !$startTime) {
            return $conflicts;
        }

        $where = ['event_date = :date', 'status NOT IN ("cancelled", "completed")'];
        $params = ['date' => $date];

        if ($excludeEventId) {
            $where[] = 'id != :exclude_id';
            $params['exclude_id'] = $excludeEventId;
        }

        // Check venue conflicts
        if ($venue) {
            $where[] = 'venue = :venue';
            $params['venue'] = $venue;
        }

        $sql = 'SELECT id, name as event_title, name as event_name, event_time as start_time, event_time as end_time, venue FROM `campaign_department_events` WHERE ' . implode(' AND ', $where);
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        $existingEvents = $stmt->fetchAll(PDO::FETCH_ASSOC);

        foreach ($existingEvents as $existing) {
            $existingStart = strtotime($existing['start_time']);
            $existingEnd = $existing['end_time'] ? strtotime($existing['end_time']) : $existingStart + 3600;
            $newStart = strtotime($startTime);
            $newEnd = $endTime ? strtotime($endTime) : $newStart + 3600;

            // Check time overlap
            if (($newStart >= $existingStart && $newStart < $existingEnd) ||
                ($newEnd > $existingStart && $newEnd <= $existingEnd) ||
                ($newStart <= $existingStart && $newEnd >= $existingEnd)) {
                $conflicts[] = [
                    'type' => $venue ? 'venue_and_time' : 'time',
                    'conflicting_event_id' => $existing['id'],
                    'conflicting_event_name' => $existing['event_title'] ?? $existing['event_name'],
                    'message' => 'Time conflict with event: ' . ($existing['event_title'] ?? $existing['event_name'])
                ];
            }
        }

        return $conflicts;
    }

    /**
     * Add agency coordination request
     */
    public function addAgencyCoordination(?array $user, array $params = []): array
    {
        if (!$user) {
            http_response_code(401);
            return ['error' => 'Authentication required'];
        }

        $eventId = (int) ($params['id'] ?? 0);
        $this->findEvent($eventId);

        $input = json_decode(file_get_contents('php://input'), true) ?? [];
        $agencyType = $input['agency_type'] ?? null;
        $agencyName = trim($input['agency_name'] ?? '');
        $requestDetails = $input['request_details'] ?? null;

        if (!$agencyType || !$agencyName) {
            http_response_code(422);
            return ['error' => 'agency_type and agency_name are required'];
        }

        $allowedTypes = ['police', 'fire', 'medical', 'rescue', 'other'];
        if (!in_array($agencyType, $allowedTypes, true)) {
            http_response_code(422);
            return ['error' => 'Invalid agency_type'];
        }

        $stmt = $this->pdo->prepare('
            INSERT INTO `campaign_department_event_agency_coordination` (
                event_id, agency_type, agency_name, status, request_details
            ) VALUES (
                :event_id, :agency_type, :agency_name, "pending", :request_details
            )
        ');
        $stmt->execute([
            'event_id' => $eventId,
            'agency_type' => $agencyType,
            'agency_name' => $agencyName,
            'request_details' => $requestDetails
        ]);

        $coordinationId = (int) $this->pdo->lastInsertId();

        // Log audit
        $this->logAudit($eventId, $user['id'], 'agency_coordinated', null, null, "Agency coordination added: {$agencyName}");

        // Create integration checkpoint
        $this->createIntegrationCheckpoint($eventId, $this->mapAgencyTypeToSubsystem($agencyType));

        return [
            'id' => $coordinationId,
            'message' => 'Agency coordination request created'
        ];
    }

    /**
     * Update agency coordination status
     */
    public function updateAgencyCoordination(?array $user, array $params = []): array
    {
        if (!$user) {
            http_response_code(401);
            return ['error' => 'Authentication required'];
        }

        $coordinationId = (int) ($params['coordination_id'] ?? 0);
        $input = json_decode(file_get_contents('php://input'), true) ?? [];
        $status = $input['status'] ?? null;
        $confirmationDetails = $input['confirmation_details'] ?? null;
        $fulfillmentDetails = $input['fulfillment_details'] ?? null;

        if (!$status) {
            http_response_code(422);
            return ['error' => 'status is required'];
        }

        $allowedStatuses = ['pending', 'confirmed', 'declined', 'completed'];
        if (!in_array($status, $allowedStatuses, true)) {
            http_response_code(422);
            return ['error' => 'Invalid status'];
        }

        $updates = ['status = :status'];
        $updateParams = ['id' => $coordinationId, 'status' => $status];

        if ($status === 'confirmed' && $confirmationDetails) {
            $updates[] = 'confirmation_details = :confirmation_details';
            $updates[] = 'confirmed_at = NOW()';
            $updateParams['confirmation_details'] = $confirmationDetails;
        }

        if ($status === 'fulfilled' && $fulfillmentDetails) {
            $updates[] = 'fulfillment_details = :fulfillment_details';
            $updates[] = 'fulfilled_at = NOW()';
            $updateParams['fulfillment_details'] = $fulfillmentDetails;
        }

        $stmt = $this->pdo->prepare('
            UPDATE `campaign_department_event_agency_coordination` 
            SET ' . implode(', ', $updates) . '
            WHERE id = :id
        ');
        $stmt->execute($updateParams);

        // Get event_id for audit
        $stmt = $this->pdo->prepare('SELECT event_id FROM `campaign_department_event_agency_coordination` WHERE id = :id');
        $stmt->execute(['id' => $coordinationId]);
        $coordination = $stmt->fetch();
        if ($coordination) {
            $this->logAudit($coordination['event_id'], $user['id'], 'agency_coordinated', null, null, "Agency coordination status updated to: {$status}");
        }

        return ['message' => 'Agency coordination updated successfully'];
    }

    /**
     * Calendar view endpoint
     */
    public function calendar(?array $user, array $params = []): array
    {
        $startDate = $_GET['start'] ?? date('Y-m-01');
        $endDate = $_GET['end'] ?? date('Y-m-t');

        $stmt = $this->pdo->prepare('
            SELECT 
                id as event_id,
                name as event_title,
                name as event_name,
                event_type,
                status as event_status,
                hazard_focus,
                event_date as date,
                event_time as start_time,
                event_time as end_time,
                venue
            FROM `campaign_department_events`
            WHERE event_date BETWEEN :start_date AND :end_date
            AND status NOT IN ("cancelled")
            ORDER BY event_date, event_time
        ');
        $stmt->execute(['start_date' => $startDate, 'end_date' => $endDate]);
        $events = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Format for calendar
        $calendarEvents = [];
        foreach ($events as $event) {
            $start = $event['date'] . 'T' . ($event['start_time'] ?? '00:00:00');
            $end = $event['date'] . 'T' . ($event['end_time'] ?? $event['start_time'] ?? '23:59:59');
            
            $calendarEvents[] = [
                'id' => $event['event_id'],
                'title' => $event['event_title'] ?? $event['event_name'],
                'start' => $start,
                'end' => $end,
                'type' => $event['event_type'],
                'status' => $event['event_status'],
                'hazard_focus' => $event['hazard_focus'],
                'venue' => $event['venue']
            ];
        }

        return ['events' => $calendarEvents];
    }

    /**
     * Attendance tracking
     */
    public function attendance(?array $user, array $params = []): array
    {
        $eventId = (int) ($params['id'] ?? 0);
        $event = $this->findEvent($eventId);

        $input = json_decode(file_get_contents('php://input'), true) ?? [];
        $audienceMemberId = isset($input['audience_member_id']) ? (int) $input['audience_member_id'] : null;
        $fullName = $input['full_name'] ?? null;
        $contact = $input['contact'] ?? null;
        $checkinMethod = $input['checkin_method'] ?? 'manual';

        if (!$audienceMemberId && !$fullName) {
            http_response_code(422);
            return ['error' => 'audience_member_id or full_name is required'];
        }

        if (!$audienceMemberId) {
            $ins = $this->pdo->prepare('INSERT INTO `campaign_department_audience_members` (segment_id, full_name, contact, channel) VALUES (NULL, :full_name, :contact, :channel)');
            $ins->execute([
                'full_name' => $fullName,
                'contact' => $contact ?: null,
                'channel' => 'other',
            ]);
            $audienceMemberId = (int) $this->pdo->lastInsertId();
        }

        $stmt = $this->pdo->prepare('
            INSERT INTO `campaign_department_attendance` (event_id, audience_member_id, participant_identifier, checkin_method, checkin_timestamp, check_in) 
            VALUES (:event_id, :audience_member_id, :participant_identifier, :checkin_method, NOW(), 1)
        ');
        $stmt->execute([
            'event_id' => $event['id'],
            'audience_member_id' => $audienceMemberId,
            'participant_identifier' => $fullName,
            'checkin_method' => $checkinMethod
        ]);

        // Update attendance count
        $this->pdo->prepare('
            UPDATE `campaign_department_events` 
            SET attendance_count = (SELECT COUNT(*) FROM `campaign_department_attendance` WHERE event_id = :id)
            WHERE id = :id
        ')->execute(['id' => $event['id']]);

        return [
            'message' => 'Check-in recorded',
            'attendance_id' => (int) $this->pdo->lastInsertId(),
            'audience_member_id' => $audienceMemberId,
        ];
    }

    /**
     * Get attendance list for an event
     */
    public function getAttendance(?array $user, array $params = []): array
    {
        $eventId = (int) ($params['id'] ?? 0);
        $this->findEvent($eventId);

        $stmt = $this->pdo->prepare('
            SELECT 
                a.id as attendance_id,
                a.participant_identifier,
                a.checkin_method,
                a.checkin_timestamp,
                am.full_name,
                am.contact
            FROM `campaign_department_attendance` a
            LEFT JOIN `campaign_department_audience_members` am ON am.id = a.audience_member_id
            WHERE a.event_id = :event_id
            ORDER BY a.checkin_timestamp DESC
        ');
        $stmt->execute(['event_id' => $eventId]);
        
        return ['data' => $stmt->fetchAll(PDO::FETCH_ASSOC)];
    }

    /**
     * Export CSV
     */
    public function exportCsv(?array $user, array $params = []): void
    {
        $eventId = (int) ($params['id'] ?? 0);
        $event = $this->findEvent($eventId);

        $stmt = $this->pdo->prepare('
            SELECT 
                a.id as attendance_id,
                COALESCE(am.full_name, a.participant_identifier) as full_name,
                am.contact,
                a.checkin_method,
                a.checkin_timestamp
            FROM `campaign_department_attendance` a
            LEFT JOIN `campaign_department_audience_members` am ON am.id = a.audience_member_id
            WHERE a.event_id = :eid
            ORDER BY a.checkin_timestamp ASC
        ');
        $stmt->execute(['eid' => $eventId]);

        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="attendance_event_' . $eventId . '.csv"');

        $out = fopen('php://output', 'w');
        fputcsv($out, ['id','full_name','contact','checkin_method','checkin_timestamp']);
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            fputcsv($out, $row);
        }
        fclose($out);
        exit;
    }

    /**
     * QR link generation
     */
    public function qrLink(?array $user, array $params = []): array
    {
        $eventId = (int) ($params['id'] ?? 0);
        $this->findEvent($eventId);
        $baseUrl = getenv('APP_URL') ?: ('http://' . ($_SERVER['HTTP_HOST'] ?? 'localhost'));
        $checkinUrl = rtrim($baseUrl, '/') . '/events/checkin.html?event_id=' . $eventId;

        return [
            'event_id' => $eventId,
            'checkin_url' => $checkinUrl,
            'qr_data' => $checkinUrl,
        ];
    }

    /**
     * Integration endpoints for future subsystems
     */
    public function integrationCheckpoint(?array $user, array $params = []): array
    {
        $eventId = (int) ($params['id'] ?? 0);
        $subsystemType = $params['subsystem'] ?? null;

        if (!$subsystemType) {
            http_response_code(422);
            return ['error' => 'subsystem parameter is required'];
        }

        $allowedSubsystems = ['law_enforcement', 'traffic_transport', 'fire_rescue', 'emergency_response', 'community_policing', 'target_audience'];
        if (!in_array($subsystemType, $allowedSubsystems, true)) {
            http_response_code(422);
            return ['error' => 'Invalid subsystem type'];
        }

        $this->findEvent($eventId);

        // Get event data for integration
        $event = $this->show($user, ['id' => $eventId]);

        // Update integration checkpoint
        $stmt = $this->pdo->prepare('
            INSERT INTO `campaign_department_event_integration_checkpoints` (
                event_id, subsystem_type, integration_status, sent_data, last_sync_at, sync_attempts
            ) VALUES (
                :event_id, :subsystem_type, "sent", :sent_data, NOW(), 1
            )
            ON DUPLICATE KEY UPDATE
                integration_status = "sent",
                sent_data = :sent_data,
                last_sync_at = NOW(),
                sync_attempts = sync_attempts + 1
        ');
        $stmt->execute([
            'event_id' => $eventId,
            'subsystem_type' => $subsystemType,
            'sent_data' => json_encode($event)
        ]);

        return [
            'message' => 'Integration checkpoint created',
            'event_id' => $eventId,
            'subsystem' => $subsystemType,
            'status' => 'sent'
        ];
    }

    // Helper methods

    private function findEvent(int $id): array
    {
        $stmt = $this->pdo->prepare('SELECT * FROM `campaign_department_events` WHERE id = :id LIMIT 1');
        $stmt->execute(['id' => $id]);
        $event = $stmt->fetch();
        if (!$event) {
            http_response_code(404);
            throw new RuntimeException('Event not found');
        }
        return $event;
    }

    private function logAudit(int $eventId, int $userId, string $actionType, ?string $fieldName, ?string $oldValue, ?string $newValue): void
    {
        try {
            $stmt = $this->pdo->prepare('
                INSERT INTO `campaign_department_event_audit_log` (
                    event_id, user_id, action_type, field_name, old_value, new_value, change_details
                ) VALUES (
                    :event_id, :user_id, :action_type, :field_name, :old_value, :new_value, :change_details
                )
            ');
            $changeDetails = $fieldName ? "Field {$fieldName} changed from '{$oldValue}' to '{$newValue}'" : null;
            $stmt->execute([
                'event_id' => $eventId,
                'user_id' => $userId,
                'action_type' => $actionType,
                'field_name' => $fieldName,
                'old_value' => $oldValue,
                'new_value' => $newValue,
                'change_details' => $changeDetails
            ]);
        } catch (\Throwable $e) {
            // Log error but don't break the main operation
            error_log('EventController::logAudit error: ' . $e->getMessage());
        }
    }

    private function createIntegrationCheckpoints(int $eventId): void
    {
        $subsystems = ['law_enforcement', 'traffic_transport', 'fire_rescue', 'emergency_response', 'community_policing', 'target_audience'];
        $stmt = $this->pdo->prepare('
            INSERT INTO `campaign_department_event_integration_checkpoints` (event_id, subsystem_type, integration_status)
            VALUES (:event_id, :subsystem_type, "pending")
        ');
        foreach ($subsystems as $subsystem) {
            $stmt->execute(['event_id' => $eventId, 'subsystem_type' => $subsystem]);
        }
    }

    private function createIntegrationCheckpoint(int $eventId, string $subsystemType): void
    {
        $stmt = $this->pdo->prepare('
            INSERT INTO `campaign_department_event_integration_checkpoints` (event_id, subsystem_type, integration_status)
            VALUES (:event_id, :subsystem_type, "pending")
            ON DUPLICATE KEY UPDATE integration_status = "pending"
        ');
        $stmt->execute(['event_id' => $eventId, 'subsystem_type' => $subsystemType]);
    }

    /**
     * Get incidents from Law Enforcement system (integration example)
     */
    public function getLawEnforcementIncidents(?array $user, array $params = []): array
    {
        if (!$user) {
            http_response_code(401);
            return ['error' => 'Unauthorized'];
        }

        try {
            $integrationService = new \App\Services\IntegrationService($this->pdo);
            
            // Check if events module has access to law_enforcement system
            if (!$integrationService->moduleHasAccess('events', 'law_enforcement', 'read')) {
                http_response_code(403);
                return ['error' => 'Events module does not have access to Law Enforcement system'];
            }

            // Try to get cached data first (faster)
            $cached = $integrationService->getCachedData('law_enforcement', 'incidents_to_events');
            
            if (!empty($cached)) {
                return [
                    'source' => 'cache',
                    'incidents' => array_map(function($item) {
                        return $item['data_json'];
                    }, $cached),
                    'count' => count($cached)
                ];
            }

            // If no cache, query external system
            // Option 1: Query external database
            try {
                $incidents = $integrationService->queryExternalDatabase(
                    'law_enforcement',
                    'SELECT incident_id, incident_type, location, reported_at, description, status 
                     FROM incidents 
                     WHERE status = :status AND reported_at >= :date 
                     ORDER BY reported_at DESC 
                     LIMIT 50',
                    [
                        'status' => 'active',
                        'date' => date('Y-m-d', strtotime('-30 days'))
                    ]
                );
                
                return [
                    'source' => 'database',
                    'incidents' => $incidents,
                    'count' => count($incidents)
                ];
            } catch (\RuntimeException $e) {
                // If database query fails, try API
                $incidents = $integrationService->queryExternalApi(
                    'law_enforcement',
                    'incidents',
                    'GET',
                    ['status' => 'active', 'date_from' => date('Y-m-d', strtotime('-30 days'))],
                    'events'
                );
                
                return [
                    'source' => 'api',
                    'incidents' => $incidents['data'] ?? $incidents,
                    'count' => count($incidents['data'] ?? $incidents)
                ];
            }
        } catch (\RuntimeException $e) {
            http_response_code(500);
            return ['error' => 'Failed to fetch incidents: ' . $e->getMessage()];
        }
    }

    /**
     * Sync incidents from Law Enforcement system to events
     */
    public function syncLawEnforcementIncidents(?array $user, array $params = []): array
    {
        if (!$user) {
            http_response_code(401);
            return ['error' => 'Unauthorized'];
        }

        try {
            $integrationService = new \App\Services\IntegrationService($this->pdo);
            
            $result = $integrationService->syncExternalData(
                'law_enforcement',
                'incidents_to_events',
                $user['id'] ?? null
            );
            
            return [
                'message' => 'Sync completed',
                'sync_result' => $result
            ];
        } catch (\RuntimeException $e) {
            http_response_code(500);
            return ['error' => 'Sync failed: ' . $e->getMessage()];
        }
    }

    /**
     * Delete an event
     */
    public function destroy(?array $user, array $params = []): array
    {
        // RBAC: Only authorized LGU roles can delete events (viewer cannot)
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
                return ['error' => 'Viewer role is read-only. You cannot delete events.'];
            }
            
            // Only admin and captain can delete events
            $allowedRoles = ['admin', 'captain', 'barangay administrator', 'system_admin', 'barangay_admin'];
            if (!$userRole || !in_array($userRoleName, $allowedRoles, true)) {
                http_response_code(403);
                return ['error' => 'Insufficient permissions. Only administrators and captains can delete events.'];
            }
        } catch (\Exception $e) {
            http_response_code(403);
            return ['error' => 'Access denied: ' . $e->getMessage()];
        }
        
        $id = (int) ($params['id'] ?? 0);
        
        // Check if event exists
        try {
            $checkTable = $this->pdo->query("SHOW TABLES LIKE 'campaign_department_events'");
            if ($checkTable->rowCount() === 0) {
                http_response_code(404);
                return ['error' => 'Events table does not exist'];
            }
        } catch (\PDOException $e) {
            http_response_code(500);
            return ['error' => 'Database error'];
        }
        
        $stmt = $this->pdo->prepare('SELECT id, name as event_title, status as event_status FROM campaign_department_events WHERE id = :id');
        $stmt->execute(['id' => $id]);
        $event = $stmt->fetch(\PDO::FETCH_ASSOC);
        
        if (!$event) {
            http_response_code(404);
            return ['error' => 'Event not found'];
        }
        
        // Only allow deletion of draft, cancelled, or archived events
        $status = strtolower($event['event_status'] ?? '');
        if (!in_array($status, ['draft', 'cancelled', 'archived'], true)) {
            http_response_code(422);
            return ['error' => 'Cannot delete events that are not in draft, cancelled, or archived status.'];
        }
        
        // Delete related records first (foreign key constraints)
        $this->pdo->beginTransaction();
        try {
            // Delete attendance records
            $stmt = $this->pdo->prepare('DELETE FROM `campaign_department_event_attendance` WHERE event_id = :id');
            $stmt->execute(['id' => $id]);
            
            // Delete agency coordination
            $stmt = $this->pdo->prepare('DELETE FROM `campaign_department_event_agency_coordination` WHERE event_id = :id');
            $stmt->execute(['id' => $id]);
            
            // Delete facilitators
            $stmt = $this->pdo->prepare('DELETE FROM `campaign_department_event_facilitators` WHERE event_id = :id');
            $stmt->execute(['id' => $id]);
            
            // Delete the event
            $stmt = $this->pdo->prepare('DELETE FROM `campaign_department_events` WHERE id = :id');
            $stmt->execute(['id' => $id]);
            
            $this->pdo->commit();
            
            return ['message' => 'Event deleted successfully'];
        } catch (\Throwable $e) {
            $this->pdo->rollBack();
            error_log('EventController::destroy - Error: ' . $e->getMessage());
            http_response_code(500);
            return ['error' => 'Failed to delete event: ' . $e->getMessage()];
        }
    }

    private function mapAgencyTypeToSubsystem(string $agencyType): string
    {
        $mapping = [
            'police' => 'law_enforcement',
            'fire_rescue' => 'fire_rescue',
            'traffic' => 'traffic_transport',
            'emergency_response' => 'emergency_response',
            'community_policing' => 'community_policing'
        ];
        return $mapping[$agencyType] ?? 'other';
    }
}
