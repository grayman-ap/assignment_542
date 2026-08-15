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

        if ($action === 'create') {
            $code = Input::trimmed($_POST['code'] ?? '');
            $title = Input::trimmed($_POST['title'] ?? '');
            $units = Input::intRange($_POST['credit_units'] ?? null, 1, 6, 'credit_units');
            $capacity = Input::intRange($_POST['capacity'] ?? null, 1, 500, 'capacity');
            $desc = mb_substr(Input::trimmed($_POST['description'] ?? ''), 0, 500);
            if (!preg_match('/^[A-Z0-9]{2,4}\s?[0-9]{3}$/i', $code)) {
                throw new ValidationError(['code' => 'Course code format is invalid.']);
            }
            if ($title === '' || strlen($title) > 120) {
                throw new ValidationError(['title' => 'Title must be 1-120 characters.']);
            }
            Database::run(
                'INSERT INTO courses (code, title, credit_units, capacity, description)
                 VALUES (?, ?, ?, ?, ?)',
                [$code, $title, $units, $capacity, $desc]
            );
            $success = "Course $code created.";
            Logger::log('course_created', ['outcome' => 'success']);
        } elseif ($action === 'delete') {
            $id = Input::intRange($_POST['course_id'] ?? null, 1, 1000000, 'course_id');
            Database::run('DELETE FROM courses WHERE id = ?', [$id]);
            $success = 'Course deleted.';
        } else {
            throw new ValidationError(['action' => 'Unknown action.']);
        }
    } catch (PDOException $e) {
        $errors['code'] = $e->getCode() === '23000'
            ? 'Course code already exists.' : 'Could not save the course.';
    } catch (ValidationError $e) {
        $errors = $e->errors;
    }
}

$courses = Database::run(
    'SELECT c.*, (SELECT COUNT(*) FROM enrolments e WHERE e.course_id = c.id) AS enrol_count
     FROM courses c ORDER BY c.code'
)->fetchAll();

$pageTitle = 'Manage Courses';
require __DIR__ . '/../../views/header.php';
?>
<div class="page-head">
  <div>
    <h1><span class="marker"></span>Manage Courses</h1>
    <p>Create courses and control the offerings students can register for.</p>
  </div>
</div>

<?php if ($success): ?>
  <div class="alert alert-success"><?= icon('check', 16) ?><span><?= e($success) ?></span></div>
<?php endif; ?>
<?php if (isset($errors['csrf_token'])): ?>
  <div class="alert alert-error"><?= icon('alert', 16) ?><span><?= e($errors['csrf_token']) ?></span></div>
<?php endif; ?>

<div class="card">
  <h2><?= icon('plus', 17) ?> Add a course</h2>
  <form method="post" action="/admin/courses.php">
    <?= Csrf::hiddenField() ?>
    <input type="hidden" name="action" value="create">
    <div class="form-grid">
      <div>
        <label for="code">Code <span class="req">*</span> <span class="muted" style="font-weight:400">(e.g. IFT 542)</span></label>
        <input type="text" id="code" name="code" required maxlength="12" placeholder="IFT 542">
        <?php if (isset($errors['code'])): ?><p class="field-error"><?= e($errors['code']) ?></p><?php endif; ?>
      </div>
      <div>
        <label for="credit_units">Credit units <span class="req">*</span></label>
        <input type="number" id="credit_units" name="credit_units" min="1" max="6" required value="3">
      </div>
      <div class="full">
        <label for="title">Title <span class="req">*</span></label>
        <input type="text" id="title" name="title" required maxlength="120" placeholder="Introduction to Security">
        <?php if (isset($errors['title'])): ?><p class="field-error"><?= e($errors['title']) ?></p><?php endif; ?>
      </div>
      <div>
        <label for="capacity">Capacity <span class="req">*</span></label>
        <input type="number" id="capacity" name="capacity" min="1" max="500" required value="60">
      </div>
      <div>
        <label for="description">Description</label>
        <input type="text" id="description" name="description" maxlength="500">
      </div>
    </div>
    <div class="form-actions">
      <button type="submit"><?= icon('plus', 16) ?> Create course</button>
    </div>
  </form>
</div>

<div class="card">
  <h2><?= icon('book', 17) ?> Existing courses</h2>
  <div class="table-wrap">
  <table>
    <thead>
      <tr><th>Code</th><th>Title</th><th>Units</th><th>Capacity</th><th>Enrolments</th><th></th></tr>
    </thead>
    <tbody>
    <?php foreach ($courses as $c): ?>
    <tr>
      <td class="strong mono"><?= e($c['code']) ?></td>
      <td><?= e($c['title']) ?></td>
      <td><?= (int) $c['credit_units'] ?></td>
      <td><?= (int) $c['capacity'] ?></td>
      <td>
        <?php if ((int) $c['enrol_count'] > 0): ?>
          <span class="badge blue"><?= (int) $c['enrol_count'] ?> enrolled</span>
        <?php else: ?>
          <span class="muted">0</span>
        <?php endif; ?>
      </td>
      <td>
        <form method="post" action="/admin/courses.php">
          <?= Csrf::hiddenField() ?>
          <input type="hidden" name="action" value="delete">
          <input type="hidden" name="course_id" value="<?= (int) $c['id'] ?>">
          <button type="submit" class="small danger"><?= icon('trash', 14) ?> Delete</button>
        </form>
      </td>
    </tr>
    <?php endforeach; ?>
    </tbody>
  </table>
  </div>
</div>
<?php require __DIR__ . '/../../views/footer.php'; ?>
