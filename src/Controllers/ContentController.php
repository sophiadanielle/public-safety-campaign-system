<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Middleware\RoleMiddleware;
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
        // RBAC: All authenticated users can view content (read access)
        if (!$user) {
            http_response_code(401);
            return ['error' => 'Authentication required'];
        }
        
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
            
            // Pagination
            $page = max(1, (int)($_GET['page'] ?? 1));
            $perPage = min(50, max(6, (int)($_GET['per_page'] ?? 6))); // Default 6, min 6, max 50
            $offset = ($page - 1) * $perPage;
            
            // Sorting
            $sortBy = $_GET['sort_by'] ?? 'latest'; // latest, oldest, title_asc, title_desc
            $orderBy = 'ci.updated_at DESC, ci.created_at DESC'; // Default: latest first
            switch ($sortBy) {
                case 'oldest':
                    $orderBy = 'ci.created_at ASC, ci.updated_at ASC';
                    break;
                case 'title_asc':
                    $orderBy = 'ci.title ASC';
                    break;
                case 'title_desc':
                    $orderBy = 'ci.title DESC';
                    break;
                case 'latest':
                default:
                    $orderBy = 'ci.updated_at DESC, ci.created_at DESC';
                    break;
            }

            // Check which audience column exists
            $audienceColCheck = $this->pdo->query("SHOW COLUMNS FROM campaign_department_content_items LIKE 'intended_audience%'")->fetchAll(PDO::FETCH_COLUMN);
            $audienceColumn = !empty($audienceColCheck) ? $audienceColCheck[0] : 'intended_audience';
            
            // Check which user column exists (created_by or uploaded_by)
            $userColCheck = $this->pdo->query("SHOW COLUMNS FROM campaign_department_content_items")->fetchAll(PDO::FETCH_COLUMN);
            $userColumn = in_array('uploaded_by', $userColCheck) ? 'uploaded_by' : 'created_by';
            
            $sql = "SELECT DISTINCT ci.id, ci.title, ci.body, ci.content_type, ci.visibility, ci.created_at, 
                           ci.hazard_category, ci.{$audienceColumn} as intended_audience_segment, ci.source, 
                           ci.approval_status, ci.version_number, ci.approved_by, ci.approval_notes,
                           ci.date_uploaded, ci.file_reference, ci.last_updated,
                           a.file_path, a.mime_type, ci.campaign_id,
                           u1.name as uploaded_by_name, u2.name as approved_by_name
                    FROM campaign_department_content_items ci
                    LEFT JOIN campaign_department_attachments a ON a.content_item_id = ci.id
                    LEFT JOIN campaign_department_users u1 ON ci.{$userColumn} = u1.id
                    LEFT JOIN campaign_department_users u2 ON ci.approved_by = u2.id";
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
            // By default, exclude archived content unless specifically requested
            $includeArchived = isset($_GET['include_archived']) && $_GET['include_archived'] === 'true';
            if ($approvalStatus && in_array($approvalStatus, ['draft', 'pending_review', 'approved', 'rejected', 'archived'], true)) {
                $where[] = 'ci.approval_status = :approval_status';
                $bind['approval_status'] = $approvalStatus;
            } elseif ($onlyApproved) {
                $where[] = 'ci.approval_status = "approved"';
            } elseif (!$includeArchived) {
                // Exclude archived content by default
                $where[] = 'ci.approval_status != "archived"';
            }

            // Filter by visibility
            if ($visibility) {
                $where[] = 'ci.visibility = :visibility';
                $bind['visibility'] = $visibility;
            }
            
            // Track if tag join was added
            $hasTagJoin = false;
            
            // Filter by tag
            if ($tag) {
                $tableCheck = $this->pdo->query("SHOW TABLES LIKE 'campaign_department_content_tags'");
                if ($tableCheck && $tableCheck->rowCount() > 0) {
                    $sql .= ' INNER JOIN campaign_department_content_tags ct ON ct.content_item_id = ci.id
                              INNER JOIN campaign_department_tags t ON t.id = ct.tag_id';
                    $where[] = 't.name = :tag';
                    $bind['tag'] = $tag;
                    $hasTagJoin = true;
                }
            }

            if ($where) {
                $sql .= ' WHERE ' . implode(' AND ', $where);
            }

            // Get total count for pagination
            // Build count query - match the main query structure
            if ($hasTagJoin) {
                // If main query has tag join, count query needs it too
                $countSql = "SELECT COUNT(DISTINCT ci.id) as total 
                            FROM campaign_department_content_items ci
                            INNER JOIN campaign_department_content_tags ct ON ct.content_item_id = ci.id
                            INNER JOIN campaign_department_tags t ON t.id = ct.tag_id";
            } else {
                $countSql = "SELECT COUNT(DISTINCT ci.id) as total FROM campaign_department_content_items ci";
            }
            
            // Apply WHERE conditions to count query
            if ($where) {
                $countSql .= ' WHERE ' . implode(' AND ', $where);
            }
            
            $countStmt = $this->pdo->prepare($countSql);
            $countStmt->execute($bind);
            $totalCount = (int)$countStmt->fetchColumn();
            $totalPages = ceil($totalCount / $perPage);

            $sql .= ' ORDER BY ' . $orderBy;
            $sql .= ' LIMIT :limit OFFSET :offset';

            error_log('ContentController::index SQL: ' . $sql);
            error_log('ContentController::index bind params: ' . json_encode($bind));
            error_log('ContentController::index pagination: page=' . $page . ', per_page=' . $perPage . ', total=' . $totalCount);

            $stmt = $this->pdo->prepare($sql);
            foreach ($bind as $key => $value) {
                $stmt->bindValue(':' . $key, $value);
            }
            $stmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
            $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
            $stmt->execute();

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
                        SELECT t.name FROM campaign_department_tags t
                        INNER JOIN campaign_department_content_tags ct ON ct.tag_id = t.id
                        WHERE ct.content_item_id = :id
                    ');
                    $tagStmt->execute(['id' => $result['id']]);
                    $result['tags'] = array_column($tagStmt->fetchAll(), 'name');
                } catch (\PDOException $e) {
                    $result['tags'] = [];
                }
            }

            return [
                'data' => $results,
                'pagination' => [
                    'current_page' => $page,
                    'per_page' => $perPage,
                    'total' => $totalCount,
                    'total_pages' => $totalPages,
                    'has_next' => $page < $totalPages,
                    'has_prev' => $page > 1,
                ],
                'sort' => [
                    'sort_by' => $sortBy,
                    'order_by' => $orderBy,
                ],
            ];
        } catch (\PDOException $e) {
            error_log('ContentController::index error: ' . $e->getMessage());
            return ['data' => [], 'pagination' => ['current_page' => 1, 'per_page' => 24, 'total' => 0, 'total_pages' => 0, 'has_next' => false, 'has_prev' => false], 'error' => 'Database error occurred'];
        } catch (\Throwable $e) {
            error_log('ContentController::index error: ' . $e->getMessage());
            return ['data' => [], 'pagination' => ['current_page' => 1, 'per_page' => 24, 'total' => 0, 'total_pages' => 0, 'has_next' => false, 'has_prev' => false], 'error' => 'An error occurred while loading content'];
        }
    }

    public function store(?array $user, array $params = []): array
    {
        // Role-based access control: Only admin and staff can create content
        try {
            $userRole = $user ? RoleMiddleware::getUserRole($user, $this->pdo) : null;
            if (!$userRole || !in_array($userRole, ['Barangay Administrator', 'Barangay Staff', 'system_admin', 'barangay_admin', 'content_manager', 'campaign_creator'], true)) {
                http_response_code(403);
                return ['error' => 'Insufficient permissions. Only administrators and staff can create content.'];
            }
        } catch (\Exception $e) {
            http_response_code(403);
            return ['error' => 'Access denied: ' . $e->getMessage()];
        }
        
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
        // Handle intended_audience_segment as array (multi-select) or string
        // FormData with name[] sends as array in $_POST
        $intendedAudienceInput = $_POST['intended_audience_segment'] ?? '';
        if (is_array($intendedAudienceInput)) {
            // Multiple selections received as array
            $intendedAudience = implode(', ', array_filter(array_map('trim', $intendedAudienceInput)));
        } else {
            // Single value or empty string
            $intendedAudience = trim($intendedAudienceInput);
        }
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
            $audienceColCheck = $this->pdo->query("SHOW COLUMNS FROM campaign_department_content_items LIKE 'intended_audience%'")->fetchAll(PDO::FETCH_COLUMN);
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
            
            // Validate user ID exists before inserting
            $userId = $user['id'] ?? null;
            if ($userId !== null) {
                $userCheck = $this->pdo->prepare('SELECT id FROM campaign_department_users WHERE id = :id AND is_active = 1 LIMIT 1');
                $userCheck->execute(['id' => $userId]);
                if (!$userCheck->fetch()) {
                    http_response_code(400);
                    $this->pdo->rollBack();
                    return ['error' => 'Invalid user ID. Please log in again.'];
                }
            }
            
            // Check which user column exists - prefer uploaded_by if both exist
            $userColCheck = $this->pdo->query("SHOW COLUMNS FROM campaign_department_content_items")->fetchAll(PDO::FETCH_COLUMN);
            $hasUploadedBy = in_array('uploaded_by', $userColCheck);
            $hasCreatedBy = in_array('created_by', $userColCheck);
            // Prefer uploaded_by if it exists (it's nullable), otherwise use created_by
            $userColumn = $hasUploadedBy ? 'uploaded_by' : ($hasCreatedBy ? 'created_by' : 'uploaded_by');
            
            // Build INSERT statement - use file_reference (preferred) or file_path
            $userParamKey = $userColumn; // Use same name for parameter key
            $columns = [
                'campaign_id', 'title', 'body', 'content_type', $userColumn, 'visibility',
                'hazard_category', $audienceColumn, 'source', 'approval_status',
                'version_number', 'file_reference'
            ];
            
            $placeholders = [
                ':campaign_id', ':title', ':body', ':content_type', ':' . $userParamKey, ':visibility',
                ':hazard_category', ':intended_audience', ':source', ':approval_status',
                ':version_number', ':file_reference'
            ];
            
            $bindParams = [
                'campaign_id' => $campaignId ?: null,
                'title' => $title,
                'body' => $body ?: null,
                'content_type' => $contentType,
                $userParamKey => $userId,
                'visibility' => $visibility,
                'hazard_category' => $hazardCategory ?: null,
                'intended_audience' => $intendedAudience ?: null,
                'source' => $source ?: null,
                'approval_status' => 'draft',
                'version_number' => 1,
                'file_reference' => $fileReference,
            ];
            
            // Log audit entry
            $this->logAudit($user['id'] ?? null, 'content', 'upload', $contentId ?? null, [
                'title' => $title,
                'content_type' => $contentType,
                'approval_status' => 'draft'
            ]);
            
            $sql = 'INSERT INTO campaign_department_content_items (' . implode(', ', $columns) . ') VALUES (' . implode(', ', $placeholders) . ')';
            
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($bindParams);
            $contentId = (int) $this->pdo->lastInsertId();

            // Save initial version (if table exists)
            try {
                $tableExists = $this->pdo->query("SHOW TABLES LIKE 'campaign_department_campaign_department_content_item_versions'")->rowCount() > 0;
                if ($tableExists) {
                    $versionStmt = $this->pdo->prepare('
                        INSERT INTO campaign_department_content_item_versions (
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
            $stmt = $this->pdo->prepare('INSERT INTO campaign_department_attachments (content_item_id, file_path, mime_type, file_size) VALUES (:content_item_id, :file_path, :mime_type, :file_size)');
            $stmt->execute([
                'content_item_id' => $contentId,
                'file_path' => $fileReference,
                'mime_type' => $mime,
                'file_size' => (int) $file['size'],
            ]);

            $tags = $this->parseTags($tagsInput);
            if (!empty($tags)) {
                $tagIds = $this->ensureTags($tags);
                $ins = $this->pdo->prepare('INSERT IGNORE INTO campaign_department_content_tags (content_item_id, tag_id) VALUES (:cid, :tid)');
                foreach ($tagIds as $tid) {
                    $ins->execute(['cid' => $contentId, 'tid' => $tid]);
                }
            }

            $this->pdo->commit();
            
            // Create notification for content creator
            try {
                \App\Controllers\NotificationController::create(
                    $this->pdo,
                    $user['id'] ?? null,
                    'content',
                    'Content Uploaded',
                    "Content '{$title}' has been uploaded and is pending approval.",
                    '/public/content.php#content-library',
                    'fas fa-file-alt'
                );
            } catch (\Exception $e) {
                error_log('Failed to create notification: ' . $e->getMessage());
            }
            
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
        // RBAC: All authenticated users can view content (read access)
        if (!$user) {
            http_response_code(401);
            return ['error' => 'Authentication required'];
        }
        
        $contentId = (int) ($params['id'] ?? 0);

        // Check which audience column exists
        $audienceColCheck = $this->pdo->query("SHOW COLUMNS FROM campaign_department_content_items LIKE 'intended_audience%'")->fetchAll(PDO::FETCH_COLUMN);
        $audienceColumn = !empty($audienceColCheck) ? $audienceColCheck[0] : 'intended_audience';

        // Check which user column exists
        $userColCheck = $this->pdo->query("SHOW COLUMNS FROM campaign_department_content_items")->fetchAll(PDO::FETCH_COLUMN);
        $userColumn = in_array('uploaded_by', $userColCheck) ? 'uploaded_by' : 'created_by';
        
        $stmt = $this->pdo->prepare("
            SELECT ci.*, u1.name as uploaded_by_name, u2.name as approved_by_name
            FROM campaign_department_content_items ci
            LEFT JOIN campaign_department_users u1 ON ci.{$userColumn} = u1.id
            LEFT JOIN campaign_department_users u2 ON ci.approved_by = u2.id
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
                SELECT t.name FROM campaign_department_tags t
                INNER JOIN campaign_department_content_tags ct ON ct.tag_id = t.id
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
                FROM campaign_department_content_item_versions civ
                LEFT JOIN campaign_department_users u ON civ.changed_by = u.id
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
                FROM campaign_department_campaign_content_items cci
                INNER JOIN campaign_department_campaigns c ON cci.campaign_id = c.id
                LEFT JOIN campaign_department_users u ON cci.attached_by = u.id
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
        // Role-based access control: Only admin and staff can update content
        try {
            $userRole = $user ? RoleMiddleware::getUserRole($user, $this->pdo) : null;
            if (!$userRole || !in_array($userRole, ['Barangay Administrator', 'Barangay Staff', 'system_admin', 'barangay_admin', 'content_manager', 'campaign_creator'], true)) {
                http_response_code(403);
                return ['error' => 'Insufficient permissions. Only administrators and staff can update content.'];
            }
        } catch (\Exception $e) {
            http_response_code(403);
            return ['error' => 'Access denied: ' . $e->getMessage()];
        }
        
        $contentId = (int) ($params['id'] ?? 0);

        $stmt = $this->pdo->prepare('SELECT * FROM campaign_department_content_items WHERE id = :id');
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
        $audienceColCheck = $this->pdo->query("SHOW COLUMNS FROM campaign_department_content_items LIKE 'intended_audience%'")->fetchAll(PDO::FETCH_COLUMN);
        $audienceColumn = !empty($audienceColCheck) ? $audienceColCheck[0] : 'intended_audience';
        // Handle intended_audience_segment as array (multi-select) or string
        $intendedAudienceInput = $input['intended_audience_segment'] ?? $input[$audienceColumn] ?? $current[$audienceColumn] ?? '';
        if (is_array($intendedAudienceInput)) {
            $intendedAudience = implode(', ', array_filter(array_map('trim', $intendedAudienceInput)));
        } else {
            $intendedAudience = trim($intendedAudienceInput);
        }
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
                    INSERT INTO campaign_department_content_item_versions (
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

            // Update content - if approved, revert to pending_review
            $updateStmt = $this->pdo->prepare("
                UPDATE campaign_department_content_items SET
                    title = :title,
                    body = :body,
                    hazard_category = :hazard_category,
                    {$audienceColumn} = :intended_audience,
                    source = :source,
                    version_number = :version_number,
                    approval_status = CASE 
                        WHEN approval_status = 'approved' THEN 'pending_review'
                        ELSE approval_status
                    END,
                    last_updated = NOW()
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
     * Update approval status (draft → pending_review → approved/rejected/archived)
     * Staff can submit for review (draft → pending_review)
     * Only admin can approve/reject/archive
     */
    public function updateApproval(?array $user, array $params = []): array
    {
        // Role-based access control: Only admin can approve/reject content
        try {
            $userRole = $user ? RoleMiddleware::getUserRole($user, $this->pdo) : null;
            if (!$userRole || !in_array($userRole, ['Barangay Administrator', 'system_admin', 'barangay_admin'], true)) {
                http_response_code(403);
                return ['error' => 'Insufficient permissions. Only administrators can approve or reject content.'];
            }
        } catch (\Exception $e) {
            http_response_code(403);
            return ['error' => 'Access denied: ' . $e->getMessage()];
        }
        
        $contentId = (int) ($params['id'] ?? 0);

        $input = json_decode(file_get_contents('php://input'), true) ?? [];
        $status = $input['approval_status'] ?? '';
        $notes = trim($input['approval_notes'] ?? '');

        if (!in_array($status, ['pending_review', 'approved', 'rejected', 'archived'], true)) {
            http_response_code(422);
            return ['error' => 'Invalid approval status'];
        }

        $stmt = $this->pdo->prepare('SELECT approval_status, title FROM campaign_department_content_items WHERE id = :id');
        $stmt->execute(['id' => $contentId]);
        $current = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$current) {
            http_response_code(404);
            throw new RuntimeException('Content not found');
        }

        // Validate workflow: draft → pending_review → approved/rejected/archived
        $currentStatus = $current['approval_status'];
        // Check if user is admin by role_id (1 = Administrator, 2 = Barangay Admin)
        $userRoleId = (int) ($user['role_id'] ?? 0);
        $isAdmin = ($userRoleId === 1 || $userRoleId === 2);
        
        // Staff can submit for review (draft → pending_review)
        if ($currentStatus === 'draft' && $status === 'pending_review') {
            // Allow staff to submit for review
        } elseif ($currentStatus === 'draft' && $status !== 'pending_review') {
            http_response_code(422);
            return ['error' => 'Draft content must be submitted as pending_review first'];
        } elseif ($currentStatus === 'pending_review' && !in_array($status, ['approved', 'rejected'], true)) {
            http_response_code(422);
            return ['error' => 'Pending content can only be approved or rejected'];
        } elseif (in_array($currentStatus, ['approved', 'rejected'], true) && $status === 'pending_review') {
            http_response_code(422);
            return ['error' => 'Cannot revert to pending_review from ' . $currentStatus];
        }
        
        // Role-based access control: Only admin can approve/reject/archive
        if (in_array($status, ['approved', 'rejected', 'archived'], true) && !$isAdmin) {
            http_response_code(403);
            return ['error' => 'Only administrators can approve, reject, or archive content'];
        }

        // Check which user column exists for notifications
        $userColCheck = $this->pdo->query("SHOW COLUMNS FROM campaign_department_content_items")->fetchAll(PDO::FETCH_COLUMN);
        $userColumn = in_array('uploaded_by', $userColCheck) ? 'uploaded_by' : 'created_by';
        
        // Get content creator ID
        $creatorStmt = $this->pdo->prepare("SELECT {$userColumn} as creator_id FROM campaign_department_content_items WHERE id = :id");
        $creatorStmt->execute(['id' => $contentId]);
        $creatorData = $creatorStmt->fetch(PDO::FETCH_ASSOC);
        $creatorId = $creatorData['creator_id'] ?? null;

        // Update approval status
        $columns = $this->pdo->query("SHOW COLUMNS FROM campaign_department_content_items LIKE 'approved_by'")->fetchAll(PDO::FETCH_COLUMN);
        $hasApprovedBy = !empty($columns);

        if ($hasApprovedBy) {
            $updateStmt = $this->pdo->prepare('
                UPDATE campaign_department_content_items SET
                    approval_status = :approval_status,
                    approved_by = :approved_by,
                    approval_notes = :approval_notes,
                    last_updated = NOW()
                WHERE id = :id
            ');
            $updateStmt->execute([
                'approval_status' => $status,
                'approved_by' => in_array($status, ['approved', 'rejected', 'archived'], true) ? ($user['id'] ?? null) : null,
                'approval_notes' => $notes ?: null,
                'id' => $contentId,
            ]);
        } else {
            $updateStmt = $this->pdo->prepare('
                UPDATE campaign_department_content_items SET
                    approval_status = :approval_status
                WHERE id = :id
            ');
            $updateStmt->execute([
                'approval_status' => $status,
                'id' => $contentId,
            ]);
        }

        // Log audit entry
        $this->logAudit($user['id'] ?? null, 'content', 'approval_' . $status, $contentId, [
            'title' => $current['title'],
            'previous_status' => $currentStatus,
            'new_status' => $status,
            'notes' => $notes
        ]);

        // Create notification for content creator when approved/rejected
        if ($creatorId && in_array($status, ['approved', 'rejected'], true)) {
            try {
                $message = $status === 'approved' 
                    ? "Content '{$current['title']}' has been approved and is now available for use."
                    : "Content '{$current['title']}' has been rejected. " . ($notes ? "Reason: {$notes}" : '');
                
                \App\Controllers\NotificationController::create(
                    $this->pdo,
                    $creatorId,
                    'content',
                    $status === 'approved' ? 'Content Approved' : 'Content Rejected',
                    $message,
                    '/public/content.php#content-library',
                    $status === 'approved' ? 'fas fa-check-circle' : 'fas fa-times-circle'
                );
            } catch (\Exception $e) {
                error_log('Failed to create notification: ' . $e->getMessage());
            }
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
        $contentStmt = $this->pdo->prepare('SELECT approval_status FROM campaign_department_content_items WHERE id = :id');
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
        $campaignStmt = $this->pdo->prepare('SELECT id FROM campaign_department_campaigns WHERE id = :id');
        $campaignStmt->execute(['id' => $campaignId]);
        if (!$campaignStmt->fetch()) {
            http_response_code(404);
            throw new RuntimeException('Campaign not found');
        }

        // Check if already attached
        try {
            $checkStmt = $this->pdo->prepare('SELECT id FROM campaign_department_campaign_content_items WHERE campaign_id = :campaign_id AND content_id = :content_id');
            $checkStmt->execute(['campaign_id' => $campaignId, 'content_id' => $contentId]);
            if ($checkStmt->fetch()) {
                http_response_code(409);
                return ['error' => 'Content is already attached to this campaign'];
            }

            $stmt = $this->pdo->prepare('
                INSERT INTO campaign_department_campaign_content_items (campaign_id, content_id, attached_by)
                VALUES (:campaign_id, :content_id, :attached_by)
            ');
            $stmt->execute([
                'campaign_id' => $campaignId,
                'content_id' => $contentId,
                'attached_by' => $user['id'] ?? null,
            ]);
        } catch (\PDOException $e) {
            // Table might not exist, use campaign_id in content_items instead
            $updateStmt = $this->pdo->prepare('UPDATE campaign_department_content_items SET campaign_id = :campaign_id WHERE id = :content_id');
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
                FROM campaign_department_campaign_content_items cci
                INNER JOIN campaign_department_campaigns c ON cci.campaign_id = c.id
                LEFT JOIN campaign_department_users u ON cci.attached_by = u.id
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
        $stmt = $this->pdo->prepare('SELECT id FROM campaign_department_content_items WHERE id = :id LIMIT 1');
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
                    INNER JOIN campaign_department_content_items ci ON cu.content_item_id = ci.id
                    LEFT JOIN campaign_department_campaigns c ON cu.campaign_id = c.id
                    LEFT JOIN campaign_department_tags t ON cu.tag_id = t.id
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
        $insert = $this->pdo->prepare('INSERT IGNORE INTO campaign_department_tags (name) VALUES (:name)');
        $select = $this->pdo->prepare('SELECT id FROM campaign_department_tags WHERE name = :name LIMIT 1');
        foreach ($tags as $name) {
            $insert->execute(['name' => $name]);
            $select->execute(['name' => $name]);
            $ids[] = (int) $select->fetchColumn();
        }
        return $ids;
    }
    
    /**
     * Archive content (soft delete, audit-safe)
     */
    public function archive(?array $user, array $params = []): array
    {
        // Role-based access control: Only admin can archive
        // Check if user is admin by role_id (1 = Administrator, 2 = Barangay Admin)
        $userRoleId = (int) ($user['role_id'] ?? 0);
        if (!$user || ($userRoleId !== 1 && $userRoleId !== 2)) {
            http_response_code(403);
            return ['error' => 'Only administrators can archive content'];
        }
        
        $contentId = (int) ($params['id'] ?? 0);
        
        $stmt = $this->pdo->prepare('SELECT id, title, approval_status FROM campaign_department_content_items WHERE id = :id');
        $stmt->execute(['id' => $contentId]);
        $content = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$content) {
            http_response_code(404);
            throw new RuntimeException('Content not found');
        }
        
        // Update to archived status
        $updateStmt = $this->pdo->prepare('
            UPDATE campaign_department_content_items SET
                approval_status = "archived",
                last_updated = NOW()
            WHERE id = :id
        ');
        $updateStmt->execute(['id' => $contentId]);
        
        // Log audit entry
        $this->logAudit($user['id'] ?? null, 'content', 'archive', $contentId, [
            'title' => $content['title'],
            'previous_status' => $content['approval_status']
        ]);
        
        return ['message' => 'Content archived successfully'];
    }
    
    /**
     * Get approved content for integration (read-only API for external subsystems)
     */
    public function getApproved(?array $user, array $params = []): array
    {
        try {
            // This endpoint only returns approved content for integration purposes
            $q = $_GET['q'] ?? '';
            $contentType = $_GET['content_type'] ?? null;
            $hazardCategory = $_GET['hazard_category'] ?? null;
            
            $audienceColCheck = $this->pdo->query("SHOW COLUMNS FROM campaign_department_content_items LIKE 'intended_audience%'")->fetchAll(PDO::FETCH_COLUMN);
            $audienceColumn = !empty($audienceColCheck) ? $audienceColCheck[0] : 'intended_audience';
            
            $sql = "SELECT ci.id, ci.title, ci.body, ci.content_type, ci.hazard_category, 
                           ci.{$audienceColumn} as intended_audience_segment, ci.source,
                           ci.file_reference, ci.date_uploaded, ci.version_number
                    FROM campaign_department_content_items ci
                    WHERE ci.approval_status = 'approved'";
            
            $where = [];
            $bind = [];
            
            if ($q) {
                $where[] = '(ci.title LIKE :q OR ci.body LIKE :q)';
                $bind['q'] = '%' . $q . '%';
            }
            
            if ($contentType) {
                $where[] = 'ci.content_type = :content_type';
                $bind['content_type'] = $contentType;
            }
            
            if ($hazardCategory) {
                $where[] = 'ci.hazard_category = :hazard_category';
                $bind['hazard_category'] = $hazardCategory;
            }
            
            if ($where) {
                $sql .= ' AND ' . implode(' AND ', $where);
            }
            
            $sql .= ' ORDER BY ci.date_uploaded DESC LIMIT 100';
            
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($bind);
            
            return ['data' => $stmt->fetchAll(PDO::FETCH_ASSOC)];
        } catch (\Throwable $e) {
            error_log('ContentController::getApproved error: ' . $e->getMessage());
            http_response_code(500);
            return ['error' => 'Failed to retrieve approved content'];
        }
    }
    
    /**
     * Log audit entry
     */
    private function logAudit(?int $userId, string $entityType, string $action, ?int $entityId, array $metadata = []): void
    {
        try {
            $ipAddress = $_SERVER['REMOTE_ADDR'] ?? null;
            $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? null;
            
            $stmt = $this->pdo->prepare('
                INSERT INTO audit_logs (user_id, action, entity_type, entity_id, ip_address, user_agent, metadata)
                VALUES (:user_id, :action, :entity_type, :entity_id, :ip_address, :user_agent, :metadata)
            ');
            $stmt->execute([
                'user_id' => $userId,
                'action' => $action,
                'entity_type' => $entityType,
                'entity_id' => $entityId,
                'ip_address' => $ipAddress,
                'user_agent' => $userAgent,
                'metadata' => json_encode($metadata),
            ]);
        } catch (\PDOException $e) {
            // Audit logging is non-critical, log error but don't fail
            error_log('Failed to log audit entry: ' . $e->getMessage());
        }
    }
}

