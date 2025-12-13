<?php

declare(strict_types=1);

require __DIR__ . '/../vendor/autoload.php';
require __DIR__ . '/../src/Config/db_connect.php';

date_default_timezone_set('UTC');

$now = date('Y-m-d H:i:s');

$fetch = $pdo->prepare('SELECT id, campaign_id, channel, notes FROM campaign_schedules WHERE scheduled_at <= :now AND status = "pending" ORDER BY scheduled_at ASC');
$fetch->execute(['now' => $now]);
$schedules = $fetch->fetchAll();

foreach ($schedules as $schedule) {
    $pdo->beginTransaction();
    try {
        // Simulate sending by writing a notification log
        $ins = $pdo->prepare('INSERT INTO notification_logs (campaign_id, audience_member_id, channel, status, response_message) VALUES (:cid, NULL, :channel, :status, :resp)');
        $ins->execute([
            'cid' => $schedule['campaign_id'],
            'channel' => $schedule['channel'],
            'status' => 'sent',
            'resp' => 'cron-dispatched',
        ]);

        $upd = $pdo->prepare('UPDATE campaign_schedules SET status = "sent", updated_at = CURRENT_TIMESTAMP WHERE id = :id');
        $upd->execute(['id' => $schedule['id']]);

        $pdo->commit();
        echo "[OK] Schedule {$schedule['id']} dispatched at {$now}\n";
    } catch (Throwable $e) {
        $pdo->rollBack();
        $fail = $pdo->prepare('UPDATE campaign_schedules SET status = "failed", updated_at = CURRENT_TIMESTAMP WHERE id = :id');
        $fail->execute(['id' => $schedule['id']]);
        echo "[FAIL] Schedule {$schedule['id']}: {$e->getMessage()}\n";
    }
}

if (empty($schedules)) {
    echo "[INFO] No pending schedules at {$now}\n";
}





