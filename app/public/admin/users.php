<?php
declare(strict_types=1);
require __DIR__ . '/../../src/bootstrap.php';

$user = Auth::requireAdmin();
$errors = [];
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    try {
        Csrf::verify();
        $targetId = Input::intRange($_POST['user_id'] ?? null, 1, 1000000, 'user_id');

        if ($action === 'unlock') {
            Database::run(
                'UPDATE users SET failed_attempts = 0, locked_until = NULL WHERE id = ?',
                [$targetId]
            );
            $success = 'Account unlocked.';
        } elseif ($action === 'reset_password') {
            $newPass = bin2hex(random_bytes(9));
            Database::run(
                'UPDATE users SET password_hash = ? WHERE id = ?',
                [password_hash($newPass, PASSWORD_ARGON2ID), $targetId]
            );
            $success = "Password reset to: $newPass (note it once; it will not be shown again).";
            Logger::log('admin_password_reset', ['outcome' => 'success']);
        } else {
            throw new ValidationError(['action' => 'Unknown action.']);
        }
    } catch (ValidationError $e) {
        $errors = $e->errors;
    }
}

$users = Database::run(
    'SELECT id, matric_no, full_name, email, role, failed_attempts,
            locked_until, created_at
     FROM users ORDER BY created_at DESC'
)->fetchAll();

$pageTitle = 'Manage Users';
require __DIR__ . '/../../views/header.php';
?>
<div class="page-head">
  <div>
    <h1><span class="marker"></span>Manage Users</h1>
    <p>Review accounts, unlock locked users and reset forgotten passwords.</p>
  </div>
</div>

<?php if ($success): ?>
  <div class="alert alert-success"><?= icon('check', 16) ?><span><?= e($success) ?></span></div>
<?php endif; ?>
<?php if (isset($errors['csrf_token'])): ?>
  <div class="alert alert-error"><?= icon('alert', 16) ?><span><?= e($errors['csrf_token']) ?></span></div>
<?php endif; ?>

<div class="card">
  <div class="table-wrap">
  <table>
    <thead>
      <tr>
        <th>Matric</th><th>Name</th><th>Email</th><th>Role</th>
        <th>Failed</th><th>Locked until</th><th>Actions</th>
      </tr>
    </thead>
    <tbody>
    <?php foreach ($users as $u): $locked = !empty($u['locked_until']); ?>
    <tr>
      <td class="strong mono"><?= e($u['matric_no']) ?></td>
      <td><?= e($u['full_name']) ?></td>
      <td><?= e($u['email']) ?></td>
      <td>
        <?php if ($u['role'] === 'admin'): ?>
          <span class="badge admin"><?= icon('settings', 12) ?> Admin</span>
        <?php else: ?>
          <span class="badge slate">Student</span>
        <?php endif; ?>
      </td>
      <td>
        <?php if ((int) $u['failed_attempts'] > 0): ?>
          <span class="badge amber"><span class="dot"></span><?= (int) $u['failed_attempts'] ?></span>
        <?php else: ?>
          <span class="muted">0</span>
        <?php endif; ?>
      </td>
      <td>
        <?php if ($locked): ?>
          <span class="badge red"><span class="dot"></span><?= e($u['locked_until']) ?></span>
        <?php else: ?>
          <span class="muted">&mdash;</span>
        <?php endif; ?>
      </td>
      <td>
        <div class="td-actions">
          <form method="post" action="/admin/users.php">
            <?= Csrf::hiddenField() ?>
            <input type="hidden" name="action" value="unlock">
            <input type="hidden" name="user_id" value="<?= (int) $u['id'] ?>">
            <button type="submit" class="small warning" <?= $locked ? '' : 'disabled' ?>><?= icon('refresh', 14) ?> Unlock</button>
          </form>
          <form method="post" action="/admin/users.php">
            <?= Csrf::hiddenField() ?>
            <input type="hidden" name="action" value="reset_password">
            <input type="hidden" name="user_id" value="<?= (int) $u['id'] ?>">
            <button type="submit" class="small ghost"><?= icon('key', 14) ?> Reset</button>
          </form>
        </div>
      </td>
    </tr>
    <?php endforeach; ?>
    </tbody>
  </table>
  </div>
</div>
<?php require __DIR__ . '/../../views/footer.php'; ?>
