<?php /** Access denied page (403). */ ?>
<?php $pageTitle = 'Access denied'; ?>
<?php require __DIR__ . '/header.php'; ?>
<div class="page-head">
  <div>
    <h1><span class="marker"></span>Access denied</h1>
    <p>You do not have permission to view this page.</p>
  </div>
</div>
<div class="card">
  <div class="empty">
    <?= icon('lock', 40) ?>
    <p><strong>403 Forbidden</strong> &mdash; your role does not allow this action.</p>
    <p class="muted">If you believe this is a mistake, contact the administrator.
    This event has been recorded in the audit log.</p>
    <div class="actions-row" style="justify-content:center;margin-top:14px">
      <a class="btn small" href="/index.php"><?= icon('grid', 15) ?> Back to dashboard</a>
    </div>
  </div>
</div>
<?php require __DIR__ . '/footer.php'; ?>
