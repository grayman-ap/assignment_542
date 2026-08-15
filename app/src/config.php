<?php
/**
 * Configuration and environment bootstrap.
 * Secrets come from environment variables (docker compose .env). A local
 * .env file next to the repository root is also honoured when present.
 * No secret values are committed to the repository.
 */
declare(strict_types=1);

function app_env(string $key, string $default = ''): string
{
    $v = getenv($key);
    return $v === false || $v === '' ? $default : $v;
}

function load_dotenv(string $path): void
{
    if (!is_file($path)) {
        return;
    }
    foreach (file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
        $line = trim($line);
        if ($line === '' || $line[0] === '#') {
            continue;
        }
        $pos = strpos($line, '=');
        if ($pos === false) {
            continue;
        }
        $key = trim(substr($line, 0, $pos));
        $val = trim(substr($line, $pos + 1));
        if (getenv($key) === false) {
            putenv("$key=$val");
            $_ENV[$key] = $val;
        }
    }
}

load_dotenv(dirname(__DIR__, 2) . '/.env');

define('APP_NAME', 'FUTMinna Student Registration');
define('APP_ENV', app_env('APP_ENV', 'production'));
define('DB_HOST', app_env('DB_HOST', 'db'));
define('DB_PORT', app_env('DB_PORT', '3306'));
define('DB_NAME', app_env('DB_NAME', 'student_reg'));
define('DB_USER', app_env('DB_USER', 'app'));
define('DB_PASSWORD', app_env('DB_PASSWORD', 'CHANGE_ME_app'));
define('LOG_DIR', __DIR__ . '/../logs');
define('STORAGE_DIR', __DIR__ . '/../storage/documents');

// Authentication policy
define('AUTH_MAX_ATTEMPTS', (int) app_env('AUTH_MAX_ATTEMPTS', '5'));
define('AUTH_LOCKOUT_MINUTES', (int) app_env('AUTH_LOCKOUT_MINUTES', '15'));
define('AUTH_RATE_LIMIT_MAX', (int) app_env('AUTH_RATE_LIMIT_MAX', '20'));
define('SESSION_SECURE', app_env('SESSION_SECURE', '0') === '1');

// Runtime hardening based on environment
error_reporting(E_ALL);
ini_set('display_errors', APP_ENV === 'production' ? '0' : '1');
ini_set('log_errors', '1');
ini_set('error_log', LOG_DIR . '/php_errors.log');
date_default_timezone_set('Africa/Lagos');

// Let the storage / log directories exist (they are outside the web root).
foreach ([LOG_DIR, STORAGE_DIR] as $dir) {
    if (!is_dir($dir)) {
        @mkdir($dir, 0750, true);
    }
}
