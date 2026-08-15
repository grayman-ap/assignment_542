<?php
declare(strict_types=1);
require __DIR__ . '/../src/bootstrap.php';

$user = Auth::requireLogin();

$id = Input::intRange($_GET['id'] ?? null, 1, 1000000, 'id');
$doc = Database::run(
    'SELECT * FROM documents WHERE id = ? AND user_id = ?',
    [$id, $user['id']]
)->fetch();
if (!$doc) {
    Logger::log('download_denied', ['reason' => 'not_owner_or_missing'], 'warning');
    http_response_code(404);
    echo '<h1>Document not found</h1>';
    exit;
}

$path = STORAGE_DIR . '/' . $doc['stored_name'];
if (!is_file($path)) {
    http_response_code(404);
    echo '<h1>Document not found</h1>';
    exit;
}

// Force attachment + a fixed allowlisted content type; nosniff is already
// sent by the bootstrap. This prevents content sniffing / script execution.
header('Content-Type: ' . $doc['mime']);
header('Content-Length: ' . (string) filesize($path));
header('Content-Disposition: attachment; filename="' . addcslashes($doc['original_name'], '"') . '"');
header('X-Content-Type-Options: nosniff');
readfile($path);
exit;
