<?php
/** @var array|null $user current authenticated user (optional) */
$current = $user ?? null;
$pageTitle = $pageTitle ?? 'Dashboard';

/**
 * Minimal inline SVG icon set (feather-style). Inline SVG avoids any
 * external resource so it is fully compatible with the strict CSP.
 */
function icon(string $name, int $size = 16): string
{
    $paths = [
        'shield'   => '<path d="M12 3l7 3v5c0 4.4-3 7.5-7 9-4-1.5-7-4.6-7-9V6l7-3z"/><path d="M9.5 11.5l2 2 3.5-3.5"/>',
        'grid'     => '<rect x="3" y="3" width="7" height="7" rx="1.5"/><rect x="14" y="3" width="7" height="7" rx="1.5"/><rect x="3" y="14" width="7" height="7" rx="1.5"/><rect x="14" y="14" width="7" height="7" rx="1.5"/>',
        'user'     => '<path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/>',
        'users'    => '<path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/>',
        'book'     => '<path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20"/><path d="M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2z"/>',
        'checklist'=> '<path d="M9 11l3 3L22 4"/><path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11"/>',
        'upload'   => '<path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><path d="M17 8l-5-5-5 5"/><path d="M12 3v12"/>',
        'download' => '<path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><path d="M7 10l5 5 5-5"/><path d="M12 15V3"/>',
        'globe'    => '<circle cx="12" cy="12" r="10"/><path d="M2 12h20"/><path d="M12 2a15 15 0 0 1 0 20 15 15 0 0 1 0-20z"/>',
        'settings' => '<circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 1 1-2.83 2.83l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 1 1-4 0v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 1 1-2.83-2.83l.06-.06a1.65 1.65 0 0 0 .33-1.82 1.65 1.65 0 0 0-1.51-1H3a2 2 0 1 1 0-4h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 1 1 2.83-2.83l.06.06a1.65 1.65 0 0 0 1.82.33H9a1.65 1.65 0 0 0 1-1.51V3a2 2 0 1 1 4 0v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 1 1 2.83 2.83l-.06.06a1.65 1.65 0 0 0-.33 1.82V9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 1 1 0 4h-.09a1.65 1.65 0 0 0-1.51 1z"/>',
        'lock'     => '<rect x="3" y="11" width="18" height="11" rx="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/>',
        'check'    => '<path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><path d="M22 4L12 14.01l-3-3"/>',
        'x'        => '<path d="M18 6L6 18"/><path d="M6 6l12 12"/>',
        'alert'    => '<path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/><path d="M12 9v4"/><path d="M12 17h.01"/>',
        'info'     => '<circle cx="12" cy="12" r="10"/><path d="M12 16v-4"/><path d="M12 8h.01"/>',
        'key'      => '<path d="M21 2l-2 2m-7.61 7.61a5.5 5.5 0 1 1-7.778 7.778 5.5 5.5 0 0 1 7.777-7.777zm0 0L15.5 7.5m0 0l3 3L22 7l-3-3m-3.5 3.5L19 4"/>',
        'logout'   => '<path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><path d="M16 17l5-5-5-5"/><path d="M21 12H9"/>',
        'file'     => '<path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><path d="M14 2v6h6"/><path d="M16 13H8"/><path d="M16 17H8"/>',
        'cap'      => '<path d="M22 10L12 5 2 10l10 5 10-5z"/><path d="M6 12v5c0 1.7 2.7 3 6 3s6-1.3 6-3v-5"/>',
        'plus'     => '<path d="M12 5v14"/><path d="M5 12h14"/>',
        'trash'    => '<path d="M3 6h18"/><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6"/><path d="M8 6V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/>',
        'refresh'  => '<path d="M23 4v6h-6"/><path d="M20.49 15a9 9 0 1 1-2.12-9.36L23 10"/>',
        'mail'     => '<path d="M4 4h16a2 2 0 0 1 2 2v12a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2z"/><path d="M22 6l-10 7L2 6"/>',
        'phone'    => '<path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72c.13.96.36 1.9.7 2.81a2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45c.91.34 1.85.57 2.81.7A2 2 0 0 1 22 16.92z"/>',
        'hash'     => '<path d="M4 9h16"/><path d="M4 15h16"/><path d="M10 3L8 21"/><path d="M16 3l-2 18"/>',
        'monitor'  => '<rect x="2" y="3" width="20" height="14" rx="2"/><path d="M8 21h8"/><path d="M12 17v4"/>',
    ];
    $d = $paths[$name] ?? $paths['info'];
    return '<svg xmlns="http://www.w3.org/2000/svg" width="' . $size . '" height="' . $size . '" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">' . $d . '</svg>';
}

function nav_is(string $fragment): bool
{
    $path = parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH) ?: '';
    return $path === $fragment || str_starts_with($path, $fragment . '/');
}

$script = basename((string) parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH));

function initials(string $name): string
{
    $parts = preg_split('/\s+/', trim($name));
    $out = '';
    foreach (array_slice($parts, 0, 2) as $p) {
        if ($p !== '') {
            $out .= mb_strtoupper(mb_substr($p, 0, 1));
        }
    }
    return $out !== '' ? $out : '?';
}
?><!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= e($pageTitle) ?> &middot; <?= e(APP_NAME) ?></title>
<link rel="stylesheet" href="/assets/style.css">
</head>
<body>
<header class="topbar">
  <div class="wrap topbar-inner">
    <a class="brand" href="/index.php">
      <span class="mark"><?= icon('cap', 22) ?></span>
      <span class="name"><b>FUTMinna</b><span>Student Registration</span></span>
    </a>
    <?php if ($current): ?>
    <div class="user-area">
      <span class="chip">
        <span class="avatar"><?= e(initials((string) $current['full_name'])) ?></span>
        <span class="who">
          <b><?= e($current['full_name']) ?></b>
          <span><?= e($current['role'] === 'admin' ? 'Administrator' : $current['matric_no']) ?></span>
        </span>
      </span>
      <a class="btn invert small" href="/logout.php"><?= icon('logout', 15) ?> Sign out</a>
    </div>
    <?php endif; ?>
  </div>
</header>

<?php if ($current): ?>
<div class="subnav">
  <div class="wrap subnav-inner">
    <a href="/index.php" class="<?= nav_is('/index.php') ? 'active' : '' ?>"><?= icon('grid', 15) ?> Dashboard</a>
    <a href="/profile.php" class="<?= nav_is('/profile.php') ? 'active' : '' ?>"><?= icon('user', 15) ?> Profile</a>
    <a href="/courses.php" class="<?= nav_is('/courses.php') ? 'active' : '' ?>"><?= icon('book', 15) ?> Register Courses</a>
    <a href="/my_courses.php" class="<?= nav_is('/my_courses.php') ? 'active' : '' ?>"><?= icon('checklist', 15) ?> My Courses</a>
    <a href="/upload.php" class="<?= nav_is('/upload.php') ? 'active' : '' ?>"><?= icon('upload', 15) ?> Documents</a>
    <a href="/import_preview.php" class="<?= nav_is('/import_preview.php') ? 'active' : '' ?>"><?= icon('globe', 15) ?> Import Preview</a>
    <?php if (($current['role'] ?? '') === 'admin'): ?>
    <span class="spacer"></span>
    <a href="/admin/users.php" class="admin-link <?= nav_is('/admin/users.php') ? 'active' : '' ?>"><?= icon('users', 15) ?> Users</a>
    <a href="/admin/courses.php" class="admin-link <?= nav_is('/admin/courses.php') ? 'active' : '' ?>"><?= icon('book', 15) ?> Courses</a>
    <a href="/admin/enrolments.php" class="admin-link <?= nav_is('/admin/enrolments.php') ? 'active' : '' ?>"><?= icon('checklist', 15) ?> Enrolments</a>
    <?php endif; ?>
  </div>
</div>
<?php endif; ?>

<main class="wrap">
