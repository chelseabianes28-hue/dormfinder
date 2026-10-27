<?php
require_once __DIR__ . '/../includes/auth.php';
require_role('owner');

$user = current_user();

$typeLabels = ['dormitory' => 'Dormitory', 'bedspace' => 'Bedspace', 'boarding_house' => 'Boarding house'];
$statusInfo = [
    'pending'  => ['Pending approval', 'warning'],
    'approved' => ['Approved', 'success'],
    'rejected' => ['Rejected', 'danger'],
];

$listings  = [];
$loadError = false;
try {
    $stmt = getDB()->prepare(
        "SELECT l.id, l.title, l.address, l.city, l.type, l.price_per_month,
                l.slots_total, l.slots_available, l.status,
                (SELECT p.file_path FROM listing_photos p
                  WHERE p.listing_id = l.id
                  ORDER BY p.is_primary DESC, p.id ASC LIMIT 1) AS photo
           FROM listings l
          WHERE l.owner_id = ?
          ORDER BY l.created_at DESC"
    );
    $stmt->execute([$user['id']]);
    $listings = $stmt->fetchAll();
} catch (PDOException $ex) {
    error_log('My listings failed: ' . $ex->getMessage());
    $loadError = true;
}

$pageTitle   = 'My listings';
$pageScripts = ['assets/js/owner.js'];
require_once __DIR__ . '/../includes/header.php';
?>

<div class="container py-5">
  <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-4">
    <div>
      <h1 class="h3 mb-0">My listings</h1>
      <p class="text-body-secondary mb-0">Add, edit, and remove your dorms and bedspaces.</p>
    </div>
    <a class="btn btn-primary" href="<?= url('owner/listing_form.php') ?>">
      <i class="bi bi-plus-lg" aria-hidden="true"></i> Add listing
    </a>
  </div>

  <?php if ($loadError): ?>
    <div class="alert alert-danger" role="alert">We could not load your listings. Please try again.</div>
  <?php elseif (!$listings): ?>
    <div class="card shadow-sm">
      <div class="card-body text-center py-5">
        <i class="bi bi-houses fs-1 text-body-secondary" aria-hidden="true"></i>
        <p class="mt-3 text-body-secondary">You have not posted anything yet.</p>
        <a class="btn btn-primary" href="<?= url('owner/listing_form.php') ?>">Add your first listing</a>
      </div>
    </div>
  <?php else: ?>
    <div class="row g-4">
      <?php foreach ($listings as $listing): ?>
        <?php [$statusText, $statusColor] = $statusInfo[$listing['status']] ?? [$listing['status'], 'secondary']; ?>
        <div class="col-md-6 col-lg-4">
          <article class="card listing-card shadow-sm h-100">
            <div class="position-relative">
              <?php if (!empty($listing['photo'])): ?>
                <img class="listing-photo" src="<?= e(url($listing['photo'])) ?>"
                     alt="Photo of <?= e($listing['title']) ?>" loading="lazy">
              <?php else: ?>
                <div class="listing-photo-placeholder" role="img" aria-label="No photo available">
                  <i class="bi bi-image" aria-hidden="true"></i>
                </div>
              <?php endif; ?>
              <span class="badge text-bg-<?= e($statusColor) ?> listing-badge"><?= e($statusText) ?></span>
            </div>

            <div class="card-body d-flex flex-column">
              <h2 class="h5 mb-1"><?= e($listing['title']) ?></h2>
              <p class="text-body-secondary small mb-2">
                <i class="bi bi-geo-alt" aria-hidden="true"></i>
                <?= e($listing['address']) ?>, <?= e($listing['city']) ?>
              </p>
              <div class="d-flex flex-wrap gap-2 mb-3">
                <span class="tag"><?= e($typeLabels[$listing['type']] ?? $listing['type']) ?></span>
                <span class="tag"><?= (int) $listing['slots_available'] ?> of <?= (int) $listing['slots_total'] ?> slots free</span>
              </div>
              <p class="mb-3">
                <span class="fs-5 fw-semibold">&#8369;<?= number_format((float) $listing['price_per_month'], 2) ?></span>
                <span class="text-body-secondary small">/ month</span>
              </p>
              <div class="d-flex gap-2 mt-auto">
                <a class="btn btn-outline-secondary btn-sm flex-fill"
                   href="<?= url('owner/listing_form.php?id=' . (int) $listing['id']) ?>">
                  <i class="bi bi-pencil" aria-hidden="true"></i> Edit
                </a>
                <button type="button" class="btn btn-outline-danger btn-sm flex-fill"
                        data-bs-toggle="modal" data-bs-target="#deleteModal"
                        data-id="<?= (int) $listing['id'] ?>" data-title="<?= e($listing['title']) ?>">
                  <i class="bi bi-trash" aria-hidden="true"></i> Delete
                </button>
              </div>
            </div>
          </article>
        </div>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>
</div>

<div class="modal fade" id="deleteModal" tabindex="-1" aria-labelledby="deleteModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <form class="modal-content" method="post" action="<?= url('owner/listing_delete.php') ?>">
      <?= csrf_field() ?>
      <input type="hidden" name="id" id="deleteId" value="">
      <div class="modal-header">
        <h2 class="modal-title h5" id="deleteModalLabel">Delete this listing?</h2>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <p class="mb-0">
          You are about to delete <strong id="deleteTitle"></strong>.
          Its photos and inquiries will be removed too. This cannot be undone.
        </p>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
        <button type="submit" class="btn btn-danger">Delete</button>
      </div>
    </form>
  </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
