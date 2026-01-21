<?php
/**
 * Script to add campaign_department_ prefix to all table names in migration files
 * Usage: php scripts/add_department_prefix.php
 */

$migrationDir = __DIR__ . '/../migrations';
$files = glob($migrationDir . '/*.sql');

// List of tables to prefix (excluding system tables and views)
$tables = [
    'roles', 'permissions', 'role_permissions', 'barangays', 'users', 'campaigns',
    'campaign_schedules', 'content_items', 'attachments', 'audience_segments',
    'audience_members', 'campaign_audience', 'events', 'attendance', 'surveys',
    'survey_questions', 'survey_responses', 'impact_metrics', 'partners',
    'partner_engagements', 'automl_predictions', 'integration_logs',
    'notification_logs', 'audit_logs', 'tags', 'content_usage',
    // Additional tables from other migrations
    'feedback', 'campaign_feedback', 'evaluation_reports', 'schedule_status',
    'links', 'enrichment_data', 'content_repository', 'content_versions',
    'event_facilitators', 'event_segments', 'event_agency_coordination',
    'event_conflicts', 'event_audit_log', 'event_integration_checkpoints',
    'event_attendance', 'messages', 'message_threads', 'notifications',
    'external_systems', 'external_system_connections', 'external_data_mappings',
    'external_data_cache', 'integration_query_logs', 'module_system_mappings'
];

$prefix = 'campaign_department_';

foreach ($files as $file) {
    $filename = basename($file);
    
    // Skip certain files
    if (in_array($filename, ['sample_db_setup.sql'])) {
        continue;
    }
    
    echo "Processing: {$filename}\n";
    
    $content = file_get_contents($file);
    $originalContent = $content;
    
    // Replace table names in various SQL statements
    foreach ($tables as $table) {
        $prefixedTable = $prefix . $table;
        
        // CREATE TABLE
        $content = preg_replace(
            '/CREATE\s+TABLE\s+(?:IF\s+NOT\s+EXISTS\s+)?`?' . preg_quote($table, '/') . '`?/i',
            'CREATE TABLE IF NOT EXISTS `' . $prefixedTable . '`',
            $content
        );
        
        // ALTER TABLE
        $content = preg_replace(
            '/ALTER\s+TABLE\s+`?' . preg_quote($table, '/') . '`?/i',
            'ALTER TABLE `' . $prefixedTable . '`',
            $content
        );
        
        // INSERT INTO
        $content = preg_replace(
            '/INSERT\s+INTO\s+`?' . preg_quote($table, '/') . '`?/i',
            'INSERT INTO `' . $prefixedTable . '`',
            $content
        );
        
        // UPDATE
        $content = preg_replace(
            '/UPDATE\s+`?' . preg_quote($table, '/') . '`?/i',
            'UPDATE `' . $prefixedTable . '`',
            $content
        );
        
        // DELETE FROM
        $content = preg_replace(
            '/DELETE\s+FROM\s+`?' . preg_quote($table, '/') . '`?/i',
            'DELETE FROM `' . $prefixedTable . '`',
            $content
        );
        
        // SELECT FROM / JOIN
        $content = preg_replace(
            '/(?:FROM|JOIN|INTO|UPDATE)\s+`?' . preg_quote($table, '/') . '`?(?:\s|$|`|,|;)/i',
            '${1}`' . $prefixedTable . '`${2}',
            $content
        );
        
        // REFERENCES in foreign keys
        $content = preg_replace(
            '/REFERENCES\s+`?' . preg_quote($table, '/') . '`?\(/i',
            'REFERENCES `' . $prefixedTable . '`(',
            $content
        );
        
        // Table references in WHERE, SET clauses (more careful)
        $content = preg_replace(
            '/\b' . preg_quote($table, '/') . '\./i',
            $prefixedTable . '.',
            $content
        );
    }
    
    // Only write if content changed
    if ($content !== $originalContent) {
        // Create backup
        $backupFile = $file . '.backup';
        copy($file, $backupFile);
        
        // Write updated content
        file_put_contents($file, $content);
        echo "  ✓ Updated {$filename} (backup saved to {$filename}.backup)\n";
    } else {
        echo "  - No changes needed for {$filename}\n";
    }
}

echo "\nDone! All migration files have been updated with campaign_department_ prefix.\n";
echo "Backup files (.backup) have been created for all modified files.\n";






