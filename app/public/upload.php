<?php
declare(strict_types=1);
require __DIR__ . '/../src/bootstrap.php';

$user = Auth::requireLogin();
$errors = [];
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        Csrf::verify();

        $allowedMime = [
            'application/pdf'                    => ['pdf'],
            'application/msword'                 => ['doc'],
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document' => ['docx'],
            'image/jpeg'                         => ['jpg', 'jpeg'],
            'image/png'                          => ['png'],
        ];
        $maxBytes = 2 * 1024 * 1024;

        $file = $_FILES['document'] ?? null;
        if (!$file || ($file['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
            throw new ValidationError(['document' => 'Please choose a file to upload.']);
        }
        if (($file['error'] ?? UPLOAD_ERR_OK) !== UPLOAD_ERR_OK) {
            throw new ValidationError(['document' => 'Upload failed (error code ' . (int) $file['error'] . ').']);
        }
        if (!is_uploaded_file($file['tmp_name'])) {
            throw new ValidationError(['document' => 'Invalid upload source.']);
        }
        if ($file['size'] > $maxBytes) {
            throw new ValidationError(['document' => 'File is larger than 2 MB.']);
        }

        $finfo = new finfo(FILEINFO_MIME_TYPE);
        $mime = (string) $finfo->file($file['tmp_name']);
        if (!isset($allowedMime[$mime])) {
            throw new ValidationError(['document' => 'File type is not allowed.']);
        }
        $ext = strtolower(pathinfo((string) $file['name'], PATHINFO_EXTENSION));
        if (!in_array($ext, $allowedMime[$mime], true)) {
            throw new ValidationError(['document' => 'File extension does not match its content.']);
        }

        // Store under a random name OUTSIDE the web root (never served
        // directly by Apache).
        $stored = bin2hex(random_bytes(16)) . '.' . $ext;
        if (!is_dir(STORAGE_DIR)) {
            mkdir(STORAGE_DIR, 0750, true);
        }
        if (!move_uploaded_file($file['tmp_name'], STORAGE_DIR . '/' . $stored)) {
            throw new RuntimeException('Could not persist the upload.');
        }

        Database::run(
            'INSERT INTO documents (user_id, original_name, stored_name, mime, size_bytes)
             VALUES (?, ?, ?, ?, ?)',
            [$user['id'], $file['name'], $stored, $mime, (int) $file['size']]
        );
        $success = 'Document uploaded successfully.';
        Logger::log('document_upload', [
            'outcome' => 'accepted',
            'file_name' => $file['name'],
            'file_size' => (string) $file['size'],
        ]);
    } catch (ValidationError $e) {
        $errors = $e->errors;
    }
}

$docs = Database::run(
    'SELECT * FROM documents WHERE user_id = ? ORDER BY uploaded_at DESC',
    [$user['id']]
)->fetchAll();

$pageTitle = 'My Documents';
require __DIR__ . '/../views/header.php';
?>
<div class="page-head">
  <div>
    <h1><span class="marker"></span>Document Upload</h1>
    <p>Allowed: PDF, DOC, DOCX, JPEG, PNG (max 2 MB). Files are stored outside
    the web root with random names.</p>
  </div>
</div>

<?php if ($success): ?>
  <div class="alert alert-success"><?= icon('check', 16) ?><span><?= e($success) ?></span></div>
<?php endif; ?>
<?php if (!empty($errors)): ?>
  <div class="alert alert-error"><?= icon('alert', 16) ?><span><?= e(reset($errors)) ?></span></div>
<?php endif; ?>

<div class="card">
  <h2><?= icon('upload', 17) ?> Upload a document</h2>
  <form method="post" action="/upload.php" enctype="multipart/form-data">
    <?= Csrf::hiddenField() ?>
    <div class="dropzone" id="dropzone">
      <?= icon('upload', 30) ?>
      <p><strong>Drop your file here</strong> or choose one below</p>
      <p class="or">PDF &middot; DOC &middot; DOCX &middot; JPG &middot; PNG &mdash; up to 2 MB</p>
      <input type="file" id="document" name="document" accept=".pdf,.doc,.docx,.jpg,.jpeg,.png" style="display:none">
    </div>
    <div class="form-actions">
      <button type="button" class="secondary" id="pickBtn"><?= icon('file', 16) ?> Choose file</button>
      <span class="muted" id="fileName" style="font-size:.84rem"></span>
      <button type="submit"><?= icon('upload', 16) ?> Upload</button>
    </div>
  </form>
</div>

<div class="card">
  <h2><?= icon('file', 17) ?> Uploaded documents</h2>
  <?php if (!$docs): ?>
    <div class="empty">
      <?= icon('file', 34) ?>
      <p>No documents uploaded yet.</p>
    </div>
  <?php else: ?>
  <div class="table-wrap">
  <table>
    <thead>
      <tr><th>File</th><th>Type</th><th>Size</th><th>Uploaded</th><th></th></tr>
    </thead>
    <tbody>
    <?php foreach ($docs as $doc): ?>
    <tr>
      <td class="strong"><?= icon('file', 15) ?> <?= e($doc['original_name']) ?></td>
      <td><span class="badge slate"><?= e($doc['mime']) ?></span></td>
      <td><?= e(number_format((int) $doc['size_bytes'])) ?> B</td>
      <td><?= e($doc['uploaded_at']) ?></td>
      <td><a class="btn ghost small" href="/download.php?id=<?= (int) $doc['id'] ?>"><?= icon('download', 14) ?> Download</a></td>
    </tr>
    <?php endforeach; ?>
    </tbody>
  </table>
  </div>
  <?php endif; ?>
</div>

<script src="/assets/upload.js"></script>
<?php require __DIR__ . '/../views/footer.php'; ?>
