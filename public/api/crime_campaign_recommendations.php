<?php
declare(strict_types=1);

ini_set('display_errors', '0');
error_reporting(E_ALL);

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');

const CRIME_REPORTS_API_URL = 'https://crime-analytics.alertaraqc.com/api/crimes';
const DISASTER_API_URL = 'https://emergency-comm.alertaraqc.com/api/?api_key=EMERGENCY-SYSTEM-INTEGRATED-KEY-2026';

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

function http_json_request(string $url, string $method = 'GET', ?array $body = null, array $headers = [], int $timeout = 20): array
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
            CURLOPT_TIMEOUT => $timeout,
            CURLOPT_CONNECTTIMEOUT => min(8, $timeout),
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
                'timeout' => $timeout,
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

function normalize_disaster_records(array $payload): array
{
    $records = [];

    foreach (['disasters', 'alerts'] as $key) {
        if (isset($payload[$key]) && is_array($payload[$key])) {
            foreach ($payload[$key] as $item) {
                if (!is_array($item)) continue;
                $title = $item['title'] ?? $item['category'] ?? 'Uncategorized Disaster';
                $records[] = [
                    'incident_title' => is_string($title) ? trim($title) : 'Uncategorized Disaster',
                    'incident_date' => $item['created_at'] ?? null,
                    'description' => $item['message'] ?? '',
                    'severity' => $item['severity'] ?? 'Medium',
                    'status' => $item['status'] ?? 'active',
                    'source' => 'disaster',
                ];
            }
        }
    }

    return $records;
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
        $title = (string) ($group['incident_title'] ?? '');
        $lower = strtolower($title);

        $campaignTemplates = [
            'earthquake' => 'Earthquake Preparedness and Safety Drill Campaign',
            'flood' => 'Flood Risk Awareness and Evacuation Planning Initiative',
            'weather' => 'Severe Weather Preparedness and Early Warning Campaign',
            'fire' => 'Fire Prevention and Community Safety Awareness Program',
            'theft' => 'Anti-Theft and Community Watch Initiative',
            'robbery' => 'Community Safety Against Robbery Prevention Campaign',
            'assault' => 'Violence Prevention and Personal Safety Awareness Campaign',
            'murder' => 'Peace and Order Community Solidarity Campaign',
            'drug' => 'Drug-Free Barangay Awareness and Prevention Campaign',
            'traffic' => 'Traffic Safety and Road Accident Prevention Campaign',
            'cyber' => 'Cybercrime Prevention and Digital Safety Awareness Campaign',
            'disaster' => 'Disaster Risk Reduction and Community Resilience Campaign',
            'covid' => 'Health Safety and Disease Prevention Community Campaign',
            'dengue' => 'Dengue Prevention and Clean-Up Drive Campaign',
            'landslide' => 'Landslide Risk Awareness and Pre-Evacuation Campaign',
            'volcanic' => 'Volcanic Eruption Preparedness and Safety Campaign',
            'typhoon' => 'Typhoon Preparedness and Community Resilience Campaign',
            'kidnap' => 'Community Watch Against Kidnapping Awareness Campaign',
            'vaccine' => 'Vaccination Awareness and Community Health Campaign',
            'emergency' => 'Emergency Response Readiness and First Aid Campaign',
        ];

        foreach ($campaignTemplates as $keyword => $campaignTitle) {
            if (strpos($lower, $keyword) !== false) {
                return [
                    'incident_title' => $title,
                    'recommended_campaign_title' => $campaignTitle,
                ];
            }
        }

        return [
            'incident_title' => $title,
            'recommended_campaign_title' => 'Community Awareness Campaign Against ' . $title,
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

function gemini_recommendations(array $groups, string $dataSource = 'crime'): ?array
{
    $apiKey = get_env_value('GEMINI_API_KEY') ?? get_env_value('GOOGLE_GEMINI_API_KEY');
    if ($apiKey === null || !$groups) {
        return null;
    }

    $model = get_env_value('GEMINI_MODEL') ?? 'gemini-2.5-flash';
    $limitedGroups = array_slice($groups, 0, 50);

    $sourceLabel = $dataSource === 'merged' ? 'crime reports and disaster alerts' : 'incident reports';

    $prompt = <<<PROMPT
You are a public safety campaign strategist for a Philippine barangay (LGU). Analyze the compiled incident data below which includes crime reports AND disaster/emergency alerts.

INSTRUCTIONS:
- For EACH incident group, think about what specific, actionable public safety campaign would best address that issue.
- DO NOT just prepend "Community Awareness Campaign Against" to the incident title.
- Instead, create targeted, professional campaign titles that suggest a specific approach (e.g., "Earthquake Preparedness and Drill Initiative for Barangay Residents", "Fire Prevention and Safety Awareness Campaign", "Anti-Theft and Community Watch Program").
- You can consolidate related incidents into broader campaigns when it makes sense.
- Keep titles concise (5-15 words), professional, and suitable for a local government public safety campaign.

Return ONLY a JSON array. Each item must have:
- "incident_title": the original incident/disaster category name
- "recommended_campaign_title": your intelligent campaign title

Incident groups (incident_title => report_count):
PROMPT;
    foreach ($limitedGroups as $g) {
        $prompt .= "\n- " . ($g['incident_title'] ?? 'Unknown') . ': ' . ($g['report_count'] ?? 0) . ' report(s)';
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
            ['x-goog-api-key: ' . $apiKey],
            8
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
        $recommendations = gemini_recommendations($groups, 'merged');

        json_response([
            'success' => true,
            'generated_by' => $recommendations ? 'gemini' : 'fallback',
            'recommendations' => $recommendations ?: fallback_recommendations($groups),
        ]);
    }

    // Fetch crime reports
    $crimePayload = http_json_request(CRIME_REPORTS_API_URL);
    $crimeRecords = normalize_records($crimePayload);

    // Fetch disaster alerts
    $disasterData = [];
    $disasterRecords = [];
    try {
        $disasterData = http_json_request(DISASTER_API_URL);
        $disasterRecords = normalize_disaster_records($disasterData);
    } catch (Throwable $disasterErr) {
        error_log('Disaster API fetch failed (non-fatal): ' . $disasterErr->getMessage());
    }

    // Merge all records
    $allRecords = array_merge($crimeRecords, $disasterRecords);
    $groups = groups_from_records($allRecords);
    $recommendations = gemini_recommendations($groups, 'merged');

    json_response([
        'success' => true,
        'sources' => [
            'crime' => CRIME_REPORTS_API_URL,
            'disaster' => DISASTER_API_URL,
        ],
        'generated_by' => $recommendations ? 'gemini' : 'fallback',
        'data' => [
            'crime' => $crimePayload,
            'disaster' => $disasterData,
        ],
        'records' => $allRecords,
        'recommendations' => $recommendations ?: fallback_recommendations($groups),
    ]);
} catch (Throwable $error) {
    error_log('Campaign recommendations proxy failed: ' . $error->getMessage());
    json_response([
        'success' => false,
        'error' => 'Unable to load campaign recommendations at this time.',
    ], 502);
}
