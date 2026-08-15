<?php
require __DIR__ . '/../src/config.php';

$user = require_login();
$error = '';
$updated = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name  = $_POST['full_name'] ?? '';
    $phone = $_POST['phone'] ?? '';
    $email = $_POST['email'] ?? '';
    $id    = $user['id'];

    // VULN: concatenated UPDATE; also unsafe if run on GET-based input.
    $sql = "UPDATE users SET full_name = '$name', phone = '$phone', email = '$email' WHERE id = $id";
    mysqli_query($conn, $sql);
    if (mysqli_error($conn)) {
        $error = mysqli_error($conn);
    } else {
        $updated = 'Profile updated.';
        $user = current_user();
    }
}

$pageTitle = 'Profile';
require __DIR__ . '/includes/header.php';
?>
<div class="card">
  <h1>Profile</h1>
  <p class="muted">Matric: <?= $user['matric_no'] ?></p>

  <?php if ($updated): ?><div class="msg-ok"><?= $updated ?></div><?php endif; ?>
  <?php if ($error): ?><div class="msg-err"><?= $error ?></div><?php endif; ?>

  <!-- VULN: reflected user-controlled value echoed unescaped (XSS) -->
  <form method="post" action="profile.php">
    <label>Full name</label>
    <input type="text" name="full_name" value="<?= $_POST['full_name'] ?? $user['full_name'] ?>">
    <label>Email</label>
    <input type="text" name="email" value="<?= $_POST['email'] ?? $user['email'] ?>">
    <label>Phone</label>
    <input type="text" name="phone" value="<?= $_POST['phone'] ?? $user['phone'] ?>">
    <button type="submit">Save</button>
  </form>
</div>
<?php require __DIR__ . '/includes/footer.php'; ?>
