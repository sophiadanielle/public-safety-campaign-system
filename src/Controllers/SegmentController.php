<?php

declare(strict_types=1);

namespace App\Controllers;

use PDO;
use RuntimeException;

class SegmentController
{
    // Quezon City barangays only - hardcoded list
    private const QC_BARANGAYS = [
        'Barangay Batasan Hills',
        'Barangay Commonwealth',
        'Barangay Holy Spirit',
        'Barangay Payatas',
        'Barangay Bagong Silangan',
        'Barangay Tandang Sora',
        'Barangay UP Campus',
        'Barangay Diliman',
        'Barangay Matandang Balara',
        'Barangay Loyola Heights',
        'Barangay Cubao',
        'Barangay Kamuning',
        'Barangay Project 6',
        'Barangay Project 8',
        'Barangay Fairview',
        'Barangay Nagkaisang Nayon',
    ];

    // Allowed values
    private const GEOGRAPHIC_SCOPES = ['Barangay', 'Zone', 'Purok'];
    private const SECTOR_TYPES = ['Households', 'Youth', 'Senior Citizens', 'Schools', 'NGOs', 'Person with Disabilities', 'Pregnant Women'];
    private const RISK_LEVELS = ['Low', 'Medium', 'High'];
    private const BASIS_OPTIONS = [
        'Historical trend',
        'Inspection results',
        'Attendance records',
        'Incident pattern reference'
    ];

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
        $stmt = $this->pdo->query('
            SELECT 
                id AS segment_id,
                segment_name,
                geographic_scope,
                location_reference,
                sector_type,
                risk_level,
                basis_of_segmentation,
                created_at,
                updated_at
            FROM audience_segments 
            ORDER BY created_at DESC
        ');
        return ['data' => $stmt->fetchAll(\PDO::FETCH_ASSOC)];
    }

    public function show(?array $user, array $params = []): array
    {
        $id = (int) ($params['id'] ?? 0);
        $stmt = $this->pdo->prepare('
            SELECT 
                id AS segment_id,
                segment_name,
                geographic_scope,
                location_reference,
                sector_type,
                risk_level,
                basis_of_segmentation,
                created_at,
                updated_at
            FROM audience_segments 
            WHERE id = :id
        ');
        $stmt->execute(['id' => $id]);
        $segment = $stmt->fetch(\PDO::FETCH_ASSOC);
        
        if (!$segment) {
            http_response_code(404);
            return ['error' => 'Segment not found'];
        }
        
        return ['data' => $segment];
    }

    public function store(?array $user, array $params = []): array
    {
        $input = json_decode(file_get_contents('php://input'), true) ?? [];
        
        $segmentName = trim($input['segment_name'] ?? '');
        $geographicScope = $input['geographic_scope'] ?? null;
        $locationReference = trim($input['location_reference'] ?? '') ?: null;
        $sectorType = $input['sector_type'] ?? null;
        $riskLevel = $input['risk_level'] ?? null;
        $basisOfSegmentation = $input['basis_of_segmentation'] ?? null;

        // Validation
        if (!$segmentName) {
            http_response_code(422);
            return ['error' => 'Segment name is required'];
        }

        if ($geographicScope && !in_array($geographicScope, self::GEOGRAPHIC_SCOPES, true)) {
            http_response_code(422);
            return ['error' => 'Invalid geographic scope. Must be: ' . implode(', ', self::GEOGRAPHIC_SCOPES)];
        }

        if ($locationReference && !in_array($locationReference, self::QC_BARANGAYS, true)) {
            http_response_code(422);
            return ['error' => 'Location reference must be a valid Quezon City barangay'];
        }

        if ($sectorType && !in_array($sectorType, self::SECTOR_TYPES, true)) {
            http_response_code(422);
            return ['error' => 'Invalid sector type. Must be: ' . implode(', ', self::SECTOR_TYPES)];
        }

        if ($riskLevel && !in_array($riskLevel, self::RISK_LEVELS, true)) {
            http_response_code(422);
            return ['error' => 'Invalid risk level. Must be: ' . implode(', ', self::RISK_LEVELS)];
        }

        if ($basisOfSegmentation && !in_array($basisOfSegmentation, self::BASIS_OPTIONS, true)) {
            http_response_code(422);
            return ['error' => 'Invalid basis of segmentation'];
        }

        // Check for duplicate segment names
        $checkStmt = $this->pdo->prepare('SELECT id FROM audience_segments WHERE segment_name = :name');
        $checkStmt->execute(['name' => $segmentName]);
        if ($checkStmt->fetch()) {
            http_response_code(422);
            return ['error' => 'Segment name already exists'];
        }

        $stmt = $this->pdo->prepare('
            INSERT INTO audience_segments (
                segment_name, 
                geographic_scope, 
                location_reference, 
                sector_type, 
                risk_level, 
                basis_of_segmentation
            ) VALUES (
                :segment_name, 
                :geographic_scope, 
                :location_reference, 
                :sector_type, 
                :risk_level, 
                :basis_of_segmentation
            )
        ');
        
        $stmt->execute([
            'segment_name' => $segmentName,
            'geographic_scope' => $geographicScope ?: null,
            'location_reference' => $locationReference,
            'sector_type' => $sectorType ?: null,
            'risk_level' => $riskLevel ?: null,
            'basis_of_segmentation' => $basisOfSegmentation ?: null,
        ]);

        return ['id' => (int) $this->pdo->lastInsertId(), 'message' => 'Segment created'];
    }

    public function update(?array $user, array $params = []): array
    {
        $id = (int) ($params['id'] ?? 0);
        $segment = $this->findSegment($id);
        
        $input = json_decode(file_get_contents('php://input'), true) ?? [];
        
        $segmentName = trim($input['segment_name'] ?? $segment['segment_name']);
        $geographicScope = $input['geographic_scope'] ?? $segment['geographic_scope'];
        $locationReference = isset($input['location_reference']) ? (trim($input['location_reference']) ?: null) : $segment['location_reference'];
        $sectorType = $input['sector_type'] ?? $segment['sector_type'];
        $riskLevel = $input['risk_level'] ?? $segment['risk_level'];
        $basisOfSegmentation = $input['basis_of_segmentation'] ?? $segment['basis_of_segmentation'];

        // Validation (same as store)
        if (!$segmentName) {
            http_response_code(422);
            return ['error' => 'Segment name is required'];
        }

        if ($geographicScope && !in_array($geographicScope, self::GEOGRAPHIC_SCOPES, true)) {
            http_response_code(422);
            return ['error' => 'Invalid geographic scope'];
        }

        if ($locationReference && !in_array($locationReference, self::QC_BARANGAYS, true)) {
            http_response_code(422);
            return ['error' => 'Location reference must be a valid Quezon City barangay'];
        }

        if ($sectorType && !in_array($sectorType, self::SECTOR_TYPES, true)) {
            http_response_code(422);
            return ['error' => 'Invalid sector type'];
        }

        if ($riskLevel && !in_array($riskLevel, self::RISK_LEVELS, true)) {
            http_response_code(422);
            return ['error' => 'Invalid risk level'];
        }

        if ($basisOfSegmentation && !in_array($basisOfSegmentation, self::BASIS_OPTIONS, true)) {
            http_response_code(422);
            return ['error' => 'Invalid basis of segmentation'];
        }

        // Check for duplicate names (excluding current segment)
        $checkStmt = $this->pdo->prepare('SELECT id FROM audience_segments WHERE segment_name = :name AND id != :id');
        $checkStmt->execute(['name' => $segmentName, 'id' => $id]);
        if ($checkStmt->fetch()) {
            http_response_code(422);
            return ['error' => 'Segment name already exists'];
        }

        $stmt = $this->pdo->prepare('
            UPDATE audience_segments SET
                segment_name = :segment_name,
                geographic_scope = :geographic_scope,
                location_reference = :location_reference,
                sector_type = :sector_type,
                risk_level = :risk_level,
                basis_of_segmentation = :basis_of_segmentation,
                updated_at = CURRENT_TIMESTAMP
            WHERE id = :id
        ');
        
        $stmt->execute([
            'id' => $id,
            'segment_name' => $segmentName,
            'geographic_scope' => $geographicScope ?: null,
            'location_reference' => $locationReference,
            'sector_type' => $sectorType ?: null,
            'risk_level' => $riskLevel ?: null,
            'basis_of_segmentation' => $basisOfSegmentation ?: null,
        ]);

        return ['message' => 'Segment updated'];
    }

    public function getMembers(?array $user, array $params = []): array
    {
        $segmentId = (int) ($params['id'] ?? 0);
        $this->findSegment($segmentId); // Verify segment exists
        
        $stmt = $this->pdo->prepare('
            SELECT 
                id,
                full_name AS name,
                sector,
                barangay,
                zone,
                purok,
                contact
            FROM audience_members 
            WHERE segment_id = :segment_id
            ORDER BY full_name
        ');
        $stmt->execute(['segment_id' => $segmentId]);
        
        return ['data' => $stmt->fetchAll(\PDO::FETCH_ASSOC)];
    }

    public function getParticipationHistory(?array $user, array $params = []): array
    {
        $segmentId = (int) ($params['id'] ?? 0);
        $this->findSegment($segmentId); // Verify segment exists
        
        // Read-only view of participation history
        $stmt = $this->pdo->prepare('
            SELECT 
                campaign_id,
                campaign_name,
                event_id,
                event_name,
                event_type,
                event_date,
                attendance_count,
                check_in,
                check_out,
                member_name
            FROM participation_history
            WHERE segment_id = :segment_id
            ORDER BY event_date DESC, check_in DESC
        ');
        $stmt->execute(['segment_id' => $segmentId]);
        
        return ['data' => $stmt->fetchAll(\PDO::FETCH_ASSOC)];
    }

    public function linkToCampaign(?array $user, array $params = []): array
    {
        $segmentId = (int) ($params['id'] ?? 0);
        $this->findSegment($segmentId);
        
        $input = json_decode(file_get_contents('php://input'), true) ?? [];
        $campaignId = (int) ($input['campaign_id'] ?? 0);
        
        if (!$campaignId) {
            http_response_code(422);
            return ['error' => 'Campaign ID is required'];
        }
        
        // Verify campaign exists
        $campaignStmt = $this->pdo->prepare('SELECT id FROM campaigns WHERE id = :id');
        $campaignStmt->execute(['id' => $campaignId]);
        if (!$campaignStmt->fetch()) {
            http_response_code(404);
            return ['error' => 'Campaign not found'];
        }
        
        // Link segment to campaign (many-to-many)
        try {
            $stmt = $this->pdo->prepare('INSERT IGNORE INTO campaign_audience (campaign_id, segment_id) VALUES (:campaign_id, :segment_id)');
            $stmt->execute([
                'campaign_id' => $campaignId,
                'segment_id' => $segmentId,
            ]);
        } catch (\PDOException $e) {
            // Already linked, that's fine
        }
        
        return ['message' => 'Segment linked to campaign'];
    }

    public function getLinkedCampaigns(?array $user, array $params = []): array
    {
        $segmentId = (int) ($params['id'] ?? 0);
        $this->findSegment($segmentId);
        
        $stmt = $this->pdo->prepare('
            SELECT 
                c.id AS campaign_id,
                c.title AS campaign_name,
                c.status,
                c.start_date,
                c.end_date
            FROM campaigns c
            INNER JOIN campaign_audience ca ON ca.campaign_id = c.id
            WHERE ca.segment_id = :segment_id
            ORDER BY c.start_date DESC
        ');
        $stmt->execute(['segment_id' => $segmentId]);
        
        return ['data' => $stmt->fetchAll(\PDO::FETCH_ASSOC)];
    }

    public function importMembers(?array $user, array $params = []): array
    {
        $segmentId = (int) ($params['id'] ?? 0);
        $this->findSegment($segmentId);

        if (!isset($_FILES['file'])) {
            http_response_code(422);
            return ['error' => 'CSV file is required'];
        }

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

        // Map headers to lowercase for case-insensitive matching
        $headerMap = array_map('strtolower', $header);
        $idxName = array_search('name', $headerMap, true) ?? array_search('full_name', $headerMap, true);
        $idxSector = array_search('sector', $headerMap, true);
        $idxBarangay = array_search('barangay', $headerMap, true);
        $idxZone = array_search('zone', $headerMap, true);
        $idxPurok = array_search('purok', $headerMap, true);
        $idxContact = array_search('contact', $headerMap, true);

        if ($idxName === false) {
            http_response_code(422);
            return ['error' => 'CSV must contain "name" or "full_name" column'];
        }

        $ins = $this->pdo->prepare('
            INSERT INTO audience_members (
                segment_id, 
                full_name, 
                sector, 
                barangay, 
                zone, 
                purok, 
                contact
            ) VALUES (
                :segment_id, 
                :full_name, 
                :sector, 
                :barangay, 
                :zone, 
                :purok, 
                :contact
            )
        ');

        $count = 0;
        $errors = [];
        
        while (($row = fgetcsv($fh)) !== false) {
            $name = trim($row[$idxName] ?? '');
            if (!$name) {
                $errors[] = 'Row ' . ($count + 1) . ': Name is required';
                continue;
            }

            $sector = $idxSector !== false ? trim($row[$idxSector] ?? '') : null;
            $barangay = $idxBarangay !== false ? trim($row[$idxBarangay] ?? '') : null;
            $zone = $idxZone !== false ? trim($row[$idxZone] ?? '') : null;
            $purok = $idxPurok !== false ? trim($row[$idxPurok] ?? '') : null;
            $contact = $idxContact !== false ? trim($row[$idxContact] ?? '') : null;

            // Validate sector if provided
            if ($sector && !in_array($sector, self::SECTOR_TYPES, true)) {
                $errors[] = 'Row ' . ($count + 1) . ': Invalid sector type';
                continue;
            }

            // Validate barangay if provided
            if ($barangay && !in_array($barangay, self::QC_BARANGAYS, true)) {
                $errors[] = 'Row ' . ($count + 1) . ': Invalid barangay (must be Quezon City barangay)';
                continue;
            }

            try {
                $ins->execute([
                    'segment_id' => $segmentId,
                    'full_name' => $name,
                    'sector' => $sector ?: null,
                    'barangay' => $barangay ?: null,
                    'zone' => $zone ?: null,
                    'purok' => $purok ?: null,
                    'contact' => $contact ?: null,
                ]);
                $count++;
            } catch (\PDOException $e) {
                $errors[] = 'Row ' . ($count + 1) . ': ' . $e->getMessage();
            }
        }

        fclose($fh);

        $result = ['message' => "Imported {$count} members"];
        if (!empty($errors)) {
            $result['errors'] = $errors;
            $result['error_count'] = count($errors);
        }

        return $result;
    }

    private function findSegment(int $id): array
    {
        $stmt = $this->pdo->prepare('
            SELECT 
                id AS segment_id,
                segment_name,
                geographic_scope,
                location_reference,
                sector_type,
                risk_level,
                basis_of_segmentation
            FROM audience_segments 
            WHERE id = :id 
            LIMIT 1
        ');
        $stmt->execute(['id' => $id]);
        $segment = $stmt->fetch(\PDO::FETCH_ASSOC);
        if (!$segment) {
            http_response_code(404);
            throw new RuntimeException('Segment not found');
        }
        return $segment;
    }
}
