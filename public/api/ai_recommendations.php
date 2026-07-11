<?php
declare(strict_types=1);

ini_set('display_errors', '0');
error_reporting(E_ALL);

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');

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
                    'disaster_type' => $item['type'] ?? $item['category'] ?? 'unknown',
                    'location' => $item['location'] ?? $item['barangay'] ?? null,
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

    return trim((string) $title) !== '' ? trim((string) $title) : 'Uncategorized Incident';
}

function extract_report_id(array $record): string
{
    return (string) ($record['id'] ?? $record['incident_code'] ?? $record['code'] ?? spl_object_id($record));
}

function extract_severity_numeric(array $record): int
{
    $sev = $record['severity'] ?? $record['category']['severity_level'] ?? 'medium';

    if (is_string($sev)) {
        $s = strtolower(trim($sev));
        if (in_array($s, ['critical', 'catastrophic', 'extreme'], true)) return 100;
        if (in_array($s, ['high', 'severe', 'grave'], true)) return 80;
        if (in_array($s, ['medium', 'moderate', 'elevated'], true)) return 50;
        if (in_array($s, ['low', 'minor', 'info'], true)) return 20;
    }

    if (is_numeric($sev)) {
        $n = (int) $sev;
        return min(100, max(0, $n));
    }

    return 50;
}

function extract_location(array $record): string
{
    $loc = $record['barangay']['barangay_name']
        ?? $record['barangay_name']
        ?? $record['location']
        ?? $record['address_details']
        ?? 'Unknown';

    return is_string($loc) ? trim($loc) : 'Unknown';
}

function extract_description(array $record): string
{
    $desc = $record['incident_description']
        ?? $record['description']
        ?? $record['message']
        ?? $record['modus_operandi']
        ?? '';

    return is_string($desc) ? trim($desc) : '';
}

function extract_incident_date(array $record): ?string
{
    return $record['incident_date']
        ?? $record['incidentDate']
        ?? $record['created_at']
        ?? $record['date_reported']
        ?? null;
}

function extract_category_name(array $record): string
{
    $cat = $record['category']['category_name']
        ?? $record['category_name']
        ?? $record['disaster_type']
        ?? null;

    return $cat ? trim((string) $cat) : '';
}

function is_report_test_data(array $record): bool
{
    $includeTest = strtolower(get_env_value('CAMPAIGN_INCLUDE_TEST_REPORTS') ?? 'false') === 'true';
    if ($includeTest) {
        return false;
    }

    $title = incident_title($record);
    $desc = extract_description($record);
    $combined = strtolower($title . ' ' . $desc);

    $testPrefixes = ['[mock]', '[test]', '[sample]', '[demo]', 'mock ', 'test ', 'sample ', 'demo '];
    foreach ($testPrefixes as $prefix) {
        if (str_starts_with($combined, $prefix)) {
            return true;
        }
    }

    $meaninglessPatterns = [
        '/^(.)\1{2,}$/',        // repeated single char: ddd, aaa
        '/^[a-z]{1,3}$/i',      // very short random letters (1-3 chars)
        '/^daw+dw+/i',          // dawdwadwa variants
        '/^[a-z]*([a-z])\1{2,}[a-z]*$/i', // repeated chars in middle
        '/^test\s*test$/i',     // "test test"
    ];

    $cleanTitle = trim(preg_replace('/[^a-z0-9\s]/i', '', $title));
    $wordCount = $cleanTitle !== '' ? count(preg_split('/\s+/', $cleanTitle)) : 0;

    foreach ($meaninglessPatterns as $pattern) {
        if (preg_match($pattern, trim($title))) {
            return true;
        }
    }

    if ($wordCount === 0 || ($wordCount === 1 && strlen(trim($title)) <= 3)) {
        return true;
    }

    return false;
}

function is_duplicate_record(array $seenHashes, array $record): bool
{
    $id = extract_report_id($record);
    $title = incident_title($record);
    $key = $id . '|' . $title;
    $hash = md5($key);

    if (isset($seenHashes[$hash])) {
        return true;
    }
    return false;
}

function normalize_text(string $text): string
{
    $text = mb_strtolower(trim($text));

    $text = preg_replace('/\[(mock|test|sample|demo)\]/i', '', $text);
    $text = preg_replace('/^(mock|test|sample|demo)\s+/i', '', $text);

    $text = preg_replace('/[^\w\s]/u', ' ', $text);

    $text = preg_replace('/\s+/', ' ', $text);

    return trim($text);
}

function map_category_to_trend_key(string $categoryName, string $source): string
{
    $cat = strtolower(trim($categoryName));

    if ($source === 'disaster') {
        $disasterMap = [
            'earthquake' => 'disaster:earthquake',
            'seismic' => 'disaster:earthquake',
            'aftershock' => 'disaster:earthquake',
            'flood' => 'disaster:flood',
            'flash flood' => 'disaster:flood',
            'rising water' => 'disaster:flood',
            'storm' => 'disaster:severe-weather',
            'typhoon' => 'disaster:severe-weather',
            'severe weather' => 'disaster:severe-weather',
            'strong winds' => 'disaster:severe-weather',
            'weather' => 'disaster:severe-weather',
            'fire' => 'disaster:fire',
            'structural fire' => 'disaster:fire',
            'landslide' => 'disaster:landslide',
            'volcanic' => 'disaster:volcanic',
            'eruption' => 'disaster:volcanic',
            'tsunami' => 'disaster:tsunami',
            'tornado' => 'disaster:severe-weather',
        ];

        foreach ($disasterMap as $keyword => $key) {
            if (str_contains($cat, $keyword)) {
                return $key;
            }
        }
        return 'disaster:other';
    }

    $crimeMap = [
        'assault' => 'crime:violent-assault',
        'homicide' => 'crime:violent-homicide',
        'murder' => 'crime:violent-homicide',
        'domestic violence' => 'crime:violent-domestic',
        'sexual offense' => 'crime:violent-sexual',
        'sexual assault' => 'crime:violent-sexual',
        'rape' => 'crime:violent-sexual',
        'theft' => 'crime:property-theft',
        'burglary' => 'crime:property-burglary',
        'vehicle theft' => 'crime:property-vehicle-theft',
        'carnapping' => 'crime:property-vehicle-theft',
        'fraud' => 'crime:financial-fraud',
        'scam' => 'crime:financial-fraud',
        'robbery' => 'crime:robbery',
        'hold-up' => 'crime:robbery',
        'drug' => 'crime:drug-related',
        'fire incident' => 'crime:fire-incident',
        'vehicular accident' => 'crime:vehicular-accident',
        'hit and run' => 'crime:vehicular-accident',
        'medical emergency' => 'crime:medical-emergency',
        'public disorder' => 'crime:public-disorder',
        'riot' => 'crime:public-disorder',
        'vandalism' => 'crime:vandalism',
        'hazardous material' => 'crime:hazmat',
        'rescue' => 'crime:rescue',
        'suspicious activity' => 'crime:suspicious',
        'noise complaint' => 'crime:noise-complaint',
        'curfew violation' => 'crime:curfew-violation',
        'trespassing' => 'crime:trespassing',
        'loitering' => 'crime:loitering',
        'kidnap' => 'crime:violent-kidnapping',
        'cyber' => 'crime:cybercrime',
        'anonymous tip' => 'crime:anonymous-tip',
        'traffic violation' => 'crime:traffic-violation',
        'reckless driving' => 'crime:traffic-violation',
        'dui' => 'crime:traffic-violation',
        'illegal parking' => 'crime:traffic-violation',
        'road obstruction' => 'crime:traffic-violation',
    ];

    foreach ($crimeMap as $keyword => $key) {
        if (str_contains($cat, $keyword)) {
            return $key;
        }
    }

    return 'crime:other';
}

function infer_trend_key_from_text(array $record, string $source): string
{
    $title = normalize_text(incident_title($record));
    $desc = normalize_text(extract_description($record));
    $combined = $title . ' ' . $desc;

    $patterns = [
        'disaster:earthquake' => ['earthquake', 'seismic', 'aftershock', 'temblor'],
        'disaster:flood' => ['flood', 'flooding', 'flash flood', 'rising water', 'baha'],
        'disaster:severe-weather' => ['typhoon', 'storm', 'severe weather', 'strong winds', 'bagyo', 'weather disturbance'],
        'disaster:fire' => ['structural fire', 'fire incident', 'fire alert'],
        'disaster:landslide' => ['landslide', 'land slip'],
        'disaster:volcanic' => ['volcanic', 'eruption', 'ashfall'],
    ];

    if ($source === 'disaster') {
        foreach ($patterns as $key => $keywords) {
            foreach ($keywords as $kw) {
                if (str_contains($combined, $kw)) {
                    return $key;
                }
            }
        }
        return 'disaster:other';
    }

    $crimeTextPatterns = [
        'crime:violent-assault' => ['assault', 'mauling', 'physical attack', 'fist', 'beaten', 'beating', 'brawl', 'street fight', 'stabbing', 'stabbed', 'gang', 'riot'],
        'crime:violent-homicide' => ['homicide', 'murder', 'killed', 'dead', 'death'],
        'crime:violent-domestic' => ['domestic', 'child abuse', 'elder abuse'],
        'crime:violent-sexual' => ['sexual', 'rape'],
        'crime:property-theft' => ['theft', 'stolen', 'snatching', 'snatched', 'pickpocket', 'shoplifting'],
        'crime:property-burglary' => ['burglary', 'break-in', 'break in', 'broke into'],
        'crime:robbery' => ['robbery', 'holdup', 'hold-up', 'hold up', 'robbed'],
        'crime:property-vehicle-theft' => ['carnapping', 'carnapped', 'vehicle theft', 'motorcycle theft'],
        'crime:financial-fraud' => ['fraud', 'scam', 'skimming', 'atm', 'investment fraud'],
        'crime:drug-related' => ['drug', 'shabu', 'marijuana', 'narcotics', 'buy-bust', 'pusher'],
        'crime:vehicular-accident' => ['accident', 'hit and run', 'vehicular'],
        'crime:fire-incident' => ['fire'],
    ];

    if ($source === 'crime') {
        foreach ($crimeTextPatterns as $key => $keywords) {
            foreach ($keywords as $kw) {
                if (str_contains($combined, $kw)) {
                    return $key;
                }
            }
        }
    }

    return $source === 'crime' ? 'crime:other' : 'disaster:other';
}

function aggregate_recommendation_actions(string $trendKey, array $records): array
{
    $actions = [];
    $isCrime = str_starts_with($trendKey, 'crime:');

    if ($isCrime) {
        $actions[] = 'Increase police visibility and patrol in affected areas';
        $actions[] = 'Conduct community awareness seminars on prevention';
        $actions[] = 'Coordinate with barangay officials for local response';

        if (str_contains($trendKey, 'violent') || str_contains($trendKey, 'assault')) {
            $actions[] = 'Establish conflict resolution and mediation programs';
            $actions[] = 'Improve street lighting and CCTV coverage in hotspots';
        }
        if (str_contains($trendKey, 'theft') || str_contains($trendKey, 'robbery')) {
            $actions[] = 'Strengthen neighborhood watch and community policing';
            $actions[] = 'Conduct home security and property protection seminars';
        }
        if (str_contains($trendKey, 'fraud')) {
            $actions[] = 'Launch digital literacy and scam awareness campaign';
            $actions[] = 'Coordinate with banks for enhanced security measures';
        }
        if (str_contains($trendKey, 'drug')) {
            $actions[] = 'Strengthen anti-drug operations and community rehabilitation';
            $actions[] = 'Conduct drug awareness and prevention programs in schools';
        }
        if (str_contains($trendKey, 'domestic')) {
            $actions[] = 'Strengthen support services for domestic violence victims';
            $actions[] = 'Conduct family counseling and conflict resolution programs';
        }
    } else {
        $actions[] = 'Disseminate early warning information to affected communities';
        $actions[] = 'Coordinate with disaster response agencies and LGU';
        $actions[] = 'Conduct pre-disaster preparedness and evacuation drills';

        if (str_contains($trendKey, 'earthquake')) {
            $actions[] = 'Inspect building integrity and identify safe evacuation zones';
            $actions[] = 'Conduct earthquake drill and public awareness campaign';
        }
        if (str_contains($trendKey, 'flood')) {
            $actions[] = 'Monitor water levels and identify flood-prone areas';
            $actions[] = 'Pre-position rescue equipment and relief supplies';
        }
        if (str_contains($trendKey, 'weather')) {
            $actions[] = 'Monitor weather updates and coordinate preemptive evacuations';
            $actions[] = 'Secure critical infrastructure and prepare emergency shelters';
        }
        if (str_contains($trendKey, 'fire')) {
            $actions[] = 'Conduct fire safety inspections and community drills';
            $actions[] = 'Ensure fire trucks and equipment are operational';
        }
    }

    return array_values(array_unique($actions));
}

function aggregate_target_audience(string $trendKey, array $records): string
{
    $isCrime = str_starts_with($trendKey, 'crime:');

    if ($isCrime) {
        if (str_contains($trendKey, 'violent') || str_contains($trendKey, 'assault')) {
            return 'General public, barangay officials, transport groups, security personnel';
        }
        if (str_contains($trendKey, 'theft') || str_contains($trendKey, 'robbery')) {
            return 'Residents, business owners, market vendors, transport operators';
        }
        if (str_contains($trendKey, 'fraud')) {
            return 'Bank customers, online users, senior citizens, business owners';
        }
        if (str_contains($trendKey, 'drug')) {
            return 'Youth, school administrators, parents, community leaders';
        }
        if (str_contains($trendKey, 'domestic')) {
            return 'Families, women, children, barangay health workers';
        }
        return 'General public and community stakeholders';
    }

    if (str_contains($trendKey, 'earthquake')) {
        return 'All residents, school administrators, building managers, barangay disaster councils';
    }
    if (str_contains($trendKey, 'flood')) {
        return 'Residents in flood-prone areas, barangay disaster response teams';
    }
    if (str_contains($trendKey, 'weather')) {
        return 'General public, farmers, fisherfolk, transport sector, disaster response units';
    }
    if (str_contains($trendKey, 'fire')) {
        return 'Homeowners, business establishments, market vendors, building administrators';
    }
    return 'General public and community stakeholders';
}

function trend_label_from_key(string $trendKey): string
{
    $labels = [
        'crime:violent-assault' => 'Assault and physical violence incidents',
        'crime:violent-homicide' => 'Homicide and fatal violence incidents',
        'crime:violent-domestic' => 'Domestic violence and family-related incidents',
        'crime:violent-sexual' => 'Sexual offenses and related incidents',
        'crime:violent-kidnapping' => 'Kidnapping and abduction incidents',
        'crime:property-theft' => 'Theft and property crimes',
        'crime:property-burglary' => 'Burglary and break-in incidents',
        'crime:property-vehicle-theft' => 'Vehicle theft and carnapping incidents',
        'crime:financial-fraud' => 'Financial fraud and scam incidents',
        'crime:robbery' => 'Robbery and hold-up incidents',
        'crime:drug-related' => 'Drug-related offenses and operations',
        'crime:vehicular-accident' => 'Vehicular accident and traffic incidents',
        'crime:fire-incident' => 'Fire incidents and related emergencies',
        'crime:medical-emergency' => 'Medical emergency and health incidents',
        'crime:public-disorder' => 'Public disorder and disturbance incidents',
        'crime:vandalism' => 'Vandalism and property damage incidents',
        'crime:hazmat' => 'Hazardous material incidents',
        'crime:rescue' => 'Rescue operations and related incidents',
        'crime:suspicious' => 'Suspicious activity reports',
        'crime:noise-complaint' => 'Noise complaint incidents',
        'crime:curfew-violation' => 'Curfew violation incidents',
        'crime:trespassing' => 'Trespassing incidents',
        'crime:loitering' => 'Loitering incidents',
        'crime:cybercrime' => 'Cybercrime and online incidents',
        'crime:traffic-violation' => 'Traffic violation and road safety incidents',
        'crime:anonymous-tip' => 'Anonymous tip and information reports',
        'crime:other' => 'Other crime-related incidents',
        'disaster:earthquake' => 'Earthquake and seismic activity',
        'disaster:flood' => 'Flood and rising water incidents',
        'disaster:severe-weather' => 'Severe weather and storm alerts',
        'disaster:fire' => 'Structural and fire-related disasters',
        'disaster:landslide' => 'Landslide and ground movement',
        'disaster:volcanic' => 'Volcanic activity and eruption alerts',
        'disaster:tsunami' => 'Tsunami and sea-level hazard alerts',
        'disaster:other' => 'Other disaster and emergency alerts',
    ];

    return $labels[$trendKey] ?? 'Uncategorized incidents';
}

function cluster_by_trend(array $records): array
{
    $clusters = [];

    foreach ($records as $record) {
        if (!is_array($record)) continue;

        $source = $record['source'] ?? 'crime';
        $categoryName = extract_category_name($record);

        if ($categoryName !== '') {
            $trendKey = map_category_to_trend_key($categoryName, $source);
        } else {
            $trendKey = infer_trend_key_from_text($record, $source);
        }

        if (!isset($clusters[$trendKey])) {
            $clusters[$trendKey] = [
                'trend_key' => $trendKey,
                'source' => $source,
                'records' => [],
                'trend_label' => trend_label_from_key($trendKey),
            ];
        }

        $clusters[$trendKey]['records'][] = $record;
    }

    return array_values($clusters);
}

function compute_weighted_priority(array $cluster, array $allClusters): array
{
    $records = $cluster['records'];
    $count = count($records);

    $severityValues = [];
    $dates = [];
    $locations = [];

    foreach ($records as $rec) {
        $severityValues[] = extract_severity_numeric($rec);
        $date = extract_incident_date($rec);
        if ($date !== null) {
            $dates[] = $date;
        }
        $loc = extract_location($rec);
        if ($loc !== 'Unknown') {
            $locations[$loc] = true;
        }
    }

    $maxSeverity = !empty($severityValues) ? max($severityValues) : 50;
    $avgSeverity = !empty($severityValues) ? array_sum($severityValues) / count($severityValues) : 50;

    $severityScore = ($maxSeverity * 0.6) + ($avgSeverity * 0.4);
    $severityScore = round(min(100, max(0, $severityScore)), 2);

    $maxCount = 1;
    foreach ($allClusters as $c) {
        $cCount = count($c['records']);
        if ($cCount > $maxCount) {
            $maxCount = $cCount;
        }
    }
    $frequencyScore = round(min(100, ($count / max(1, $maxCount)) * 100), 2);

    $recencyScore = 0;
    if (!empty($dates)) {
        $parsedDates = [];
        foreach ($dates as $d) {
            $ts = strtotime((string) $d);
            if ($ts !== false) {
                $parsedDates[] = $ts;
            }
        }
        if (!empty($parsedDates)) {
            $newest = max($parsedDates);
            $now = time();
            $daysDiff = max(0, ($now - $newest) / 86400);
            $recencyScore = round(min(100, max(0, 100 - ($daysDiff * 2))), 2);
        }
    }

    $uniqueLocations = count($locations);
    $geographicScore = round(min(100, $uniqueLocations * 25), 2);

    $priorityScore = round(
        ($severityScore * 0.40) + ($frequencyScore * 0.25) + ($recencyScore * 0.20) + ($geographicScore * 0.15),
        2
    );

    $level = 'Low';
    if ($priorityScore >= 80) $level = 'Critical';
    elseif ($priorityScore >= 60) $level = 'High';
    elseif ($priorityScore >= 40) $level = 'Medium';

    $earliestDate = null;
    $latestDate = null;
    if (!empty($parsedDates)) {
        $earliestDate = date('Y-m-d H:i:s', min($parsedDates));
        $latestDate = date('Y-m-d H:i:s', max($parsedDates));
    }

    return [
        'severity_score' => $severityScore,
        'frequency_score' => $frequencyScore,
        'recency_score' => $recencyScore,
        'geographic_score' => $geographicScore,
        'priority_score' => $priorityScore,
        'priority_level' => $level,
        'report_count' => $count,
        'unique_locations' => array_keys($locations),
        'earliest_date' => $earliestDate,
        'latest_date' => $latestDate,
    ];
}

function generate_cluster_reasoning(array $cluster, array $scoreData): string
{
    $trendLabel = $cluster['trend_label'];
    $count = $scoreData['report_count'];
    $level = $scoreData['priority_level'];
    $locations = $scoreData['unique_locations'];
    $locCount = count($locations);
    $severity = $scoreData['severity_score'];
    $recency = $scoreData['recency_score'];

    $parts = [];

    $parts[] = "This recommendation received {$level} priority because {$count} related " . strtolower($trendLabel);

    if ($severity >= 70) {
        $parts[] = "with multiple reports indicating high severity";
    } elseif ($severity >= 40) {
        $parts[] = "with moderate severity levels reported";
    } else {
        $parts[] = "with generally lower severity reports";
    }

    if ($locCount > 0) {
        $parts[] = "spanning {$locCount} different location(s)";
    }

    if ($recency >= 70) {
        $parts[] = "and very recent reports requiring immediate attention";
    } elseif ($recency >= 40) {
        $parts[] = "with reports distributed over a recent time period";
    }

    $parts[] = "based on automated analysis of report volume, severity, geographic spread, and temporal patterns.";

    return implode(', ', $parts);
}

function generate_source_report_ids(array $records): array
{
    $ids = [];
    foreach ($records as $rec) {
        $id = extract_report_id($rec);
        $title = incident_title($rec);
        $ids[] = ['id' => $id, 'title' => $title];
    }
    return $ids;
}

function generate_campaign_title_from_cluster(string $trendKey, array $records, int $reportCount): string
{
    $isCrime = str_starts_with($trendKey, 'crime:');
    $titles = [];
    foreach ($records as $rec) {
        $t = normalize_text(incident_title($rec));
        if ($t !== '' && $t !== 'uncategorized incident') {
            $titles[] = $t;
        }
    }

    $templates = [
        'crime:violent-assault' => 'Public Safety and Violence Prevention Initiative',
        'crime:violent-homicide' => 'Peace and Order Community Safety Campaign',
        'crime:violent-domestic' => 'Family Protection and Domestic Violence Prevention',
        'crime:violent-sexual' => 'Community Safety and Women Protection Campaign',
        'crime:violent-kidnapping' => 'Community Watch and Personal Safety Campaign',
        'crime:property-theft' => 'Property Protection and Crime Prevention Campaign',
        'crime:property-burglary' => 'Home Security and Break-In Prevention Initiative',
        'crime:property-vehicle-theft' => 'Vehicle Security and Anti-Carnapping Campaign',
        'crime:financial-fraud' => 'Financial Safety and Fraud Prevention Campaign',
        'crime:robbery' => 'Community Safety and Robbery Prevention Campaign',
        'crime:drug-related' => 'Drug-Free Community Awareness and Prevention Campaign',
        'crime:vehicular-accident' => 'Road Safety and Accident Prevention Campaign',
        'crime:fire-incident' => 'Fire Prevention and Community Safety Campaign',
        'crime:medical-emergency' => 'Emergency Response and First Aid Readiness Campaign',
        'crime:public-disorder' => 'Community Peace and Order Campaign',
        'crime:vandalism' => 'Community Property Protection Campaign',
        'crime:cybercrime' => 'Digital Safety and Cybercrime Prevention Campaign',
        'crime:traffic-violation' => 'Traffic Safety and Responsible Driving Campaign',
        'disaster:earthquake' => 'Earthquake Preparedness and Community Resilience Campaign',
        'disaster:flood' => 'Flood Preparedness and Evacuation Readiness Campaign',
        'disaster:severe-weather' => 'Severe Weather Preparedness and Early Warning Campaign',
        'disaster:fire' => 'Fire Safety and Emergency Preparedness Campaign',
        'disaster:landslide' => 'Landslide Preparedness and Community Safety Campaign',
        'disaster:volcanic' => 'Volcanic Eruption Preparedness and Safety Campaign',
        'disaster:tsunami' => 'Tsunami Preparedness and Coastal Safety Campaign',
    ];

    if (isset($templates[$trendKey])) {
        return $templates[$trendKey];
    }

    if ($isCrime) {
        return 'Community Safety and Awareness Campaign';
    }

    return 'Disaster Preparedness and Community Resilience Campaign';
}

function extract_json_from_text(string $text): ?array
{
    $text = trim($text);
    if ($text === '') return null;

    $decoded = json_decode($text, true);
    if (is_array($decoded)) return $decoded;

    if (preg_match('/```(?:json)?\s*(.*?)```/is', $text, $matches)) {
        $decoded = json_decode(trim($matches[1]), true);
        if (is_array($decoded)) return $decoded;
    }

    $start = strpos($text, '[');
    $end = strrpos($text, ']');
    if ($start !== false && $end !== false && $end > $start) {
        $decoded = json_decode(substr($text, $start, $end - $start + 1), true);
        if (is_array($decoded)) return $decoded;
    }

    $start = strpos($text, '{');
    $end = strrpos($text, '}');
    if ($start !== false && $end !== false && $end > $start) {
        $decoded = json_decode(substr($text, $start, $end - $start + 1), true);
        if (is_array($decoded)) return $decoded;
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

function ai_generate_titles_for_clusters(array $clusters): array
{
    $apiKey = get_env_value('GEMINI_API_KEY') ?? get_env_value('GOOGLE_GEMINI_API_KEY');
    if ($apiKey === null || empty($clusters)) {
        return [];
    }

    $model = get_env_value('GEMINI_MODEL') ?? 'gemini-2.5-flash';

    $clusterSummaries = [];
    foreach ($clusters as $i => $cluster) {
        $records = $cluster['records'];
        $count = count($records);
        $titles = array_map(function($r) { return incident_title($r); }, $records);
        $uniqueTitles = array_unique($titles);
        $clusterSummaries[] = [
            'index' => $i,
            'trend_key' => $cluster['trend_key'],
            'category' => $cluster['source'],
            'report_count' => $count,
            'sample_titles' => array_slice(array_values($uniqueTitles), 0, 8),
            'suggested_trend' => $cluster['trend_label'],
        ];
    }

    $prompt = "You are a public safety campaign strategist for a Philippine LGU barangay.\n\n";
    $prompt .= "Below are compiled incident clusters. For EACH cluster, generate a professional, actionable campaign title (5-12 words) that describes a prevention or preparedness campaign.\n\n";
    $prompt .= "RULES:\n";
    $prompt .= "- Title must describe a PREVENTION or PREPAREDNESS objective, NOT copy incident titles\n";
    $prompt .= "- Do NOT use: 'Community Awareness Campaign Against {X}' templates\n";
    $prompt .= "- Be specific to the actual incidents in the cluster\n";
    $prompt .= "- Keep it concise (5-12 words), professional, suitable for LGU public safety\n\n";
    $prompt .= "Return a VALID JSON object with cluster indices as keys. Example:\n";
    $prompt .= '{"0": {"campaign_title": "Public Safety and Violence Prevention Initiative", "description": "Campaign addressing recent assault incidents...", "reasoning": "Multiple assault incidents reported...", "target_audience": "General public...", "actions": ["Action 1", "Action 2"]}}' . "\n\n";

    foreach ($clusterSummaries as $cs) {
        $prompt .= "Cluster {$cs['index']} ({$cs['category']}, {$cs['report_count']} reports, trend: {$cs['suggested_trend']}):\n";
        foreach ($cs['sample_titles'] as $st) {
            $prompt .= "- {$st}\n";
        }
        $prompt .= "\n";
    }

    $prompt .= "Return ONLY the JSON object. No markdown, no explanation.";

    try {
        $generateModel = str_starts_with($model, 'models/') ? substr($model, 7) : $model;
        $generateResponse = http_json_request(
            'https://generativelanguage.googleapis.com/v1beta/models/' . rawurlencode($generateModel) . ':generateContent',
            'POST',
            [
                'contents' => [
                    ['parts' => [['text' => $prompt]]],
                ],
            ],
            ['x-goog-api-key: ' . $apiKey],
            15
        );

        $parsed = extract_json_from_text(parse_gemini_text($generateResponse));
        if (is_array($parsed)) {
            return $parsed;
        }
    } catch (Throwable $e) {
        error_log('Gemini cluster title generation failed: ' . $e->getMessage());
    }

    return [];
}

function get_db_connection(): ?PDO
{
    $host = get_env_value('DB_HOST') ?? 'localhost';
    $port = get_env_value('DB_PORT') ?? '3306';
    $name = get_env_value('DB_NAME') ?? 'pscm';
    $user = get_env_value('DB_USER') ?? 'root';
    $pass = get_env_value('DB_PASSWORD') ?? '';

    try {
        $pdo = new PDO(
            "mysql:host={$host};port={$port};dbname={$name};charset=utf8mb4",
            $user,
            $pass,
            [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_TIMEOUT => 3,
            ]
        );
        return $pdo;
    } catch (Throwable $e) {
        error_log('AI Recommendations DB connection failed: ' . $e->getMessage());
        return null;
    }
}

function store_recommendations_in_db(PDO $pdo, array $recommendations): void
{
    try {
        $pdo->exec("DELETE FROM campaign_department_ai_recommendations WHERE created_at < DATE_SUB(NOW(), INTERVAL 24 HOUR)");

        $upsertStmt = $pdo->prepare(
            "INSERT INTO campaign_department_ai_recommendations
            (category, campaign_title, main_trend, trend_key, description, report_count,
             cluster_report_ids, affected_locations, earliest_date, latest_date,
             severity_score, frequency_score, recency_score, geographic_score,
             priority_score, priority_level, scoring_breakdown,
             ai_reasoning, ai_recommended_actions, ai_target_audience,
             generated_by, recommendation_hash, data_snapshot, is_test_data)
            VALUES
            (:category, :campaign_title, :main_trend, :trend_key, :description, :report_count,
             :cluster_report_ids, :affected_locations, :earliest_date, :latest_date,
             :severity_score, :frequency_score, :recency_score, :geographic_score,
             :priority_score, :priority_level, :scoring_breakdown,
             :ai_reasoning, :ai_recommended_actions, :ai_target_audience,
             :generated_by, :hash, :snapshot, :is_test_data)
            ON DUPLICATE KEY UPDATE
             campaign_title = VALUES(campaign_title),
             main_trend = VALUES(main_trend),
             description = VALUES(description),
             report_count = VALUES(report_count),
             cluster_report_ids = VALUES(cluster_report_ids),
             affected_locations = VALUES(affected_locations),
             earliest_date = VALUES(earliest_date),
             latest_date = VALUES(latest_date),
             severity_score = VALUES(severity_score),
             frequency_score = VALUES(frequency_score),
             recency_score = VALUES(recency_score),
             geographic_score = VALUES(geographic_score),
             priority_score = VALUES(priority_score),
             priority_level = VALUES(priority_level),
             scoring_breakdown = VALUES(scoring_breakdown),
             ai_reasoning = VALUES(ai_reasoning),
             ai_recommended_actions = VALUES(ai_recommended_actions),
             ai_target_audience = VALUES(ai_target_audience),
             generated_by = VALUES(generated_by),
             data_snapshot = VALUES(data_snapshot),
             is_test_data = VALUES(is_test_data),
             updated_at = NOW()"
        );

        foreach ($recommendations as $rec) {
            $hashInput = $rec['trend_key'] . '|' . json_encode($rec['cluster_report_ids']);
            $hash = hash('sha256', $hashInput);

            $upsertStmt->execute([
                ':category' => $rec['category'],
                ':campaign_title' => $rec['campaign_title'],
                ':main_trend' => $rec['main_trend'],
                ':trend_key' => $rec['trend_key'],
                ':description' => $rec['description'] ?? null,
                ':report_count' => $rec['report_count'],
                ':cluster_report_ids' => json_encode($rec['cluster_report_ids']),
                ':affected_locations' => json_encode($rec['affected_locations']),
                ':earliest_date' => $rec['earliest_date'],
                ':latest_date' => $rec['latest_date'],
                ':severity_score' => $rec['severity_score'],
                ':frequency_score' => $rec['frequency_score'],
                ':recency_score' => $rec['recency_score'],
                ':geographic_score' => $rec['geographic_score'],
                ':priority_score' => $rec['priority_score'],
                ':priority_level' => $rec['priority_level'],
                ':scoring_breakdown' => json_encode($rec['scoring_breakdown']),
                ':ai_reasoning' => $rec['ai_reasoning'] ?? null,
                ':ai_recommended_actions' => json_encode($rec['ai_recommended_actions'] ?? []),
                ':ai_target_audience' => $rec['ai_target_audience'] ?? null,
                ':generated_by' => $rec['generated_by'] ?? 'rule-based',
                ':hash' => $hash,
                ':snapshot' => json_encode($rec),
                ':is_test_data' => $rec['is_test_data'] ? 1 : 0,
            ]);
        }
    } catch (Throwable $e) {
        error_log('Failed to store recommendations in DB: ' . $e->getMessage());
    }
}

function load_cached_recommendations(PDO $pdo): ?array
{
    try {
        $stmt = $pdo->query(
            "SELECT * FROM campaign_department_ai_recommendations
             WHERE is_test_data = 0
             ORDER BY priority_score DESC, report_count DESC
             LIMIT 50"
        );
        $rows = $stmt->fetchAll();

        if (empty($rows)) {
            return null;
        }

        $recommendations = [];
        foreach ($rows as $row) {
            $rec = json_decode($row['data_snapshot'], true);
            if (is_array($rec) && isset($rec['trend_key'])) {
                $rec['id'] = (int) $row['id'];
                $recommendations[] = $rec;
            }
        }

        return $recommendations;
    } catch (Throwable $e) {
        error_log('Failed to load cached recommendations: ' . $e->getMessage());
        return null;
    }
}

try {
    load_local_env();

    $sourceFilter = (string) ($_GET['source'] ?? 'all');
    $forceRefresh = ($_GET['refresh'] ?? '0') === '1';
    $allRecords = [];
    $seenHashes = [];

    if ($sourceFilter === 'all' || $sourceFilter === 'crime') {
        $crimeUrl = get_env_value('CRIME_API_URL') ?? 'https://crime-analytics.alertaraqc.com/api/crimes';
        try {
            $crimePayload = http_json_request($crimeUrl);
            $crimeRecords = normalize_records($crimePayload);
            foreach ($crimeRecords as &$cr) {
                if (!isset($cr['source'])) {
                    $cr['source'] = 'crime';
                }
            }
            unset($cr);
            $allRecords = array_merge($allRecords, $crimeRecords);
        } catch (Throwable $e) {
            error_log('Crime API fetch failed: ' . $e->getMessage());
        }
    }

    if ($sourceFilter === 'all' || $sourceFilter === 'disaster') {
        $disasterApiKey = get_env_value('EMERGENCY_API_KEY') ?? 'EMERGENCY-SYSTEM-INTEGRATED-KEY-2026';
        $disasterBaseUrl = get_env_value('EMERGENCY_API_URL') ?? 'https://emergency-comm.alertaraqc.com/api/';
        $disasterUrl = $disasterBaseUrl . '?api_key=' . rawurlencode($disasterApiKey);

        try {
            $disasterPayload = http_json_request($disasterUrl);
            $disasterRecords = normalize_disaster_records($disasterPayload);
            foreach ($disasterRecords as &$dr) {
                $dr['source'] = 'disaster';
            }
            unset($dr);
            $allRecords = array_merge($allRecords, $disasterRecords);
        } catch (Throwable $e) {
            error_log('Disaster API fetch failed: ' . $e->getMessage());
        }
    }

    $validRecords = [];
    $testRecords = [];
    $duplicateCount = 0;
    $testCount = 0;

    foreach ($allRecords as $rec) {
        if (!is_array($rec)) continue;

        if (is_duplicate_record($seenHashes, $rec)) {
            $duplicateCount++;
            continue;
        }
        $id = extract_report_id($rec);
        $title = incident_title($rec);
        $seenHashes[md5($id . '|' . $title)] = true;

        if (is_report_test_data($rec)) {
            $testCount++;
            $testRecords[] = $rec;
        } else {
            $validRecords[] = $rec;
        }
    }

    $pdo = get_db_connection();

    if (!$forceRefresh && $pdo) {
        $cached = load_cached_recommendations($pdo);
        if ($cached !== null && count($cached) > 0) {
            json_response([
                'success' => true,
                'source' => 'all',
                'generated_by' => 'cache',
                'total_records' => count($allRecords),
                'valid_records' => count($validRecords),
                'test_records_filtered' => $testCount,
                'duplicates_filtered' => $duplicateCount,
                'total_clusters' => count($cached),
                'recommendations' => $cached,
            ]);
        }
    }

    $clusters = cluster_by_trend($validRecords);

    if (empty($clusters)) {
        json_response([
            'success' => true,
            'source' => 'all',
            'generated_by' => 'rule-based',
            'total_records' => count($allRecords),
            'valid_records' => count($validRecords),
            'test_records_filtered' => $testCount,
            'duplicates_filtered' => $duplicateCount,
            'total_clusters' => 0,
            'recommendations' => [],
        ]);
    }

    $aiTitles = ai_generate_titles_for_clusters($clusters);
    $generatedBy = !empty($aiTitles) ? 'gemini' : 'rule-based';

    $recommendations = [];

    foreach ($clusters as $i => $cluster) {
        $records = $cluster['records'];
        $trendKey = $cluster['trend_key'];
        $scoreData = compute_weighted_priority($cluster, $clusters);

        $sourceReportIds = generate_source_report_ids($records);

        $aiData = $aiTitles[(string) $i] ?? $aiTitles[$i] ?? null;
        $campaignTitle = generate_campaign_title_from_cluster($trendKey, $records, $scoreData['report_count']);
        $description = null;
        $reasoning = null;
        $targetAudience = null;
        $actions = [];

        if ($aiData && isset($aiData['campaign_title'])) {
            $campaignTitle = $aiData['campaign_title'];
            $generatedBy = 'gemini';
        }
        if ($aiData && isset($aiData['description'])) {
            $description = $aiData['description'];
        }
        if ($aiData && isset($aiData['reasoning'])) {
            $reasoning = $aiData['reasoning'];
        }
        if ($aiData && isset($aiData['target_audience'])) {
            $targetAudience = $aiData['target_audience'];
        }
        if ($aiData && isset($aiData['actions']) && is_array($aiData['actions'])) {
            $actions = $aiData['actions'];
        }

        if ($reasoning === null) {
            $reasoning = generate_cluster_reasoning($cluster, $scoreData);
        }
        if ($targetAudience === null) {
            $targetAudience = aggregate_target_audience($trendKey, $records);
        }
        if (empty($actions)) {
            $actions = aggregate_recommendation_actions($trendKey, $records);
        }

        $isTest = false;

        $rec = [
            'category' => $cluster['source'],
            'campaign_title' => $campaignTitle,
            'main_trend' => $cluster['trend_label'],
            'trend_key' => $trendKey,
            'description' => $description,
            'report_count' => $scoreData['report_count'],
            'cluster_report_ids' => $sourceReportIds,
            'affected_locations' => $scoreData['unique_locations'],
            'earliest_date' => $scoreData['earliest_date'],
            'latest_date' => $scoreData['latest_date'],
            'severity_score' => $scoreData['severity_score'],
            'frequency_score' => $scoreData['frequency_score'],
            'recency_score' => $scoreData['recency_score'],
            'geographic_score' => $scoreData['geographic_score'],
            'priority_score' => $scoreData['priority_score'],
            'priority_level' => $scoreData['priority_level'],
            'scoring_breakdown' => [
                'severity_weight' => 0.40,
                'frequency_weight' => 0.25,
                'recency_weight' => 0.20,
                'geographic_weight' => 0.15,
                'formula' => 'severity x 0.40 + frequency x 0.25 + recency x 0.20 + geographic x 0.15',
            ],
            'ai_reasoning' => $reasoning,
            'ai_recommended_actions' => $actions,
            'ai_target_audience' => $targetAudience,
            'generated_by' => $generatedBy,
            'is_test_data' => $isTest,
        ];

        $recommendations[] = $rec;
    }

    usort($recommendations, static function (array $a, array $b): int {
        return ($b['priority_score'] <=> $a['priority_score'])
            ?: ($b['report_count'] <=> $a['report_count']);
    });

    if ($pdo) {
        store_recommendations_in_db($pdo, $recommendations);
    }

    json_response([
        'success' => true,
        'source' => 'all',
        'generated_by' => $generatedBy,
        'total_records' => count($allRecords),
        'valid_records' => count($validRecords),
        'test_records_filtered' => $testCount,
        'duplicates_filtered' => $duplicateCount,
        'total_clusters' => count($recommendations),
        'recommendations' => $recommendations,
    ]);
} catch (Throwable $error) {
    error_log('AI recommendations endpoint failed: ' . $error->getMessage());
    json_response([
        'success' => false,
        'error' => 'Unable to load campaign recommendations at this time.',
    ], 502);
}
