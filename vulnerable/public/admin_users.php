<?php
require __DIR__ . '/../src/config.php';

$user = require_admin();

// VULN: no CSRF; no access control on individual actions; concatenated SQL.
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'unlock') {
    $uid = (int) ($_POST['user_id'] ?? 0);
    mysqli_query($conn, "UPDATE users SET failed_attempts = 0, locked_until = NULL WHERE id = $uid");
}

$rows = mysqli_query($conn, 'SELECT id, matric_no, full_name, email, role, failed_attempts FROM users ORDER BY id');
$pageTitle = 'Admin Users';
require __DIR__ . '/includes/header.php';
?>
<div class="card">
  <h1>Admin: Users</h1>
  <p class="muted">Unlock actions carry no CSRF token.</p>

  <table>
    <tr><th>ID</th><th>Matric</th><th>Name</th><th>Email</th><th>Role</th><th>Failed</th><th></th></tr>
    <?php while ($u = mysqli_fetch_assoc($rows)): ?>
    <tr>
      <td><?= $u['id'] ?></td>
      <td><b><?= $u['matric_no'] ?></b></td>
      <td><?= $u['full_name'] ?></td>
      <td><?= $u['email'] ?></td>
      <td><?= $u['role'] ?></td>
      <td><?= $u['failed_attempts'] ?></td>
      <td>
        <form method="post" action="admin_users.php">
          <input type="hidden" name="action" value="unlock">
          <input type="hidden" name="user_id" value="<?= $u['id'] ?>">
          <button type="submit">Unlock</button>
        </form>
      </td>
    </tr>
    <?php endwhile; ?>
  </table>
</div>
<?php require __DIR__ . '/includes/footer.php'; ?>
