<?php
require __DIR__ . '/../src/config.php';

$user = require_login();
$preview = '';
$pageTitle = 'Import Preview';
require __DIR__ . '/includes/header.php';
?>
<div class="card">
  <h1>URL Preview / Import</h1>
  <p class="muted">Fetches <b>any</b> URL server-side &mdash; SSRF risk
  (loopback, metadata and private hosts are reachable).</p>

  <?php if ($_SERVER['REQUEST_METHOD'] === 'POST'):
    // VULN: SSRF - arbitrary URL fetched server-side with no allowlist.
    $url = $_POST['url'] ?? '';
    $preview = @file_get_contents($url);
    if ($preview === false): ?>
      <div class="msg-err">Could not fetch <?= $url ?></div>
    <?php else: ?>
      <div class="card" style="background:#0f172a;color:#d7e6f5;font-family:Consolas,Menlo,monospace;font-size:.82rem;overflow-x:auto"><?= $preview ?></div>
    <?php endif;
  endif; ?>

  <form method="post" action="import_preview.php">
    <label>URL</label>
    <input type="text" name="url" placeholder="http://catalog.ftminna.internal/">
    <button type="submit">Preview</button>
  </form>
</div>
<?php require __DIR__ . '/includes/footer.php'; ?>
