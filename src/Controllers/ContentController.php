<?php

declare(strict_types=1);

namespace App\Controllers;

use PDO;
use RuntimeException;

class ContentController
{
    private string $uploadDir;
    private int $maxUploadSize;
    private array $allowedMime;

    public function __construct(
        private PDO $pdo,
        private string $jwtSecret,
        private string $jwtIssuer,
        private string $jwtAudience,
        private int $jwtExpirySeconds
    ) {
        $configuredPath = getenv('UPLOAD_PATH') ?: (__DIR__ . '/../../public/uploads');
        $this->uploadDir = realpath($configuredPath) ?: $configuredPath;
        $this->maxUploadSize = 5 * 1024 * 1024; // 5MB
        $this->allowedMime = [
            'image/png',
            'image/jpeg',
            'image/gif',
            'image/webp',
            'application/pdf',
        ];
    }

    public function index(?array $user, array $params = []): array
    {
        try {
            // Content Repository filters
            $q = $_GET['q'] ?? '';
            $contentType = $_GET['content_type'] ?? null;
            $hazardCategory = $_GET['hazard_category'] ?? null;
            $intendedAudience = $_GET['intended_audience'] ?? null;
            $source = $_GET['source'] ?? null;
            $approvalStatus = $_GET['approval_status'] ?? null;
            $onlyApproved = isset($_GET['only_approved']) && $_GET['only_approved'] === 'true';
            $tag = $_GET['tag'] ?? null;
            $visibility = $_GET['visibility'] ?? null;

            // Check which audience column exists
            $audienceColCheck = $this->pdo->query("SHOW COLUMNS FROM content_items LIKE 'intended_audience%'")->fetchAll(PDO::FETCH_COLUMN);
            $audienceColumn = !empty($audienceColCheck) ? $audienceColCheck[0] : 'intended_audience';
            
            $sql = "SELECT ci.id, ci.title, ci.body, ci.content_type, ci.visibility, ci.created_at, 
                           ci.hazard_category, ci.{$audienceColumn} as intended_audience_segment, ci.source, 
                           ci.approval_status, ci.version_number, ci.approved_by, ci.approval_notes,
                           ci.date_uploaded, ci.file_reference,
                           a.file_path, a.mime_type, ci.campaign_id,
                           u1.name as uploaded_by_name, u2.name as approved_by_name
                    FROM content_items ci
                    LEFT JOIN attachments a ON a.content_item_id = ci.id
                    LEFT JOIN users u1 ON ci.created_by = u1.id
                    LEFT JOIN users u2 ON ci.approved_by = u2.id";
            $where = [];
            $bind = [];

            // Search by title or description
            if ($q) {
                $where[] = '(ci.title LIKE :q OR ci.body LIKE :q)';
                $bind['q'] = '%' . $q . '%';
            }

            // Filter by content type
            if ($contentType) {
                $where[] = 'ci.content_type = :content_type';
                $bind['content_type'] = $contentType;
            }

            // Filter by hazard category
            if ($hazardCategory) {
                $where[] = 'ci.hazard_category = :hazard_category';
                $bind['hazard_category'] = $hazardCategory;
            }

            // Filter by intended audience
            if ($intendedAudience) {
                $where[] = "ci.{$audienceColumn} LIKE :intended_audience";
                $bind['intended_audience'] = '%' . $intendedAudience . '%';
            }

            // Filter by source
            if ($source) {
                $where[] = 'ci.source = :source';
                $bind['source'] = $source;
            }

            // Filter by approval status
            if ($approvalStatus && in_array($approvalStatus, ['draft', 'pending', 'approved', 'rejected'], true)) {
                $where[] = 'ci.approval_status = :approval_status';
                $bind['approval_status'] = $approvalStatus;
            } elseif ($onlyApproved) {
                $where[] = 'ci.approval_status = "approved"';
            }

            // Filter by visibility
            if ($visibility) {
                $where[] = 'ci.visibility = :visibility';
                $bind['visibility'] = $visibility;
            }
            
            // Filter by tag
            if ($tag) {
                $tableCheck = $this->pdo->query("SHOW TABLES LIKE 'content_tags'");
                if ($tableCheck && $tableCheck->rowCount() > 0) {
                    $sql .= ' INNER JOIN content_tags ct ON ct.content_item_id = ci.id
                              INNER JOIN tags t ON t.id = ct.tag_id';
                    $where[] = 't.name = :tag';
                    $bind['tag'] = $tag;
                }
            }

            if ($where) {
                $sql .= ' WHERE ' . implode(' AND ', $where);
            }

            $sql .= ' ORDER BY ci.updated_at DESC, ci.created_at DESC';

            error_log('ContentController::index SQL: ' . $sql);
            error_log('ContentController::index bind params: ' . json_encode($bind));

            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($bind);

            $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            error_log('ContentController::index results count: ' . count($results));
            
            // Ensure file_path is properly formatted and get tags
            foreach ($results as &$result) {
                // Use file_path from attachments if file_reference is empty
                if (empty($result['file_reference']) && !empty($result['file_path'])) {
                    $result['file_reference'] = $result['file_path'];
                }
                if (isset($result['file_path']) && $result['file_path'] && !str_starts_with($result['file_path'], 'http')) {
                    $result['file_path'] = ltrim($result['file_path'], '/');
                }
                if (isset($result['file_reference']) && $result['file_reference'] && !str_starts_with($result['file_reference'], 'http')) {
                    $result['file_reference'] = ltrim($result['file_reference'], '/');
                }
                // Get tags for each item
                try {
                    $tagStmt = $this->pdo->prepare('
                        SELECT t.name FROM tags t
                        INNER JOIN content_tags ct ON ct.tag_id = t.id
                        WHERE ct.content_item_id = :id
                    ');
                    $tagStmt->execute(['id' => $result['id']]);
                    $result['tags'] = array_column($tagStmt->fetchAll(), 'name');
                } catch (\PDOException $e) {
                    $result['tags'] = [];
                }
            }

            return ['data' => $results];
        } catch (\PDOException $e) {
            error_log('ContentController::index error: ' . $e->getMessage());
            return ['data' => [], 'error' => 'Database error occurred'];
        } catch (\Throwable $e) {
            error_log('ContentController::index error: ' . $e->getMessage());
            return ['data' => [], 'error' => 'An error occurred while loading content'];
        }
    }

    public function store(?array $user, array $params = []): array
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            return ['error' => 'Method not allowed'];
        }

        if (!isset($_FILES['file'])) {
            http_response_code(422);
            return ['error' => 'File is required'];
        }

        $file = $_FILES['file'];
        if ($file['error'] !== UPLOAD_ERR_OK) {
            http_response_code(400);
            return ['error' => 'Upload failed with error code ' . $file['error']];
        }

        if ($file['size'] > $this->maxUploadSize) {
            http_response_code(413);
            return ['error' => 'File too large (max 5MB)'];
        }

        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mime = finfo_file($finfo, $file['tmp_name']);
        finfo_close($finfo);

        if (!in_array($mime, $this->allowedMime, true)) {
            http_response_code(415);
            return ['error' => 'Unsupported file type'];
        }

        $title = trim($_POST['title'] ?? pathinfo($file['name'], PATHINFO_FILENAME));
        $body = trim($_POST['description'] ?? '');
        $contentType = $_POST['content_type'] ?? $this->mapMimeToType($mime);
        $campaignId = isset($_POST['campaign_id']) ? (int) $_POST['campaign_id'] : null;
        $visibility = $_POST['visibility'] ?? 'public';
        $tagsInput = $_POST['tags'] ?? '';
        // Content Repository fields
        $hazardCategory = trim($_POST['hazard_category'] ?? '');
        $intendedAudience = trim($_POST['intended_audience_segment'] ?? '');
        $source = trim($_POST['source'] ?? '');

        if (!$title) {
            http_response_code(422);
            return ['error' => 'Title is required'];
        }

        $allowedVisibility = ['public','private','internal'];
        if (!in_array($visibility, $allowedVisibility, true)) {
            http_response_code(422);
            return ['error' => 'Invalid visibility'];
        }

        $this->pdo->beginTransaction();
        try {
            // Check which audience column exists
            $audienceColCheck = $this->pdo->query("SHOW COLUMNS FROM content_items LIKE 'intended_audience%'")->fetchAll(PDO::FETCH_COLUMN);
            $audienceColumn = !empty($audienceColCheck) ? $audienceColCheck[0] : 'intended_audience';
            
            // Create content_repository subdirectory
            $contentRepoDir = rtrim($this->uploadDir, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . 'content_repository';
            if (!is_dir($contentRepoDir)) {
                mkdir($contentRepoDir, 0775, true);
            }
            
            $newFileName = $this->uniqueFilename($file['name']);
            $targetPath = $contentRepoDir . DIRECTORY_SEPARATOR . $newFileName;
            if (!move_uploaded_file($file['tmp_name'], $targetPath)) {
                throw new RuntimeException('Failed to save file');
            }

            $fileReference = 'uploads/content_repository/' . $newFileName;
            
            // Build INSERT statement - use file_reference (preferred) or file_path
            $columns = [
                'campaign_id', 'title', 'body', 'content_type', 'created_by', 'visibility',
                'hazard_category', $audienceColumn, 'source', 'approval_status',
                'version_number', 'file_reference'
            ];
            
            $placeholders = [
                ':campaign_id', ':title', ':body', ':content_type', ':created_by', ':visibility',
                ':hazard_category', ':intended_audience', ':source', ':approval_status',
                ':version_number', ':file_reference'
            ];
            
            $bindParams = [
                'campaign_id' => $campaignId ?: null,
                'title' => $title,
                'body' => $body ?: null,
                'content_type' => $contentType,
                'created_by' => $user['id'] ?? null,
                'visibility' => $visibility,
                'hazard_category' => $hazardCategory ?: null,
                'intended_audience' => $intendedAudience ?: null,
                'source' => $source ?: null,
                'approval_status' => 'draft',
                'version_number' => 1,
                'file_reference' => $fileReference,
            ];
            
            $sql = 'INSERT INTO content_items (' . implode(', ', $columns) . ') VALUES (' . implode(', ', $placeholders) . ')';
            
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($bindParams);
            $contentId = (int) $this->pdo->lastInsertId();

            // Save initial version (if table exists)
            try {
                $tableExists = $this->pdo->query("SHOW TABLES LIKE 'content_item_versions'")->rowCount() > 0;
                if ($tableExists) {
                    $versionStmt = $this->pdo->prepare('
                        INSERT INTO content_item_versions (
                            content_id, version_number, title, body, file_reference, file_path, changed_by
                        ) VALUES (
                            :content_id, 1, :title, :body, :file_reference, :file_path, :changed_by
                        )
                    ');
                    $versionStmt->execute([
                        'content_id' => $contentId,
                        'version_number' => 1,
                        'title' => $title,
                        'body' => $body ?: null,
                        'file_reference' => $fileReference,
                        'file_path' => $fileReference,
                        'changed_by' => $user['id'] ?? null,
                    ]);
                }
            } catch (\PDOException $e) {
                // Version table might not exist or have different structure, that's okay
                error_log('Could not save version history: ' . $e->getMessage());
            }

            // Also save to attachments table for backward compatibility
            $stmt = $this->pdo->prepare('INSERT INTO attachments (content_item_id, file_path, mime_type, file_size) VALUES (:content_item_id, :file_path, :mime_type, :file_size)');
            $stmt->execute([
                'content_item_id' => $contentId,
                'file_path' => $fileReference,
                'mime_type' => $mime,
                'file_size' => (int) $file['size'],
            ]);

            $tags = $this->parseTags($tagsInput);
            if (!empty($tags)) {
                $tagIds = $this->ensureTags($tags);
                $ins = $this->pdo->prepare('INSERT IGNORE INTO content_tags (content_item_id, tag_id) VALUES (:cid, :tid)');
                foreach ($tagIds as $tid) {
                    $ins->execute(['cid' => $contentId, 'tid' => $tid]);
                }
            }

            $this->pdo->commit();
            
            // Return the created content ID
            return ['data' => ['id' => $contentId, 'title' => $title], 'message' => 'Content uploaded successfully'];
        } catch (RuntimeException $e) {
            $this->pdo->rollBack();
            http_response_code(400);
            return ['error' => $e->getMessage()];
        } catch (\Throwable $e) {
            $this->pdo->rollBack();
            http_response_code(500);
            return ['error' => 'Upload failed'];
        }

        return [
            'id' => $contentId,
            'message' => 'Content uploaded',
        ];
    }

    /**
     * Get single content item with full details
     */
    public function show(?array $user, array $params = []): array
    {
        $contentId = (int) ($params['id'] ?? 0);

        // Check which audience column exists
        $audienceColCheck = $this->pdo->query("SHOW COLUMNS FROM content_items LIKE 'intended_audience%'")->fetchAll(PDO::FETCH_COLUMN);
        $audienceColumn = !empty($audienceColCheck) ? $audienceColCheck[0] : 'intended_audience';

        $stmt = $this->pdo->prepare("
            SELECT ci.*, u1.name as uploaded_by_name, u2.name as approved_by_name
            FROM content_items ci
            LEFT JOIN users u1 ON ci.created_by = u1.id
            LEFT JOIN users u2 ON ci.approved_by = u2.id
            WHERE ci.id = :id
        ");
        $stmt->execute(['id' => $contentId]);
        $item = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$item) {
            http_response_code(404);
            throw new RuntimeException('Content not found');
        }

        // Ensure intended_audience_segment is set (for frontend compatibility)
        if (!isset($item['intended_audience_segment']) && isset($item[$audienceColumn])) {
            $item['intended_audience_segment'] = $item[$audienceColumn];
        }

        // Get tags
        try {
            $tagStmt = $this->pdo->prepare('
                SELECT t.name FROM tags t
                INNER JOIN content_tags ct ON ct.tag_id = t.id
                WHERE ct.content_item_id = :id
            ');
            $tagStmt->execute(['id' => $contentId]);
            $item['tags'] = array_column($tagStmt->fetchAll(PDO::FETCH_ASSOC), 'name');
        } catch (\PDOException $e) {
            $item['tags'] = [];
        }

        // Get version history
        try {
            $versionStmt = $this->pdo->prepare('
                SELECT version_id, version_number, title, body, file_reference, file_path, 
                       changed_by, change_notes, created_at,
                       u.name as changed_by_name
                FROM content_item_versions civ
                LEFT JOIN users u ON civ.changed_by = u.id
                WHERE civ.content_id = :content_id
                ORDER BY civ.version_number DESC
            ');
            $versionStmt->execute(['content_id' => $contentId]);
            $item['versions'] = $versionStmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (\PDOException $e) {
            $item['versions'] = [];
        }

        // Get campaigns linked to this content
        try {
            $campaignStmt = $this->pdo->prepare('
                SELECT c.id, c.title, c.status, cci.attached_at, u.name as attached_by_name
                FROM campaign_content_items cci
                INNER JOIN campaigns c ON cci.campaign_id = c.id
                LEFT JOIN users u ON cci.attached_by = u.id
                WHERE cci.content_id = :content_id
                ORDER BY cci.attached_at DESC
            ');
            $campaignStmt->execute(['content_id' => $contentId]);
            $item['campaigns'] = $campaignStmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (\PDOException $e) {
            $item['campaigns'] = [];
        }

        // Ensure file paths are properly formatted
        if (isset($item['file_path']) && $item['file_path'] && !str_starts_with($item['file_path'], 'http')) {
            $item['file_path'] = ltrim($item['file_path'], '/');
        }
        if (isset($item['file_reference']) && $item['file_reference'] && !str_starts_with($item['file_reference'], 'http')) {
            $item['file_reference'] = ltrim($item['file_reference'], '/');
        }

        return ['data' => $item];
    }

    /**
     * Update content item (creates new version)
     */
    public function update(?array $user, array $params = []): array
    {
        $contentId = (int) ($params['id'] ?? 0);

        $stmt = $this->pdo->prepare('SELECT * FROM content_items WHERE id = :id');
        $stmt->execute(['id' => $contentId]);
        $current = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$current) {
            http_response_code(404);
            throw new RuntimeException('Content not found');
        }

        $input = json_decode(file_get_contents('php://input'), true) ?? [];
        
        $title = trim($input['title'] ?? $current['title']);
        $body = trim($input['body'] ?? $current['body'] ?? '');
        $hazardCategory = trim($input['hazard_category'] ?? $current['hazard_category'] ?? '');
        
        // Check which audience column exists
        $audienceColCheck = $this->pdo->query("SHOW COLUMNS FROM content_items LIKE 'intended_audience%'")->fetchAll(PDO::FETCH_COLUMN);
        $audienceColumn = !empty($audienceColCheck) ? $audienceColCheck[0] : 'intended_audience';
        $intendedAudience = trim($input['intended_audience_segment'] ?? $input[$audienceColumn] ?? $current[$audienceColumn] ?? '');
        $source = trim($input['source'] ?? $current['source'] ?? '');

        if (!$title) {
            http_response_code(422);
            return ['error' => 'Title is required'];
        }

        $this->pdo->beginTransaction();
        try {
            $newVersion = ($current['version_number'] ?? 1) + 1;

            // Save current version to history (if table exists)
            try {
                $versionStmt = $this->pdo->prepare('
                    INSERT INTO content_item_versions (
                        content_id, version_number, title, body, file_reference, file_path, changed_by, change_notes
                    ) VALUES (
                        :content_id, :version_number, :title, :body, :file_reference, :file_path, :changed_by, :change_notes
                    )
                ');
                $versionStmt->execute([
                    'content_id' => $contentId,
                    'version_number' => $current['version_number'] ?? 1,
                    'title' => $current['title'],
                    'body' => $current['body'] ?? null,
                    'file_reference' => $current['file_reference'] ?? $current['file_path'] ?? null,
                    'file_path' => $current['file_path'] ?? null,
                    'changed_by' => $user['id'] ?? null,
                    'change_notes' => $input['change_notes'] ?? null,
                ]);
            } catch (\PDOException $e) {
                // Version table might not exist, continue without version tracking
            }

            // Update content
            $updateStmt = $this->pdo->prepare("
                UPDATE content_items SET
                    title = :title,
                    body = :body,
                    hazard_category = :hazard_category,
                    {$audienceColumn} = :intended_audience,
                    source = :source,
                    version_number = :version_number,
                    approval_status = CASE 
                        WHEN approval_status = 'approved' THEN 'pending'
                        ELSE approval_status
                    END
                WHERE id = :id
            ");
            $updateStmt->execute([
                'title' => $title,
                'body' => $body ?: null,
                'hazard_category' => $hazardCategory ?: null,
                'intended_audience' => $intendedAudience ?: null,
                'source' => $source ?: null,
                'version_number' => $newVersion,
                'id' => $contentId,
            ]);

            $this->pdo->commit();
        } catch (\Throwable $e) {
            $this->pdo->rollBack();
            error_log('ContentController::update error: ' . $e->getMessage());
            http_response_code(500);
            return ['error' => 'Failed to update content: ' . $e->getMessage()];
        }

        return ['message' => 'Content updated successfully', 'version' => $newVersion];
    }

    /**
     * Update approval status (draft → pending → approved/rejected)
     */
    public function updateApproval(?array $user, array $params = []): array
    {
        $contentId = (int) ($params['id'] ?? 0);

        $input = json_decode(file_get_contents('php://input'), true) ?? [];
        $status = $input['approval_status'] ?? '';
        $notes = trim($input['approval_notes'] ?? '');

        if (!in_array($status, ['pending', 'approved', 'rejected'], true)) {
            http_response_code(422);
            return ['error' => 'Invalid approval status'];
        }

        $stmt = $this->pdo->prepare('SELECT approval_status FROM content_items WHERE id = :id');
        $stmt->execute(['id' => $contentId]);
        $current = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$current) {
            http_response_code(404);
            throw new RuntimeException('Content not found');
        }

        // Validate workflow: draft → pending → approved/rejected
        $currentStatus = $current['approval_status'];
        if ($currentStatus === 'draft' && $status !== 'pending') {
            http_response_code(422);
            return ['error' => 'Draft content must be submitted as pending first'];
        }
        if ($currentStatus === 'pending' && !in_array($status, ['approved', 'rejected'], true)) {
            http_response_code(422);
            return ['error' => 'Pending content can only be approved or rejected'];
        }

        // Check if approved_by column exists
        $columns = $this->pdo->query("SHOW COLUMNS FROM content_items LIKE 'approved_by'")->fetchAll(PDO::FETCH_COLUMN);
        $hasApprovedBy = !empty($columns);

        if ($hasApprovedBy) {
            $stmt = $this->pdo->prepare('
                UPDATE content_items SET
                    approval_status = :approval_status,
                    approved_by = :approved_by,
                    approval_notes = :approval_notes
                WHERE id = :id
            ');
            $stmt->execute([
                'approval_status' => $status,
                'approved_by' => $user['id'] ?? null,
                'approval_notes' => $notes ?: null,
                'id' => $contentId,
            ]);
        } else {
            $stmt = $this->pdo->prepare('
                UPDATE content_items SET
                    approval_status = :approval_status
                WHERE id = :id
            ');
            $stmt->execute([
                'approval_status' => $status,
                'id' => $contentId,
            ]);
        }

        return ['message' => 'Approval status updated successfully'];
    }

    /**
     * Attach content to campaign (many-to-many)
     */
    public function attachToCampaign(?array $user, array $params = []): array
    {
        $contentId = (int) ($params['id'] ?? 0);

        $input = json_decode(file_get_contents('php://input'), true) ?? [];
        $campaignId = (int) ($input['campaign_id'] ?? 0);

        if (!$campaignId) {
            http_response_code(422);
            return ['error' => 'Campaign ID is required'];
        }

        // Verify content exists and is approved
        $contentStmt = $this->pdo->prepare('SELECT approval_status FROM content_items WHERE id = :id');
        $contentStmt->execute(['id' => $contentId]);
        $content = $contentStmt->fetch(PDO::FETCH_ASSOC);

        if (!$content) {
            http_response_code(404);
            throw new RuntimeException('Content not found');
        }

        if ($content['approval_status'] !== 'approved') {
            http_response_code(422);
            return ['error' => 'Only approved content can be attached to campaigns'];
        }

        // Verify campaign exists
        $campaignStmt = $this->pdo->prepare('SELECT id FROM campaigns WHERE id = :id');
        $campaignStmt->execute(['id' => $campaignId]);
        if (!$campaignStmt->fetch()) {
            http_response_code(404);
            throw new RuntimeException('Campaign not found');
        }

        // Check if already attached
        try {
            $checkStmt = $this->pdo->prepare('SELECT id FROM campaign_content_items WHERE campaign_id = :campaign_id AND content_id = :content_id');
            $checkStmt->execute(['campaign_id' => $campaignId, 'content_id' => $contentId]);
            if ($checkStmt->fetch()) {
                http_response_code(409);
                return ['error' => 'Content is already attached to this campaign'];
            }

            $stmt = $this->pdo->prepare('
                INSERT INTO campaign_content_items (campaign_id, content_id, attached_by)
                VALUES (:campaign_id, :content_id, :attached_by)
            ');
            $stmt->execute([
                'campaign_id' => $campaignId,
                'content_id' => $contentId,
                'attached_by' => $user['id'] ?? null,
            ]);
        } catch (\PDOException $e) {
            // Table might not exist, use campaign_id in content_items instead
            $updateStmt = $this->pdo->prepare('UPDATE content_items SET campaign_id = :campaign_id WHERE id = :content_id');
            $updateStmt->execute([
                'campaign_id' => $campaignId,
                'content_id' => $contentId,
            ]);
        }

        return ['message' => 'Content attached to campaign successfully'];
    }

    /**
     * Get campaigns linked to a content item
     */
    public function getCampaigns(?array $user, array $params = []): array
    {
        $contentId = (int) ($params['id'] ?? 0);

        try {
            $stmt = $this->pdo->prepare('
                SELECT c.id, c.title, c.status, cci.attached_at, u.name as attached_by_name
                FROM campaign_content_items cci
                INNER JOIN campaigns c ON cci.campaign_id = c.id
                LEFT JOIN users u ON cci.attached_by = u.id
                WHERE cci.content_id = :content_id
                ORDER BY cci.attached_at DESC
            ');
            $stmt->execute(['content_id' => $contentId]);
            return ['data' => $stmt->fetchAll(PDO::FETCH_ASSOC)];
        } catch (\PDOException $e) {
            // Table might not exist, return empty array
            return ['data' => []];
        }
    }

    public function useContent(?array $user, array $params = []): array
    {
        $contentId = (int) ($params['id'] ?? 0);
        $stmt = $this->pdo->prepare('SELECT id FROM content_items WHERE id = :id LIMIT 1');
        $stmt->execute(['id' => $contentId]);
        if (!$stmt->fetch()) {
            http_response_code(404);
            throw new RuntimeException('Content not found');
        }

        $input = json_decode(file_get_contents('php://input'), true) ?? [];
        $campaignId = isset($input['campaign_id']) ? (int) $input['campaign_id'] : null;
        $eventId = isset($input['event_id']) ? (int) $input['event_id'] : null;
        $surveyId = isset($input['survey_id']) ? (int) $input['survey_id'] : null;
        $usageContext = $input['usage_context'] ?? null;
        $tag = $input['tag'] ?? null;

        $tagId = null;
        if ($tag) {
            $tagId = $this->ensureTags([$tag])[0];
        }

        $stmt = $this->pdo->prepare('INSERT INTO content_usage (content_item_id, tag_id, campaign_id, event_id, survey_id, usage_context) VALUES (:cid, :tid, :campaign_id, :event_id, :survey_id, :usage_context)');
        $stmt->execute([
            'cid' => $contentId,
            'tid' => $tagId,
            'campaign_id' => $campaignId ?: null,
            'event_id' => $eventId ?: null,
            'survey_id' => $surveyId ?: null,
            'usage_context' => $usageContext,
        ]);

        return ['message' => 'Content usage recorded', 'id' => (int) $this->pdo->lastInsertId()];
    }

    public function getUsage(?array $user, array $params = []): array
    {
        try {
            $contentId = isset($_GET['content_id']) ? (int) $_GET['content_id'] : null;
            $campaignId = isset($_GET['campaign_id']) ? (int) $_GET['campaign_id'] : null;
            
            $sql = "SELECT cu.id, cu.content_item_id, cu.campaign_id, cu.event_id, cu.survey_id, 
                           cu.usage_context, cu.created_at,
                           ci.title as content_title, ci.content_type,
                           c.title as campaign_title,
                           t.name as tag_name
                    FROM content_usage cu
                    INNER JOIN content_items ci ON cu.content_item_id = ci.id
                    LEFT JOIN campaigns c ON cu.campaign_id = c.id
                    LEFT JOIN tags t ON cu.tag_id = t.id
                    WHERE 1=1";
            $bind = [];
            
            if ($contentId) {
                $sql .= ' AND cu.content_item_id = :content_id';
                $bind['content_id'] = $contentId;
            }
            
            if ($campaignId) {
                $sql .= ' AND cu.campaign_id = :campaign_id';
                $bind['campaign_id'] = $campaignId;
            }
            
            $sql .= ' ORDER BY cu.created_at DESC';
            
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($bind);
            $records = $stmt->fetchAll(\PDO::FETCH_ASSOC);
            
            return ['data' => $records];
        } catch (\Throwable $e) {
            error_log('ContentController::getUsage error: ' . $e->getMessage());
            http_response_code(500);
            return ['error' => 'Failed to retrieve usage records'];
        }
    }

    private function uniqueFilename(string $original): string
    {
        $ext = pathinfo($original, PATHINFO_EXTENSION);
        return uniqid('content_', true) . ($ext ? '.' . $ext : '');
    }

    private function mapMimeToType(string $mime): string
    {
        return match ($mime) {
            'image/png', 'image/jpeg', 'image/gif', 'image/webp' => 'image',
            'application/pdf' => 'file',
            default => 'file',
        };
    }

    private function parseTags(string $input): array
    {
        if (!$input) {
            return [];
        }
        $parts = array_filter(array_map('trim', explode(',', $input)));
        return array_values(array_unique($parts));
    }

    private function ensureTags(array $tags): array
    {
        $ids = [];
        $insert = $this->pdo->prepare('INSERT IGNORE INTO tags (name) VALUES (:name)');
        $select = $this->pdo->prepare('SELECT id FROM tags WHERE name = :name LIMIT 1');
        foreach ($tags as $name) {
            $insert->execute(['name' => $name]);
            $select->execute(['name' => $name]);
            $ids[] = (int) $select->fetchColumn();
        }
        return $ids;
    }
}

