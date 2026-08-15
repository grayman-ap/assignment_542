<?php
declare(strict_types=1);
require __DIR__ . '/../../src/bootstrap.php';

$user = Auth::requireAdmin();
$errors = [];
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        Csrf::verify();
        $id = Input::intRange($_POST['enrolment_id'] ?? null, 1, 1000000, 'enrolment_id');
        $status = $_POST['status'] ?? '';
        if (!in_array($status, ['enrolled', 'dropped'], true)) {
            throw new ValidationError(['status' => 'Invalid status.']);
        }
        Database::run(
            'UPDATE enrolments SET status = ? WHERE id = ?',
            [$status, $id]
        );
        $success = 'Enrolment updated.';
        Logger::log('enrolment_updated', ['outcome' => $status]);
    } catch (ValidationError $e) {
        $errors = $e->errors;
    }
}

$rows = Database::run(
    'SELECT e.id, e.status, e.registered_at, u.matric_no, u.full_name, u.email, c.code, c.title
     FROM enrolments e
     JOIN users u   ON u.id = e.user_id
     JOIN courses c ON c.id = e.course_id
     ORDER BY e.registered_at DESC'
)->fetchAll();

$pageTitle = 'Manage Enrolments';
require __DIR__ . '/../../views/header.php';
?>
<div class="page-head">
  <div>
    <h1><span class="marker"></span>Manage Enrolments</h1>
    <p>Approve or drop student course registrations.</p>
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
      <tr><th>Student</th><th>Matric</th><th>Course</th><th>Status</th><th>Registered</th><th>Actions</th></tr>
    </thead>
    <tbody>
    <?php foreach ($rows as $r): ?>
    <tr>
      <td class="strong"><?= e($r['full_name']) ?></td>
      <td class="mono"><?= e($r['matric_no']) ?></td>
      <td><span class="strong"><?= e($r['code']) ?></span> &mdash; <?= e($r['title']) ?></td>
      <td>
        <?php if ($r['status'] === 'enrolled'): ?>
          <span class="badge green"><span class="dot"></span>Enrolled</span>
        <?php elseif ($r['status'] === 'pending'): ?>
          <span class="badge amber"><span class="dot"></span>Pending</span>
        <?php else: ?>
          <span class="badge red"><span class="dot"></span>Dropped</span>
        <?php endif; ?>
      </td>
      <td><?= e($r['registered_at']) ?></td>
      <td>
        <div class="td-actions">
          <?php if ($r['status'] !== 'enrolled'): ?>
          <form method="post" action="/admin/enrolments.php">
            <?= Csrf::hiddenField() ?>
            <input type="hidden" name="enrolment_id" value="<?= (int) $r['id'] ?>">
            <input type="hidden" name="status" value="enrolled">
            <button type="submit" class="small"><?= icon('check', 14) ?> Approve</button>
          </form>
          <?php endif; ?>
          <?php if ($r['status'] !== 'dropped'): ?>
          <form method="post" action="/admin/enrolments.php">
            <?= Csrf::hiddenField() ?>
            <input type="hidden" name="enrolment_id" value="<?= (int) $r['id'] ?>">
            <input type="hidden" name="status" value="dropped">
            <button type="submit" class="small danger"><?= icon('x', 14) ?> Drop</button>
          </form>
          <?php endif; ?>
        </div>
      </td>
    </tr>
    <?php endforeach; ?>
    </tbody>
  </table>
  </div>
</div>
<?php require __DIR__ . '/../../views/footer.php'; ?>
