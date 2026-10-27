<?php
require_once __DIR__ . '/includes/auth.php';
require_login();

$user = current_user();

// Owners see a quick summary of their listings.
$stats = ['total' => 0, 'approved' => 0, 'pending' => 0];
if ($user['role'] === 'owner') {
    try {
        $stmt = getDB()->prepare(
            "SELECT COUNT(*) AS total,
                    COALESCE(SUM(status = 'approved'), 0) AS approved,
                    COALESCE(SUM(status = 'pending'), 0) AS pending
               FROM listings WHERE owner_id = ?"
        );
        $stmt->execute([$user['id']]);
        $stats = array_map('intval', $stmt->fetch());
    } catch (PDOException $ex) {
        error_log('Dashboard stats failed: ' . $ex->getMessage());
    }
}

$pageTitle = 'Dashboard';
require_once __DIR__ . '/includes/header.php';

$roleLabels = ['student' => 'Student', 'owner' => 'Owner', 'admin' => 'Administrator'];
?>

<div class="container py-5">
  <h1 class="h3">Hello, <?= e($user['name']) ?></h1>
  <p class="text-body-secondary">
    You are logged in as <span class="badge text-bg-primary"><?= e($roleLabels[$user['role']] ?? $user['role']) ?></span>
  </p>

  <div class="row g-3 mt-2">
    <?php if ($user['role'] === 'student'): ?>
      <div class="col-md-6">
        <div class="card h-100 shadow-sm"><div class="card-body">
          <h2 class="h5">Saved listings</h2>
          <p class="text-body-secondary mb-0">Your favorite dorms will appear here.</p>
        </div></div>
      </div>
      <div class="col-md-6">
        <div class="card h-100 shadow-sm"><div class="card-body">
          <h2 class="h5">My inquiries</h2>
          <p class="text-body-secondary mb-0">Your messages to owners will appear here.</p>
        </div></div>
      </div>
    <?php elseif ($user['role'] === 'owner'): ?>
      <div class="col-md-6">
        <div class="card h-100 shadow-sm"><div class="card-body">
          <h2 class="h5">My listings</h2>
          <p class="text-body-secondary">
            <?= $stats['total'] ?> total &middot; <?= $stats['approved'] ?> approved &middot; <?= $stats['pending'] ?> pending approval
          </p>
          <div class="d-flex flex-wrap gap-2">
            <a class="btn btn-primary btn-sm" href="<?= url('owner/my-listings.php') ?>">Manage listings</a>
            <a class="btn btn-outline-secondary btn-sm" href="<?= url('owner/listing_form.php') ?>">Add listing</a>
          </div>
        </div></div>
      </div>
      <div class="col-md-6">
        <div class="card h-100 shadow-sm"><div class="card-body">
          <h2 class="h5">Inquiries</h2>
          <p class="text-body-secondary mb-0">Student requests will appear here.</p>
        </div></div>
      </div>
    <?php else: ?>
      <div class="col-md-6">
        <div class="card h-100 shadow-sm"><div class="card-body">
          <h2 class="h5">Pending listings</h2>
          <p class="text-body-secondary mb-0">Listings waiting for approval will appear here.</p>
        </div></div>
      </div>
      <div class="col-md-6">
        <div class="card h-100 shadow-sm"><div class="card-body">
          <h2 class="h5">Users</h2>
          <p class="text-body-secondary mb-0">User management will appear here.</p>
        </div></div>
      </div>
    <?php endif; ?>
  </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
