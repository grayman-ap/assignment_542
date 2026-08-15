<?php
/**
 * CLI helper running inside the cli container.
 * Usage: docker compose run --rm cli <command> [args...]
 *
 * Commands:
 *   seed            run db/seed.php (hardened DB, Argon2id hashes)
 *   make_test_users insert hermetic test accounts
 *   count <sql>     print row count for a SELECT
 *   sql <sql>       print first column of each row (TSV)
 *   assert_hash <email> <plain>   verify stored hash properties
 */
declare(strict_types=1);

function cli_env(string $key, string $default = ''): string
{
    $v = getenv($key);
    return $v === false || $v === '' ? $default : $v;
}

$pdo = new PDO(
    'mysql:host=' . cli_env('DB_HOST', 'db') . ';port=' . cli_env('DB_PORT', '3306')
    . ';dbname=' . cli_env('DB_NAME', 'student_reg') . ';charset=utf8mb4',
    cli_env('DB_USER', 'app'),
    cli_env('DB_PASSWORD', 'CHANGE_ME_app'),
    [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_EMULATE_PREPARES => false]
);

$cmd = $argv[1] ?? 'help';

switch ($cmd) {
    case 'seed':
        require __DIR__ . '/../../db/seed.php';
        exit(0);

    case 'make_test_users':
        require __DIR__ . '/make_test_users.php';
        exit(0);

    case 'count': {
        $sql = $argv[2] ?? 'SELECT 1';
        echo (int) $pdo->query($sql)->fetchColumn(), PHP_EOL;
        exit(0);
    }

    case 'sql': {
        $sql = $argv[2] ?? '';
        foreach ($pdo->query($sql)->fetchAll(PDO::FETCH_NUM) as $row) {
            echo implode("\t", $row), PHP_EOL;
        }
        exit(0);
    }

    case 'assert_hash': {
        $email = $argv[2] ?? '';
        $plain = $argv[3] ?? '';
        $s = $pdo->prepare('SELECT password_hash FROM users WHERE email = ?');
        $s->execute([$email]);
        $hash = $s->fetchColumn();
        if ($hash === false) {
            fwrite(STDERR, "FAIL user $email not found\n");
            exit(1);
        }
        if ($hash === $plain) {
            fwrite(STDERR, "FAIL password stored in plaintext\n");
            exit(1);
        }
        if (!str_starts_with((string) $hash, '$argon2id$')) {
            fwrite(STDERR, "FAIL hash is not Argon2id: " . substr((string) $hash, 0, 20) . "\n");
            exit(1);
        }
        if (!password_verify($plain, (string) $hash)) {
            fwrite(STDERR, "FAIL password_verify() rejected the stored hash\n");
            exit(1);
        }
        echo "OK argon2id hash verified for $email (no plaintext stored)\n";
        exit(0);
    }

    default:
        fwrite(STDOUT, "Usage: cli <seed|make_test_users|count|sql|assert_hash> [...]\n");
        exit(0);
}
