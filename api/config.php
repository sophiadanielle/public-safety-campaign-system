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

// CRITICAL: Prioritize PROD_DB_* settings first (typically localhost), then DB_* (typically remote), then LOCAL_DB_*
if (!empty($_ENV['PROD_DB_HOST'])) {
    $db_host = $_ENV['PROD_DB_HOST'];
} elseif (!empty($_ENV['DB_HOST'])) {
    $db_host = $_ENV['DB_HOST'];
} elseif (array_key_exists('LOCAL_DB_HOST', $_ENV)) {
    $db_host = $_ENV['LOCAL_DB_HOST'];
} else {
    $db_host = 'localhost'; // Default fallback
}

if (!empty($_ENV['PROD_DB_NAME'])) {
    $db_name = $_ENV['PROD_DB_NAME'];
} elseif (!empty($_ENV['DB_DATABASE'])) {
    $db_name = $_ENV['DB_DATABASE'];
} elseif (!empty($_ENV['DB_NAME'])) {
    $db_name = $_ENV['DB_NAME'];
} elseif (array_key_exists('LOCAL_DB_NAME', $_ENV)) {
    $db_name = $_ENV['LOCAL_DB_NAME'];
} else {
    $db_name = 'LGU'; // Production default
}

if (!empty($_ENV['PROD_DB_USER'])) {
    $db_user = $_ENV['PROD_DB_USER'];
} elseif (!empty($_ENV['DB_USERNAME'])) {
    $db_user = $_ENV['DB_USERNAME'];
} elseif (!empty($_ENV['DB_USER'])) {
    $db_user = $_ENV['DB_USER'];
} elseif (array_key_exists('LOCAL_DB_USER', $_ENV)) {
    $db_user = $_ENV['LOCAL_DB_USER'];
} else {
    $db_user = 'root'; // Production default
}

if (!empty($_ENV['PROD_DB_PASS'])) {
    $db_pass = $_ENV['PROD_DB_PASS'];
} elseif (array_key_exists('PROD_DB_PASS', $_ENV)) {
    $db_pass = $_ENV['PROD_DB_PASS'];
} elseif (array_key_exists('DB_PASSWORD', $_ENV)) {
    $db_pass = $_ENV['DB_PASSWORD'];
} elseif (array_key_exists('DB_PASS', $_ENV)) {
    $db_pass = $_ENV['DB_PASS'];
} elseif (array_key_exists('LOCAL_DB_PASS', $_ENV)) {
    $db_pass = $_ENV['LOCAL_DB_PASS'];
} else {
    $db_pass = ''; // Will be empty if not set
}

if (!empty($_ENV['PROD_DB_PORT'])) {
    $db_port = $_ENV['PROD_DB_PORT'];
} elseif (!empty($_ENV['DB_PORT'])) {
    $db_port = $_ENV['DB_PORT'];
} elseif (array_key_exists('LOCAL_DB_PORT', $_ENV)) {
    $db_port = $_ENV['LOCAL_DB_PORT'];
} else {
    $db_port = 3306;
}

$mysqli = new mysqli($db_host, $db_user, $db_pass, $db_name, $db_port);

if ($mysqli->connect_error) {
    error_log('MySQLi Connection Error: ' . $mysqli->connect_error);
    exit('Database connection failed.');
}

$mysqli->set_charset("utf8mb4");