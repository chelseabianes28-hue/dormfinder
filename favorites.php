<?php
require_once __DIR__ . '/includes/auth.php';
require_role('student');

$user      = current_user();
$favorites = [];
try {
    $stmt = getDB()->prepare(
        "SELECT l.id, l.title, l.address, l.city, l.type, l.price_per_month, l.slots_available, l.gender_policy,
                (SELECT p.file_path FROM listing_photos p WHERE p.listing_id = l.id
                  ORDER BY p.is_primary DESC, p.id ASC LIMIT 1) AS photo
           FROM favorites f JOIN listings l ON l.id = f.listing_id
          WHERE f.user_id = ? AND l.status = 'approved'
          ORDER BY f.created_at DESC"
    );
    $stmt->execute([$user['id']]);
    $favorites = $stmt->fetchAll();
} catch (PDOException $ex) {
    error_log('Favorites failed: ' . $ex->getMessage());
}

$pageTitle   = 'Favorites';
$pageScripts = ['assets/js/favorites.js'];
require_once __DIR__ . '/includes/header.php';
?>

<div class="container py-5" id="favoritesPage" data-csrf="<?= e(csrf_token()) ?>">
  <h1 class="h2"><i class="bi bi-heart" aria-hidden="true"></i> Favorites</h1>
  <p class="text-body-secondary mb-4">Dorms and bedspaces you saved.</p>
  <div id="pageAlert" aria-live="polite"></div>

  <?php if (!$favorites): ?>
    <div class="card shadow-sm"><div class="card-body text-center py-5">
      <i class="bi bi-heart fs-1 text-body-secondary" aria-hidden="true"></i>
      <p class="mt-3 text-body-secondary">You have no favorites yet.</p>
      <a class="btn btn-primary" href="<?= url('listings.php') ?>">Find a dorm</a>
    </div></div>
  <?php else: ?>
    <div class="row g-4">
      <?php foreach ($favorites as $listing): ?>
        <div class="col-md-6 col-lg-4 fav-item">
          <div class="position-relative h-100">
            <?php include __DIR__ . '/includes/listing_card.php'; ?>
            <button type="button" class="btn btn-light btn-sm position-absolute top-0 end-0 m-3 rounded-circle shadow-sm fav-remove"
                    data-id="<?= (int) $listing['id'] ?>" aria-label="Remove <?= e($listing['title']) ?> from favorites">
              <i class="bi bi-heart-fill text-danger" aria-hidden="true"></i>
            </button>
          </div>
        </div>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
