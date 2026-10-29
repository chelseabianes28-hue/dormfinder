<?php
require_once __DIR__ . '/includes/auth.php';
require_login();

$sessionUser = current_user();
$pdo = getDB();
$errors = [];

$stmt = $pdo->prepare('SELECT full_name, email, phone, role, created_at FROM users WHERE id = ?');
$stmt->execute([$sessionUser['id']]);
$account = $stmt->fetch();
if (!$account) {
    redirect('logout.php');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf()) {
        $errors[] = 'Your session expired. Please try again.';
    } else {
        $fullName = trim($_POST['full_name'] ?? '');
        $phone    = trim($_POST['phone'] ?? '');
        $nameLength = mb_strlen($fullName);

        if ($nameLength < 2 || $nameLength > 100) {
            $errors[] = 'Please enter your full name (2 to 100 characters).';
        }
        if ($phone !== '' && !preg_match('/^[0-9+\-\s()]{7,20}$/', $phone)) {
            $errors[] = 'Please enter a valid phone number or leave it blank.';
        }
        if ($sessionUser['role'] === 'owner' && $phone === '') {
            $errors[] = 'Owners need a phone number so students can contact them.';
        }

        if (!$errors) {
            try {
                $pdo->prepare('UPDATE users SET full_name = ?, phone = ? WHERE id = ?')
                    ->execute([$fullName, $phone !== '' ? $phone : null, $sessionUser['id']]);
                $_SESSION['user']['name'] = $fullName;
                set_flash('success', 'Profile updated.');
                redirect('profile.php');
            } catch (PDOException $ex) {
                error_log('Profile update failed: ' . $ex->getMessage());
                $errors[] = 'Something went wrong. Please try again.';
            }
        }
        $account['full_name'] = $fullName;
        $account['phone']     = $phone;
    }
}

$roleLabels = ['student' => 'Student', 'owner' => 'Owner', 'admin' => 'Administrator'];
$pageTitle  = 'Profile';
require_once __DIR__ . '/includes/header.php';
?>

<div class="container py-5">
  <div class="row g-5 align-items-start">
    <div class="col-md-4 text-center">
      <i class="bi bi-person-circle" style="font-size: 8rem; line-height: 1;" aria-hidden="true"></i>
    </div>
    <div class="col-md-8">
      <span class="badge text-bg-primary mb-2"><?= e($roleLabels[$account['role']] ?? $account['role']) ?></span>
      <h1 class="h2"><?= e($account['full_name']) ?></h1>
      <p class="text-body-secondary mb-1"><i class="bi bi-envelope" aria-hidden="true"></i> <?= e($account['email']) ?></p>
      <p class="text-body-secondary mb-1"><i class="bi bi-telephone" aria-hidden="true"></i> <?= $account['phone'] ? e($account['phone']) : 'No phone number yet' ?></p>
      <p class="text-body-secondary mb-4"><i class="bi bi-calendar3" aria-hidden="true"></i> Member since <?= e(date('F Y', strtotime($account['created_at']))) ?></p>

      <div class="card shadow-sm"><div class="card-body p-4">
        <h2 class="h5 mb-3">Edit profile</h2>
        <?php if ($errors): ?>
          <div class="alert alert-danger" role="alert"><ul class="mb-0">
            <?php foreach ($errors as $error): ?><li><?= e($error) ?></li><?php endforeach; ?>
          </ul></div>
        <?php endif; ?>
        <form method="post" action="<?= url('profile.php') ?>">
          <?= csrf_field() ?>
          <div class="mb-3">
            <label for="full_name" class="form-label">Full name</label>
            <input type="text" class="form-control" id="full_name" name="full_name" required maxlength="100" value="<?= e($account['full_name']) ?>">
          </div>
          <div class="mb-3">
            <label for="email" class="form-label">Email</label>
            <input type="email" class="form-control" id="email" value="<?= e($account['email']) ?>" disabled aria-describedby="emailHelp">
            <div id="emailHelp" class="form-text">Your email is your login, so it cannot be changed here.</div>
          </div>
          <div class="mb-4">
            <label for="phone" class="form-label">Phone <?= $sessionUser['role'] === 'owner' ? '' : '<span class="text-body-secondary">(optional)</span>' ?></label>
            <input type="tel" class="form-control" id="phone" name="phone" maxlength="20" value="<?= e((string) $account['phone']) ?>">
          </div>
          <button type="submit" class="btn btn-primary">Save changes</button>
        </form>
      </div></div>
    </div>
  </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
