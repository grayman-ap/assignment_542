<?php
require __DIR__ . '/../src/config.php';

$user = require_admin();
$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // VULN: no CSRF; concatenated SQL.
    $code = $_POST['code'] ?? '';
    $title = $_POST['title'] ?? '';
    $units = (int) ($_POST['credit_units'] ?? 2);
    $cap = (int) ($_POST['capacity'] ?? 60);
    if (mysqli_query($conn,
        "INSERT INTO courses (code, title, credit_units, capacity) VALUES ('$code', '$title', $units, $cap)")) {
        $message = 'Course added.';
    } else {
        $message = mysqli_error($conn);
    }
}

$rows = mysqli_query($conn, 'SELECT * FROM courses ORDER BY code');
$pageTitle = 'Admin Courses';
require __DIR__ . '/includes/header.php';
?>
<div class="card">
  <h1>Admin: Courses</h1>
  <p class="muted">Unvalidated input, concatenated SQL and no CSRF token.</p>

  <?php if ($message): ?><div class="msg-ok"><?= $message ?></div><?php endif; ?>

  <form method="post" action="admin_courses.php">
    <label>Code</label>
    <input type="text" name="code" placeholder="IFT 542">
    <label>Title</label>
    <input type="text" name="title">
    <label>Credit units</label>
    <input type="number" name="credit_units" value="3">
    <label>Capacity</label>
    <input type="number" name="capacity" value="60">
    <button type="submit">Add course</button>
  </form>
</div>

<div class="card">
  <h2>Existing courses</h2>
  <table>
    <tr><th>Code</th><th>Title</th><th>Units</th><th>Capacity</th></tr>
    <?php while ($c = mysqli_fetch_assoc($rows)): ?>
    <tr>
      <td><b><?= $c['code'] ?></b></td>
      <td><?= $c['title'] ?></td>
      <td><?= $c['credit_units'] ?></td>
      <td><?= $c['capacity'] ?></td>
    </tr>
    <?php endwhile; ?>
  </table>
</div>
<?php require __DIR__ . '/includes/footer.php'; ?>
