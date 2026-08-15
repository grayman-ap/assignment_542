<?php
/**
 * SSRF hardening for the URL preview/import feature.
 *
 * Defence in depth:
 *  1. Scheme allowlist - only http/https may be requested.
 *  2. Destination allowlist - only the fictitious internal catalogue host
 *     (catalog.ftminna.internal) may be fetched. Raw IP addresses are never
 *     accepted, so direct requests to 127.0.0.1, 10.x, 192.168.x,
 *     169.254.169.254 (cloud metadata) etc. are all rejected.
 *  3. Resolved-IP re-validation - even an allowlisted hostname is refused if
 *     it resolves to loopback, link-local or cloud-metadata ranges.
 *  4. Short timeouts, a redirect cap, and connection to the validated IP with
 *     the original Host header (limits DNS-rebinding abuse).
 *
 * The allowlisted host is an internal lab service on the Docker bridge
 * network (RFC1918), which is expected. Its resolution is re-validated on
 * every request. Residual risks are documented in the report.
 */
declare(strict_types=1);

final class SsrfError extends RuntimeException {}

final class Ssrf
{
    private const ALLOWED_HOSTS = [
        'catalog.ftminna.internal' => true,
    ];

    private const ALLOWED_SCHEMES = ['http', 'https'];

    public static function validate(string $url): array
    {
        $parts = parse_url($url);
        if ($parts === false || empty($parts['host'])) {
            throw new SsrfError('URL could not be parsed.');
        }

        $scheme = strtolower((string) ($parts['scheme'] ?? ''));
        if (!in_array($scheme, self::ALLOWED_SCHEMES, true)) {
            throw new SsrfError('Only http/https URLs are allowed.');
        }

        $host = strtolower((string) $parts['host']);
        if (!isset(self::ALLOWED_HOSTS[$host])) {
            throw new SsrfError('Host is not on the allowed destination list.');
        }

        $ip = gethostbyname($host);
        if ($ip === $host || filter_var($ip, FILTER_VALIDATE_IP) === false) {
            throw new SsrfError('Host could not be resolved.');
        }

        // Even the allowlisted host must not resolve to loopback, link-local
        // or cloud-metadata ranges (169.254.0.0/16 covers 169.254.169.254).
        if (self::isBlockedResolution($ip)) {
            throw new SsrfError('Destination resolves to a blocked (loopback/link-local/metadata) address.');
        }

        $port = isset($parts['port']) ? (int) $parts['port'] : ($scheme === 'https' ? 443 : 80);
        if ($port < 1 || $port > 65535) {
            throw new SsrfError('Port is invalid.');
        }

        return [
            'scheme' => $scheme,
            'host'   => $host,
            'ip'     => $ip,
            'port'   => $port,
            'path'   => $parts['path'] ?? '/',
            'query'  => $parts['query'] ?? '',
        ];
    }

    public static function fetchPreview(string $url): string
    {
        $t = self::validate($url);

        $path = $t['path'] . ($t['query'] !== '' ? '?' . $t['query'] : '');
        // Connect directly to the validated IP to limit DNS rebinding.
        $fp = @stream_socket_client(
            "tcp://{$t['ip']}:{$t['port']}",
            $errno,
            $errstr,
            5.0,
            STREAM_CLIENT_CONNECT
        );
        if ($fp === false) {
            throw new SsrfError('Could not connect to the allowed destination.');
        }
        stream_set_timeout($fp, 5);

        $hostHeader = $t['host'] . ($t['port'] !== 80 && $t['port'] !== 443 ? ':' . $t['port'] : '');
        $request = "GET {$path} HTTP/1.1\r\n"
                 . "Host: {$hostHeader}\r\n"
                 . "User-Agent: IFT542-Preview/1.0\r\n"
                 . "Connection: close\r\n\r\n";
        fwrite($fp, $request);

        $body = '';
        $redirects = 0;
        while (!feof($fp)) {
            $line = fgets($fp);
            if ($line === false) {
                break;
            }
            if (preg_match('/^Location:\s*(\S+)/i', $line, $m)) {
                if (++$redirects > 2) {
                    fclose($fp);
                    throw new SsrfError('Too many redirects.');
                }
                fclose($fp);
                return self::fetchPreview(self::absoluteUrl($url, trim($m[1])));
            }
            if ($line === "\r\n") {
                $body = stream_get_contents($fp);
                break;
            }
        }
        fclose($fp);

        if ($body === false || $body === '') {
            throw new SsrfError('No content returned by the destination.');
        }
        // Rendered as text by the caller - never injected as raw HTML/JS.
        return mb_substr($body, 0, 8192);
    }

    private static function absoluteUrl(string $base, string $loc): string
    {
        $b = parse_url($base);
        if (preg_match('#^[a-z][a-z0-9+.-]*://#i', $loc)) {
            return $loc;
        }
        if (($loc[0] ?? '') === '/') {
            return ($b['scheme'] ?? 'http') . '://' . ($b['host'] ?? '') . $loc;
        }
        return $base;
    }

    private static function isBlockedResolution(string $ip): bool
    {
        $v4 = filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4);
        if ($v4 !== false) {
            // 127.0.0.0/8 loopback and 169.254.0.0/16 link-local + metadata.
            if (str_starts_with($ip, '127.')) {
                return true;
            }
            if (str_starts_with($ip, '169.254.')) {
                return true;
            }
            return false;
        }
        $lower = strtolower($ip);
        // ::1 loopback, fe80::/10 link-local, fc00::/7 unique-local.
        if ($lower === '::1' || str_starts_with($lower, 'fe80:')) {
            return true;
        }
        if (str_starts_with($lower, 'fc') || str_starts_with($lower, 'fd')) {
            return true;
        }
        return false;
    }
}
