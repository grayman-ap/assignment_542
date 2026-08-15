<?php
require __DIR__ . '/../src/config.php';

$user = require_login();
$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // VULN: no validation of type/size/name, stored with original name in a
    // location served directly by Apache (arbitrary file upload / RCE risk).
    if (isset($_FILES['document']) && $_FILES['document']['error'] === UPLOAD_ERR_OK) {
        $original = basename($_FILES['document']['name']);
        $dest = $UPLOAD_DIR . '/' . $original;
        if (move_uploaded_file($_FILES['document']['tmp_name'], $dest)) {
            $userId = (int) $user['id'];
            $mime = $_FILES['document']['type'];   // VULN: client-supplied MIME
            $size = (int) $_FILES['document']['size'];
            // VULN: concatenated SQL.
            mysqli_query($conn,
                "INSERT INTO documents (user_id, original_name, stored_name, mime, size_bytes)
                 VALUES ($userId, '$original', '$original', '$mime', $size)");
            $message = 'Uploaded ' . $original;
        } else {
            $message = 'Upload failed.';
        }
    }
}

$r = mysqli_query($conn, 'SELECT * FROM documents WHERE user_id = ' . (int) $user['id'] . ' ORDER BY uploaded_at DESC');
$pageTitle = 'Document Upload';
require __DIR__ . '/includes/header.php';
?>
<div class="card">
  <h1>Document Upload</h1>
  <p class="muted">No type, size or name validation. Stored with its original
  name in a web-served folder &mdash; arbitrary upload risk.</p>

  <?php if ($message): ?><div class="msg-ok"><?= $message ?></div><?php endif; ?>

  <form method="post" action="upload.php" enctype="multipart/form-data">
    <label>Document</label>
    <input type="file" name="document">
    <button type="submit">Upload</button>
  </form>
</div>

<div class="card">
  <h2>Uploaded documents</h2>
  <table>
    <tr><th>File</th><th>Type</th><th>Size</th></tr>
    <?php if ($r): while ($d = mysqli_fetch_assoc($r)): ?>
    <tr>
      <td><a href="../storage/documents/<?= $d['stored_name'] ?>"><?= $d['original_name'] ?></a></td>
      <td><?= $d['mime'] ?></td>
      <td><?= $d['size_bytes'] ?></td>
    </tr>
    <?php endwhile; else: ?>
    <tr><td colspan="3" class="muted">No documents uploaded yet.</td></tr>
    <?php endif; ?>
  </table>
</div>
<?php require __DIR__ . '/includes/footer.php'; ?>
