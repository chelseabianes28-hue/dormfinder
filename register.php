<?php
require_once __DIR__ . '/includes/auth.php';

if (is_logged_in()) {
    redirect('dashboard.php');
}

$errors = [];
$old = ['full_name' => '', 'email' => '', 'phone' => '', 'role' => 'student'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf()) {
        $errors[] = 'Your session expired. Please try again.';
    } else {
        $old = [
            'full_name' => trim($_POST['full_name'] ?? ''),
            'email'     => strtolower(trim($_POST['email'] ?? '')),
            'phone'     => trim($_POST['phone'] ?? ''),
            'role'      => $_POST['role'] ?? 'student',
        ];
        $password = $_POST['password'] ?? '';
        $confirm  = $_POST['confirm_password'] ?? '';

        // Server-side validation (never trust the browser alone)
        $nameLength = mb_strlen($old['full_name']);
        if ($nameLength < 2 || $nameLength > 100) {
            $errors[] = 'Please enter your full name (2 to 100 characters).';
        }
        if (!filter_var($old['email'], FILTER_VALIDATE_EMAIL) || strlen($old['email']) > 150) {
            $errors[] = 'Please enter a valid email address.';
        }
        if ($old['phone'] !== '' && !preg_match('/^[0-9+\-\s()]{7,20}$/', $old['phone'])) {
            $errors[] = 'Please enter a valid phone number or leave it blank.';
        }
        if (!in_array($old['role'], ['student', 'owner'], true)) {
            $errors[] = 'Please choose whether you are a student or an owner.';
        }
        if (strlen($password) < 8 || !preg_match('/[A-Za-z]/', $password) || !preg_match('/[0-9]/', $password)) {
            $errors[] = 'Password must be at least 8 characters and include a letter and a number.';
        }
        if ($password !== $confirm) {
            $errors[] = 'Passwords do not match.';
        }

        if (!$errors) {
            try {
                $pdo = getDB();
                $check = $pdo->prepare('SELECT id FROM users WHERE email = ?');
                $check->execute([$old['email']]);

                if ($check->fetch()) {
                    $errors[] = 'That email is already registered. Try logging in instead.';
                } else {
                    $insert = $pdo->prepare(
                        'INSERT INTO users (full_name, email, password_hash, role, phone) VALUES (?, ?, ?, ?, ?)'
                    );
                    $insert->execute([
                        $old['full_name'],
                        $old['email'],
                        password_hash($password, PASSWORD_DEFAULT),
                        $old['role'],
                        $old['phone'] !== '' ? $old['phone'] : null,
                    ]);
                    set_flash('success', 'Account created! You can log in now.');
                    redirect('login.php');
                }
            } catch (PDOException $ex) {
                error_log('Register failed: ' . $ex->getMessage());
                $errors[] = 'Something went wrong on our side. Please try again.';
            }
        }
    }
}

$pageTitle   = 'Sign up';
$pageScripts = ['assets/js/auth.js'];
require_once __DIR__ . '/includes/header.php';
?>

<div class="container py-5">
  <div class="row justify-content-center">
    <div class="col-md-8 col-lg-6">
      <div class="card shadow-sm">
        <div class="card-body p-4">
          <h1 class="h3 mb-1">Create your account</h1>
          <p class="text-body-secondary">Join DormFinder to search or post dorms and bedspaces.</p>

          <?php if ($errors): ?>
            <div class="alert alert-danger" role="alert">
              <ul class="mb-0">
                <?php foreach ($errors as $error): ?>
                  <li><?= e($error) ?></li>
                <?php endforeach; ?>
              </ul>
            </div>
          <?php endif; ?>

          <form method="post" action="<?= url('register.php') ?>">
            <?= csrf_field() ?>

            <fieldset class="mb-3">
              <legend class="form-label fs-6">I am a</legend>
              <div class="form-check">
                <input class="form-check-input" type="radio" name="role" id="roleStudent" value="student"
                       <?= $old['role'] === 'student' ? 'checked' : '' ?>>
                <label class="form-check-label" for="roleStudent">Student looking for a place</label>
              </div>
              <div class="form-check">
                <input class="form-check-input" type="radio" name="role" id="roleOwner" value="owner"
                       <?= $old['role'] === 'owner' ? 'checked' : '' ?>>
                <label class="form-check-label" for="roleOwner">Owner posting a dorm or bedspace</label>
              </div>
            </fieldset>

            <div class="mb-3">
              <label for="full_name" class="form-label">Full name</label>
              <input type="text" class="form-control" id="full_name" name="full_name" required
                     maxlength="100" autocomplete="name" value="<?= e($old['full_name']) ?>">
            </div>

            <div class="mb-3">
              <label for="email" class="form-label">Email</label>
              <input type="email" class="form-control" id="email" name="email" required
                     maxlength="150" autocomplete="email" value="<?= e($old['email']) ?>">
            </div>

            <div class="mb-3">
              <label for="phone" class="form-label">Phone <span class="text-body-secondary">(optional)</span></label>
              <input type="tel" class="form-control" id="phone" name="phone"
                     maxlength="20" autocomplete="tel" value="<?= e($old['phone']) ?>">
            </div>

            <div class="mb-3">
              <label for="password" class="form-label">Password</label>
              <div class="input-group">
                <input type="password" class="form-control" id="password" name="password" required
                       minlength="8" autocomplete="new-password" aria-describedby="passwordHelp">
                <button class="btn btn-outline-secondary" type="button" data-toggle-password="#password" aria-label="Show password">
                  <i class="bi bi-eye" aria-hidden="true"></i>
                </button>
              </div>
              <div class="progress mt-2" style="height: 6px;" role="progressbar" aria-label="Password strength"
                   aria-valuemin="0" aria-valuemax="100" aria-valuenow="0" id="strengthTrack">
                <div class="progress-bar" id="strengthBar" style="width: 0%"></div>
              </div>
              <div id="passwordHelp" class="form-text">
                At least 8 characters with a letter and a number. Strength: <span id="strengthText">Too weak</span>
              </div>
            </div>

            <div class="mb-4">
              <label for="confirm_password" class="form-label">Confirm password</label>
              <input type="password" class="form-control" id="confirm_password" name="confirm_password" required
                     minlength="8" autocomplete="new-password" aria-describedby="confirmHelp">
              <div id="confirmHelp" class="form-text" aria-live="polite"></div>
            </div>

            <button type="submit" class="btn btn-primary w-100">Create account</button>
          </form>

          <p class="text-center mt-3 mb-0">
            Already have an account? <a href="<?= url('login.php') ?>">Log in</a>
          </p>
        </div>
      </div>
    </div>
  </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
