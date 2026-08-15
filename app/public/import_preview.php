<?php
declare(strict_types=1);
require __DIR__ . '/../src/bootstrap.php';

$user = Auth::requireLogin();
$preview = null;
$error = '';
$url = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        Csrf::verify();
        $url = Input::trimmed($_POST['url'] ?? '');
        if ($url === '') {
            throw new ValidationError(['url' => 'URL is required.']);
        }
        $preview = Ssrf::fetchPreview($url);
        Logger::log('url_preview', ['outcome' => 'allowed', 'url_host' => parse_url($url, PHP_URL_HOST) ?? '']);
    } catch (SsrfError $e) {
        $error = $e->getMessage();
        Logger::log('url_preview', ['outcome' => 'blocked', 'url_host' => parse_url($url, PHP_URL_HOST) ?? ''], 'warning');
    } catch (ValidationError $e) {
        $error = reset($e->errors);
    }
}

$pageTitle = 'Import Preview';
require __DIR__ . '/../views/header.php';
?>
<div class="page-head">
  <div>
    <h1><span class="marker"></span>URL Preview / Import</h1>
    <p>Fetch a preview from the allowed course-catalogue service only. Loopback,
    private and cloud-metadata addresses are blocked by the SSRF guard.</p>
  </div>
</div>

<?php if ($error): ?>
  <div class="alert alert-error"><?= icon('alert', 16) ?><span><?= e($error) ?></span></div>
<?php endif; ?>

<div class="card">
  <h2><?= icon('globe', 17) ?> Fetch a preview</h2>
  <form method="post" action="/import_preview.php">
    <?= Csrf::hiddenField() ?>
    <label for="url">Destination URL <span class="req">*</span></label>
    <input type="url" id="url" name="url" required
           placeholder="http://catalog.ftminna.internal/"
           value="<?= e($url) ?>">
    <p class="readonly-note">Only <span class="mono">catalog.ftminna.internal</span>
    is on the allowlist; responses are rendered as plain text.</p>
    <div class="form-actions">
      <button type="submit"><?= icon('globe', 16) ?> Preview</button>
    </div>
  </form>
</div>

<?php if ($preview !== null): ?>
<div class="card">
  <h2><?= icon('monitor', 17) ?> Preview result</h2>
  <pre class="preview-pane"><?= e($preview) ?></pre>
  <p class="preview-meta">Rendered as text only — fetched content is never
  interpreted as HTML or JavaScript.</p>
</div>
<?php endif; ?>
<?php require __DIR__ . '/../views/footer.php'; ?>
