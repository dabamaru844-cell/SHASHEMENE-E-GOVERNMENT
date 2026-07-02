<?php

declare(strict_types=1);

$config = require __DIR__ . '/../config/app.php';
$dbConfig = require __DIR__ . '/../config/database.php';
$GLOBALS['app_config'] = $config;

date_default_timezone_set($config['timezone']);

if (session_status() === PHP_SESSION_NONE) {
    session_name($config['session_name']);
    session_set_cookie_params([
        'lifetime' => $config['session_lifetime'],
        'path' => '/',
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
    session_start();
}

require_once __DIR__ . '/functions.php';
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/lang.php';

try {
    $dsn = sprintf(
        'mysql:host=%s;port=%d;dbname=%s;charset=%s',
        $dbConfig['host'],
        $dbConfig['port'],
        $dbConfig['database'],
        $dbConfig['charset']
    );
    $pdo = new PDO($dsn, $dbConfig['username'], $dbConfig['password'], $dbConfig['options']);
} catch (PDOException $e) {
    error_log('Database connection failed: ' . $e->getMessage());
    if (php_sapi_name() !== 'cli') {
        http_response_code(503);
        die('Database connection failed. Please check configuration.');
    }
    throw $e;
}

if (isset($_GET['lang']) && in_array($_GET['lang'], $config['supported_locales'], true)) {
    $_SESSION['locale'] = $_GET['lang'];
    setcookie('locale', $_GET['lang'], time() + 86400 * 365, '/');
}

$locale = $_SESSION['locale'] ?? $_COOKIE['locale'] ?? $config['locale'];
if (!in_array($locale, $config['supported_locales'], true)) {
    $locale = $config['locale'];
}
loadTranslations($locale);
