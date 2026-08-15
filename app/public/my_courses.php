<?php
declare(strict_types=1);
require __DIR__ . '/../src/bootstrap.php';

$user = Auth::requireLogin();

$rows = Database::run(
    'SELECT c.code, c.title, c.credit_units, e.status, e.registered_at
     FROM enrolments e JOIN courses c ON c.id = e.course_id
     WHERE e.user_id = ? ORDER BY e.registered_at DESC',
    [$user['id']]
)->fetchAll();

$units = 0;
$approved = 0;
$waiting = 0;
foreach ($rows as $r) {
    if ($r['status'] === 'enrolled') {
        $units += (int) $r['credit_units'];
        $approved++;
    } elseif ($r['status'] === 'pending') {
        $waiting++;
    }
}

$pageTitle = 'My Courses';
require __DIR__ . '/../views/header.php';
?>
<div class="page-head">
  <div>
    <h1><span class="marker"></span>My Courses</h1>
    <p>Courses you have registered for this session.</p>
  </div>
</div>

<div class="stat-grid" style="grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));">
  <div class="stat">
    <span class="ico green"><?= icon('checklist', 24) ?></span>
    <div><div class="n"><?= $approved ?></div><div class="l">Enrolled</div></div>
  </div>
  <div class="stat">
    <span class="ico amber"><?= icon('book', 24) ?></span>
    <div><div class="n"><?= $waiting ?></div><div class="l">Pending approval</div></div>
  </div>
  <div class="stat">
    <span class="ico slate"><?= icon('cap', 24) ?></span>
    <div><div class="n"><?= $units ?></div><div class="l">Total units</div></div>
  </div>
</div>

<div class="card">
  <h2><?= icon('checklist', 17) ?> Registration list</h2>
  <?php if (!$rows): ?>
    <div class="empty">
      <?= icon('book', 34) ?>
      <p>You have not registered for any courses yet.</p>
      <div class="actions-row" style="justify-content:center;margin-top:10px">
        <a class="btn small" href="/courses.php"><?= icon('plus', 14) ?> Register now</a>
      </div>
    </div>
  <?php else: ?>
  <div class="table-wrap">
  <table>
    <thead>
      <tr><th>Code</th><th>Title</th><th>Units</th><th>Status</th><th>Registered</th></tr>
    </thead>
    <tbody>
    <?php foreach ($rows as $row): ?>
    <tr>
      <td class="strong"><?= e($row['code']) ?></td>
      <td><?= e($row['title']) ?></td>
      <td><?= (int) $row['credit_units'] ?></td>
      <td>
        <?php if ($row['status'] === 'enrolled'): ?>
          <span class="badge green"><span class="dot"></span>Enrolled</span>
        <?php elseif ($row['status'] === 'pending'): ?>
          <span class="badge amber"><span class="dot"></span>Pending</span>
        <?php else: ?>
          <span class="badge red"><span class="dot"></span><?= e(ucfirst($row['status'])) ?></span>
        <?php endif; ?>
      </td>
      <td><?= e($row['registered_at']) ?></td>
    </tr>
    <?php endforeach; ?>
    </tbody>
  </table>
  </div>
  <?php endif; ?>
</div>
<?php require __DIR__ . '/../views/footer.php'; ?>
