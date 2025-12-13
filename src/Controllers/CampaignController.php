<?php

declare(strict_types=1);

namespace App\Controllers;

use PDO;
use RuntimeException;

class CampaignController
{
    public function __construct(
        private PDO $pdo,
        private string $jwtSecret,
        private string $jwtIssuer,
        private string $jwtAudience,
        private int $jwtExpirySeconds
    ) {
    }

    public function index(?array $user, array $params = []): array
    {
        $stmt = $this->pdo->query('SELECT id, title, description, status, start_date, end_date, owner_id, created_at, objectives, location, assigned_staff, barangay_target_zones, budget, staff_count, materials_json FROM campaigns ORDER BY created_at DESC');
        return ['data' => $stmt->fetchAll()];
    }

    public function store(?array $user, array $params = []): array
    {
        $input = json_decode(file_get_contents('php://input'), true) ?? [];
        $title = trim($input['title'] ?? '');
        $description = trim($input['description'] ?? '');
        $status = $input['status'] ?? 'draft';
        $startDate = $input['start_date'] ?? null;
        $endDate = $input['end_date'] ?? null;
        $ownerId = $user['id'] ?? null;

        if (!$title) {
            http_response_code(422);
            return ['error' => 'Title is required'];
        }

        $allowedStatus = ['draft','pending','approved','ongoing','completed','scheduled','active','archived'];
        if (!in_array($status, $allowedStatus, true)) {
            http_response_code(422);
            return ['error' => 'Invalid status'];
        }

        $objectives = $input['objectives'] ?? null;
        $location = $input['location'] ?? null;
        $assignedStaff = isset($input['assigned_staff']) ? json_encode($input['assigned_staff']) : null;
        $barangayTargetZones = isset($input['barangay_target_zones']) ? json_encode($input['barangay_target_zones']) : null;
        $budget = isset($input['budget']) ? (float) $input['budget'] : null;
        $staffCount = isset($input['staff_count']) ? (int) $input['staff_count'] : null;
        $materialsJson = isset($input['materials_json']) ? json_encode($input['materials_json']) : null;

        $stmt = $this->pdo->prepare('INSERT INTO campaigns (title, description, status, start_date, end_date, owner_id, objectives, location, assigned_staff, barangay_target_zones, budget, staff_count, materials_json) VALUES (:title, :description, :status, :start_date, :end_date, :owner_id, :objectives, :location, :assigned_staff, :barangay_target_zones, :budget, :staff_count, :materials_json)');
        $stmt->execute([
            'title' => $title,
            'description' => $description ?: null,
            'status' => $status,
            'start_date' => $startDate ?: null,
            'end_date' => $endDate ?: null,
            'owner_id' => $ownerId,
            'objectives' => $objectives ?: null,
            'location' => $location ?: null,
            'assigned_staff' => $assignedStaff,
            'barangay_target_zones' => $barangayTargetZones,
            'budget' => $budget,
            'staff_count' => $staffCount,
            'materials_json' => $materialsJson,
        ]);

        return ['id' => (int) $this->pdo->lastInsertId(), 'message' => 'Campaign created'];
    }

    public function show(?array $user, array $params = []): array
    {
        $id = (int) ($params['id'] ?? 0);
        $campaign = $this->findCampaign($id);
        return ['data' => $campaign];
    }

    public function update(?array $user, array $params = []): array
    {
        $id = (int) ($params['id'] ?? 0);
        $this->findCampaign($id); // ensure exists

        $input = json_decode(file_get_contents('php://input'), true) ?? [];
        $fields = [];
        $bindings = ['id' => $id];

        $allowedStatus = ['draft','pending','approved','ongoing','completed','scheduled','active','archived'];

        if (isset($input['title'])) {
            $fields[] = 'title = :title';
            $bindings['title'] = trim($input['title']);
        }
        if (isset($input['description'])) {
            $fields[] = 'description = :description';
            $bindings['description'] = trim((string) $input['description']) ?: null;
        }
        if (isset($input['status'])) {
            if (!in_array($input['status'], $allowedStatus, true)) {
                http_response_code(422);
                return ['error' => 'Invalid status'];
            }
            $fields[] = 'status = :status';
            $bindings['status'] = $input['status'];
        }
        if (isset($input['start_date'])) {
            $fields[] = 'start_date = :start_date';
            $bindings['start_date'] = $input['start_date'] ?: null;
        }
        if (isset($input['end_date'])) {
            $fields[] = 'end_date = :end_date';
            $bindings['end_date'] = $input['end_date'] ?: null;
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

        if (empty($fields)) {
            return ['message' => 'Nothing to update'];
        }

        $sql = 'UPDATE campaigns SET ' . implode(', ', $fields) . ', updated_at = CURRENT_TIMESTAMP WHERE id = :id';
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($bindings);

        return ['message' => 'Campaign updated'];
    }

    public function addSchedule(?array $user, array $params = []): array
    {
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

        $stmt = $this->pdo->prepare('INSERT INTO campaign_schedules (campaign_id, scheduled_at, channel, notes) VALUES (:campaign_id, :scheduled_at, :channel, :notes)');
        $stmt->execute([
            'campaign_id' => $campaignId,
            'scheduled_at' => $scheduledAt,
            'channel' => $channel,
            'notes' => $notes ?: null,
        ]);

        return ['id' => (int) $this->pdo->lastInsertId(), 'message' => 'Schedule created'];
    }

    public function listSchedules(?array $user, array $params = []): array
    {
        $campaignId = (int) ($params['id'] ?? 0);
        $this->findCampaign($campaignId);

        $stmt = $this->pdo->prepare('SELECT id, scheduled_at, channel, notes, created_at FROM campaign_schedules WHERE campaign_id = :campaign_id ORDER BY scheduled_at ASC');
        $stmt->execute(['campaign_id' => $campaignId]);
        return ['data' => $stmt->fetchAll()];
    }

    public function sendSchedule(?array $user, array $params = []): array
    {
        $campaignId = (int) ($params['id'] ?? 0);
        $scheduleId = (int) ($params['sid'] ?? 0);

        $this->findCampaign($campaignId);
        $schedule = $this->findSchedule($campaignId, $scheduleId);

        // Simulate sending by inserting a notification_log entry and integration log
        $stmt = $this->pdo->prepare('INSERT INTO notification_logs (campaign_id, audience_member_id, channel, status, response_message) VALUES (:campaign_id, NULL, :channel, :status, :response_message)');
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
        $log = $this->pdo->prepare('INSERT INTO integration_logs (source, payload, status) VALUES (:source, :payload, :status)');
        $log->execute([
            'source' => 'notification_dispatch',
            'payload' => json_encode($payload),
            'status' => 'queued',
        ]);

        // Fire outbound webhook if configured
        $webhookUrl = getenv('NOTIFY_WEBHOOK_URL') ?: null;
        if ($webhookUrl) {
            $this->dispatchWebhook($webhookUrl, $payload);
        }

        return [
            'message' => 'Schedule sent (simulated)',
            'notification_log_id' => (int) $this->pdo->lastInsertId(),
            'integration_log_id' => (int) $this->pdo->lastInsertId(),
        ];
    }

    public function listSegments(?array $user, array $params = []): array
    {
        $campaignId = (int) ($params['id'] ?? 0);
        $this->findCampaign($campaignId);

        $stmt = $this->pdo->prepare('
            SELECT s.id, s.name, s.criteria, s.created_at
            FROM campaign_audience ca
            INNER JOIN audience_segments s ON s.id = ca.segment_id
            WHERE ca.campaign_id = :cid
            ORDER BY s.created_at DESC
        ');
        $stmt->execute(['cid' => $campaignId]);
        return ['data' => $stmt->fetchAll()];
    }

    public function syncSegments(?array $user, array $params = []): array
    {
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
            $del = $this->pdo->prepare('DELETE FROM campaign_audience WHERE campaign_id = :cid');
            $del->execute(['cid' => $campaignId]);

            $ins = $this->pdo->prepare('INSERT INTO campaign_audience (campaign_id, segment_id) VALUES (:cid, :sid)');
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

    private function findCampaign(int $id): array
    {
        $stmt = $this->pdo->prepare('SELECT id, title, description, status, start_date, end_date, owner_id, objectives, location, assigned_staff, barangay_target_zones, budget, staff_count, materials_json FROM campaigns WHERE id = :id LIMIT 1');
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
        $stmt = $this->pdo->prepare('SELECT id, campaign_id, scheduled_at, channel, notes FROM campaign_schedules WHERE id = :sid AND campaign_id = :cid LIMIT 1');
        $stmt->execute(['sid' => $scheduleId, 'cid' => $campaignId]);
        $schedule = $stmt->fetch();
        if (!$schedule) {
            http_response_code(404);
            throw new RuntimeException('Schedule not found');
        }
        return $schedule;
    }

    private function assertSegments(array $ids): void
    {
        if (empty($ids)) {
            return;
        }
        $placeholders = implode(',', array_fill(0, count($ids), '?'));
        $stmt = $this->pdo->prepare("SELECT COUNT(*) FROM audience_segments WHERE id IN ($placeholders)");
        $stmt->execute($ids);
        $found = (int) $stmt->fetchColumn();
        if ($found !== count($ids)) {
            throw new RuntimeException('One or more segments not found');
        }
    }

    private function dispatchWebhook(string $url, array $payload): void
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
            @file_get_contents($url, false, $ctx);
        } catch (\Throwable $e) {
            // swallow to avoid breaking flow; logged via integration_logs
        }
    }
}


