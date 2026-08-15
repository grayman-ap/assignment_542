<?php
declare(strict_types=1);
require __DIR__ . '/../src/bootstrap.php';

$user = Auth::requireLogin();
$errors = [];
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'register') {
    try {
        Csrf::verify();
        $courseId = Input::intRange($_POST['course_id'] ?? null, 1, 1000000, 'course_id');

        $course = Database::run('SELECT * FROM courses WHERE id = ?', [$courseId])->fetch();
        if (!$course) {
            throw new ValidationError(['course_id' => 'Course does not exist.']);
        }
        $count = (int) Database::run(
            'SELECT COUNT(*) FROM enrolments WHERE course_id = ? AND status = "enrolled"',
            [$courseId]
        )->fetchColumn();
        if ($count >= (int) $course['capacity']) {
            throw new ValidationError(['course_id' => 'Course has reached full capacity.']);
        }
        Database::run(
            'INSERT INTO enrolments (user_id, course_id, status) VALUES (?, ?, "pending")',
            [$user['id'], $courseId]
        );
        $success = 'Registration request submitted for ' . $course['code'] . '.';
        Logger::log('course_registration', ['outcome' => 'pending', 'course_id' => (string) $courseId]);
    } catch (PDOException $e) {
        if ($e->getCode() === '23000') {
            $errors['course_id'] = 'You already registered this course.';
        } else {
            throw $e;
        }
    } catch (ValidationError $e) {
        $errors = $e->errors;
    }
}

// Course list with each student's existing registration status.
$courses = Database::run(
    'SELECT c.*,
            (SELECT COUNT(*) FROM enrolments e
              WHERE e.course_id = c.id AND e.status = "enrolled") AS enrolled_count,
            (SELECT status FROM enrolments e
              WHERE e.course_id = c.id AND e.user_id = ?) AS my_status
     FROM courses c ORDER BY c.code',
    [$user['id']]
)->fetchAll();

$pageTitle = 'Register Courses';
require __DIR__ . '/../views/header.php';
?>
<div class="page-head">
  <div>
    <h1><span class="marker"></span>Course Registration</h1>
    <p>Select courses to register for. Capacity and your current registration status are shown.</p>
  </div>
</div>

<?php if ($success): ?>
  <div class="alert alert-success"><?= icon('check', 16) ?><span><?= e($success) ?></span></div>
<?php endif; ?>
<?php if (isset($errors['csrf_token'])): ?>
  <div class="alert alert-error"><?= icon('alert', 16) ?><span><?= e($errors['csrf_token']) ?></span></div>
<?php endif; ?>
<?php if (!empty($errors['course_id']) && !isset($errors['csrf_token'])): ?>
  <div class="alert alert-error"><?= icon('alert', 16) ?><span><?= e($errors['course_id']) ?></span></div>
<?php endif; ?>

<div class="card">
  <div class="table-wrap">
  <table>
    <thead>
      <tr><th>Code</th><th>Title</th><th>Units</th><th>Capacity</th><th>Status</th><th></th></tr>
    </thead>
    <tbody>
    <?php foreach ($courses as $course): ?>
    <?php
        $enrolled = (int) $course['enrolled_count'];
        $capacity = (int) $course['capacity'];
        $pct = $capacity > 0 ? (int) round($enrolled / $capacity * 100) : 0;
        $full = $enrolled >= $capacity;
        $barClass = $full ? 'full' : ($pct >= 85 ? 'hot' : '');
    ?>
    <tr>
      <td class="strong"><?= e($course['code']) ?></td>
      <td><?= e($course['title']) ?></td>
      <td><?= (int) $course['credit_units'] ?></td>
      <td>
        <span class="cap">
          <span class="bar"><i class="<?= $barClass ?>" style="width:<?= min(100, $pct) ?>%"></i></span>
          <span class="num"><?= $enrolled ?> / <?= $capacity ?></span>
        </span>
      </td>
      <td>
        <?php if ($course['my_status'] === 'pending'): ?>
          <span class="badge amber"><span class="dot"></span>Pending</span>
        <?php elseif ($course['my_status'] === 'enrolled'): ?>
          <span class="badge green"><span class="dot"></span>Enrolled</span>
        <?php elseif ($course['my_status'] === 'dropped'): ?>
          <span class="badge slate"><span class="dot"></span>Dropped</span>
        <?php else: ?>
          <span class="muted" style="font-size:.82rem">Not registered</span>
        <?php endif; ?>
      </td>
      <td>
        <?php if (!$course['my_status'] && !$full): ?>
        <form method="post" action="/courses.php">
          <?= Csrf::hiddenField() ?>
          <input type="hidden" name="action" value="register">
          <input type="hidden" name="course_id" value="<?= (int) $course['id'] ?>">
          <button type="submit" class="small"><?= icon('plus', 14) ?> Register</button>
        </form>
        <?php elseif ($full && !$course['my_status']): ?>
          <span class="badge red"><span class="dot"></span>Full</span>
        <?php else: ?>
          <span class="muted" style="font-size:.82rem">&mdash;</span>
        <?php endif; ?>
      </td>
    </tr>
    <?php endforeach; ?>
    </tbody>
  </table>
  </div>
</div>
<?php require __DIR__ . '/../views/footer.php'; ?>
