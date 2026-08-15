<?php
/**
 * STARTER (VULNERABLE) application - insecure baseline for comparison.
 * DO NOT use these patterns in production. Every page here demonstrates at
 * least one weakness that is corrected in /app (the hardened version).
 *
 * Known issues: raw SQL concatenation (SQLi), plaintext passwords,
 * no input validation, verbose errors, default admin credentials, session
 * without hardening, XSS (unescaped output), no CSRF tokens, open redirect,
 * unvalidated file uploads and an unguarded SSRF endpoint.
 */
declare(strict_types=1);

error_reporting(E_ALL);
ini_set('display_errors', '1');
ini_set('display_startup_errors', '1');
date_default_timezone_set('Africa/Lagos');

$DB_HOST = getenv('DB_HOST') ?: 'db';
$DB_NAME = getenv('DB_NAME') ?: 'student_reg_vuln';
$DB_USER = getenv('DB_USER') ?: 'app';
$DB_PASS = getenv('DB_PASSWORD') ?: getenv('MYSQL_PASSWORD') ?: 'CHANGE_ME_app';

$conn = mysqli_connect($DB_HOST, $DB_USER, $DB_PASS, $DB_NAME);
if (!$conn) {
    die('Database connection failed: ' . mysqli_connect_error());
}
mysqli_set_charset($conn, 'utf8mb4');

session_start();

function current_user(): ?array
{
    global $conn;
    if (empty($_SESSION['user_id'])) {
        return null;
    }
    $id = $_SESSION['user_id'];
    // VULN: concatenated SQL (SQLi on user_id).
    $r = mysqli_query($conn, "SELECT * FROM users WHERE id = $id LIMIT 1");
    if ($r && ($row = mysqli_fetch_assoc($r))) {
        return $row;
    }
    return null;
}

function require_login(): array
{
    $u = current_user();
    if (!$u) {
        header('Location: login.php');
        exit;
    }
    return $u;
}

function require_admin(): array
{
    $u = require_login();
    if ($u['role'] !== 'admin') {
        http_response_code(403);
        echo '<h1>403 Forbidden</h1>';
        exit;
    }
    return $u;
}

// VULN: unsafe file uploads - raw $_FILES processed without checks.
$UPLOAD_DIR = __DIR__ . '/../storage/documents';
if (!is_dir($UPLOAD_DIR)) {
    @mkdir($UPLOAD_DIR, 0750, true);
}
