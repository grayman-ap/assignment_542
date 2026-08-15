<?php
/**
 * Authentication. Implements the hardened login flow:
 *  1. Validate email/password format before any database work.
 *  2. Reject early if the source IP is rate-limited (429).
 *  3. Fetch the account by identifier (email) with a prepared statement.
 *  4. Reject locked accounts.
 *  5. Verify the password with password_verify() (Argon2id).
 *  6. On success: reset counters, regenerate the session ID (fixation
 *     defence), record success.
 *  7. On failure: increment counters / apply lockout, log, return a generic
 *     "Invalid email or password." message (no account enumeration).
 */
declare(strict_types=1);

require_once __DIR__ . '/db.php';
require_once __DIR__ . '/input.php';
require_once __DIR__ . '/ratelimit.php';
require_once __DIR__ . '/logger.php';

final class AuthException extends RuntimeException
{
    public function __construct(
        public readonly int $httpStatus,
        public readonly string $safeMessage,
        string $reason = ''
    ) {
        parent::__construct($reason !== '' ? $reason : $safeMessage);
    }
}

final class Auth
{
    public static function login(string $emailInput, string $passwordInput): array
    {
        // 1. Input validation (type/length/format) - no DB contact on bad input.
        $email = Input::email($emailInput);
        Input::password($passwordInput);
        $ip = Logger::clientIp();

        // 2. Source-IP rate limiting (sliding window).
        if (AuthThrottle::ipIsBlocked($ip)) {
            Logger::log('login_rate_limited', ['email' => $email, 'reason' => 'ip_window_exceeded'], 'warning');
            throw new AuthException(429, 'Too many attempts. Please try again later.', 'rate_limited');
        }

        // 3. Retrieve by identifier only (parameterized).
        $stmt = Database::run('SELECT * FROM users WHERE email = ? LIMIT 1', [$email]);
        $user = $stmt->fetch();

        // 4. Locked account is rejected even with the right password.
        if ($user) {
            $lock = AuthThrottle::lockoutRemaining((int) $user['id']);
            if ($lock > 0) {
                Logger::log('login_denied_locked', ['email' => $email, 'reason' => 'account_locked'], 'warning');
                throw new AuthException(401, 'Invalid email or password.', 'account_locked');
            }
        }

        // 5. Constant flow: verify against the stored hash.
        $valid = false;
        if ($user) {
            $valid = password_verify($passwordInput, (string) $user['password_hash']);
        }

        if (!$user || !$valid) {
            if ($user) {
                $attempts = AuthThrottle::registerFailure((int) $user['id']);
            }
            AuthThrottle::record($email, $ip, 'failure');
            Logger::log('login_failed', [
                'email'   => $email,
                'outcome' => 'rejected',
                'attempts' => $user ? AuthThrottle::lockoutRemaining((int) $user['id']) > 0 ? 'locked' : 'retry' : 'unknown_account',
            ], 'warning');
            throw new AuthException(401, 'Invalid email or password.', 'invalid_credentials');
        }

        // 6. Success.
        AuthThrottle::clearFailures((int) $user['id']);
        AuthThrottle::record($email, $ip, 'success');
        Logger::log('login_success', ['email' => $email, 'outcome' => 'success']);

        // 7. Session-ID regeneration defeats session fixation.
        session_regenerate_id(true);
        $_SESSION['user_id']    = (int) $user['id'];
        $_SESSION['role']       = $user['role'];
        $_SESSION['email']      = $user['email'];
        Csrf::regenerate();

        return $user;
    }

    public static function user(): ?array
    {
        if (empty($_SESSION['user_id'])) {
            return null;
        }
        $stmt = Database::run('SELECT * FROM users WHERE id = ? LIMIT 1', [$_SESSION['user_id']]);
        $user = $stmt->fetch();
        return $user ?: null;
    }

    public static function requireLogin(): array
    {
        $user = self::user();
        if ($user === null) {
            Logger::log('auth_denied', ['reason' => 'not_authenticated'], 'warning');
            header('Location: /login.php?next=' . urlencode($_SERVER['REQUEST_URI'] ?? '/index.php'));
            exit;
        }
        return $user;
    }

    public static function requireAdmin(): array
    {
        $user = self::requireLogin();
        if (($user['role'] ?? '') !== 'admin') {
            Logger::log('auth_denied', ['reason' => 'role_not_admin'], 'warning');
            http_response_code(403);
            require __DIR__ . '/../views/denied.php';
            exit;
        }
        return $user;
    }

    public static function logout(): void
    {
        Logger::log('logout', ['email' => $_SESSION['email'] ?? '']);
        $_SESSION = [];
        if (ini_get('session.use_cookies')) {
            $p = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000, $p['path'], $p['domain'], $p['secure'], $p['httponly']);
        }
        session_destroy();
    }
}
