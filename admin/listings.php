<?php
require_once __DIR__ . '/../includes/auth.php';
require_role('admin');

$filter = in_array($_GET['status'] ?? '', ['pending', 'approved', 'rejected', 'all'], true) ? $_GET['status'] : 'pending';
$statusInfo = ['pending' => ['Pending', 'warning'], 'approved' => ['Approved', 'success'], 'rejected' => ['Rejected', 'danger']];

$counts = ['pending' => 0, 'approved' => 0, 'rejected' => 0];
$listings = [];
try {
    $pdo = getDB();
    foreach ($pdo->query('SELECT status, COUNT(*) FROM listings GROUP BY status')->fetchAll(PDO::FETCH_NUM) as [$st, $n]) {
        $counts[$st] = (int) $n;
    }
    $sql = "SELECT l.id, l.title, l.address, l.city, l.price_per_month, l.status, l.created_at, u.full_name AS owner_name,
                   (SELECT p.file_path FROM listing_photos p WHERE p.listing_id = l.id ORDER BY p.is_primary DESC, p.id ASC LIMIT 1) AS photo
              FROM listings l JOIN users u ON u.id = l.owner_id"
         . ($filter === 'all' ? '' : ' WHERE l.status = ?') . ' ORDER BY l.created_at DESC';
    $stmt = $pdo->prepare($sql);
    $stmt->execute($filter === 'all' ? [] : [$filter]);
    $listings = $stmt->fetchAll();
} catch (PDOException $ex) {
    error_log('Admin listings failed: ' . $ex->getMessage());
}

$pageTitle   = 'Review listings';
$pageScripts = ['assets/js/admin.js'];
require_once __DIR__ . '/../includes/header.php';
?>

<div class="container py-5" id="adminPage" data-csrf="<?= e(csrf_token()) ?>" data-endpoint="<?= e(url('api/admin_action.php')) ?>"
     data-remove-on-change="<?= $filter === 'all' ? '0' : '1' ?>">
  <a class="text-decoration-none" href="<?= url('dashboard.php') ?>"><i class="bi bi-arrow-left" aria-hidden="true"></i> Dashboard</a>
  <h1 class="h2 mt-2">Review listings</h1>
  <p class="text-body-secondary">Approve or decline what owners post. Rejecting an approved listing removes it from the site.</p>
  <div id="pageAlert" aria-live="polite"></div>

  <div class="d-flex flex-wrap gap-2 mb-4">
    <?php foreach (['pending' => 'Pending', 'approved' => 'Approved', 'rejected' => 'Rejected', 'all' => 'All'] as $key => $label): ?>
      <a class="chip<?= $filter === $key ? ' active' : '' ?>" href="?status=<?= e($key) ?>">
        <?= e($label) ?><?= $key !== 'all' ? ' (' . $counts[$key] . ')' : '' ?>
      </a>
    <?php endforeach; ?>
  </div>

  <?php if (!$listings): ?>
    <div class="card shadow-sm"><div class="card-body text-center py-5 text-body-secondary">No listings in this group.</div></div>
  <?php else: ?>
    <div class="vstack gap-3">
      <?php foreach ($listings as $l): ?>
        <?php [$statusText, $statusColor] = $statusInfo[$l['status']]; ?>
        <article class="card shadow-sm" data-row="<?= (int) $l['id'] ?>" data-title="<?= e($l['title']) ?>">
          <div class="card-body d-flex flex-wrap gap-3 align-items-center">
            <?php if ($l['photo']): ?>
              <img src="<?= e(url($l['photo'])) ?>" alt="" class="photo-thumb" style="width: 110px;">
            <?php else: ?>
              <div class="listing-photo-placeholder rounded" style="width: 110px; font-size: 1.5rem;"><i class="bi bi-image" aria-hidden="true"></i></div>
            <?php endif; ?>
            <div class="flex-grow-1">
              <h2 class="h6 mb-1"><a class="text-decoration-none" href="<?= url('listing.php?id=' . (int) $l['id']) ?>"><?= e($l['title']) ?></a>
                <span class="badge text-bg-<?= e($statusColor) ?> ms-1 row-status"><?= e($statusText) ?></span></h2>
              <p class="small text-body-secondary mb-0">
                <?= e($l['address']) ?>, <?= e($l['city']) ?> &middot; &#8369;<?= number_format((float) $l['price_per_month'], 2) ?>/month<br>
                By <?= e($l['owner_name']) ?> &middot; posted <?= e(date('M j, Y', strtotime($l['created_at']))) ?>
              </p>
            </div>
            <div class="d-flex gap-2">
              <button type="button" class="btn btn-primary btn-sm admin-act<?= $l['status'] === 'approved' ? ' d-none' : '' ?>"
                      data-action="approve_listing" data-id="<?= (int) $l['id'] ?>">Approve</button>
              <button type="button" class="btn btn-outline-danger btn-sm admin-act<?= $l['status'] === 'rejected' ? ' d-none' : '' ?>"
                      data-action="reject_listing" data-id="<?= (int) $l['id'] ?>">Reject</button>
            </div>
          </div>
        </article>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
