<?php
/**
 * Structured application logging (JSON Lines).
 * Events record who / what / when WITHOUT secrets or unnecessary personal
 * data. Passwords, tokens and cookies are never written to logs.
 */
declare(strict_types=1);

final class Logger
{
    public static function log(
        string $event,
        array $context = [],
        string $level = 'info'
    ): void {
        $entry = [
            'ts'      => gmdate('c'),
            'level'   => $level,
            'event'   => $event,
            'actor'   => $_SESSION['user_id'] ?? null,
            'ip'      => self::clientIp(),
            'ua'      => substr((string) ($_SERVER['HTTP_USER_AGENT'] ?? ''), 0, 120),
            'method'  => $_SERVER['REQUEST_METHOD'] ?? null,
            'uri'     => self::truncate((string) ($_SERVER['REQUEST_URI'] ?? ''), 200),
        ];

        // Never log secrets; keep context keys on an allowlist.
        $allowed = ['email', 'outcome', 'reason', 'field', 'attempts', 'url_host', 'file_name', 'file_size'];
        foreach ($allowed as $k) {
            if (isset($context[$k])) {
                $entry[$k] = self::truncate((string) $context[$k], 254);
            }
        }

        $line = json_encode($entry, JSON_UNESCAPED_SLASHES);
        $file = LOG_DIR . '/app.log';
        if ($line !== false) {
            @file_put_contents($file, $line . PHP_EOL, FILE_APPEND | LOCK_EX);
        }
    }

    public static function clientIp(): string
    {
        // The app sits behind Docker's bridge network; use REMOTE_ADDR only to
        // avoid trusting spoofable headers.
        return substr((string) ($_SERVER['REMOTE_ADDR'] ?? 'unknown'), 0, 45);
    }

    private static function truncate(string $s, int $len): string
    {
        return mb_strlen($s) > $len ? mb_substr($s, 0, $len - 3) . '...' : $s;
    }
}
