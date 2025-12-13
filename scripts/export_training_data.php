<?php

declare(strict_types=1);

require __DIR__ . '/../vendor/autoload.php';
require __DIR__ . '/../src/Config/db_connect.php';

$stmt = $pdo->query("
    SELECT
        c.id AS campaign_id,
        c.title,
        COALESCE((
            SELECT COUNT(*) FROM notification_logs nl WHERE nl.campaign_id = c.id AND nl.status = 'sent'
        ), 0) AS reach,
        COALESCE((
            SELECT COUNT(*) FROM attendance a INNER JOIN events e ON e.id = a.event_id WHERE e.campaign_id = c.id
        ), 0) AS attendance_count,
        COALESCE((
            SELECT COUNT(*) FROM survey_responses sr INNER JOIN surveys s ON s.id = sr.survey_id WHERE s.campaign_id = c.id
        ), 0) AS survey_responses,
        COALESCE((
            SELECT COUNT(*) FROM notification_logs nl WHERE nl.campaign_id = c.id AND nl.status = 'failed'
        ), 0) AS notification_failed
    FROM campaigns c
");

$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

$out = fopen('php://output', 'w');
fputcsv($out, ['campaign_id','title','reach','attendance_count','survey_responses','notification_failed']);
foreach ($rows as $row) {
    fputcsv($out, [
        $row['campaign_id'],
        $row['title'],
        $row['reach'],
        $row['attendance_count'],
        $row['survey_responses'],
        $row['notification_failed'],
    ]);
}
fclose($out);





