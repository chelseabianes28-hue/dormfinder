<?php
require_once __DIR__ . '/includes/auth.php';

$id      = (int) ($_GET['id'] ?? 0);
$user    = current_user();
$listing = null;

if ($id > 0) {
    try {
        $stmt = getDB()->prepare(
            'SELECT l.*, u.full_name AS owner_name, u.phone AS owner_phone
               FROM listings l JOIN users u ON u.id = l.owner_id WHERE l.id = ?'
        );
        $stmt->execute([$id]);
        $listing = $stmt->fetch() ?: null;
    } catch (PDOException $ex) {
        error_log('Listing page failed: ' . $ex->getMessage());
    }
}

// Visitors only see approved listings. The owner and admin can also preview unapproved ones.
$canView = $listing && ($listing['status'] === 'approved'
    || ($user && ($user['role'] === 'admin' || (int) $user['id'] === (int) $listing['owner_id'])));

if (!$canView) {
    http_response_code(404);
    $pageTitle = 'Listing not found';
    require_once __DIR__ . '/includes/header.php';
    echo '<div class="container py-5 text-center"><h1 class="h3">Listing not found</h1>'
       . '<p class="text-body-secondary">It may have been removed or is not available yet.</p>'
       . '<a class="btn btn-primary" href="' . url('listings.php') . '">Back to Find a dorm</a></div>';
    require_once __DIR__ . '/includes/footer.php';
    exit;
}

$photoStmt = getDB()->prepare('SELECT id, file_path FROM listing_photos WHERE listing_id = ? ORDER BY is_primary DESC, id ASC');
$photoStmt->execute([$id]);
$photos = $photoStmt->fetchAll();

$isStudent   = $user && $user['role'] === 'student';
$isFavorite  = false;
if ($isStudent) {
    $favStmt = getDB()->prepare('SELECT 1 FROM favorites WHERE user_id = ? AND listing_id = ?');
    $favStmt->execute([$user['id'], $id]);
    $isFavorite = (bool) $favStmt->fetchColumn();
}

$typeLabels   = ['dormitory' => 'Dormitory', 'bedspace' => 'Bedspace', 'boarding_house' => 'Boarding house'];
$genderLabels = ['any' => 'All genders', 'male_only' => 'Male only', 'female_only' => 'Female only'];
$rules     = array_values(array_filter(array_map('trim', preg_split('/\R/', (string) $listing['rules']))));
$amenities = array_values(array_filter(array_map('trim', explode(',', (string) $listing['amenities']))));
$slots     = (int) $listing['slots_available'];

$pageTitle   = $listing['title'];
$pageStyles  = ['https://cdn.jsdelivr.net/npm/leaflet@1.9.4/dist/leaflet.css', 'assets/css/listing.css'];
$pageScripts = ['https://cdn.jsdelivr.net/npm/leaflet@1.9.4/dist/leaflet.js', 'assets/js/listing.js'];
require_once __DIR__ . '/includes/header.php';
?>

<div class="container py-5" id="listingPage" data-id="<?= (int) $id ?>" data-csrf="<?= e(csrf_token()) ?>"
     data-login-url="<?= e(url('login.php')) ?>">
  <?php if ($listing['status'] !== 'approved'): ?>
    <div class="alert alert-warning">This listing is <strong><?= e($listing['status']) ?></strong> and only you can see it.</div>
  <?php endif; ?>
  <div id="pageAlert" aria-live="polite"></div>

  <div class="row g-5">
    <div class="col-lg-6">
      <?php if ($photos): ?>
        <img class="detail-photo" src="<?= e(url($photos[0]['file_path'])) ?>" alt="Main photo of <?= e($listing['title']) ?>">
      <?php else: ?>
        <div class="listing-photo-placeholder detail-photo" role="img" aria-label="No photo available"><i class="bi bi-image"></i></div>
      <?php endif; ?>
    </div>

    <div class="col-lg-6">
      <span class="badge text-bg-light mb-2"><?= e($typeLabels[$listing['type']] ?? $listing['type']) ?></span>
      <h1 class="h2"><?= e($listing['title']) ?></h1>
      <p class="text-body-secondary mb-1"><i class="bi bi-geo-alt" aria-hidden="true"></i> <?= e($listing['address']) ?>, <?= e($listing['city']) ?></p>
      <p class="mb-1"><strong>Slots:</strong> <?= $slots ?> of <?= (int) $listing['slots_total'] ?> free &middot; <?= e($genderLabels[$listing['gender_policy']] ?? '') ?></p>
      <p class="mb-1"><strong>Status:</strong>
        <span class="badge text-bg-<?= $slots > 0 ? 'success' : 'secondary' ?>"><?= $slots > 0 ? 'Available' : 'Full' ?></span></p>
      <p class="mb-3"><a href="#detailMap"><i class="bi bi-map" aria-hidden="true"></i> View on map</a></p>

      <p class="mb-1"><span class="display-6 fw-semibold">&#8369;<?= number_format((float) $listing['price_per_month'], 2) ?></span>
        <span class="text-body-secondary">/ month</span></p>

      <p class="mb-4">
        <strong>Owner contact:</strong>
        <?php if ($user): ?>
          <?php if ($listing['owner_phone']): ?>
            <a href="tel:<?= e(preg_replace('/[^0-9+]/', '', $listing['owner_phone'])) ?>"><?= e($listing['owner_phone']) ?></a>
            (<?= e($listing['owner_name']) ?>)
          <?php else: ?>
            <span class="text-body-secondary">not provided</span>
          <?php endif; ?>
        <?php else: ?>
          <a href="<?= url('login.php') ?>">Log in</a> to see the contact number.
        <?php endif; ?>
      </p>

      <?php if ($isStudent): ?>
        <button type="button" class="btn btn-primary w-100 mb-2" data-bs-toggle="modal" data-bs-target="#inquiryModal">Inquire</button>
        <button type="button" class="btn btn-outline-secondary w-100" id="favoriteBtn" aria-pressed="<?= $isFavorite ? 'true' : 'false' ?>">
          <i class="bi <?= $isFavorite ? 'bi-heart-fill' : 'bi-heart' ?>" aria-hidden="true"></i>
          <span><?= $isFavorite ? 'Saved to favorites' : 'Add to favorite' ?></span>
        </button>
      <?php elseif (!$user): ?>
        <a class="btn btn-primary w-100" href="<?= url('login.php') ?>">Log in to inquire or save</a>
      <?php endif; ?>
    </div>
  </div>
</div>

<section class="info-band py-4 my-2">
  <div class="container row mx-auto g-4">
    <div class="col-md-6">
      <h2 class="h5">Rules / Policies</h2>
      <?php if ($rules): ?><ul class="mb-0"><?php foreach ($rules as $rule): ?><li><?= e($rule) ?></li><?php endforeach; ?></ul>
      <?php else: ?><p class="text-body-secondary mb-0">No rules listed.</p><?php endif; ?>
    </div>
    <div class="col-md-6">
      <h2 class="h5">Amenities</h2>
      <?php if ($amenities): ?><ul class="mb-0"><?php foreach ($amenities as $item): ?><li><?= e($item) ?></li><?php endforeach; ?></ul>
      <?php else: ?><p class="text-body-secondary mb-0">No amenities listed.</p><?php endif; ?>
    </div>
  </div>
</section>

<div class="container py-4">
  <?php if ($listing['description']): ?>
    <h2 class="h4">About this place</h2>
    <p class="mb-4"><?= nl2br(e($listing['description'])) ?></p>
  <?php endif; ?>

  <h2 class="h4">Gallery</h2>
  <?php if (count($photos) > 0): ?>
    <div class="row g-3 mb-4">
      <?php foreach ($photos as $photo): ?>
        <div class="col-6 col-md-4">
          <button type="button" class="p-0 border-0 bg-transparent w-100" data-bs-toggle="modal" data-bs-target="#photoModal"
                  data-src="<?= e(url($photo['file_path'])) ?>" aria-label="Enlarge photo">
            <img class="gallery-thumb" src="<?= e(url($photo['file_path'])) ?>" alt="Photo of <?= e($listing['title']) ?>" loading="lazy">
          </button>
        </div>
      <?php endforeach; ?>
    </div>
  <?php else: ?>
    <p class="text-body-secondary">No photos yet.</p>
  <?php endif; ?>

  <h2 class="h4" id="mapTitle">Location</h2>
  <div id="detailMap" data-lat="<?= e((string) $listing['latitude']) ?>" data-lng="<?= e((string) $listing['longitude']) ?>"
       role="application" aria-labelledby="mapTitle"></div>
</div>

<div class="modal fade" id="photoModal" tabindex="-1" aria-label="Photo" aria-hidden="true">
  <div class="modal-dialog modal-lg modal-dialog-centered">
    <div class="modal-content bg-transparent border-0">
      <button type="button" class="btn-close btn-close-white ms-auto mb-2" data-bs-dismiss="modal" aria-label="Close"></button>
      <img id="photoModalImg" class="img-fluid rounded" src="" alt="Enlarged photo">
    </div>
  </div>
</div>

<?php if ($isStudent): ?>
<div class="modal fade" id="inquiryModal" tabindex="-1" aria-labelledby="inquiryTitle" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <form class="modal-content" id="inquiryForm" novalidate>
      <div class="modal-header">
        <h2 class="modal-title h5" id="inquiryTitle">Send an inquiry</h2>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <div class="alert alert-danger d-none" id="inquiryError" role="alert"></div>
        <div class="mb-3">
          <label for="inquiryMessage" class="form-label">Message to the owner</label>
          <textarea class="form-control" id="inquiryMessage" name="message" rows="4" maxlength="1000" required
                    placeholder="Hi! Is this still available? I would like to ask about..."></textarea>
          <div class="form-text"><span id="inquiryCount">0</span> / 1000 characters (at least 10)</div>
        </div>
        <div>
          <label for="moveIn" class="form-label">Preferred move-in date <span class="text-body-secondary">(optional)</span></label>
          <input type="date" class="form-control" id="moveIn" name="move_in_date">
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
        <button type="submit" class="btn btn-primary" id="inquirySubmit">Send inquiry</button>
      </div>
    </form>
  </div>
</div>
<?php endif; ?>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
