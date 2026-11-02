<?php
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/avatars.php';
require_login();

$sessionUser   = current_user();
$pdo           = getDB();
$errors        = [];
$avatarEnabled = true;

try {
    $stmt = $pdo->prepare('SELECT full_name, email, phone, role, avatar, created_at FROM users WHERE id = ?');
    $stmt->execute([$sessionUser['id']]);
    $account = $stmt->fetch();
} catch (PDOException $ex) {
    // The avatar column is missing: run database/add_avatar_column.sql.
    error_log('Profile query failed: ' . $ex->getMessage());
    $avatarEnabled = false;
    $stmt = $pdo->prepare('SELECT full_name, email, phone, role, created_at FROM users WHERE id = ?');
    $stmt->execute([$sessionUser['id']]);
    $account = $stmt->fetch();
    if ($account) {
        $account['avatar'] = null;
    }
}
if (!$account) {
    redirect('logout.php');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf()) {
        $errors[] = (empty($_POST) && !empty($_SERVER['CONTENT_LENGTH']))
            ? 'That photo is too large. The maximum is 2 MB.'
            : 'Your session expired. Please try again.';
    } else {
        $action = $_POST['action'] ?? 'profile';

        if ($action === 'avatar' && $avatarEnabled) {
            $newPath = null;
            try {
                $newPath = process_avatar_upload($_FILES['avatar'] ?? []);
                $pdo->prepare('UPDATE users SET avatar = ? WHERE id = ?')->execute([$newPath, $sessionUser['id']]);
                if ($account['avatar']) {
                    delete_avatar_file($account['avatar']);
                }
                set_flash('success', 'Profile photo updated.');
                redirect('profile.php');
            } catch (RuntimeException $ex) {
                $errors[] = $ex->getMessage();
            } catch (PDOException $ex) {
                if ($newPath) {
                    delete_avatar_file($newPath);
                }
                error_log('Avatar save failed: ' . $ex->getMessage());
                $errors[] = 'Something went wrong. Please try again.';
            }
        } elseif ($action === 'remove_avatar' && $avatarEnabled) {
            try {
                if ($account['avatar']) {
                    $pdo->prepare('UPDATE users SET avatar = NULL WHERE id = ?')->execute([$sessionUser['id']]);
                    delete_avatar_file($account['avatar']);
                }
                set_flash('success', 'Profile photo removed.');
                redirect('profile.php');
            } catch (PDOException $ex) {
                error_log('Avatar remove failed: ' . $ex->getMessage());
                $errors[] = 'Something went wrong. Please try again.';
            }
        } else {
            $fullName   = trim($_POST['full_name'] ?? '');
            $phone      = trim($_POST['phone'] ?? '');
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
}

$roleLabels  = ['student' => 'Student', 'owner' => 'Owner', 'admin' => 'Administrator'];
$pageTitle   = 'Profile';
$pageScripts = ['assets/js/profile.js'];
require_once __DIR__ . '/includes/header.php';
?>

<div class="container py-5">
  <?php if ($errors): ?>
    <div class="alert alert-danger" role="alert"><ul class="mb-0">
      <?php foreach ($errors as $error): ?><li><?= e($error) ?></li><?php endforeach; ?>
    </ul></div>
  <?php endif; ?>
  <?php if (!$avatarEnabled): ?>
    <div class="alert alert-warning">Profile photos need a database update. Run <code>database/add_avatar_column.sql</code> in phpMyAdmin.</div>
  <?php endif; ?>

  <div class="row g-5 align-items-start">
    <div class="col-md-4 text-center">
      <div id="avatarBox" class="mb-3">
        <?php if ($account['avatar']): ?>
          <img id="avatarImg" src="<?= e(url($account['avatar'])) ?>" alt="Profile photo of <?= e($account['full_name']) ?>"
               class="rounded-circle border" width="160" height="160" style="object-fit: cover;">
        <?php else: ?>
          <i id="avatarIcon" class="bi bi-person-circle" style="font-size: 10rem; line-height: 1;" aria-hidden="true"></i>
        <?php endif; ?>
      </div>

      <?php if ($avatarEnabled): ?>
        <form method="post" enctype="multipart/form-data" action="<?= url('profile.php') ?>" id="avatarForm">
          <?= csrf_field() ?>
          <input type="hidden" name="action" value="avatar">
          <label for="avatar" class="form-label small text-body-secondary">JPG, PNG, or WebP, up to 2 MB</label>
          <input type="file" class="form-control form-control-sm mb-2" id="avatar" name="avatar" accept="image/jpeg,image/png,image/webp">
          <div class="small text-danger mb-2" id="avatarError" role="alert"></div>
          <button type="submit" class="btn btn-primary btn-sm">Save photo</button>
        </form>
        <?php if ($account['avatar']): ?>
          <form method="post" action="<?= url('profile.php') ?>" class="mt-2">
            <?= csrf_field() ?>
            <input type="hidden" name="action" value="remove_avatar">
            <button type="submit" class="btn btn-link btn-sm text-danger">Remove photo</button>
          </form>
        <?php endif; ?>
      <?php endif; ?>
    </div>

    <div class="col-md-8">
      <span class="badge text-bg-primary mb-2"><?= e($roleLabels[$account['role']] ?? $account['role']) ?></span>
      <h1 class="h2"><?= e($account['full_name']) ?></h1>
      <p class="text-body-secondary mb-1"><i class="bi bi-envelope" aria-hidden="true"></i> <?= e($account['email']) ?></p>
      <p class="text-body-secondary mb-1"><i class="bi bi-telephone" aria-hidden="true"></i> <?= $account['phone'] ? e($account['phone']) : 'No phone number yet' ?></p>
      <p class="text-body-secondary mb-4"><i class="bi bi-calendar3" aria-hidden="true"></i> Member since <?= e(date('F Y', strtotime($account['created_at']))) ?></p>

      <div class="card shadow-sm"><div class="card-body p-4">
        <h2 class="h5 mb-3">Edit profile</h2>
        <form method="post" action="<?= url('profile.php') ?>">
          <?= csrf_field() ?>
          <input type="hidden" name="action" value="profile">
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
