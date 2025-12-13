<?php

declare(strict_types=1);

namespace App\Controllers;

use PDO;
use RuntimeException;

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

    public function index(?array $user, array $params = []): array
    {
        $stmt = $this->pdo->query('SELECT id, campaign_id, name, location, starts_at, ends_at, created_at FROM events ORDER BY starts_at DESC');
        return ['data' => $stmt->fetchAll()];
    }

    public function store(?array $user, array $params = []): array
    {
        $input = json_decode(file_get_contents('php://input'), true) ?? [];
        $name = trim($input['name'] ?? '');
        $campaignId = isset($input['campaign_id']) ? (int) $input['campaign_id'] : null;
        $location = $input['location'] ?? null;
        $startsAt = $input['starts_at'] ?? null;
        $endsAt = $input['ends_at'] ?? null;

        if (!$name || !$startsAt) {
            http_response_code(422);
            return ['error' => 'name and starts_at are required'];
        }

        $stmt = $this->pdo->prepare('INSERT INTO events (campaign_id, name, location, starts_at, ends_at) VALUES (:campaign_id, :name, :location, :starts_at, :ends_at)');
        $stmt->execute([
            'campaign_id' => $campaignId ?: null,
            'name' => $name,
            'location' => $location ?: null,
            'starts_at' => $startsAt,
            'ends_at' => $endsAt ?: null,
        ]);

        return ['id' => (int) $this->pdo->lastInsertId(), 'message' => 'Event created'];
    }

    public function attendance(?array $user, array $params = []): array
    {
        $eventId = (int) ($params['id'] ?? 0);
        $event = $this->findEvent($eventId);

        $input = json_decode(file_get_contents('php://input'), true) ?? [];
        $audienceMemberId = isset($input['audience_member_id']) ? (int) $input['audience_member_id'] : null;
        $fullName = $input['full_name'] ?? null;
        $contact = $input['contact'] ?? null;
        $channel = $input['channel'] ?? 'other';

        if (!$audienceMemberId && !$fullName) {
            http_response_code(422);
            return ['error' => 'audience_member_id or full_name is required'];
        }

        if (!$audienceMemberId) {
            $ins = $this->pdo->prepare('INSERT INTO audience_members (segment_id, full_name, contact, channel) VALUES (NULL, :full_name, :contact, :channel)');
            $ins->execute([
                'full_name' => $fullName,
                'contact' => $contact ?: null,
                'channel' => $channel ?: 'other',
            ]);
            $audienceMemberId = (int) $this->pdo->lastInsertId();
        }

        $stmt = $this->pdo->prepare('INSERT INTO attendance (event_id, audience_member_id, check_in) VALUES (:event_id, :audience_member_id, NOW())');
        $stmt->execute([
            'event_id' => $event['id'],
            'audience_member_id' => $audienceMemberId ?: null,
        ]);

        return [
            'message' => 'Check-in recorded',
            'attendance_id' => (int) $this->pdo->lastInsertId(),
            'audience_member_id' => $audienceMemberId,
        ];
    }

    public function exportCsv(?array $user, array $params = []): void
    {
        $eventId = (int) ($params['id'] ?? 0);
        $event = $this->findEvent($eventId);

        $stmt = $this->pdo->prepare('SELECT a.id, am.full_name, am.contact, am.channel, a.check_in, a.check_out
            FROM attendance a
            LEFT JOIN audience_members am ON am.id = a.audience_member_id
            WHERE a.event_id = :eid
            ORDER BY a.check_in ASC');
        $stmt->execute(['eid' => $eventId]);

        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="attendance_event_' . $eventId . '.csv"');

        $out = fopen('php://output', 'w');
        fputcsv($out, ['id','full_name','contact','channel','check_in','check_out']);
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            fputcsv($out, $row);
        }
        fclose($out);
        exit;
    }

    public function qrLink(?array $user, array $params = []): array
    {
        $eventId = (int) ($params['id'] ?? 0);
        $this->findEvent($eventId);
        $baseUrl = getenv('APP_URL') ?: ('http://' . ($_SERVER['HTTP_HOST'] ?? 'localhost'));
        $checkinUrl = rtrim($baseUrl, '/') . '/events/checkin.html?event_id=' . $eventId;

        return [
            'event_id' => $eventId,
            'checkin_url' => $checkinUrl,
            'qr_data' => $checkinUrl, // can be encoded by client into QR
        ];
    }

    private function findEvent(int $id): array
    {
        $stmt = $this->pdo->prepare('SELECT id, name, starts_at FROM events WHERE id = :id LIMIT 1');
        $stmt->execute(['id' => $id]);
        $event = $stmt->fetch();
        if (!$event) {
            http_response_code(404);
            throw new RuntimeException('Event not found');
        }
        return $event;
    }
}


