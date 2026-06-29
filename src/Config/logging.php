<?php
// Keep application logs out of FastCGI stderr to prevent oversized upstream headers.

$appLogFile = getenv('APP_LOG_FILE') ?: sys_get_temp_dir() . '/safety_campaign_php_errors.log';
$appLogDir = dirname($appLogFile);

if (is_dir($appLogDir) && (is_writable($appLogDir) || file_exists($appLogFile))) {
    ini_set('log_errors', '1');
    ini_set('error_log', $appLogFile);
}
