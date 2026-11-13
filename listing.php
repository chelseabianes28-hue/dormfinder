<?php
require_once __DIR__ . '/includes/auth.php';

$id      = (int) ($_GET['id'] ?? 0);
$user    = current_user();
$listing = null;

if ($id > 0) {
    try {
        $stmt = getDB()->prepare(
            'SELECT l.*, u.full_name AS owner_name, u.phone AS owner_phone, u.email AS owner_email, u.avatar AS owner_avatar
               FROM listings l JOIN users u ON u.id = l.owner_id WHERE l.id = ?'
        );
        $stmt->execute([$id]);
        $listing = $stmt->fetch() ?: null;
    } catch (PDOException $ex) {
        error_log('Listing page failed: ' . $ex->getMessage());
    }
}

$canView = $listing && ($listing['status'] === 'approved'
    || ($user && ($user['role'] === 'admin' || (int) $user['id'] === (int) $listing['owner_id'])));

if (!$canView) {
    http_response_code(404);
    $pageTitle = 'Listing not found';
    require_once __DIR__ . '/includes/header.php';
    echo '<div class="container py-5 text-center"><h1 class="h3">Listing not found</h1>'
       . '<p class="text-body-secondary">It may have been removed or is not available yet.</p>'
       . '<a class="btn btn-primary" href="' . url('listings.php') . '">Back to Find a place</a></div>';
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

$typeLabels   = ['dormitory' => 'Dormitory', 'bedspace' => 'Bedspace', 'boarding_house' => 'Boarding house', 'apartment' => 'Apartment'];
$genderLabels = ['any' => 'All genders', 'male_only' => 'Male only', 'female_only' => 'Female only'];
$rules     = array_values(array_filter(array_map('trim', preg_split('/\R/', (string) $listing['rules']))));
$amenities = array_values(array_filter(array_map('trim', explode(',', (string) $listing['amenities']))));
$slots     = (int) $listing['slots_available'];
$isFull    = ($slots <= 0 || strtolower((string) $listing['status']) === 'full');

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
      <p class="text-body-secondary mb-3 d-flex align-items-start gap-2">
        <i class="bi bi-geo-alt-fill text-primary mt-1" aria-hidden="true"></i>
        <span><?= e($listing['address']) ?>, <?= e($listing['city']) ?></span>
      </p>

      <div class="detail-facts mb-3">
        <span class="fact"><i class="bi bi-people" aria-hidden="true"></i> <?= $slots ?> of <?= (int) $listing['slots_total'] ?> slots free</span>
        <span class="fact"><i class="bi bi-gender-ambiguous" aria-hidden="true"></i> <?= e($genderLabels[$listing['gender_policy']] ?? '') ?></span>
        <span class="fact fact-<?= $slots > 0 ? 'ok' : 'full' ?>"><i class="bi <?= $slots > 0 ? 'bi-check-circle-fill' : 'bi-x-circle-fill' ?>" aria-hidden="true"></i> <?= $slots > 0 ? 'Available' : 'Full' ?></span>
      </div>

      <a class="btn btn-sm btn-outline-primary rounded-pill px-3 mb-4" href="#detailMap" id="viewOnMap">
        <i class="bi bi-map me-1" aria-hidden="true"></i> View on map
      </a>

      <p class="detail-price mb-4">&#8369;<?= number_format((float) $listing['price_per_month'], 2) ?>
        <span class="text-body-secondary fs-6 fw-normal">/ month</span></p>

      <div class="owner-card mb-4">
        <div class="owner-card-label">Contact the owner</div>
        <?php if ($user): ?>
          <a class="owner-head" href="<?= url('owner-profile.php?id=' . (int) $listing['owner_id']) ?>" title="View the owner's profile">
            <?php if (!empty($listing['owner_avatar'])): ?>
              <img src="<?= e(url($listing['owner_avatar'])) ?>" alt="" class="owner-avatar">
            <?php else: ?>
              <span class="owner-avatar owner-initial" aria-hidden="true"><?= e(mb_strtoupper(mb_substr((string) $listing['owner_name'], 0, 1))) ?></span>
            <?php endif; ?>
            <span class="flex-grow-1">
              <span class="d-block fw-semibold"><?= e($listing['owner_name']) ?></span>
              <span class="d-block small text-body-secondary">Property owner &middot; View profile</span>
            </span>
            <i class="bi bi-chevron-right text-body-secondary" aria-hidden="true"></i>
          </a>
          <div class="owner-contacts">
            <?php if ($listing['owner_phone']): ?>
              <a class="owner-contact" href="tel:<?= e(preg_replace('/[^0-9+]/', '', $listing['owner_phone'])) ?>">
                <span class="contact-icon"><i class="bi bi-telephone" aria-hidden="true"></i></span>
                <span><span class="d-block small text-body-secondary">Phone</span><?= e($listing['owner_phone']) ?></span>
              </a>
            <?php else: ?>
              <div class="owner-contact">
                <span class="contact-icon"><i class="bi bi-telephone" aria-hidden="true"></i></span>
                <span><span class="d-block small text-body-secondary">Phone</span><span class="text-body-secondary">Not provided</span></span>
              </div>
            <?php endif; ?>
            <a class="owner-contact" href="mailto:<?= e($listing['owner_email']) ?>">
              <span class="contact-icon"><i class="bi bi-envelope" aria-hidden="true"></i></span>
              <span class="text-truncate"><span class="d-block small text-body-secondary">Email</span><?= e($listing['owner_email']) ?></span>
            </a>
          </div>
        <?php else: ?>
          <p class="mb-0 text-body-secondary"><i class="bi bi-lock me-1" aria-hidden="true"></i>
            <a href="<?= url('login.php') ?>">Log in</a> to see the owner's name, phone number, and email.</p>
        <?php endif; ?>
      </div>

      <?php if ($isStudent): ?>
        <?php if ($isFull): ?>
          <div class="alert alert-warning mb-2" role="alert">
            This property currently has no availability and is not accepting inquiries. Please wait for an update.
          </div>
          <button type="button" class="btn btn-secondary w-100 mb-2" disabled>No Availability</button>
        <?php else: ?>
          <button type="button" class="btn btn-primary btn-lg w-100 mb-2" data-bs-toggle="modal" data-bs-target="#inquiryModal">
            <i class="bi bi-chat-dots me-1" aria-hidden="true"></i> Inquire
          </button>
        <?php endif; ?>

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
      <h2 class="h5 mb-3">Rules / Policies</h2>
      <?php if ($rules): ?>
        <ul class="mb-0 lh-lg"><?php foreach ($rules as $rule): ?><li><?= e($rule) ?></li><?php endforeach; ?></ul>
      <?php else: ?>
        <p class="text-body-secondary mb-0">No rules listed.</p>
      <?php endif; ?>
    </div>
    <div class="col-md-6">
      <h2 class="h5 mb-3">Amenities</h2>
      <?php if ($amenities): ?>
        <div class="amenities-grid">
          <?php foreach ($amenities as $item): ?>
            <?php $icon = get_amenity_icon($item); // regex engine in includes/init.php ?>
            <div class="amenity-card">
              <i class="bi <?= e($icon) ?>" aria-hidden="true"></i>
              <span><?= e($item) ?></span>
            </div>
          <?php endforeach; ?>
        </div>
      <?php else: ?>
        <p class="text-body-secondary mb-0">No amenities listed.</p>
      <?php endif; ?>
    </div>
  </div>
</section>

<div class="container py-4">
  <?php if ($listing['description']): ?>
    <h2 class="h4">About this place</h2>
    <p class="mb-4 text-body-secondary lh-lg"><?= nl2br(e($listing['description'])) ?></p>
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

<?php if ($isStudent && !$isFull): ?>
<div class="modal fade inquiry-modal" id="inquiryModal" tabindex="-1" aria-labelledby="inquiryTitle" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <form id="inquiryForm" novalidate>
        <div class="modal-header">
          <div>
            <h2 class="modal-title h5 mb-0" id="inquiryTitle">Send an inquiry</h2>
            <p class="small text-body-secondary mb-0">The owner will be notified and can accept or decline.</p>
          </div>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>

        <div class="modal-body">
          <div class="inquiry-summary mb-3">
            <?php if ($photos): ?>
              <img src="<?= e(url($photos[0]['file_path'])) ?>" alt="" class="inquiry-thumb">
            <?php else: ?>
              <span class="inquiry-thumb d-grid place-center"><i class="bi bi-house" aria-hidden="true"></i></span>
            <?php endif; ?>
            <div class="min-w-0">
              <div class="fw-semibold text-truncate"><?= e($listing['title']) ?></div>
              <div class="small text-body-secondary text-truncate"><?= e($listing['city']) ?> &middot; &#8369;<?= number_format((float) $listing['price_per_month'], 2) ?> / month</div>
              <div class="small text-body-secondary">To: <?= e($listing['owner_name']) ?></div>
            </div>
          </div>

          <div class="alert alert-danger d-none py-2" id="inquiryError" role="alert"></div>

          <label for="inquiryMessage" class="form-label fw-semibold">Your message</label>
          <div class="quick-replies mb-2" aria-label="Quick messages">
            <button type="button" class="chip quick-reply">Is this still available?</button>
            <button type="button" class="chip quick-reply">Can I schedule a visit?</button>
            <button type="button" class="chip quick-reply">Are water and electricity included?</button>
          </div>
          <textarea class="form-control" id="inquiryMessage" name="message" rows="4" maxlength="1000" required
                    aria-describedby="inquiryHelp"
                    placeholder="Hi! I'm a student and I'm interested in this place..."></textarea>
          <div class="d-flex justify-content-between form-text mb-3" id="inquiryHelp">
            <span>At least 10 characters</span>
            <span><span id="inquiryCount">0</span> / 1000</span>
          </div>

          <label for="moveIn" class="form-label fw-semibold">Preferred move-in date <span class="text-body-secondary fw-normal">(optional)</span></label>
          <input type="date" class="form-control" id="moveIn" name="move_in_date">
        </div>

        <div class="modal-footer">
          <button type="button" class="btn btn-outline-secondary rounded-pill px-4" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" class="btn btn-primary rounded-pill px-4" id="inquirySubmit">
            <i class="bi bi-send me-1" aria-hidden="true"></i> <span id="inquirySubmitText">Send inquiry</span>
          </button>
        </div>
      </form>
    </div>
  </div>
</div>
<?php endif; ?>

<?php require_once __DIR__ . '/includes/footer.php'; ?>