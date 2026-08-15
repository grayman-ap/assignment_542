<?php
/**
 * Rate limiting and account lockout.
 *  - Rate limiting: a sliding window of failed login events per source IP.
 *    Past the threshold, the login endpoint returns 429 before any work.
 *  - Account lockout: after AUTH_MAX_ATTEMPTS consecutive failures the account
 *    is locked for AUTH_LOCKOUT_MINUTES. A locked account is rejected even
 *    with the correct password (it also records the incident).
 */
declare(strict_types=1);

require_once __DIR__ . '/db.php';

final class AuthThrottle
{
    public static function ipIsBlocked(string $ip): bool
    {
        $cutoff = gmdate('Y-m-d H:i:s', time() - 900);
        $stmt = Database::run(
            'SELECT COUNT(*) FROM auth_attempts
             WHERE ip = ? AND outcome = "failure" AND created_at >= ?',
            [$ip, $cutoff]
        );
        return (int) $stmt->fetchColumn() >= AUTH_RATE_LIMIT_MAX;
    }

    public static function record(string $email, string $ip, string $outcome): void
    {
        Database::run(
            'INSERT INTO auth_attempts (ip, email, outcome) VALUES (?, ?, ?)',
            [$ip, $email, $outcome]
        );
        // Opportunistic cleanup of old records (>24h).
        if (random_int(0, 99) === 0) {
            Database::run(
                'DELETE FROM auth_attempts WHERE created_at < ?',
                [gmdate('Y-m-d H:i:s', time() - 86400)]
            );
        }
    }

    public static function lockoutRemaining(int $userId): int
    {
        $stmt = Database::run('SELECT locked_until FROM users WHERE id = ?', [$userId]);
        $until = $stmt->fetchColumn();
        if ($until === false || $until === null) {
            return 0;
        }
        $remaining = (int) (strtotime((string) $until) - time());
        return $remaining > 0 ? $remaining : 0;
    }

    public static function registerFailure(int $userId): int
    {
        Database::run(
            'UPDATE users SET failed_attempts = failed_attempts + 1 WHERE id = ?',
            [$userId]
        );
        $stmt = Database::run('SELECT failed_attempts FROM users WHERE id = ?', [$userId]);
        $attempts = (int) $stmt->fetchColumn();

        if ($attempts >= AUTH_MAX_ATTEMPTS) {
            // Compute the expiry in PHP so the app and DB timezones cannot
            // disagree about how long the lock lasts.
            $until = date('Y-m-d H:i:s', time() + AUTH_LOCKOUT_MINUTES * 60);
            Database::run(
                'UPDATE users SET locked_until = ? WHERE id = ?',
                [$until, $userId]
            );
        }
        return $attempts;
    }

    public static function clearFailures(int $userId): void
    {
        Database::run(
            'UPDATE users SET failed_attempts = 0, locked_until = NULL WHERE id = ?',
            [$userId]
        );
    }
}
