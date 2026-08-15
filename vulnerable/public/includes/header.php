<?php
/** Starter (vulnerable) build header. Navigation is rendered raw. */
$pageTitle = $pageTitle ?? 'Student Registration';
$vulnUser = $user ?? null;
$vulnPath = parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH) ?: '';
function vuln_active(string $f): bool { global $vulnPath; return $vulnPath === '/' . $f; }
?><!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= $pageTitle ?> &middot; Student Registration</title>
<link rel="stylesheet" href="/assets/style.css">
</head>
<body>
<header class="topbar">
  <div class="wrap topbar-inner">
    <a class="brand" href="index.php">
      <span class="mark">SR</span>
      <span class="name"><b>FUTMinna Student Registration</b><span>Starter build</span></span>
    </a>
    <span class="starter-pill"><span class="dot"></span>Starter build</span>
  </div>
</header>
<?php if ($vulnUser): ?>
<div class="navrow">
  <div class="wrap navrow-inner">
    <a href="index.php" class="<?= vuln_active('index.php') ? 'active' : '' ?>">Dashboard</a>
    <a href="profile.php" class="<?= vuln_active('profile.php') ? 'active' : '' ?>">Profile</a>
    <a href="courses.php" class="<?= vuln_active('courses.php') ? 'active' : '' ?>">Register Courses</a>
    <a href="my_courses.php" class="<?= vuln_active('my_courses.php') ? 'active' : '' ?>">My Courses</a>
    <a href="upload.php" class="<?= vuln_active('upload.php') ? 'active' : '' ?>">Documents</a>
    <a href="import_preview.php" class="<?= vuln_active('import_preview.php') ? 'active' : '' ?>">Import Preview</a>
    <?php if (($vulnUser['role'] ?? '') === 'admin'): ?>
    <a href="admin_courses.php" class="<?= vuln_active('admin_courses.php') ? 'active' : '' ?>">Admin Courses</a>
    <a href="admin_users.php" class="<?= vuln_active('admin_users.php') ? 'active' : '' ?>">Admin Users</a>
    <?php endif; ?>
    <a href="logout.php">Logout</a>
  </div>
</div>
<?php endif; ?>
<main class="wrap">
