<?php
function loadEnv($path) {
    if (!file_exists($path)) return;

    $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        $line = trim($line);
        if ($line === '' || strpos($line, '#') === 0) continue;
        if (strpos($line, '=') === false) continue;

        list($name, $value) = explode('=', $line, 2);
        $_ENV[trim($name)] = trim($value);
    }
}

loadEnv(__DIR__ . '/../.env');

$app_env = $_ENV['APP_ENV'] ?? 'local';

if ($app_env === 'production') {
    $db_host = $_ENV['PROD_DB_HOST'] ?? 'localhost';
    $db_name = $_ENV['PROD_DB_NAME'] ?? '';
    $db_user = $_ENV['PROD_DB_USER'] ?? '';
    $db_pass = $_ENV['PROD_DB_PASS'] ?? '';
    $db_port = $_ENV['PROD_DB_PORT'] ?? 3306;
} else {
    $db_host = $_ENV['LOCAL_DB_HOST'] ?? 'localhost';
    $db_name = $_ENV['LOCAL_DB_NAME'] ?? '';
    $db_user = $_ENV['LOCAL_DB_USER'] ?? '';
    $db_pass = $_ENV['LOCAL_DB_PASS'] ?? '';
    $db_port = $_ENV['LOCAL_DB_PORT'] ?? 3306;
}

$mysqli = new mysqli($db_host, $db_user, $db_pass, $db_name, $db_port);

if ($mysqli->connect_error) {
    error_log('MySQLi Connection Error: ' . $mysqli->connect_error);
    exit('Database connection failed.');
}

$mysqli->set_charset("utf8mb4");