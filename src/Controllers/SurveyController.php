<?php

declare(strict_types=1);

namespace App\Controllers;

use PDO;
use RuntimeException;

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
        $stmt = $this->pdo->query('SELECT id, title, description, status, campaign_id, created_at FROM surveys ORDER BY created_at DESC');
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
        $input = json_decode(file_get_contents('php://input'), true) ?? [];
        $title = trim($input['title'] ?? '');
        $description = $input['description'] ?? null;
        $campaignId = isset($input['campaign_id']) ? (int) $input['campaign_id'] : null;
        $eventId = isset($input['event_id']) ? (int) $input['event_id'] : null;

        if (!$title) {
            http_response_code(422);
            return ['error' => 'Title is required'];
        }

        if ($eventId) {
            $this->assertEvent($eventId);
        }

        $stmt = $this->pdo->prepare('INSERT INTO surveys (campaign_id, event_id, title, description, status) VALUES (:campaign_id, :event_id, :title, :description, :status)');
        $stmt->execute([
            'campaign_id' => $campaignId ?: null,
            'event_id' => $eventId ?: null,
            'title' => $title,
            'description' => $description ?: null,
            'status' => 'draft',
        ]);

        return ['id' => (int) $this->pdo->lastInsertId(), 'message' => 'Survey created'];
    }

    public function addQuestion(?array $user, array $params = []): array
    {
        $surveyId = (int) ($params['id'] ?? 0);
        $survey = $this->findSurvey($surveyId);

        $input = json_decode(file_get_contents('php://input'), true) ?? [];
        $questionText = trim($input['question_text'] ?? '');
        $questionType = $input['question_type'] ?? 'text';
        $options = $input['options'] ?? [];

        $allowed = ['text','single_choice','multiple_choice','rating'];
        if (!in_array($questionType, $allowed, true)) {
            http_response_code(422);
            return ['error' => 'Invalid question_type'];
        }
        if (!$questionText) {
            http_response_code(422);
            return ['error' => 'question_text is required'];
        }

        $stmt = $this->pdo->prepare('INSERT INTO survey_questions (survey_id, question_text, question_type, options_json) VALUES (:survey_id, :question_text, :question_type, :options_json)');
        $stmt->execute([
            'survey_id' => $survey['id'],
            'question_text' => $questionText,
            'question_type' => $questionType,
            'options_json' => $options ? json_encode($options) : null,
        ]);

        return ['id' => (int) $this->pdo->lastInsertId(), 'message' => 'Question added'];
    }

    public function publish(?array $user, array $params = []): array
    {
        $surveyId = (int) ($params['id'] ?? 0);
        $this->findSurvey($surveyId);

        $stmt = $this->pdo->prepare('UPDATE surveys SET status = :status WHERE id = :id');
        $stmt->execute(['status' => 'published', 'id' => $surveyId]);

        return ['message' => 'Survey published'];
    }

    public function submitResponse(?array $user, array $params = []): array
    {
        $surveyId = (int) ($params['id'] ?? 0);
        $survey = $this->findSurvey($surveyId, allowDraft: false, allowPublic: true);

        $input = json_decode(file_get_contents('php://input'), true) ?? [];
        $responses = $input['responses'] ?? null;
        $audienceMemberId = isset($input['audience_member_id']) ? (int) $input['audience_member_id'] : null;

        if (!$responses || !is_array($responses)) {
            http_response_code(422);
            return ['error' => 'responses array is required'];
        }

        // Ensure referenced questions belong to this survey
        $questionIds = array_map('intval', array_keys($responses));
        if ($questionIds) {
            $in = implode(',', array_fill(0, count($questionIds), '?'));
            $stmt = $this->pdo->prepare("SELECT COUNT(*) FROM survey_questions WHERE survey_id = ? AND id IN ($in)");
            $stmt->execute(array_merge([$surveyId], $questionIds));
            $count = (int) $stmt->fetchColumn();
            if ($count !== count($questionIds)) {
                http_response_code(422);
                return ['error' => 'One or more question IDs are invalid'];
            }
        }

        $stmt = $this->pdo->prepare('INSERT INTO survey_responses (survey_id, audience_member_id, responses_json, submitted_at) VALUES (:survey_id, :audience_member_id, :responses_json, NOW())');
        $stmt->execute([
            'survey_id' => $surveyId,
            'audience_member_id' => $audienceMemberId ?: null,
            'responses_json' => json_encode($responses),
        ]);

        return ['message' => 'Response submitted', 'id' => (int) $this->pdo->lastInsertId()];
    }

    public function exportCsv(?array $user, array $params = []): void
    {
        $surveyId = (int) ($params['id'] ?? 0);
        $survey = $this->findSurvey($surveyId);
        $questions = $this->getQuestions($surveyId);

        $stmt = $this->pdo->prepare('SELECT id, audience_member_id, responses_json, submitted_at FROM survey_responses WHERE survey_id = :sid ORDER BY submitted_at ASC');
        $stmt->execute(['sid' => $surveyId]);

        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="survey_' . $surveyId . '_responses.csv"');

        $out = fopen('php://output', 'w');
        $headers = ['response_id', 'audience_member_id', 'submitted_at'];
        foreach ($questions as $q) {
            $headers[] = 'q' . $q['id'] . ':' . $q['question_text'];
        }
        fputcsv($out, $headers);

        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $resp = json_decode($row['responses_json'] ?? '[]', true) ?: [];
            $line = [
                $row['id'],
                $row['audience_member_id'],
                $row['submitted_at'],
            ];
            foreach ($questions as $q) {
                $line[] = $resp[(string) $q['id']] ?? $resp[$q['id']] ?? '';
            }
            fputcsv($out, $line);
        }
        fclose($out);
        exit;
    }

    public function qrLink(?array $user, array $params = []): array
    {
        $surveyId = (int) ($params['id'] ?? 0);
        $this->findSurvey($surveyId, allowDraft: false);
        $baseUrl = getenv('APP_URL') ?: ('http://' . ($_SERVER['HTTP_HOST'] ?? 'localhost'));
        $publicUrl = rtrim($baseUrl, '/') . '/surveys/respond.html?survey_id=' . $surveyId;

        return [
            'survey_id' => $surveyId,
            'public_url' => $publicUrl,
            'qr_data' => $publicUrl,
        ];
    }

    private function findSurvey(int $id, bool $allowDraft = true, bool $allowPublic = false): array
    {
        $stmt = $this->pdo->prepare('SELECT id, title, description, status, campaign_id FROM surveys WHERE id = :id LIMIT 1');
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
        $stmt = $this->pdo->prepare('SELECT id, question_text, question_type, options_json FROM survey_questions WHERE survey_id = :sid ORDER BY id ASC');
        $stmt->execute(['sid' => $surveyId]);
        return $stmt->fetchAll();
    }

    private function assertEvent(int $id): void
    {
        $stmt = $this->pdo->prepare('SELECT id FROM events WHERE id = :id LIMIT 1');
        $stmt->execute(['id' => $id]);
        if (!$stmt->fetch()) {
            throw new RuntimeException('Event not found');
        }
    }
}


