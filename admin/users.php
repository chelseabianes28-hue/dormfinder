<?php
require_once __DIR__ . '/../includes/auth.php';
require_role('admin');

$admin = current_user();
$q     = trim((string) ($_GET['q'] ?? ''));
$role  = in_array($_GET['role'] ?? '', ['student', 'owner', 'admin'], true) ? $_GET['role'] : '';

$users = [];
try {
    $where  = [];
    $params = [];
    if ($q !== '') {
        $like = '%' . addcslashes(mb_substr($q, 0, 100), '%_\\') . '%';
        $where[] = '(full_name LIKE ? OR email LIKE ?)';
        array_push($params, $like, $like);
    }
    if ($role !== '') {
        $where[] = 'role = ?';
        $params[] = $role;
    }
    $stmt = getDB()->prepare('SELECT id, full_name, email, role, phone, is_active, created_at FROM users'
        . ($where ? ' WHERE ' . implode(' AND ', $where) : '') . ' ORDER BY created_at DESC LIMIT 200');
    $stmt->execute($params);
    $users = $stmt->fetchAll();
} catch (PDOException $ex) {
    error_log('Admin users failed: ' . $ex->getMessage());
}

$roleLabels  = ['student' => 'Student', 'owner' => 'Owner', 'admin' => 'Admin'];
$pageTitle   = 'Manage users';
$pageScripts = ['assets/js/admin.js'];
require_once __DIR__ . '/../includes/header.php';
?>

<div class="container py-5" id="adminPage" data-csrf="<?= e(csrf_token()) ?>" data-endpoint="<?= e(url('api/admin_action.php')) ?>">
  <a class="text-decoration-none" href="<?= url('dashboard.php') ?>"><i class="bi bi-arrow-left" aria-hidden="true"></i> Dashboard</a>
  <h1 class="h2 mt-2">Manage users</h1>
  <p class="text-body-secondary">Disable an account to stop that person from logging in. You can enable it again anytime.</p>
  <div id="pageAlert" aria-live="polite"></div>

  <form class="row g-2 mb-4" method="get">
    <div class="col-md-6">
      <label for="q" class="visually-hidden">Search by name or email</label>
      <input type="search" class="form-control" id="q" name="q" placeholder="Search by name or email" maxlength="100" value="<?= e($q) ?>">
    </div>
    <div class="col-6 col-md-3">
      <label for="role" class="visually-hidden">Role</label>
      <select class="form-select" id="role" name="role">
        <option value="">All roles</option>
        <?php foreach ($roleLabels as $value => $label): ?>
          <option value="<?= e($value) ?>" <?= $role === $value ? 'selected' : '' ?>><?= e($label) ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <div class="col-6 col-md-3"><button class="btn btn-primary w-100" type="submit">Search</button></div>
  </form>

  <div class="card shadow-sm"><div class="table-responsive">
    <table class="table align-middle mb-0">
      <caption class="visually-hidden">List of users</caption>
      <thead><tr><th scope="col">Name</th><th scope="col">Role</th><th scope="col">Phone</th><th scope="col">Joined</th><th scope="col">Status</th><th scope="col"><span class="visually-hidden">Action</span></th></tr></thead>
      <tbody>
        <?php if (!$users): ?>
          <tr><td colspan="6" class="text-center text-body-secondary py-4">No users found.</td></tr>
        <?php endif; ?>
        <?php foreach ($users as $u): ?>
          <?php $locked = $u['role'] === 'admin' || (int) $u['id'] === (int) $admin['id']; ?>
          <tr data-row="<?= (int) $u['id'] ?>" data-name="<?= e($u['full_name']) ?>">
            <td><strong><?= e($u['full_name']) ?></strong><br><small class="text-body-secondary"><?= e($u['email']) ?></small></td>
            <td><span class="badge text-bg-light"><?= e($roleLabels[$u['role']] ?? $u['role']) ?></span></td>
            <td><?= $u['phone'] ? e($u['phone']) : '<span class="text-body-secondary">none</span>' ?></td>
            <td class="text-nowrap"><?= e(date('M j, Y', strtotime($u['created_at']))) ?></td>
            <td><span class="badge text-bg-<?= $u['is_active'] ? 'success' : 'secondary' ?> row-status"><?= $u['is_active'] ? 'Active' : 'Disabled' ?></span></td>
            <td class="text-end">
              <?php if (!$locked): ?>
                <button type="button" class="btn btn-sm <?= $u['is_active'] ? 'btn-outline-danger' : 'btn-outline-secondary' ?> admin-act"
                        data-action="<?= $u['is_active'] ? 'disable_user' : 'enable_user' ?>" data-id="<?= (int) $u['id'] ?>">
                  <?= $u['is_active'] ? 'Disable' : 'Enable' ?></button>
              <?php endif; ?>
            </td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div></div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
