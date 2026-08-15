<?php
require __DIR__ . '/../src/config.php';

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = $_POST['email'] ?? '';
    $pass  = $_POST['password'] ?? '';

    // VULN: raw input concatenated into SQL - classic SQL injection.
    // The first row of the result is trusted, so payloads like
    // "email=' OR '1'='1' -- " authenticate as the first user (admin).
    $sql = "SELECT * FROM users WHERE email = '$email' AND password_hash = '$pass'";
    $result = mysqli_query($conn, $sql);

    if ($result && ($row = mysqli_fetch_assoc($result))) {
        $_SESSION['user_id'] = $row['id'];   // VULN: no session-ID regeneration
        $_SESSION['role'] = $row['role'];
        header('Location: index.php');
        exit;
    }
    $error = 'Login failed. ' . mysqli_error($conn); // VULN: leaks DB details
}

$pageTitle = 'Login';
$user = null;
require __DIR__ . '/includes/header.php';
?>
<div class="login-shell">
  <aside class="login-aside">
    <h2>Starter build</h2>
    <p>Baseline version used to demonstrate the security assessment. Login is
    intentionally weak &mdash; try the documented account or the classic SQLi
    payload on the email field.</p>
    <ul>
      <li>Plaintext password storage in the database</li>
      <li>Raw SQL concatenation (SQL injection)</li>
      <li>No CSRF tokens, no rate limiting, no lockout</li>
      <li>Unescaped output (XSS), unsafe uploads, unguarded preview URL</li>
    </ul>
  </aside>
  <div class="login-form">
    <h1>Sign in</h1>
    <p class="muted">Fictitious demo account: <code>admin@ftminna.edu.ng / admin</code></p>

    <?php if ($error): ?><div class="msg-err"><?= $error ?></div><?php endif; ?>

    <form method="post" action="login.php">
      <label>Email</label>
      <input type="text" name="email" value="<?= $_POST['email'] ?? '' ?>">
      <label>Password</label>
      <input type="text" name="password">
      <button type="submit" class="btn">Sign in</button>
    </form>
  </div>
</div>
<?php require __DIR__ . '/includes/footer.php'; ?>
