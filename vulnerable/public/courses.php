<?php
require __DIR__ . '/../src/config.php';

$user = require_login();
$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $courseId = (int) ($_POST['course_id'] ?? 0);
    $userId   = (int) $user['id'];

    // VULN: concatenated INSERT with no CSRF protection and no capacity check.
    $sql = "INSERT INTO enrolments (user_id, course_id, status) VALUES ($userId, $courseId, 'pending')";
    if (mysqli_query($conn, $sql)) {
        $message = 'Registered!';
    } else {
        $message = mysqli_error($conn); // VULN: leaks schema details
    }
}

$rows = mysqli_query($conn, 'SELECT * FROM courses ORDER BY code');
$pageTitle = 'Course Registration';
require __DIR__ . '/includes/header.php';
?>
<div class="card">
  <h1>Course Registration</h1>
  <p class="muted">No capacity checks, no CSRF protection, unescaped values.</p>

  <?php if ($message): ?><div class="msg-ok"><?= $message ?></div><?php endif; ?>

  <table>
    <tr><th>Code</th><th>Title</th><th>Units</th><th></th></tr>
    <?php while ($c = mysqli_fetch_assoc($rows)): ?>
    <tr>
      <td><b><?= $c['code'] ?></b></td>
      <td><?= $c['title'] ?></td>
      <td><?= $c['credit_units'] ?></td>
      <td>
        <form method="post" action="courses.php">
          <input type="hidden" name="course_id" value="<?= $c['id'] ?>">
          <button type="submit">Register</button>
        </form>
      </td>
    </tr>
    <?php endwhile; ?>
  </table>
</div>
<?php require __DIR__ . '/includes/footer.php'; ?>
