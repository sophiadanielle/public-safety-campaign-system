<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Services\AudienceEvaluator;
use PDO;
use RuntimeException;

class SegmentController
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
        $stmt = $this->pdo->query('SELECT id, name, criteria, demographics_json, risk_level, geographies_json, preferences_json, created_at, updated_at FROM audience_segments ORDER BY created_at DESC');
        return ['data' => $stmt->fetchAll()];
    }

    public function store(?array $user, array $params = []): array
    {
        $input = json_decode(file_get_contents('php://input'), true) ?? [];
        $name = trim($input['name'] ?? '');
        $criteria = $input['criteria'] ?? [];
        $demographics = $input['demographics'] ?? null;
        $riskLevel = $input['risk_level'] ?? null;
        $geographies = $input['geographies'] ?? null;
        $preferences = $input['preferences'] ?? null;

        if (!$name) {
            http_response_code(422);
            return ['error' => 'Name is required'];
        }

        $stmt = $this->pdo->prepare('INSERT INTO audience_segments (name, criteria, demographics_json, risk_level, geographies_json, preferences_json) VALUES (:name, :criteria, :demographics, :risk_level, :geographies, :preferences)');
        $stmt->execute([
            'name' => $name,
            'criteria' => json_encode($criteria),
            'demographics' => $demographics ? json_encode($demographics) : null,
            'risk_level' => $riskLevel ?: null,
            'geographies' => $geographies ? json_encode($geographies) : null,
            'preferences' => $preferences ? json_encode($preferences) : null,
        ]);

        return ['id' => (int) $this->pdo->lastInsertId(), 'message' => 'Segment created'];
    }

    public function importMembers(?array $user, array $params = []): array
    {
        $segmentId = (int) ($params['id'] ?? 0);
        $segment = $this->findSegment($segmentId);

        $members = [];

        if (isset($_FILES['file'])) {
            $file = $_FILES['file'];
            if ($file['error'] !== UPLOAD_ERR_OK) {
                http_response_code(400);
                return ['error' => 'Upload error'];
            }
            $tmp = $file['tmp_name'];
            $fh = fopen($tmp, 'r');
            if (!$fh) {
                http_response_code(400);
                return ['error' => 'Cannot open uploaded file'];
            }
            $header = fgetcsv($fh);
            if (!$header) {
                http_response_code(400);
                return ['error' => 'CSV missing header'];
            }
            $map = array_map('strtolower', $header);
            $idxName = array_search('full_name', $map, true);
            $idxContact = array_search('contact', $map, true);
            $idxChannel = array_search('channel', $map, true);
            while (($row = fgetcsv($fh)) !== false) {
                $members[] = [
                    'full_name' => $idxName !== false ? $row[$idxName] : null,
                    'contact' => $idxContact !== false ? $row[$idxContact] : null,
                    'channel' => $idxChannel !== false ? $row[$idxChannel] : 'other',
                ];
            }
            fclose($fh);
        } else {
            $input = json_decode(file_get_contents('php://input'), true) ?? [];
            if (!isset($input['members']) || !is_array($input['members'])) {
                http_response_code(422);
                return ['error' => 'members array is required'];
            }
            $members = $input['members'];
        }

        if (empty($members)) {
            http_response_code(422);
            return ['error' => 'No members provided'];
        }

        $ins = $this->pdo->prepare('INSERT INTO audience_members (segment_id, full_name, contact, channel, risk_level, geo, preferences_json) VALUES (:segment_id, :full_name, :contact, :channel, :risk_level, :geo, :prefs)');
        $count = 0;
        foreach ($members as $m) {
            $fullName = trim($m['full_name'] ?? '');
            $contact = trim($m['contact'] ?? '');
            $channel = $m['channel'] ?? 'other';
            $riskLevel = $m['risk_level'] ?? null;
            $geo = $m['geo'] ?? null;
            $prefs = $m['preferences'] ?? null;
            if (!$fullName) {
                continue;
            }
            $ins->execute([
                'segment_id' => $segment['id'],
                'full_name' => $fullName,
                'contact' => $contact ?: null,
                'channel' => $channel ?: 'other',
                'risk_level' => $riskLevel ?: null,
                'geo' => $geo ?: null,
                'prefs' => $prefs ? json_encode($prefs) : null,
            ]);
            $count++;
        }

        return ['message' => 'Members imported', 'count' => $count];
    }

    public function evaluate(?array $user, array $params = []): array
    {
        $segmentId = (int) ($params['id'] ?? 0);
        $segment = $this->findSegment($segmentId);
        $criteria = json_decode($segment['criteria'] ?? '[]', true) ?: [];

        // Fetch members; in a real system we might push this down to SQL, but here we demo via PHP.
        $stmt = $this->pdo->prepare('SELECT id, full_name, contact, channel, created_at FROM audience_members WHERE segment_id = :sid');
        $stmt->execute(['sid' => $segmentId]);
        $members = $stmt->fetchAll();

        $evaluator = new AudienceEvaluator($criteria);
        $matched = $evaluator->filter($members);

        return [
            'segment_id' => $segmentId,
            'criteria' => $criteria,
            'matched_count' => count($matched),
            'sample' => array_slice($matched, 0, 5),
        ];
    }

    private function findSegment(int $id): array
    {
        $stmt = $this->pdo->prepare('SELECT id, name, criteria FROM audience_segments WHERE id = :id LIMIT 1');
        $stmt->execute(['id' => $id]);
        $segment = $stmt->fetch();
        if (!$segment) {
            http_response_code(404);
            throw new RuntimeException('Segment not found');
        }
        return $segment;
    }
}


