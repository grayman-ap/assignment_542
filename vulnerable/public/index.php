<?php
require __DIR__ . '/../src/config.php';

$user = require_login();

// VULN: session cookie lacks HttpOnly/SameSite; no lockout; no audit log.
$pageTitle = 'Dashboard';
require __DIR__ . '/includes/header.php';
?>
<div class="card">
  <h1>Welcome, <?= $user['full_name'] ?></h1>
  <p class="muted">Signed in as <?= $user['email'] ?> &middot; Role: <?= $user['role'] ?> &middot; Matric: <?= $user['matric_no'] ?></p>
  <p><a href="logout.php">Sign out</a></p>
</div>

<div class="card">
  <h2>Quick links</h2>
  <table>
    <tr><th>Section</th><th>Page</th></tr>
    <tr><td>Profile</td><td><a href="profile.php">profile.php</a></td></tr>
    <tr><td>Course registration</td><td><a href="courses.php">courses.php</a></td></tr>
    <tr><td>My courses</td><td><a href="my_courses.php">my_courses.php</a></td></tr>
    <tr><td>Document upload</td><td><a href="upload.php">upload.php</a></td></tr>
    <tr><td>URL preview / import</td><td><a href="import_preview.php">import_preview.php</a></td></tr>
    <?php if ($user['role'] === 'admin'): ?>
    <tr><td>Admin &mdash; courses</td><td><a href="admin_courses.php">admin_courses.php</a></td></tr>
    <tr><td>Admin &mdash; users</td><td><a href="admin_users.php">admin_users.php</a></td></tr>
    <?php endif; ?>
  </table>
</div>
<?php require __DIR__ . '/includes/footer.php'; ?>
