<?php

declare(strict_types=1);

namespace App\Controllers;

use PDO;

class SearchController
{
    public function __construct(
        private PDO $pdo,
        private string $jwtSecret,
        private string $jwtIssuer,
        private string $jwtAudience,
        private int $jwtExpirySeconds
    ) {}

    /**
     * Global search across campaigns, events, content, and surveys
     */
    public function search(?array $user, array $params = []): array
    {
        if (!$user) {
            http_response_code(401);
            return ['error' => 'Authentication required'];
        }

        $query = trim($_GET['q'] ?? '');
        $type = $_GET['type'] ?? 'all'; // all, campaigns, events, content, surveys
        $limit = min((int) ($_GET['limit'] ?? 20), 50);

        if (strlen($query) < 2) {
            return [
                'data' => [],
                'message' => 'Search query must be at least 2 characters'
            ];
        }

        $results = [];
        $searchTerm = '%' . $query . '%';

        try {
            // Search campaigns
            if ($type === 'all' || $type === 'campaigns') {
                $stmt = $this->pdo->prepare("
                    SELECT 
                        id,
                        title,
                        description,
                        category,
                        status,
                        'campaign' as result_type,
                        created_at
                    FROM campaign_department_campaigns
                    WHERE title LIKE :search OR description LIKE :search2 OR category LIKE :search3
                    ORDER BY created_at DESC
                    LIMIT :limit
                ");
                $stmt->bindValue(':search', $searchTerm, PDO::PARAM_STR);
                $stmt->bindValue(':search2', $searchTerm, PDO::PARAM_STR);
                $stmt->bindValue(':search3', $searchTerm, PDO::PARAM_STR);
                $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
                $stmt->execute();
                
                while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                    $results[] = [
                        'id' => $row['id'],
                        'title' => $row['title'],
                        'subtitle' => $row['category'] ? ucfirst($row['category']) . ' Campaign' : 'Campaign',
                        'description' => $row['description'] ? substr($row['description'], 0, 100) . '...' : '',
                        'type' => 'campaign',
                        'status' => $row['status'],
                        'url' => '/public/campaigns.php?view=' . $row['id'],
                        'icon' => 'fa-bullhorn',
                        'created_at' => $row['created_at']
                    ];
                }
            }

            // Search events
            if ($type === 'all' || $type === 'events') {
                try {
                    $stmt = $this->pdo->prepare("
                        SELECT 
                            id,
                            name as title,
                            description,
                            event_type,
                            status,
                            event_date,
                            'event' as result_type,
                            created_at
                        FROM campaign_department_events
                        WHERE name LIKE :search OR description LIKE :search2 OR venue LIKE :search3
                        ORDER BY event_date DESC
                        LIMIT :limit
                    ");
                    $stmt->bindValue(':search', $searchTerm, PDO::PARAM_STR);
                    $stmt->bindValue(':search2', $searchTerm, PDO::PARAM_STR);
                    $stmt->bindValue(':search3', $searchTerm, PDO::PARAM_STR);
                    $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
                    $stmt->execute();
                    
                    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                        $results[] = [
                            'id' => $row['id'],
                            'title' => $row['title'],
                            'subtitle' => ucfirst($row['event_type'] ?? 'Event') . ($row['event_date'] ? ' - ' . date('M j, Y', strtotime($row['event_date'])) : ''),
                            'description' => $row['description'] ? substr($row['description'], 0, 100) . '...' : '',
                            'type' => 'event',
                            'status' => $row['status'],
                            'url' => '/public/events.php?view=' . $row['id'],
                            'icon' => 'fa-calendar',
                            'created_at' => $row['created_at']
                        ];
                    }
                } catch (\PDOException $e) {
                    error_log('SearchController: Events search failed: ' . $e->getMessage());
                }
            }

            // Search content
            if ($type === 'all' || $type === 'content') {
                try {
                    $stmt = $this->pdo->prepare("
                        SELECT 
                            id,
                            title,
                            body as description,
                            content_type,
                            status,
                            'content' as result_type,
                            created_at
                        FROM campaign_department_content_items
                        WHERE title LIKE :search OR body LIKE :search2
                        ORDER BY created_at DESC
                        LIMIT :limit
                    ");
                    $stmt->bindValue(':search', $searchTerm, PDO::PARAM_STR);
                    $stmt->bindValue(':search2', $searchTerm, PDO::PARAM_STR);
                    $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
                    $stmt->execute();
                    
                    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                        $results[] = [
                            'id' => $row['id'],
                            'title' => $row['title'],
                            'subtitle' => ucfirst($row['content_type'] ?? 'Content'),
                            'description' => $row['description'] ? substr(strip_tags($row['description']), 0, 100) . '...' : '',
                            'type' => 'content',
                            'status' => $row['status'],
                            'url' => '/public/content.php?view=' . $row['id'],
                            'icon' => 'fa-file-alt',
                            'created_at' => $row['created_at']
                        ];
                    }
                } catch (\PDOException $e) {
                    error_log('SearchController: Content search failed: ' . $e->getMessage());
                }
            }

            // Search surveys
            if ($type === 'all' || $type === 'surveys') {
                try {
                    $stmt = $this->pdo->prepare("
                        SELECT 
                            id,
                            title,
                            description,
                            status,
                            'survey' as result_type,
                            created_at
                        FROM campaign_department_surveys
                        WHERE title LIKE :search OR description LIKE :search2
                        ORDER BY created_at DESC
                        LIMIT :limit
                    ");
                    $stmt->bindValue(':search', $searchTerm, PDO::PARAM_STR);
                    $stmt->bindValue(':search2', $searchTerm, PDO::PARAM_STR);
                    $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
                    $stmt->execute();
                    
                    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                        $results[] = [
                            'id' => $row['id'],
                            'title' => $row['title'],
                            'subtitle' => 'Survey',
                            'description' => $row['description'] ? substr($row['description'], 0, 100) . '...' : '',
                            'type' => 'survey',
                            'status' => $row['status'],
                            'url' => '/public/surveys.php?view=' . $row['id'],
                            'icon' => 'fa-clipboard-list',
                            'created_at' => $row['created_at']
                        ];
                    }
                } catch (\PDOException $e) {
                    error_log('SearchController: Surveys search failed: ' . $e->getMessage());
                }
            }

            // Sort all results by created_at descending
            usort($results, function($a, $b) {
                return strtotime($b['created_at'] ?? '1970-01-01') - strtotime($a['created_at'] ?? '1970-01-01');
            });

            // Limit total results
            $results = array_slice($results, 0, $limit);

            return [
                'success' => true,
                'data' => $results,
                'total' => count($results),
                'query' => $query
            ];

        } catch (\Throwable $e) {
            error_log('SearchController::search error: ' . $e->getMessage());
            http_response_code(500);
            return [
                'success' => false,
                'error' => 'Search failed: ' . $e->getMessage(),
                'data' => []
            ];
        }
    }
}
