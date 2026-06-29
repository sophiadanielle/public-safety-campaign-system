<?php
declare(strict_types=1);

ini_set('display_errors', '0');
error_reporting(E_ALL);

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');

const CRIME_REPORTS_API_URL = 'https://crime-analytics.alertaraqc.com/api/crimes';

function load_local_env(): void
{
    $envPath = dirname(__DIR__, 2) . '/.env';
    if (!is_file($envPath) || !is_readable($envPath)) {
        return;
    }

    $lines = file($envPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    if ($lines === false) {
        return;
    }

    foreach ($lines as $line) {
        $line = trim($line);
        if ($line === '' || $line[0] === '#' || strpos($line, '=') === false) {
            continue;
        }

        [$key, $value] = explode('=', $line, 2);
        $key = trim($key);
        $value = trim($value);

        if (
            strlen($value) >= 2
            && (($value[0] === '"' && substr($value, -1) === '"') || ($value[0] === "'" && substr($value, -1) === "'"))
        ) {
            $value = substr($value, 1, -1);
        }

        if ($key !== '' && getenv($key) === false) {
            putenv($key . '=' . $value);
            $_ENV[$key] = $value;
        }
    }
}

function json_response(array $payload, int $statusCode = 200): void
{
    http_response_code($statusCode);
    echo json_encode($payload, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    exit;
}

function read_json_body(): array
{
    $raw = file_get_contents('php://input');
    if ($raw === false || trim($raw) === '') {
        return [];
    }

    $decoded = json_decode($raw, true);
    return is_array($decoded) ? $decoded : [];
}

function get_env_value(string $key): ?string
{
    $value = getenv($key);
    if ($value !== false && trim((string) $value) !== '') {
        return trim((string) $value);
    }

    if (isset($_ENV[$key]) && trim((string) $_ENV[$key]) !== '') {
        return trim((string) $_ENV[$key]);
    }

    return null;
}

function http_json_request(string $url, string $method = 'GET', ?array $body = null, array $headers = []): array
{
    $headers[] = 'Accept: application/json';
    $payload = $body === null ? null : json_encode($body, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

    if ($payload !== null) {
        $headers[] = 'Content-Type: application/json';
    }

    if (function_exists('curl_init')) {
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_TIMEOUT => 20,
            CURLOPT_CONNECTTIMEOUT => 8,
            CURLOPT_CUSTOMREQUEST => $method,
            CURLOPT_HTTPHEADER => $headers,
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_SSL_VERIFYHOST => 2,
        ]);

        if ($payload !== null) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
        }

        $responseBody = curl_exec($ch);
        $statusCode = (int) curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
        $error = curl_error($ch);
        curl_close($ch);

        if ($responseBody === false || $statusCode >= 400) {
            throw new RuntimeException($error ?: 'HTTP request failed with status ' . $statusCode);
        }
    } else {
        $options = [
            'http' => [
                'method' => $method,
                'header' => implode("\r\n", $headers),
                'timeout' => 20,
                'ignore_errors' => true,
            ],
        ];

        if ($payload !== null) {
            $options['http']['content'] = $payload;
        }

        $responseBody = file_get_contents($url, false, stream_context_create($options));
        if ($responseBody === false) {
            throw new RuntimeException('HTTP request failed');
        }
    }

    $decoded = json_decode((string) $responseBody, true);
    if (!is_array($decoded)) {
        throw new RuntimeException('Remote endpoint returned invalid JSON');
    }

    return $decoded;
}

function is_record_array(array $value): bool
{
    foreach ($value as $item) {
        if (is_array($item)) {
            return true;
        }
    }

    return false;
}

function is_list_array(array $value): bool
{
    if ($value === []) {
        return true;
    }

    return array_keys($value) === range(0, count($value) - 1);
}

function normalize_records($payload, int $depth = 0): array
{
    if ($depth > 5 || $payload === null) {
        return [];
    }

    if (is_array($payload)) {
        if (is_list_array($payload)) {
            if (is_record_array($payload)) {
                return array_values(array_filter($payload, 'is_array'));
            }

            foreach ($payload as $item) {
                $nested = normalize_records($item, $depth + 1);
                if ($nested) {
                    return $nested;
                }
            }

            return [];
        }

        foreach (['data', 'crimes', 'incidents', 'records', 'reports', 'results', 'items'] as $key) {
            if (array_key_exists($key, $payload)) {
                $nested = normalize_records($payload[$key], $depth + 1);
                if ($nested) {
                    return $nested;
                }
            }
        }

        foreach ($payload as $value) {
            $nested = normalize_records($value, $depth + 1);
            if ($nested) {
                return $nested;
            }
        }
    }

    return [];
}

function incident_title(array $record): string
{
    $title = $record['incident_title']
        ?? $record['incidentTitle']
        ?? $record['title']
        ?? ($record['category']['category_name'] ?? null)
        ?? 'Uncategorized Incident';

    $title = trim((string) $title);
    return $title !== '' ? $title : 'Uncategorized Incident';
}

function groups_from_records(array $records): array
{
    $groups = [];

    foreach ($records as $record) {
        if (!is_array($record)) {
            continue;
        }

        $title = incident_title($record);
        if (!isset($groups[$title])) {
            $groups[$title] = [
                'incident_title' => $title,
                'report_count' => 0,
            ];
        }

        $groups[$title]['report_count']++;
    }

    usort($groups, static function (array $a, array $b): int {
        return ($b['report_count'] <=> $a['report_count'])
            ?: strcmp($a['incident_title'], $b['incident_title']);
    });

    return $groups;
}

function fallback_recommendations(array $groups): array
{
    return array_map(static function (array $group): array {
        return [
            'incident_title' => (string) ($group['incident_title'] ?? 'Uncategorized Incident'),
            'recommended_campaign_title' => 'Community Awareness Campaign Against ' . (string) ($group['incident_title'] ?? 'Uncategorized Incident'),
        ];
    }, $groups);
}

function extract_json_from_text(string $text): ?array
{
    $text = trim($text);
    if ($text === '') {
        return null;
    }

    $decoded = json_decode($text, true);
    if (is_array($decoded)) {
        return $decoded;
    }

    if (preg_match('/```(?:json)?\s*(.*?)```/is', $text, $matches)) {
        $decoded = json_decode(trim($matches[1]), true);
        if (is_array($decoded)) {
            return $decoded;
        }
    }

    $start = strpos($text, '[');
    $end = strrpos($text, ']');
    if ($start !== false && $end !== false && $end > $start) {
        $decoded = json_decode(substr($text, $start, $end - $start + 1), true);
        if (is_array($decoded)) {
            return $decoded;
        }
    }

    return null;
}

function parse_gemini_text(array $response): string
{
    if (isset($response['output_text']) && is_string($response['output_text'])) {
        return $response['output_text'];
    }

    if (isset($response['candidates'][0]['content']['parts']) && is_array($response['candidates'][0]['content']['parts'])) {
        $parts = [];
        foreach ($response['candidates'][0]['content']['parts'] as $part) {
            if (isset($part['text']) && is_string($part['text'])) {
                $parts[] = $part['text'];
            }
        }
        return trim(implode("\n", $parts));
    }

    if (isset($response['response']['candidates'][0]['content']['parts']) && is_array($response['response']['candidates'][0]['content']['parts'])) {
        $parts = [];
        foreach ($response['response']['candidates'][0]['content']['parts'] as $part) {
            if (isset($part['text']) && is_string($part['text'])) {
                $parts[] = $part['text'];
            }
        }
        return trim(implode("\n", $parts));
    }

    return '';
}

function gemini_recommendations(array $groups): ?array
{
    $apiKey = get_env_value('GEMINI_API_KEY') ?? get_env_value('GOOGLE_GEMINI_API_KEY');
    if ($apiKey === null || !$groups) {
        return null;
    }

    $model = get_env_value('GEMINI_MODEL') ?? 'gemini-2.5-flash';
    $limitedGroups = array_slice($groups, 0, 40);
    $prompt = "Generate concise public-safety campaign titles from crime report incident groups. Return only a JSON array. Each item must have incident_title and recommended_campaign_title. Keep titles professional, specific, and suitable for a barangay public safety campaign.\n\nIncident groups:\n" . json_encode($limitedGroups, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

    try {
        $interactionResponse = http_json_request(
            'https://generativelanguage.googleapis.com/v1beta/interactions',
            'POST',
            [
                'model' => $model,
                'input' => $prompt,
            ],
            ['x-goog-api-key: ' . $apiKey]
        );

        $parsed = extract_json_from_text(parse_gemini_text($interactionResponse));
        if (is_array($parsed)) {
            return $parsed;
        }
    } catch (Throwable $interactionError) {
        error_log('Gemini interactions request failed: ' . $interactionError->getMessage());
    }

    try {
        $generateModel = str_starts_with($model, 'models/') ? substr($model, 7) : $model;
        $generateResponse = http_json_request(
            'https://generativelanguage.googleapis.com/v1beta/models/' . rawurlencode($generateModel) . ':generateContent',
            'POST',
            [
                'contents' => [
                    [
                        'parts' => [
                            ['text' => $prompt],
                        ],
                    ],
                ],
            ],
            ['x-goog-api-key: ' . $apiKey]
        );

        $parsed = extract_json_from_text(parse_gemini_text($generateResponse));
        if (is_array($parsed)) {
            return $parsed;
        }
    } catch (Throwable $generateError) {
        error_log('Gemini generateContent request failed: ' . $generateError->getMessage());
    }

    return null;
}

try {
    load_local_env();

    $requestBody = read_json_body();
    $mode = (string) ($requestBody['mode'] ?? ($_GET['mode'] ?? 'reports'));
    $requestMethod = (string) ($_SERVER['REQUEST_METHOD'] ?? 'GET');

    if ($requestMethod === 'POST' && $mode === 'titles') {
        $groups = is_array($requestBody['groups'] ?? null) ? $requestBody['groups'] : [];
        $recommendations = gemini_recommendations($groups);

        json_response([
            'success' => true,
            'generated_by' => $recommendations ? 'gemini' : 'fallback',
            'recommendations' => $recommendations ?: fallback_recommendations($groups),
        ]);
    }

    $remotePayload = http_json_request(CRIME_REPORTS_API_URL);
    $records = normalize_records($remotePayload);
    $groups = groups_from_records($records);
    $recommendations = gemini_recommendations($groups);

    json_response([
        'success' => true,
        'source' => CRIME_REPORTS_API_URL,
        'generated_by' => $recommendations ? 'gemini' : 'fallback',
        'data' => $remotePayload,
        'recommendations' => $recommendations ?: fallback_recommendations($groups),
    ]);
} catch (Throwable $error) {
    error_log('Crime campaign recommendations proxy failed: ' . $error->getMessage());
    json_response([
        'success' => false,
        'error' => 'Unable to load crime campaign recommendations at this time.',
    ], 502);
}
