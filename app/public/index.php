<?php
declare(strict_types=1);
require __DIR__ . '/../src/bootstrap.php';

$user = Auth::requireLogin();
$stats = [];

// Summary numbers for the student dashboard.
$enrolled = Database::run(
    'SELECT COUNT(*) FROM enrolments WHERE user_id = ? AND status = "enrolled"',
    [$user['id']]
)->fetchColumn();
$docs = Database::run(
    'SELECT COUNT(*) FROM documents WHERE user_id = ?',
    [$user['id']]
)->fetchColumn();
$pending = Database::run(
    'SELECT COUNT(*) FROM enrolments WHERE user_id = ? AND status = "pending"',
    [$user['id']]
)->fetchColumn();
$units = Database::run(
    'SELECT COALESCE(SUM(c.credit_units), 0) FROM enrolments e
      JOIN courses c ON c.id = e.course_id
     WHERE e.user_id = ? AND e.status = "enrolled"',
    [$user['id']]
)->fetchColumn();
$isAdmin = ($user['role'] ?? '') === 'admin';

$pageTitle = 'Dashboard';
require __DIR__ . '/../views/header.php';
?>
<div class="page-head">
  <div>
    <h1><span class="marker"></span>Welcome back, <?= e(explode(' ', (string) $user['full_name'])[0]) ?></h1>
    <p>Here is what is happening with your registration today.</p>
  </div>
</div>

<div class="stat-grid">
  <div class="stat">
    <span class="ico green"><?= icon('checklist', 24) ?></span>
    <div><div class="n"><?= (int) $enrolled ?></div><div class="l">Enrolled courses</div></div>
  </div>
  <div class="stat">
    <span class="ico amber"><?= icon('book', 24) ?></span>
    <div><div class="n"><?= (int) $pending ?></div><div class="l">Pending approvals</div></div>
  </div>
  <div class="stat">
    <span class="ico slate"><?= icon('cap', 24) ?></span>
    <div><div class="n"><?= (int) $units ?></div><div class="l">Credit units</div></div>
  </div>
  <div class="stat">
    <span class="ico blue"><?= icon('file', 24) ?></span>
    <div><div class="n"><?= (int) $docs ?></div><div class="l">Documents uploaded</div></div>
  </div>
</div>

<div class="card">
  <h2><?= icon('bolt', 17) ?> Quick actions</h2>
  <div class="actions-row">
    <a class="btn" href="/courses.php"><?= icon('book', 16) ?> Register for courses</a>
    <a class="btn secondary" href="/my_courses.php"><?= icon('checklist', 16) ?> View my courses</a>
    <a class="btn ghost" href="/upload.php"><?= icon('upload', 16) ?> Upload document</a>
    <a class="btn ghost" href="/profile.php"><?= icon('user', 16) ?> Edit profile</a>
  </div>
</div>

<?php if ($isAdmin): ?>
<div class="card">
  <h2><?= icon('settings', 17) ?> Administration</h2>
  <div class="actions-row">
    <a class="btn" href="/admin/users.php"><?= icon('users', 16) ?> Manage users</a>
    <a class="btn secondary" href="/admin/courses.php"><?= icon('book', 16) ?> Manage courses</a>
    <a class="btn ghost" href="/admin/enrolments.php"><?= icon('checklist', 16) ?> Manage enrolments</a>
  </div>
</div>
<?php endif; ?>
<?php require __DIR__ . '/../views/footer.php'; ?>
