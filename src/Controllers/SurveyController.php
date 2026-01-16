<?php

declare(strict_types=1);

namespace App\Controllers;

use PDO;
use RuntimeException;
use App\Middleware\RoleMiddleware;

class SurveyController
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
        // Apply filters
        $whereClause = [];
        $params_bind = [];
        
        if (isset($_GET['campaign_id']) && $_GET['campaign_id'] !== '') {
            $whereClause[] = 's.campaign_id = :campaign_id';
            $params_bind['campaign_id'] = (int) $_GET['campaign_id'];
        }
        
        if (isset($_GET['event_id']) && $_GET['event_id'] !== '') {
            $whereClause[] = 's.event_id = :event_id';
            $params_bind['event_id'] = (int) $_GET['event_id'];
        }
        
        if (isset($_GET['status']) && $_GET['status'] !== '') {
            $whereClause[] = 's.status = :status';
            $params_bind['status'] = $_GET['status'];
        }
        
        $where = $whereClause ? 'WHERE ' . implode(' AND ', $whereClause) : '';
        
        $sql = "SELECT s.id, s.title, s.description, s.status, s.published_via, s.campaign_id, s.event_id, 
                       s.created_by, s.published_by, s.published_at, s.closed_at, s.created_at,
                       COUNT(DISTINCT sq.id) as question_count,
                       COUNT(DISTINCT sr.id) as total_responses
                FROM `campaign_department_surveys` s
                LEFT JOIN `campaign_department_survey_questions` sq ON sq.survey_id = s.id
                LEFT JOIN `campaign_department_survey_responses` sr ON sr.survey_id = s.id
                $where
                GROUP BY s.id
                ORDER BY s.created_at DESC";
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params_bind);
        $surveys = $stmt->fetchAll();
        
        return ['data' => $surveys];
    }

    public function show(?array $user, array $params = []): array
    {
        $id = (int) ($params['id'] ?? 0);
        $survey = $this->findSurvey($id, allowDraft: false, allowPublic: true);

        $questions = $this->getQuestions($id);
        $survey['questions'] = $questions;
        return ['data' => $survey];
    }

    public function store(?array $user, array $params = []): array
    {
        if (!$user) {
            http_response_code(401);
            return ['error' => 'Authentication required'];
        }

        $input = json_decode(file_get_contents('php://input'), true) ?? [];
        $title = trim($input['title'] ?? '');
        $description = $input['description'] ?? null;
        $campaignId = isset($input['campaign_id']) ? (int) $input['campaign_id'] : null;
        $eventId = isset($input['event_id']) ? (int) $input['event_id'] : null;

        if (!$title) {
            http_response_code(422);
            return ['error' => 'Title is required'];
        }

        // Validation: Must be linked to exactly one campaign or event
        if (!$campaignId && !$eventId) {
            http_response_code(422);
            return ['error' => 'Survey must be linked to either a campaign or an event'];
        }
        if ($campaignId && $eventId) {
            http_response_code(422);
            return ['error' => 'Survey can only be linked to either a campaign OR an event, not both'];
        }

        if ($campaignId) {
            $this->assertCampaign($campaignId);
        }
        if ($eventId) {
            $this->assertEvent($eventId);
        }

        $userId = (int) ($user['id'] ?? 0);

        $stmt = $this->pdo->prepare('INSERT INTO `campaign_department_surveys` (campaign_id, event_id, title, description, status, created_by) VALUES (:campaign_id, :event_id, :title, :description, :status, :created_by)');
        $stmt->execute([
            'campaign_id' => $campaignId ?: null,
            'event_id' => $eventId ?: null,
            'title' => $title,
            'description' => $description ?: null,
            'status' => 'draft',
            'created_by' => $userId,
        ]);

        $surveyId = (int) $this->pdo->lastInsertId();
        $this->logAudit($surveyId, $userId, 'created', null, null, 'Survey created');

        return ['id' => $surveyId, 'message' => 'Survey created'];
    }

    public function addQuestion(?array $user, array $params = []): array
    {
        if (!$user) {
            http_response_code(401);
            return ['error' => 'Authentication required'];
        }

        $surveyId = (int) ($params['id'] ?? 0);
        $survey = $this->findSurvey($surveyId);

        // Lock survey structure once responses exist
        $stmt = $this->pdo->prepare('SELECT COUNT(*) FROM `campaign_department_survey_responses` WHERE survey_id = :sid');
        $stmt->execute(['sid' => $surveyId]);
        $responseCount = (int) $stmt->fetchColumn();
        if ($responseCount > 0) {
            http_response_code(403);
            return ['error' => 'Cannot modify survey structure after responses have been submitted'];
        }

        $input = json_decode(file_get_contents('php://input'), true) ?? [];
        $questionText = trim($input['question_text'] ?? '');
        $questionType = $input['question_type'] ?? 'open_ended';
        $options = $input['options'] ?? [];
        $questionOrder = isset($input['question_order']) ? (int) $input['question_order'] : 0;
        $requiredFlag = isset($input['required_flag']) ? (bool) $input['required_flag'] : false;

        $allowed = ['rating', 'multiple_choice', 'yes_no', 'open_ended', 'text', 'single_choice'];
        if (!in_array($questionType, $allowed, true)) {
            http_response_code(422);
            return ['error' => 'Invalid question_type. Allowed: ' . implode(', ', $allowed)];
        }
        if (!$questionText) {
            http_response_code(422);
            return ['error' => 'question_text is required'];
        }

        // Auto-increment order if not provided
        if ($questionOrder === 0) {
            $stmt = $this->pdo->prepare('SELECT COALESCE(MAX(question_order), 0) + 1 FROM `campaign_department_survey_questions` WHERE survey_id = :sid');
            $stmt->execute(['sid' => $surveyId]);
            $questionOrder = (int) $stmt->fetchColumn();
        }

        $stmt = $this->pdo->prepare('INSERT INTO `campaign_department_survey_questions` (survey_id, question_text, question_type, options_json, question_order, required_flag) VALUES (:survey_id, :question_text, :question_type, :options_json, :question_order, :required_flag)');
        $stmt->execute([
            'survey_id' => $surveyId,
            'question_text' => $questionText,
            'question_type' => $questionType,
            'options_json' => $options ? json_encode($options) : null,
            'question_order' => $questionOrder,
            'required_flag' => $requiredFlag ? 1 : 0,
        ]);

        $questionId = (int) $this->pdo->lastInsertId();
        $userId = (int) ($user['id'] ?? 0);
        $this->logAudit($surveyId, $userId, 'question_added', 'question_id', null, $questionId);

        return ['id' => $questionId, 'message' => 'Question added'];
    }

    public function publish(?array $user, array $params = []): array
    {
        if (!$user) {
            http_response_code(401);
            return ['error' => 'Authentication required'];
        }

        $surveyId = (int) ($params['id'] ?? 0);
        $survey = $this->findSurvey($surveyId);

        // Check if survey has at least one question
        $stmt = $this->pdo->prepare('SELECT COUNT(*) FROM `campaign_department_survey_questions` WHERE survey_id = :sid');
        $stmt->execute(['sid' => $surveyId]);
        $questionCount = (int) $stmt->fetchColumn();
        if ($questionCount === 0) {
            http_response_code(422);
            return ['error' => 'Survey must have at least one question before publishing'];
        }

        $input = json_decode(file_get_contents('php://input'), true) ?? [];
        $publishedVia = $input['published_via'] ?? 'link';
        if (!in_array($publishedVia, ['link', 'qr_code', 'both'], true)) {
            $publishedVia = 'link';
        }

        $userId = (int) ($user['id'] ?? 0);

        $stmt = $this->pdo->prepare('UPDATE `campaign_department_surveys` SET status = :status, published_via = :published_via, published_by = :published_by, published_at = NOW() WHERE id = :id');
        $stmt->execute([
            'status' => 'published',
            'published_via' => $publishedVia,
            'published_by' => $userId,
            'id' => $surveyId
        ]);

        $this->logAudit($surveyId, $userId, 'published', 'status', 'draft', 'published');

        return ['message' => 'Survey published', 'published_via' => $publishedVia];
    }

    public function submitResponse(?array $user, array $params = []): array
    {
        $surveyId = (int) ($params['id'] ?? 0);
        $survey = $this->findSurvey($surveyId, allowDraft: false, allowPublic: true);

        // Check if survey is closed
        if ($survey['status'] === 'closed') {
            http_response_code(403);
            return ['error' => 'Survey is closed and no longer accepting responses'];
        }

        $input = json_decode(file_get_contents('php://input'), true) ?? [];
        $responses = $input['responses'] ?? null;
        $audienceMemberId = isset($input['audience_member_id']) ? (int) $input['audience_member_id'] : null;
        $respondentIdentifier = $input['respondent_identifier'] ?? null;

        if (!$responses || !is_array($responses)) {
            http_response_code(422);
            return ['error' => 'responses array is required'];
        }

        // Get all questions for validation
        $questions = $this->getQuestions($surveyId);
        $questionIds = array_map('intval', array_keys($responses));
        
        // Validate required questions
        foreach ($questions as $q) {
            if ($q['required_flag'] && !isset($responses[$q['id']])) {
                http_response_code(422);
                return ['error' => 'Required question ' . $q['id'] . ' is missing a response'];
            }
        }

        // Ensure referenced questions belong to this survey
        if ($questionIds) {
            $in = implode(',', array_fill(0, count($questionIds), '?'));
            $stmt = $this->pdo->prepare("SELECT COUNT(*) FROM `campaign_department_survey_questions` WHERE survey_id = ? AND id IN ($in)");
            $stmt->execute(array_merge([$surveyId], $questionIds));
            $count = (int) $stmt->fetchColumn();
            if ($count !== count($questionIds)) {
                http_response_code(422);
                return ['error' => 'One or more question IDs are invalid'];
            }
        }

        // Insert response
        $stmt = $this->pdo->prepare('INSERT INTO `campaign_department_survey_responses` (survey_id, audience_member_id, respondent_identifier, responses_json, submission_timestamp) VALUES (:survey_id, :audience_member_id, :respondent_identifier, :responses_json, NOW())');
        $stmt->execute([
            'survey_id' => $surveyId,
            'audience_member_id' => $audienceMemberId ?: null,
            'respondent_identifier' => $respondentIdentifier ?: null,
            'responses_json' => json_encode($responses),
        ]);

        $responseId = (int) $this->pdo->lastInsertId();

        // Insert individual response details for better querying
        foreach ($responses as $questionId => $responseValue) {
            $stmt = $this->pdo->prepare('INSERT INTO `campaign_department_survey_response_details` (response_id, question_id, response_value) VALUES (:response_id, :question_id, :response_value)');
            $stmt->execute([
                'response_id' => $responseId,
                'question_id' => (int) $questionId,
                'response_value' => is_array($responseValue) ? json_encode($responseValue) : (string) $responseValue,
            ]);
        }

        // Update aggregated results
        $this->updateAggregatedResults($surveyId);

        // Log audit
        $userId = $user ? (int) ($user['id'] ?? 0) : null;
        $this->logAudit($surveyId, $userId, 'response_submitted', 'response_id', null, $responseId);

        return ['message' => 'Response submitted', 'id' => $responseId];
    }

    public function exportCsv(?array $user, array $params = []): void
    {
        if (!$user) {
            http_response_code(401);
            return;
        }

        $surveyId = (int) ($params['id'] ?? 0);
        $survey = $this->findSurvey($surveyId);
        $questions = $this->getQuestions($surveyId);

        $stmt = $this->pdo->prepare('SELECT id, audience_member_id, respondent_identifier, responses_json, submission_timestamp FROM `campaign_department_survey_responses` WHERE survey_id = :sid ORDER BY submission_timestamp ASC');
        $stmt->execute(['sid' => $surveyId]);

        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="survey_' . $surveyId . '_responses_' . date('Y-m-d') . '.csv"');

        $out = fopen('php://output', 'w');
        
        // Metadata header
        fputcsv($out, ['Survey ID', $surveyId]);
        fputcsv($out, ['Survey Title', $survey['title']]);
        fputcsv($out, ['Linked Campaign ID', $survey['campaign_id'] ?? '']);
        fputcsv($out, ['Linked Event ID', $survey['event_id'] ?? '']);
        fputcsv($out, ['Export Date', date('Y-m-d H:i:s')]);
        fputcsv($out, []); // Empty row

        // Data headers
        $headers = ['response_id', 'audience_member_id', 'respondent_identifier', 'submission_timestamp'];
        foreach ($questions as $q) {
            $headers[] = 'q' . $q['id'] . ':' . $q['question_text'];
        }
        fputcsv($out, $headers);

        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $resp = json_decode($row['responses_json'] ?? '[]', true) ?: [];
            $line = [
                $row['id'],
                $row['audience_member_id'] ?? '',
                $row['respondent_identifier'] ?? '',
                $row['submission_timestamp'],
            ];
            foreach ($questions as $q) {
                $value = $resp[(string) $q['id']] ?? $resp[$q['id']] ?? '';
                $line[] = is_array($value) ? json_encode($value) : $value;
            }
            fputcsv($out, $line);
        }
        fclose($out);
        exit;
    }

    public function qrLink(?array $user, array $params = []): array
    {
        if (!$user) {
            http_response_code(401);
            return ['error' => 'Authentication required'];
        }

        $surveyId = (int) ($params['id'] ?? 0);
        $survey = $this->findSurvey($surveyId, allowDraft: false);
        
        if ($survey['status'] !== 'published') {
            http_response_code(403);
            return ['error' => 'Survey must be published to generate QR code'];
        }

        $baseUrl = getenv('APP_URL') ?: ('http://' . ($_SERVER['HTTP_HOST'] ?? 'localhost'));
        $publicUrl = rtrim($baseUrl, '/') . '/surveys/respond.html?survey_id=' . $surveyId;

        return [
            'survey_id' => $surveyId,
            'public_url' => $publicUrl,
            'qr_data' => $publicUrl,
        ];
    }

    public function closeSurvey(?array $user, array $params = []): array
    {
        if (!$user) {
            http_response_code(401);
            return ['error' => 'Authentication required'];
        }

        $surveyId = (int) ($params['id'] ?? 0);
        $survey = $this->findSurvey($surveyId);

        if ($survey['status'] === 'closed') {
            return ['message' => 'Survey is already closed'];
        }

        $userId = (int) ($user['id'] ?? 0);

        $stmt = $this->pdo->prepare('UPDATE `campaign_department_surveys` SET status = :status, closed_at = NOW() WHERE id = :id');
        $stmt->execute(['status' => 'closed', 'id' => $surveyId]);

        $this->logAudit($surveyId, $userId, 'closed', 'status', $survey['status'], 'closed');

        return ['message' => 'Survey closed'];
    }

    public function getResponses(?array $user, array $params = []): array
    {
        if (!$user) {
            http_response_code(401);
            return ['error' => 'Authentication required'];
        }

        $surveyId = (int) ($params['id'] ?? 0);
        $this->findSurvey($surveyId);

        // Role-based access: Only admins and campaign managers can view responses
        $userRole = RoleMiddleware::getUserRole($user, $this->pdo);
        if (!in_array($userRole, ['Barangay Administrator', 'Barangay Staff', 'Campaign Manager'], true)) {
            http_response_code(403);
            return ['error' => 'Insufficient permissions to view responses'];
        }

        $page = isset($_GET['page']) ? max(1, (int) $_GET['page']) : 1;
        $limit = isset($_GET['limit']) ? min(100, max(1, (int) $_GET['limit'])) : 20;
        $offset = ($page - 1) * $limit;

        $stmt = $this->pdo->prepare('SELECT id, survey_id, audience_member_id, respondent_identifier, responses_json, submission_timestamp FROM `campaign_department_survey_responses` WHERE survey_id = :sid ORDER BY submission_timestamp DESC LIMIT :limit OFFSET :offset');
        $stmt->bindValue(':sid', $surveyId, PDO::PARAM_INT);
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();
        $responses = $stmt->fetchAll();

        // Get total count
        $stmt = $this->pdo->prepare('SELECT COUNT(*) FROM `campaign_department_survey_responses` WHERE survey_id = :sid');
        $stmt->execute(['sid' => $surveyId]);
        $total = (int) $stmt->fetchColumn();

        return [
            'data' => $responses,
            'pagination' => [
                'page' => $page,
                'limit' => $limit,
                'total' => $total,
                'pages' => (int) ceil($total / $limit)
            ]
        ];
    }

    public function aggregatedResults(?array $user, array $params = []): array
    {
        if (!$user) {
            http_response_code(401);
            return ['error' => 'Authentication required'];
        }

        $surveyId = (int) ($params['id'] ?? 0);
        $survey = $this->findSurvey($surveyId);

        // Role-based access
        $userRole = RoleMiddleware::getUserRole($user, $this->pdo);
        if (!in_array($userRole, ['Barangay Administrator', 'Barangay Staff', 'Campaign Manager'], true)) {
            http_response_code(403);
            return ['error' => 'Insufficient permissions to view aggregated results'];
        }

        $questions = $this->getQuestions($surveyId);
        $results = [];

        foreach ($questions as $question) {
            $questionId = (int) $question['id'];
            $questionType = $question['question_type'];

            // Get aggregated result from cache or compute
            $stmt = $this->pdo->prepare('SELECT average_rating, response_distribution, total_responses FROM `campaign_department_survey_aggregated_results` WHERE survey_id = :sid AND question_id = :qid');
            $stmt->execute(['sid' => $surveyId, 'qid' => $questionId]);
            $aggregated = $stmt->fetch();

            if (!$aggregated) {
                // Compute on the fly
                $aggregated = $this->computeAggregatedResult($surveyId, $questionId, $questionType);
            }

            $results[] = [
                'question_id' => $questionId,
                'question_text' => $question['question_text'],
                'question_type' => $questionType,
                'average_rating' => $aggregated['average_rating'] ?? null,
                'response_distribution' => $aggregated['response_distribution'] ? json_decode($aggregated['response_distribution'], true) : null,
                'total_responses' => (int) ($aggregated['total_responses'] ?? 0)
            ];
        }

        return [
            'survey_id' => $surveyId,
            'survey_title' => $survey['title'],
            'total_responses' => $this->getTotalResponseCount($surveyId),
            'results' => $results
        ];
    }

    public function exportAggregatedCsv(?array $user, array $params = []): void
    {
        if (!$user) {
            http_response_code(401);
            return;
        }

        $surveyId = (int) ($params['id'] ?? 0);
        $survey = $this->findSurvey($surveyId);

        $results = $this->aggregatedResults($user, $params);
        if (isset($results['error'])) {
            http_response_code(403);
            header('Content-Type: application/json');
            echo json_encode($results);
            exit;
        }

        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="survey_' . $surveyId . '_aggregated_results.csv"');

        $out = fopen('php://output', 'w');
        fputcsv($out, ['Survey ID', 'Survey Title', 'Total Responses', 'Export Date']);
        fputcsv($out, [$surveyId, $survey['title'], $results['total_responses'], date('Y-m-d H:i:s')]);
        fputcsv($out, []); // Empty row
        fputcsv($out, ['Question ID', 'Question Text', 'Question Type', 'Average Rating', 'Response Distribution', 'Total Responses']);

        foreach ($results['results'] as $result) {
            fputcsv($out, [
                $result['question_id'],
                $result['question_text'],
                $result['question_type'],
                $result['average_rating'] ?? '',
                $result['response_distribution'] ? json_encode($result['response_distribution']) : '',
                $result['total_responses']
            ]);
        }

        fclose($out);
        exit;
    }

    private function findSurvey(int $id, bool $allowDraft = true, bool $allowPublic = false): array
    {
        $stmt = $this->pdo->prepare('SELECT id, title, description, status, campaign_id, event_id, published_via, created_by, published_by, published_at, closed_at FROM `campaign_department_surveys` WHERE id = :id LIMIT 1');
        $stmt->execute(['id' => $id]);
        $survey = $stmt->fetch();
        if (!$survey) {
            http_response_code(404);
            throw new RuntimeException('Survey not found');
        }
        if (!$allowDraft && $survey['status'] !== 'published') {
            http_response_code(403);
            throw new RuntimeException('Survey not published');
        }
        if (!$allowPublic && !($GLOBALS['matched']['middleware'] ?? false)) {
            // maintain backward compatibility; relying on route middleware to protect
        }
        return $survey;
    }

    private function getQuestions(int $surveyId): array
    {
        $stmt = $this->pdo->prepare('SELECT id, question_text, question_type, options_json, question_order, required_flag FROM `campaign_department_survey_questions` WHERE survey_id = :sid ORDER BY question_order ASC, id ASC');
        $stmt->execute(['sid' => $surveyId]);
        return $stmt->fetchAll();
    }

    private function assertEvent(int $id): void
    {
        $stmt = $this->pdo->prepare('SELECT id FROM `campaign_department_events` WHERE id = :id LIMIT 1');
        $stmt->execute(['id' => $id]);
        if (!$stmt->fetch()) {
            throw new RuntimeException('Event not found');
        }
    }

    private function assertCampaign(int $id): void
    {
        $stmt = $this->pdo->prepare('SELECT id FROM `campaign_department_campaigns` WHERE id = :id LIMIT 1');
        $stmt->execute(['id' => $id]);
        if (!$stmt->fetch()) {
            throw new RuntimeException('Campaign not found');
        }
    }

    private function logAudit(int $surveyId, ?int $userId, string $actionType, ?string $fieldName, ?string $oldValue, ?string $newValue, ?string $changeDetails = null): void
    {
        $stmt = $this->pdo->prepare('INSERT INTO `campaign_department_survey_audit_log` (survey_id, user_id, action_type, field_name, old_value, new_value, change_details) VALUES (:survey_id, :user_id, :action_type, :field_name, :old_value, :new_value, :change_details)');
        $stmt->execute([
            'survey_id' => $surveyId,
            'user_id' => $userId,
            'action_type' => $actionType,
            'field_name' => $fieldName,
            'old_value' => $oldValue,
            'new_value' => $newValue,
            'change_details' => $changeDetails
        ]);
    }

    private function updateAggregatedResults(int $surveyId): void
    {
        $questions = $this->getQuestions($surveyId);
        
        foreach ($questions as $question) {
            $questionId = (int) $question['id'];
            $aggregated = $this->computeAggregatedResult($surveyId, $questionId, $question['question_type']);

            // Upsert aggregated result
            $stmt = $this->pdo->prepare('INSERT INTO `campaign_department_survey_aggregated_results` (survey_id, question_id, average_rating, response_distribution, total_responses) VALUES (:sid, :qid, :avg, :dist, :total) ON DUPLICATE KEY UPDATE average_rating = VALUES(average_rating), response_distribution = VALUES(response_distribution), total_responses = VALUES(total_responses), computed_at = NOW()');
            $stmt->execute([
                'sid' => $surveyId,
                'qid' => $questionId,
                'avg' => $aggregated['average_rating'],
                'dist' => $aggregated['response_distribution'] ? json_encode($aggregated['response_distribution']) : null,
                'total' => $aggregated['total_responses']
            ]);
        }
    }

    private function computeAggregatedResult(int $surveyId, int $questionId, string $questionType): array
    {
        $stmt = $this->pdo->prepare('SELECT response_value FROM `campaign_department_survey_response_details` WHERE survey_id = :sid AND question_id = :qid');
        $stmt->execute(['sid' => $surveyId, 'qid' => $questionId]);
        $responses = $stmt->fetchAll(PDO::FETCH_COLUMN);

        $totalResponses = count($responses);
        $averageRating = null;
        $distribution = [];

        if ($questionType === 'rating') {
            $ratings = array_filter(array_map('floatval', $responses));
            if (count($ratings) > 0) {
                $averageRating = round(array_sum($ratings) / count($ratings), 2);
            }
        } else {
            // Count distribution
            foreach ($responses as $response) {
                $value = is_string($response) ? $response : json_encode($response);
                $distribution[$value] = ($distribution[$value] ?? 0) + 1;
            }
        }

        return [
            'average_rating' => $averageRating,
            'response_distribution' => $distribution,
            'total_responses' => $totalResponses
        ];
    }

    private function getTotalResponseCount(int $surveyId): int
    {
        $stmt = $this->pdo->prepare('SELECT COUNT(*) FROM `campaign_department_survey_responses` WHERE survey_id = :sid');
        $stmt->execute(['sid' => $surveyId]);
        return (int) $stmt->fetchColumn();
    }
}


