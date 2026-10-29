<?php
require_once __DIR__ . '/includes/auth.php';

if (is_logged_in()) {
    redirect('dashboard.php');
}

$errors = [];
$email  = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf()) {
        $errors[] = 'Your session expired. Please try again.';
    } else {
        $email    = strtolower(trim($_POST['email'] ?? ''));
        $password = $_POST['password'] ?? '';

        if ($email === '' || $password === '') {
            $errors[] = 'Please complete all required fields.';
        } else {
            try {
                $stmt = getDB()->prepare(
                    'SELECT id, full_name, password_hash, role, is_active FROM users WHERE email = ?'
                );
                $stmt->execute([$email]);
                $found = $stmt->fetch();

                // Same message for unknown email and wrong password, so attackers learn nothing.
                if (!$found || !password_verify($password, $found['password_hash'])) {
                    $errors[] = 'Invalid email or password.';
                } elseif (!(int) $found['is_active']) {
                    $errors[] = 'This account has been disabled. Please use the Contact link at the bottom of the page to reach the administrator.';
                } else {
                    session_regenerate_id(true);
                    $_SESSION['user'] = [
                        'id'   => (int) $found['id'],
                        'name' => $found['full_name'],
                        'role' => $found['role'],
                    ];
                    set_flash('success', 'Welcome back, ' . $found['full_name'] . '!');
                    redirect('dashboard.php');
                }
            } catch (PDOException $ex) {
                error_log('Login failed: ' . $ex->getMessage());
                $errors[] = 'Something went wrong on our side. Please try again.';
            }
        }
    }
}

$pageTitle   = 'Log in';
$pageScripts = ['assets/js/auth.js'];
require_once __DIR__ . '/includes/header.php';
?>

<div class="container py-5">
  <div class="row justify-content-center">
    <div class="col-md-8 col-lg-5">
      <div class="card shadow-sm">
        <div class="card-body p-4">
          <h1 class="h3 mb-1">Log in</h1>
          <p class="text-body-secondary">Welcome back to DormFinder.</p>

          <?php if ($errors): ?>
            <div class="alert alert-danger" role="alert">
              <ul class="mb-0">
                <?php foreach ($errors as $error): ?>
                  <li><?= e($error) ?></li>
                <?php endforeach; ?>
              </ul>
            </div>
          <?php endif; ?>

          <form method="post" action="<?= url('login.php') ?>">
            <?= csrf_field() ?>

            <div class="mb-3">
              <label for="email" class="form-label">Email</label>
              <input type="email" class="form-control" id="email" name="email" required
                     autocomplete="email" value="<?= e($email) ?>">
            </div>

            <div class="mb-4">
              <label for="password" class="form-label">Password</label>
              <div class="input-group">
                <input type="password" class="form-control" id="password" name="password" required
                       autocomplete="current-password">
                <button class="btn btn-outline-secondary" type="button" data-toggle-password="#password" aria-label="Show password">
                  <i class="bi bi-eye" aria-hidden="true"></i>
                </button>
              </div>
            </div>

            <button type="submit" class="btn btn-primary w-100">Log in</button>
          </form>

          <p class="text-center mt-3 mb-0">
            New here? <a href="<?= url('register.php') ?>">Create an account</a>
          </p>
        </div>
      </div>
    </div>
  </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
