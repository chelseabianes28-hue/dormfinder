<?php
require_once __DIR__ . '/includes/auth.php';
require_login();

$user = current_user();
$role = $user['role'];

function scalar(PDO $pdo, string $sql, array $params = []): int
{
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    return (int) $stmt->fetchColumn();
}

$s = [];
try {
    $pdo = getDB();
    $uid = [$user['id']];
    if ($role === 'student') {
        $s['favorites']     = scalar($pdo, 'SELECT COUNT(*) FROM favorites WHERE user_id = ?', $uid);
        $s['inq_total']     = scalar($pdo, 'SELECT COUNT(*) FROM inquiries WHERE student_id = ?', $uid);
        $s['inq_pending']   = scalar($pdo, "SELECT COUNT(*) FROM inquiries WHERE student_id = ? AND status = 'pending'", $uid);
        $s['inq_accepted']  = scalar($pdo, "SELECT COUNT(*) FROM inquiries WHERE student_id = ? AND status = 'accepted'", $uid);
    } elseif ($role === 'owner') {
        $s['l_total']       = scalar($pdo, 'SELECT COUNT(*) FROM listings WHERE owner_id = ?', $uid);
        $s['l_approved']    = scalar($pdo, "SELECT COUNT(*) FROM listings WHERE owner_id = ? AND status = 'approved'", $uid);
        $s['l_pending']     = scalar($pdo, "SELECT COUNT(*) FROM listings WHERE owner_id = ? AND status = 'pending'", $uid);
        $s['inq_total']     = scalar($pdo, 'SELECT COUNT(*) FROM inquiries i JOIN listings l ON l.id = i.listing_id WHERE l.owner_id = ?', $uid);
        $s['inq_pending']   = scalar($pdo, "SELECT COUNT(*) FROM inquiries i JOIN listings l ON l.id = i.listing_id WHERE l.owner_id = ? AND i.status = 'pending'", $uid);
    } else {
        $s['users']         = scalar($pdo, 'SELECT COUNT(*) FROM users');
        $s['l_total']       = scalar($pdo, 'SELECT COUNT(*) FROM listings');
        $s['l_pending']     = scalar($pdo, "SELECT COUNT(*) FROM listings WHERE status = 'pending'");
        $s['inq_total']     = scalar($pdo, 'SELECT COUNT(*) FROM inquiries');
    }
} catch (PDOException $ex) {
    error_log('Dashboard stats failed: ' . $ex->getMessage());
}

function stat_card(string $label, $value, string $icon): void
{
    echo '<div class="col-6 col-lg-3"><div class="card shadow-sm h-100"><div class="card-body">'
       . '<i class="bi ' . e($icon) . ' fs-4 text-body-secondary" aria-hidden="true"></i>'
       . '<div class="display-6 fw-semibold">' . (int) $value . '</div>'
       . '<div class="text-body-secondary small">' . e($label) . '</div></div></div></div>';
}

$roleLabels = ['student' => 'Student', 'owner' => 'Owner', 'admin' => 'Administrator'];
$hasCharts  = in_array($role, ['admin', 'owner'], true);
$pageTitle  = 'Dashboard';
if ($hasCharts) {
    $pageScripts = ['https://cdn.jsdelivr.net/npm/chart.js@4.4.3/dist/chart.umd.js', 'assets/js/dashboard.js'];
}
require_once __DIR__ . '/includes/header.php';
?>

<div class="container py-5">
  <h1 class="h2">Hello, <?= e($user['name']) ?></h1>
  <p class="text-body-secondary mb-4">
    You are logged in as <span class="badge text-bg-primary"><?= e($roleLabels[$role] ?? $role) ?></span>
  </p>

  <?php if ($role === 'student'): ?>
    <div class="row g-3 mb-4">
      <?php stat_card('Saved listings', $s['favorites'] ?? 0, 'bi-heart'); ?>
      <?php stat_card('Inquiries sent', $s['inq_total'] ?? 0, 'bi-envelope'); ?>
      <?php stat_card('Waiting for reply', $s['inq_pending'] ?? 0, 'bi-hourglass-split'); ?>
      <?php stat_card('Accepted', $s['inq_accepted'] ?? 0, 'bi-check-circle'); ?>
    </div>
    <div class="d-flex flex-wrap gap-2">
      <a class="btn btn-primary" href="<?= url('listings.php') ?>">Find a dorm</a>
      <a class="btn btn-outline-secondary" href="<?= url('favorites.php') ?>">My favorites</a>
      <a class="btn btn-outline-secondary" href="<?= url('inquiries.php') ?>">My inquiries</a>
    </div>

  <?php elseif ($role === 'owner'): ?>
    <div class="row g-3 mb-4">
      <?php stat_card('Total listings', $s['l_total'] ?? 0, 'bi-houses'); ?>
      <?php stat_card('Approved', $s['l_approved'] ?? 0, 'bi-check-circle'); ?>
      <?php stat_card('Pending approval', $s['l_pending'] ?? 0, 'bi-hourglass-split'); ?>
      <?php stat_card('New inquiries', $s['inq_pending'] ?? 0, 'bi-envelope'); ?>
    </div>
    <div class="d-flex flex-wrap gap-2 mb-4">
      <a class="btn btn-primary" href="<?= url('owner/listing_form.php') ?>"><i class="bi bi-plus-lg" aria-hidden="true"></i> Post new</a>
      <a class="btn btn-outline-secondary" href="<?= url('owner/my-listings.php') ?>">My listings</a>
      <a class="btn btn-outline-secondary" href="<?= url('inquiries.php') ?>">Inquiries</a>
    </div>
    <div class="row g-3" id="dashboardCharts" data-endpoint="<?= e(url('api/stats.php')) ?>">
      <div class="col-lg-5"><div class="card shadow-sm h-100"><div class="card-body">
        <h2 class="h6">My listings by status</h2>
        <canvas id="chartStatus" role="img" aria-label="Chart of my listings by status" height="220"></canvas>
      </div></div></div>
      <div class="col-lg-7"><div class="card shadow-sm h-100"><div class="card-body">
        <h2 class="h6">Inquiries per listing</h2>
        <canvas id="chartInquiries" role="img" aria-label="Chart of inquiries per listing" height="220"></canvas>
      </div></div></div>
    </div>

  <?php else: ?>
    <div class="row g-3 mb-4">
      <?php stat_card('Users', $s['users'] ?? 0, 'bi-people'); ?>
      <?php stat_card('Listings', $s['l_total'] ?? 0, 'bi-houses'); ?>
      <?php stat_card('Pending listings', $s['l_pending'] ?? 0, 'bi-hourglass-split'); ?>
      <?php stat_card('Inquiries', $s['inq_total'] ?? 0, 'bi-envelope'); ?>
    </div>
    <div class="d-flex flex-wrap gap-2 mb-4">
      <a class="btn btn-primary" href="<?= url('admin/listings.php') ?>">Approve or decline listings</a>
      <a class="btn btn-outline-secondary" href="<?= url('admin/users.php') ?>">Manage users</a>
    </div>
    <div class="row g-3" id="dashboardCharts" data-endpoint="<?= e(url('api/stats.php')) ?>">
      <div class="col-md-6 col-lg-4"><div class="card shadow-sm h-100"><div class="card-body">
        <h2 class="h6">Listings by status</h2>
        <canvas id="chartStatus" role="img" aria-label="Chart of listings by status" height="220"></canvas>
      </div></div></div>
      <div class="col-md-6 col-lg-4"><div class="card shadow-sm h-100"><div class="card-body">
        <h2 class="h6">Users by role</h2>
        <canvas id="chartRoles" role="img" aria-label="Chart of users by role" height="220"></canvas>
      </div></div></div>
      <div class="col-lg-4"><div class="card shadow-sm h-100"><div class="card-body">
        <h2 class="h6">Top cities (approved listings)</h2>
        <canvas id="chartCities" role="img" aria-label="Chart of approved listings by city" height="220"></canvas>
      </div></div></div>
    </div>
  <?php endif; ?>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
