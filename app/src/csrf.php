<?php
/**
 * CSRF protection. A per-session unpredictable token is generated once and
 * embedded in every state-changing form. The token travels in the POST body
 * (double-submit pattern) and is compared with hash_equals() - the same
 * constant-time comparison used for passwords. Combined with the SameSite=Lax
 * session cookie this blocks cross-site request forgery.
 */
declare(strict_types=1);

require_once __DIR__ . '/input.php';

final class Csrf
{
    public static function token(): string
    {
        if (empty($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['csrf_token'];
    }

    public static function hiddenField(): string
    {
        return '<input type="hidden" name="csrf_token" value="' . e(self::token()) . '">';
    }

    public static function verify(): void
    {
        Input::csrfToken(); // throws ValidationError on mismatch
    }

    public static function regenerate(): void
    {
        unset($_SESSION['csrf_token']);
    }
}
