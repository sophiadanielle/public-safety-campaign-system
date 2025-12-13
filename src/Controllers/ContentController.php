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
        $type = $_GET['type'] ?? null;
        $tag = $_GET['tag'] ?? null;
        $visibility = $_GET['visibility'] ?? null;
        $q = $_GET['q'] ?? null;

        $sql = 'SELECT ci.id, ci.title, ci.body, ci.content_type, ci.visibility, ci.created_at, a.file_path, a.mime_type
                FROM content_items ci
                LEFT JOIN attachments a ON a.content_item_id = ci.id';
        $where = [];
        $bind = [];

        if ($type) {
            $where[] = 'ci.content_type = :type';
            $bind['type'] = $type;
        }
        if ($visibility) {
            $where[] = 'ci.visibility = :visibility';
            $bind['visibility'] = $visibility;
        }
        if ($q) {
            $where[] = '(ci.title LIKE :q OR ci.body LIKE :q)';
            $bind['q'] = '%' . $q . '%';
        }
        if ($tag) {
            $sql .= ' INNER JOIN content_tags ct ON ct.content_item_id = ci.id
                      INNER JOIN tags t ON t.id = ct.tag_id';
            $where[] = 't.name = :tag';
            $bind['tag'] = $tag;
        }

        if ($where) {
            $sql .= ' WHERE ' . implode(' AND ', $where);
        }

        $sql .= ' ORDER BY ci.created_at DESC';

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($bind);

        return ['data' => $stmt->fetchAll()];
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

        $title = $_POST['title'] ?? pathinfo($file['name'], PATHINFO_FILENAME);
        $body = $_POST['description'] ?? null;
        $contentType = $_POST['content_type'] ?? $this->mapMimeToType($mime);
        $campaignId = isset($_POST['campaign_id']) ? (int) $_POST['campaign_id'] : null;
        $visibility = $_POST['visibility'] ?? 'public';
        $tagsInput = $_POST['tags'] ?? '';

        $allowedVisibility = ['public','private','internal'];
        if (!in_array($visibility, $allowedVisibility, true)) {
            http_response_code(422);
            return ['error' => 'Invalid visibility'];
        }

        $this->pdo->beginTransaction();
        try {
            $stmt = $this->pdo->prepare('INSERT INTO content_items (campaign_id, title, body, content_type, created_by, visibility) VALUES (:campaign_id, :title, :body, :content_type, :created_by, :visibility)');
            $stmt->execute([
                'campaign_id' => $campaignId ?: null,
                'title' => $title,
                'body' => $body ?: null,
                'content_type' => $contentType,
                'created_by' => $user['id'] ?? null,
                'visibility' => $visibility,
            ]);
            $contentId = (int) $this->pdo->lastInsertId();

            $newFileName = $this->uniqueFilename($file['name']);
            $targetPath = rtrim($this->uploadDir, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . $newFileName;
            if (!is_dir(dirname($targetPath))) {
                mkdir(dirname($targetPath), 0775, true);
            }
            if (!move_uploaded_file($file['tmp_name'], $targetPath)) {
                throw new RuntimeException('Failed to save file');
            }

            $stmt = $this->pdo->prepare('INSERT INTO attachments (content_item_id, file_path, mime_type, file_size) VALUES (:content_item_id, :file_path, :mime_type, :file_size)');
            $stmt->execute([
                'content_item_id' => $contentId,
                'file_path' => 'uploads/' . $newFileName,
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

