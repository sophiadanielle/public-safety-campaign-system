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
    }

    /**
     * List events with filtering and pagination
     * Role: All authenticated users can view finalized events
     */
    public function index(?array $user, array $params = []): array
    {
        $filters = [];
        $where = [];
        $queryParams = [];

        // Apply filters
        if (isset($_GET['date'])) {
            $where[] = 'e.date = :filter_date';
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
        if (isset($_GET['event_status'])) {
            $where[] = 'e.event_status = :filter_event_status';
            $queryParams['filter_event_status'] = $_GET['event_status'];
        }
        if (isset($_GET['hazard_focus'])) {
            $where[] = 'e.hazard_focus = :filter_hazard_focus';
            $queryParams['filter_hazard_focus'] = $_GET['hazard_focus'];
        }

        // Role-based filtering: Viewers only see finalized events, LGU roles see all
        $userRole = $user ? RoleMiddleware::getUserRole($user, $this->pdo) : null;
        $userRoleName = $userRole ? strtolower($userRole) : '';
        $isViewer = in_array($userRoleName, ['viewer', 'partner'], true);
        $isLGUStaff = in_array($userRoleName, ['admin', 'staff', 'secretary', 'kagawad', 'captain', 'barangay administrator', 'barangay staff', 'system_admin', 'barangay_admin', 'campaign_creator'], true);
        
        // Viewers can only see confirmed/completed events (read-only)
        if ($isViewer && !$isLGUStaff) {
            $where[] = "e.event_status IN ('confirmed', 'completed')";
        }

        $whereClause = !empty($where) ? 'WHERE ' . implode(' AND ', $where) : '';

        $sql = "
            SELECT 
                e.id as event_id,
                e.event_title,
                e.event_name,
                e.event_type,
                e.event_description,
                e.hazard_focus,
                e.target_audience_profile_id,
                e.linked_campaign_id,
                e.date,
                e.start_time,
                e.end_time,
                e.venue,
                e.location,
                e.event_status,
                e.attendance_count,
                e.created_by,
                e.created_at,
                e.updated_at,
                c.title as campaign_title,
                a.name as audience_segment_name,
                a.risk_level as audience_risk_level
            FROM `campaign_department_events` e
            LEFT JOIN `campaign_department_campaigns` c ON c.id = e.linked_campaign_id
            LEFT JOIN `campaign_department_audience_segments` a ON a.id = e.target_audience_profile_id
            {$whereClause}
            ORDER BY e.date DESC, e.start_time DESC
            LIMIT 100
        ";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($queryParams);
        $events = $stmt->fetchAll(PDO::FETCH_ASSOC);

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

        return ['data' => $events];
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
                e.*,
                c.title as campaign_title,
                a.name as audience_segment_name,
                a.risk_level as audience_risk_level,
                u.name as created_by_name
            FROM `campaign_department_events` e
            LEFT JOIN `campaign_department_campaigns` c ON c.id = e.linked_campaign_id
            LEFT JOIN `campaign_department_audience_segments` a ON a.id = e.target_audience_profile_id
            LEFT JOIN `campaign_department_users` u ON u.id = e.created_by
            WHERE e.id = :id
        ');
        $stmt->execute(['id' => $eventId]);
        $event = $stmt->fetch(PDO::FETCH_ASSOC);

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
            SELECT a.id, a.name, a.risk_level
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
        $eventStatus = $input['event_status'] ?? $input['status'] ?? 'draft';
        $transportRequirements = $input['transport_requirements'] ?? null;
        $trainerRequirements = $input['trainer_requirements'] ?? null;
        $equipmentRequirements = $input['equipment_requirements'] ?? null;
        $volunteerRequirements = $input['volunteer_requirements'] ?? null;

        // Validation
        if (!$eventTitle) {
            http_response_code(422);
            return ['error' => 'event_title is required'];
        }

        if (!in_array($eventType, ['seminar', 'drill', 'workshop', 'orientation'], true)) {
            http_response_code(422);
            return ['error' => 'Invalid event_type'];
        }

        if (!in_array($eventStatus, ['draft', 'scheduled', 'confirmed', 'completed', 'cancelled'], true)) {
            http_response_code(422);
            return ['error' => 'Invalid event_status'];
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
            // Insert event
            $stmt = $this->pdo->prepare('
                INSERT INTO `campaign_department_events` (
                    event_title, event_name, event_type, event_description, hazard_focus,
                    target_audience_profile_id, linked_campaign_id, date, start_time, end_time,
                    venue, location, event_status, transport_requirements, trainer_requirements,
                    equipment_requirements, volunteer_requirements, created_by, starts_at, ends_at
                ) VALUES (
                    :event_title, :event_name, :event_type, :event_description, :hazard_focus,
                    :target_audience_profile_id, :linked_campaign_id, :date, :start_time, :end_time,
                    :venue, :location, :event_status, :transport_requirements, :trainer_requirements,
                    :equipment_requirements, :volunteer_requirements, :created_by, :starts_at, :ends_at
                )
            ');
            $stmt->execute([
                'event_title' => $eventTitle,
                'event_name' => $eventTitle, // Keep both for compatibility
                'event_type' => $eventType,
                'event_description' => $eventDescription,
                'hazard_focus' => $hazardFocus,
                'target_audience_profile_id' => $targetAudienceProfileId ?: null,
                'linked_campaign_id' => $linkedCampaignId ?: null,
                'date' => $date,
                'start_time' => $startTime,
                'end_time' => $endTime,
                'venue' => $venue,
                'location' => $location ?: $venue,
                'event_status' => $eventStatus,
                'transport_requirements' => $transportRequirements,
                'trainer_requirements' => $trainerRequirements,
                'equipment_requirements' => $equipmentRequirements,
                'volunteer_requirements' => $volunteerRequirements,
                'created_by' => $user['id'],
                'starts_at' => $startsAt,
                'ends_at' => $endsAt,
            ]);

            $eventId = (int) $this->pdo->lastInsertId();

            // Handle facilitators
            if (isset($input['facilitator_ids']) && is_array($input['facilitator_ids'])) {
                $facStmt = $this->pdo->prepare('INSERT INTO `campaign_department_event_facilitators` (event_id, user_id) VALUES (:event_id, :user_id)');
                foreach ($input['facilitator_ids'] as $facilitatorId) {
                    $facStmt->execute(['event_id' => $eventId, 'user_id' => (int) $facilitatorId]);
                }
            }

            // Handle audience segments
            if (isset($input['segment_ids']) && is_array($input['segment_ids'])) {
                $segStmt = $this->pdo->prepare('INSERT INTO `campaign_department_event_audience_segments` (event_id, segment_id) VALUES (:event_id, :segment_id)');
                foreach ($input['segment_ids'] as $segmentId) {
                    $segStmt->execute(['event_id' => $eventId, 'segment_id' => (int) $segmentId]);
                }
            }

            // Log audit
            $this->logAudit($eventId, $user['id'], 'created', null, null, 'Event created');

            // Create integration checkpoints
            $this->createIntegrationCheckpoints($eventId);

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

        $fields = [
            'event_title', 'event_name', 'event_type', 'event_description', 'hazard_focus',
            'target_audience_profile_id', 'linked_campaign_id', 'date', 'start_time', 'end_time',
            'venue', 'location', 'event_status', 'transport_requirements', 'trainer_requirements',
            'equipment_requirements', 'volunteer_requirements', 'post_event_notes'
        ];

        foreach ($fields as $field) {
            if (isset($input[$field])) {
                $oldValue = $event[$field] ?? null;
                $newValue = $input[$field];
                
                if ($oldValue != $newValue) {
                    $updates[] = "{$field} = :{$field}";
                    $updateParams[$field] = $newValue;
                    $oldValues[$field] = $oldValue;
                }
            }
        }

        // Handle starts_at and ends_at
        if (isset($input['date']) || isset($input['start_time'])) {
            $date = $input['date'] ?? $event['date'];
            $startTime = $input['start_time'] ?? $event['start_time'];
            if ($date && $startTime) {
                $startsAt = $date . ' ' . $startTime . ':00';
                $updates[] = "starts_at = :starts_at";
                $updateParams['starts_at'] = $startsAt;
            }
        }
        if (isset($input['date']) || isset($input['end_time'])) {
            $date = $input['date'] ?? $event['date'];
            $endTime = $input['end_time'] ?? $event['end_time'];
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
            $sql = 'UPDATE `campaign_department_events` SET ' . implode(', ', $updates) . ', last_updated = NOW() WHERE id = :event_id';
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

        $where = ['date = :date', 'event_status NOT IN ("cancelled", "completed")'];
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

        $sql = 'SELECT id, event_title, event_name, start_time, end_time, venue FROM `campaign_department_events` WHERE ' . implode(' AND ', $where);
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

        $allowedTypes = ['police', 'fire_rescue', 'traffic', 'emergency_response', 'community_policing', 'other'];
        if (!in_array($agencyType, $allowedTypes, true)) {
            http_response_code(422);
            return ['error' => 'Invalid agency_type'];
        }

        $stmt = $this->pdo->prepare('
            INSERT INTO `campaign_department_event_agency_coordination` (
                event_id, agency_type, agency_name, request_status, request_details
            ) VALUES (
                :event_id, :agency_type, :agency_name, "requested", :request_details
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
        $status = $input['request_status'] ?? null;
        $confirmationDetails = $input['confirmation_details'] ?? null;
        $fulfillmentDetails = $input['fulfillment_details'] ?? null;

        if (!$status) {
            http_response_code(422);
            return ['error' => 'request_status is required'];
        }

        $allowedStatuses = ['requested', 'confirmed', 'fulfilled', 'cancelled'];
        if (!in_array($status, $allowedStatuses, true)) {
            http_response_code(422);
            return ['error' => 'Invalid request_status'];
        }

        $updates = ['request_status = :status'];
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
                event_title,
                event_name,
                event_type,
                event_status,
                hazard_focus,
                date,
                start_time,
                end_time,
                venue
            FROM `campaign_department_events`
            WHERE date BETWEEN :start_date AND :end_date
            AND event_status NOT IN ("cancelled")
            ORDER BY date, start_time
        ');
        $stmt->execute(['start_date' => $startDate, 'end_date' => $endDate]);
        $events = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Format for calendar
        $calendarEvents = [];
        foreach ($events as $event) {
            $start = $event['date'] . 'T' . ($event['start_time'] ?? '00:00:00');
            $end = $event['date'] . 'T' . ($event['end_time'] ?? '23:59:59');
            
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
            INSERT INTO `campaign_department_attendance` (event_id, audience_member_id, participant_identifier, checkin_method, checkin_timestamp) 
            VALUES (:event_id, :audience_member_id, :participant_identifier, :checkin_method, NOW())
        ');
        $stmt->execute([
            'event_id' => $event['id'],
            'audience_member_id' => $audienceMemberId ?: null,
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
                a.attendance_id,
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
                a.attendance_id,
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
