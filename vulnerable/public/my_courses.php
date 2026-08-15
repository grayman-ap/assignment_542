<?php
require __DIR__ . '/../src/config.php';

$user = require_login();

// VULN: concatenated SQL.
$id = (int) $user['id'];
$r = mysqli_query($conn,
    "SELECT c.code, c.title, c.credit_units, e.status
     FROM enrolments e JOIN courses c ON c.id = e.course_id
     WHERE e.user_id = $id ORDER BY e.registered_at DESC");

$pageTitle = 'My Courses';
require __DIR__ . '/includes/header.php';
?>
<div class="card">
  <h1>My Courses</h1>
  <table>
    <tr><th>Code</th><th>Title</th><th>Units</th><th>Status</th></tr>
    <?php if ($r): while ($row = mysqli_fetch_assoc($r)): ?>
    <tr>
      <td><b><?= $row['code'] ?></b></td>
      <td><?= $row['title'] ?></td>
      <td><?= $row['credit_units'] ?></td>
      <td><?= $row['status'] ?></td>
    </tr>
    <?php endwhile; else: ?>
    <tr><td colspan="4" class="muted">You have not registered for any courses yet.</td></tr>
    <?php endif; ?>
  </table>
</div>
<?php require __DIR__ . '/includes/footer.php'; ?>
