<?php
declare(strict_types=1);
require __DIR__ . '/../src/bootstrap.php';

$user = Auth::requireLogin();
$errors = [];
$updated = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        Csrf::verify();
        $fullName = Input::name($_POST['full_name'] ?? null);
        $phone    = Input::phone($_POST['phone'] ?? null);
        $email    = Input::email($_POST['email'] ?? null);

        // Update profile using a parameterized UPDATE - user data never
        // becomes part of the SQL text.
        Database::run(
            'UPDATE users SET full_name = ?, phone = ?, email = ? WHERE id = ?',
            [$fullName, $phone, $email, $user['id']]
        );
        $_SESSION['email'] = $email;
        $updated = true;
        $user = Auth::user();
    } catch (ValidationError $e) {
        $errors = $e->errors;
    }
}
$user = Auth::user();
$pageTitle = 'My Profile';
require __DIR__ . '/../views/header.php';
?>
<div class="page-head">
  <div>
    <h1><span class="marker"></span>My Profile</h1>
    <p>Your registration details. Your matric number is read-only.</p>
  </div>
</div>

<?php if ($updated): ?>
  <div class="alert alert-success"><?= icon('check', 16) ?><span>Profile updated successfully.</span></div>
<?php endif; ?>
<?php if (isset($errors['csrf_token'])): ?>
  <div class="alert alert-error"><?= icon('alert', 16) ?><span><?= e($errors['csrf_token']) ?></span></div>
<?php endif; ?>

<div class="stat-grid" style="grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));">
  <div class="stat" style="border:none;box-shadow:none;background:transparent">
    <div class="identity">
      <span class="avatar-lg"><?= e(initials((string) $user['full_name'])) ?></span>
      <div>
        <h2><?= e($user['full_name']) ?></h2>
        <div class="meta">
          <span><?= e($user['role'] === 'admin' ? 'Administrator' : 'Student') ?></span>
          <span>Matric <b><?= e($user['matric_no']) ?></b></span>
        </div>
      </div>
    </div>
  </div>
  <div class="stat" style="border:none;box-shadow:none;background:transparent">
    <div class="meta">
      <span style="font-size:.8rem">Email &middot; <b><?= e($user['email']) ?></b></span>
      <span style="font-size:.8rem">Phone &middot; <b><?= e($user['phone'] ?? '—') ?></b></span>
      <span style="font-size:.8rem">Member since &middot; <b><?= e($user['created_at'] ?? '—') ?></b></span>
    </div>
  </div>
</div>

<div class="card">
  <h2><?= icon('user', 17) ?> Edit profile</h2>
  <form method="post" action="/profile.php">
    <?= Csrf::hiddenField() ?>

    <div class="form-grid">
      <div>
        <label for="matric_no">Matric number <span class="req">*</span></label>
        <input type="text" id="matric_no" value="<?= e($user['matric_no']) ?>" disabled>
        <p class="readonly-note">Matric numbers cannot be changed.</p>
      </div>
      <div>
        <label for="full_name">Full name <span class="req">*</span></label>
        <input type="text" id="full_name" name="full_name" required maxlength="100"
               value="<?= e($user['full_name']) ?>">
        <?php if (isset($errors['full_name'])): ?><p class="field-error"><?= e($errors['full_name']) ?></p><?php endif; ?>
      </div>
      <div>
        <label for="email">Email address <span class="req">*</span></label>
        <input type="email" id="email" name="email" required maxlength="254"
               value="<?= e($user['email']) ?>">
        <?php if (isset($errors['email'])): ?><p class="field-error"><?= e($errors['email']) ?></p><?php endif; ?>
      </div>
      <div>
        <label for="phone">Phone number</label>
        <input type="tel" id="phone" name="phone" maxlength="20"
               value="<?= e($user['phone'] ?? '') ?>">
        <?php if (isset($errors['phone'])): ?><p class="field-error"><?= e($errors['phone']) ?></p><?php endif; ?>
      </div>
    </div>

    <div class="form-actions">
      <button type="submit"><?= icon('check', 16) ?> Save changes</button>
    </div>
  </form>
</div>
<?php require __DIR__ . '/../views/footer.php'; ?>
