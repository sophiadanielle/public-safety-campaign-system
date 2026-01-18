<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Middleware\RoleMiddleware;
use PDO;
use RuntimeException;

class PartnerController
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
        // RBAC: All authenticated users can view partners (read access)
        if (!$user) {
            http_response_code(401);
            return ['error' => 'Authentication required'];
        }
        
        $stmt = $this->pdo->query('SELECT id, name, contact_person, contact_email, contact_phone, created_at FROM partners ORDER BY created_at DESC');
        return ['data' => $stmt->fetchAll()];
    }

    public function store(?array $user, array $params = []): array
    {
        // RBAC: Only authorized LGU roles can create partners (viewer cannot)
        if (!$user) {
            http_response_code(401);
            return ['error' => 'Authentication required'];
        }
        
        try {
            $userRole = RoleMiddleware::getUserRole($user, $this->pdo);
            $userRoleName = $userRole ? strtolower($userRole) : '';
            
            // Viewer is read-only
            if ($userRoleName === 'viewer') {
                http_response_code(403);
                return ['error' => 'Viewer role is read-only. You cannot create partners.'];
            }
            
            // Allowed roles: admin, staff, secretary, kagawad, captain
            $allowedRoles = ['admin', 'staff', 'secretary', 'kagawad', 'captain', 'barangay administrator', 'barangay staff', 'system_admin', 'barangay_admin'];
            if (!$userRole || !in_array($userRoleName, $allowedRoles, true)) {
                http_response_code(403);
                return ['error' => 'Insufficient permissions. Only authorized LGU personnel can create partners.'];
            }
        } catch (\Exception $e) {
            http_response_code(403);
            return ['error' => 'Access denied: ' . $e->getMessage()];
        }
        
        $input = json_decode(file_get_contents('php://input'), true) ?? [];
        $name = trim($input['name'] ?? '');
        $contactPerson = $input['contact_person'] ?? null;
        $contactEmail = $input['contact_email'] ?? null;
        $contactPhone = $input['contact_phone'] ?? null;

        if (!$name) {
            http_response_code(422);
            return ['error' => 'Name is required'];
        }

        $stmt = $this->pdo->prepare('INSERT INTO partners (name, contact_person, contact_email, contact_phone) VALUES (:name, :cp, :ce, :cph)');
        $stmt->execute([
            'name' => $name,
            'cp' => $contactPerson ?: null,
            'ce' => $contactEmail ?: null,
            'cph' => $contactPhone ?: null,
        ]);

        return ['id' => (int) $this->pdo->lastInsertId(), 'message' => 'Partner created'];
    }

    public function engage(?array $user, array $params = []): array
    {
        // RBAC: Only authorized LGU roles can engage partners (viewer cannot)
        if (!$user) {
            http_response_code(401);
            return ['error' => 'Authentication required'];
        }
        
        try {
            $userRole = RoleMiddleware::getUserRole($user, $this->pdo);
            $userRoleName = $userRole ? strtolower($userRole) : '';
            
            // Viewer is read-only
            if ($userRoleName === 'viewer') {
                http_response_code(403);
                return ['error' => 'Viewer role is read-only. You cannot engage partners.'];
            }
            
            // Allowed roles: admin, staff, secretary, kagawad, captain, partner
            $allowedRoles = ['admin', 'staff', 'secretary', 'kagawad', 'captain', 'partner', 'barangay administrator', 'barangay staff', 'system_admin', 'barangay_admin'];
            if (!$userRole || !in_array($userRoleName, $allowedRoles, true)) {
                http_response_code(403);
                return ['error' => 'Insufficient permissions. Only authorized LGU personnel can engage partners.'];
            }
        } catch (\Exception $e) {
            http_response_code(403);
            return ['error' => 'Access denied: ' . $e->getMessage()];
        }
        
        $partnerId = (int) ($params['id'] ?? 0);
        $partner = $this->findPartner($partnerId);

        $input = json_decode(file_get_contents('php://input'), true) ?? [];
        $campaignId = isset($input['campaign_id']) ? (int) $input['campaign_id'] : 0;
        $engagementType = $input['engagement_type'] ?? 'collaboration';
        $notes = $input['notes'] ?? null;
        $webhookUrl = $input['webhook_url'] ?? null;
        $eventId = isset($input['event_id']) ? (int) $input['event_id'] : null;

        $this->assertCampaign($campaignId);
        if ($eventId) {
            $this->assertEvent($eventId);
        }

        $stmt = $this->pdo->prepare('INSERT INTO partner_engagements (partner_id, campaign_id, event_id, engagement_type, notes) VALUES (:pid, :cid, :eid, :etype, :notes)');
        $stmt->execute([
            'pid' => $partnerId,
            'cid' => $campaignId,
            'eid' => $eventId ?: null,
            'etype' => $engagementType,
            'notes' => $notes ?: null,
        ]);

        $engagementId = (int) $this->pdo->lastInsertId();

        $deliveryStatus = 'skipped';
        if ($webhookUrl) {
            $payload = [
                'partner_id' => $partnerId,
                'campaign_id' => $campaignId,
                'event_id' => $eventId,
                'engagement_type' => $engagementType,
                'notes' => $notes,
            ];
            $deliveryStatus = $this->sendWebhook($webhookUrl, $payload) ? 'success' : 'failed';
            $this->logIntegration('partner_invite', $payload, $deliveryStatus);
        }

        return [
            'message' => 'Engagement created',
            'engagement_id' => $engagementId,
            'webhook_status' => $deliveryStatus,
        ];
    }

    public function assignments(?array $user, array $params = []): array
    {
        $partnerId = (int) ($params['id'] ?? 0);
        $this->findPartner($partnerId);

        $stmt = $this->pdo->prepare('
            SELECT pe.id as engagement_id, c.id as campaign_id, c.title as campaign_title, c.status,
                   e.id as event_id, e.name as event_name, e.starts_at
            FROM partner_engagements pe
            INNER JOIN `campaign_department_campaigns` c ON c.id = pe.campaign_id
            LEFT JOIN `campaign_department_events` e ON e.id = pe.event_id
            WHERE pe.partner_id = :pid
            ORDER BY c.created_at DESC, e.starts_at ASC
        ');
        $stmt->execute(['pid' => $partnerId]);
        $rows = $stmt->fetchAll();

        return ['data' => $rows];
    }

    private function findPartner(int $id): array
    {
        $stmt = $this->pdo->prepare('SELECT id, name FROM partners WHERE id = :id LIMIT 1');
        $stmt->execute(['id' => $id]);
        $partner = $stmt->fetch();
        if (!$partner) {
            http_response_code(404);
            throw new RuntimeException('Partner not found');
        }
        return $partner;
    }

    private function assertCampaign(int $id): void
    {
        $stmt = $this->pdo->prepare('SELECT id FROM `campaign_department_campaigns` WHERE id = :id LIMIT 1');
        $stmt->execute(['id' => $id]);
        if (!$stmt->fetch()) {
            throw new RuntimeException('Campaign not found');
        }
    }

    private function assertEvent(int $id): void
    {
        $stmt = $this->pdo->prepare('SELECT id FROM `campaign_department_events` WHERE id = :id LIMIT 1');
        $stmt->execute(['id' => $id]);
        if (!$stmt->fetch()) {
            throw new RuntimeException('Event not found');
        }
    }

    private function sendWebhook(string $url, array $payload): bool
    {
        $secret = getenv('PARTNER_WEBHOOK_SECRET') ?: 'demo_secret';
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
            $res = @file_get_contents($url, false, $ctx);
            return $res !== false;
        } catch (\Throwable $e) {
            return false;
        }
    }

    private function logIntegration(string $source, array $payload, string $status): void
    {
        $stmt = $this->pdo->prepare('INSERT INTO `campaign_department_integration_logs` (source, payload, status) VALUES (:source, :payload, :status)');
        $stmt->execute([
            'source' => $source,
            'payload' => json_encode($payload),
            'status' => $status,
        ]);
    }
}





