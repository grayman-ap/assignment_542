<?php
/**
 * Seed runner for the hardened database (student_reg).
 * Generates Argon2id password hashes at runtime - plaintext is never stored.
 *
 * Usage (from repo root):
 *   docker compose run --rm cli seed
 *
 * Fictitious data only.
 */
declare(strict_types=1);

function env(string $key, string $default = ''): string
{
    $v = getenv($key);
    return $v === false || $v === '' ? $default : $v;
}

$host = env('DB_HOST', 'db');
$port = env('DB_PORT', '3306');
$name = env('DB_NAME', 'student_reg');
$user = env('DB_USER', 'app');
$pass = env('DB_PASSWORD', 'CHANGE_ME_app');

$pdo = new PDO(
    "mysql:host=$host;port=$port;dbname=$name;charset=utf8mb4",
    $user,
    $pass,
    [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
);

$outDir = __DIR__ . '/seed-output';
if (!is_dir($outDir)) {
    mkdir($outDir, 0750, true);
}

// --- Admin -------------------------------------------------------------
$adminEmail = env('ADMIN_EMAIL', 'admin@ftminna.edu.ng');
$adminPass  = env('ADMIN_PASSWORD', '');
$generated  = false;
if ($adminPass === '') {
    $adminPass = bin2hex(random_bytes(12)); // 24-char random placeholder
    $generated = true;
}

$stmt = $pdo->prepare('SELECT COUNT(*) FROM users WHERE email = ?');
$stmt->execute([$adminEmail]);
$exists = (int) $stmt->fetchColumn() > 0;

if (!$exists) {
    $pdo->prepare(
        'INSERT INTO users (matric_no, full_name, email, password_hash, role, phone)
         VALUES (:m, :n, :e, :h, :r, :p)'
    )->execute([
        ':m' => '2024/1-00001',
        ':n' => 'System Administrator',
        ':e' => $adminEmail,
        ':h' => password_hash($adminPass, PASSWORD_ARGON2ID),
        ':r' => 'admin',
        ':p' => '00000000000',
    ]);
    echo "[seed] admin created: $adminEmail\n";
} else {
    echo "[seed] admin already exists, leaving unchanged: $adminEmail\n";
}

if ($generated) {
    file_put_contents("$outDir/ADMIN_PASSWORD.txt", $adminPass . "\n");
    echo "[seed] random admin password written to db/seed-output/ADMIN_PASSWORD.txt (git-ignored).\n";
}

// --- Demo students -------------------------------------------------------
$students = [
    ['2022/1-10111', 'Amina Yusuf',   'amina.yusuf@ftminna.edu.ng',   'Student@1234!'],
    ['2022/1-10112', 'Tunde Bakare',  'tunde.bakare@ftminna.edu.ng',  'Student@1234!'],
    ['2022/1-10113', 'Ngozi Okafor',  'ngozi.okafor@ftminna.edu.ng',  'Student@1234!'],
];

$stmt = $pdo->prepare(
    'INSERT IGNORE INTO users (matric_no, full_name, email, password_hash, role, phone)
     VALUES (:m, :n, :e, :h, :r, :p)'
);
foreach ($students as [$m, $n, $e, $pw]) {
    $stmt->execute([
        ':m' => $m,
        ':n' => $n,
        ':e' => $e,
        ':h' => password_hash($pw, PASSWORD_ARGON2ID),
        ':r' => 'student',
        ':p' => '00000000000',
    ]);
}
echo "[seed] demo students ensured.\n";

// --- Courses -------------------------------------------------------------
$courses = [
    ['IFT 542', 'Information Security',      3, 60, 'Security assessment and hardening'],
    ['COS 101', 'Introduction to Computing', 3, 60, 'Foundations of computing'],
    ['COS 201', 'Data Structures',           3, 60, 'Linear and non-linear data structures'],
    ['MAT 111', 'Engineering Mathematics I', 3, 60, 'Calculus and algebra'],
    ['PHY 101', 'General Physics I',         2, 60, 'Mechanics and heat'],
    ['GST 105', 'Use of English',            2, 60, 'Communication skills'],
];
$stmt = $pdo->prepare(
    'INSERT IGNORE INTO courses (code, title, credit_units, capacity, description)
     VALUES (:c, :t, :u, :cap, :d)'
);
foreach ($courses as [$code, $title, $units, $cap, $desc]) {
    $stmt->execute([':c' => $code, ':t' => $title, ':u' => $units, ':cap' => $cap, ':d' => $desc]);
}
echo "[seed] courses ensured.\n";

// --- Sample enrolments -----------------------------------------------------
$getUserId = function (string $email) use ($pdo): ?int {
    $s = $pdo->prepare('SELECT id FROM users WHERE email = ?');
    $s->execute([$email]);
    $id = $s->fetchColumn();
    return $id === false ? null : (int) $id;
};
$getCourseId = function (string $code) use ($pdo): ?int {
    $s = $pdo->prepare('SELECT id FROM courses WHERE code = ?');
    $s->execute([$code]);
    $id = $s->fetchColumn();
    return $id === false ? null : (int) $id;
};

$samples = [
    ['amina.yusuf@ftminna.edu.ng', 'IFT 542', 'enrolled'],
    ['tunde.bakare@ftminna.edu.ng', 'IFT 542', 'enrolled'],
    ['ngozi.okafor@ftminna.edu.ng', 'COS 101', 'pending'],
];

$stmt = $pdo->prepare(
    'INSERT IGNORE INTO enrolments (user_id, course_id, status) VALUES (:u, :c, :s)'
);
foreach ($samples as [$email, $code, $status]) {
    $u = $getUserId($email);
    $c = $getCourseId($code);
    if ($u !== null && $c !== null) {
        $stmt->execute([':u' => $u, ':c' => $c, ':s' => $status]);
    }
}
echo "[seed] sample enrolments ensured.\n";

echo "[seed] done.\n";
