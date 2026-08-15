<?php
/**
 * Hermetic test accounts. Created directly in the DB so the test suite does
 * not depend on seed contents. Fictitious data only.
 */
declare(strict_types=1);

$accounts = [
    ['2022/1-99001', 'Test Student', 'tstudent1@ftminna.local', 'TestPass!123', 'student'],
    ['2022/1-99002', 'Test Admin',   'tadmin1@ftminna.local',  'TestPass!123', 'admin'],
    ['2022/1-99003', 'Lock Test',    'tlock1@ftminna.local',   'TestPass!123', 'student'],
];

$stmt = $pdo->prepare(
    'INSERT IGNORE INTO users (matric_no, full_name, email, password_hash, role, phone)
     VALUES (:m, :n, :e, :h, :r, :p)'
);
foreach ($accounts as [$m, $n, $e, $pw, $role]) {
    $stmt->execute([
        ':m' => $m, ':n' => $n, ':e' => $e,
        ':h' => password_hash($pw, PASSWORD_ARGON2ID),
        ':r' => $role, ':p' => '00000000000',
    ]);
    echo "[tests] ensured $e\n";
}
