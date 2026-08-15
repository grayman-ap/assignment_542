<?php
/**
 * Global bootstrap: loads libraries, starts a hardened session and emits
 * security headers (Content-Security-Policy, frame/XSS-Content-Type options,
 * referrer policy). Called by every public entry point.
 */
declare(strict_types=1);

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/output.php';
require_once __DIR__ . '/input.php';
require_once __DIR__ . '/csrf.php';
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/ratelimit.php';
require_once __DIR__ . '/logger.php';
require_once __DIR__ . '/ssrf.php';
require_once __DIR__ . '/auth.php';

// --- Session hardening -------------------------------------------------
session_set_cookie_params([
    'lifetime' => 0,
    'path'     => '/',
    'domain'   => '',
    'secure'   => SESSION_SECURE,
    'httponly' => true,
    'samesite' => 'Lax',
]);
session_name('IFT542SID');
session_start();

// --- Security headers ----------------------------------------------------
header('Content-Security-Policy: default-src \'self\'; script-src \'self\'; '
     . 'style-src \'self\'; img-src \'self\' data:; font-src \'self\'; '
     . "connect-src 'self'; object-src 'none'; base-uri 'self'; "
     . "form-action 'self'; frame-ancestors 'none'");
header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: DENY');
header('Referrer-Policy: strict-origin-when-cross-origin');
header('Permissions-Policy: camera=(), microphone=(), geolocation=()');
if (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') {
    header('Strict-Transport-Security: max-age=31536000; includeSubDomains');
}
header('Cache-Control: no-store, no-cache, must-revalidate');

// --- Consistent error handling -------------------------------------------
set_exception_handler(static function (Throwable $e): void {
    if ($e instanceof AuthException) {
        http_response_code($e->httpStatus);
        // Caller handles safe messages; nothing extra rendered here.
        return;
    }
    if ($e instanceof ValidationError) {
        http_response_code(422);
        Logger::log('validation_rejected', [
            'field'  => implode(',', array_keys($e->errors)),
            'reason' => mb_substr($e->getMessage(), 0, 200),
        ], 'warning');
        return;
    }
    if ($e instanceof SsrfError) {
        http_response_code(403);
        return;
    }
    if (APP_ENV !== 'production') {
        echo '<pre>' . e($e->getMessage()) . '</pre>';
        return;
    }
    Logger::log('unhandled_exception', ['reason' => get_class($e) . ': ' . mb_substr($e->getMessage(), 0, 300)], 'error');
    http_response_code(500);
    echo '<h1>Something went wrong</h1><p>Please contact the administrator and try again later.</p>';
});
