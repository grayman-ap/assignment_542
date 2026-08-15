<?php
declare(strict_types=1);
require __DIR__ . '/../src/bootstrap.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        Csrf::verify();
        Auth::login(
            (string) ($_POST['email'] ?? ''),
            (string) ($_POST['password'] ?? '')
        );
        $next = (string) ($_POST['next'] ?? '/index.php');
        if (!str_starts_with($next, '/') || str_starts_with($next, '//')) {
            $next = '/index.php';
        }
        header('Location: ' . $next);
        exit;
    } catch (AuthException $e) {
        http_response_code($e->httpStatus);
        $error = $e->safeMessage;
    } catch (ValidationError $e) {
        http_response_code(422);
        $errors = $e->errors;
    }
}

$next = (string) ($_GET['next'] ?? '/index.php');
$user = null;
$pageTitle = 'Login';
require __DIR__ . '/../views/header.php';
?>
<div class="login-shell">
  <aside class="login-aside">
    <span class="mark-big"><?= icon('shield', 32) ?></span>
    <h2>Secure student registration, hardened by design.</h2>
    <p>This lab build demonstrates authentication, authorisation and web
    defence controls verified by an automated security test suite.</p>
    <ul>
      <li><?= icon('check', 15) ?><span>Argon2id password hashing &mdash; no plaintext or default credentials</span></li>
      <li><?= icon('check', 15) ?><span>Prepared statements block SQL injection on every query</span></li>
      <li><?= icon('check', 15) ?><span>Rate limiting and account lockout against brute force</span></li>
      <li><?= icon('check', 15) ?><span>CSRF tokens, CSP, output encoding and an SSRF allowlist</span></li>
    </ul>
    <div class="foot">Authorised-lab only &middot; fictitious data &middot; IFT 542</div>
  </aside>
  <div class="login-form">
    <h1>Sign in</h1>
    <p class="sub">Use your fictitious student or admin account.</p>

    <?php if (isset($error)): ?>
      <div class="alert alert-error"><?= icon('alert', 16) ?><span><?= e($error) ?></span></div>
    <?php endif; ?>
    <?php if (isset($errors['csrf_token'])): ?>
      <div class="alert alert-error"><?= icon('alert', 16) ?><span><?= e($errors['csrf_token']) ?></span></div>
    <?php endif; ?>

    <form method="post" action="/login.php">
      <?= Csrf::hiddenField() ?>
      <input type="hidden" name="next" value="<?= e($next) ?>">

      <label for="email">Email address</label>
      <input type="email" id="email" name="email" required maxlength="254"
             value="<?= e($_POST['email'] ?? '') ?>" autocomplete="username"
             placeholder="student@ftminna.edu.ng">
      <?php if (isset($errors['email'])): ?><p class="field-error"><?= e($errors['email']) ?></p><?php endif; ?>

      <label for="password">Password</label>
      <input type="password" id="password" name="password" required maxlength="128"
             autocomplete="current-password" placeholder="Your password">
      <?php if (isset($errors['password'])): ?><p class="field-error"><?= e($errors['password']) ?></p><?php endif; ?>

      <button type="submit"><?= icon('key', 16) ?> Sign in</button>
    </form>

    <p class="muted" style="font-size:.78rem;margin-top:16px">
      All accounts are fictitious and seeded by <span class="mono">make seed</span>.
      See the README for the generated passwords.
    </p>
  </div>
</div>
<?php require __DIR__ . '/../views/footer.php'; ?>
