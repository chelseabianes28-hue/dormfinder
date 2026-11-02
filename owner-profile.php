<?php
require_once __DIR__ . '/includes/auth.php';

$id     = (int) ($_GET['id'] ?? 0);
$viewer = current_user();
$owner  = null;

// Only active owners have a public profile. Student and admin profiles stay private.
if ($id > 0) {
    try {
        $stmt = getDB()->prepare("SELECT id, full_name, email, phone, avatar, created_at FROM users WHERE id = ? AND role = 'owner' AND is_active = 1");
        $stmt->execute([$id]);
        $owner = $stmt->fetch() ?: null;
    } catch (PDOException $ex) {
        error_log('Owner profile failed: ' . $ex->getMessage());
        try {        // the avatar column may not exist yet
            $stmt = getDB()->prepare("SELECT id, full_name, email, phone, created_at FROM users WHERE id = ? AND role = 'owner' AND is_active = 1");
            $stmt->execute([$id]);
            $owner = $stmt->fetch() ?: null;
        } catch (PDOException $ex2) {
            error_log('Owner profile fallback failed: ' . $ex2->getMessage());
        }
    }
}

if (!$owner) {
    http_response_code(404);
    $pageTitle = 'Profile not found';
    require_once __DIR__ . '/includes/header.php';
    echo '<div class="container py-5 text-center"><h1 class="h3">Profile not found</h1>'
       . '<p class="text-body-secondary">This owner does not exist or is not available.</p>'
       . '<a class="btn btn-primary" href="' . url('listings.php') . '">Back to Find a dorm</a></div>';
    require_once __DIR__ . '/includes/footer.php';
    exit;
}

$listings = [];
try {
    $stmt = getDB()->prepare(
        "SELECT l.id, l.title, l.address, l.city, l.type, l.price_per_month, l.slots_available, l.gender_policy,
                (SELECT p.file_path FROM listing_photos p WHERE p.listing_id = l.id
                  ORDER BY p.is_primary DESC, p.id ASC LIMIT 1) AS photo
           FROM listings l WHERE l.owner_id = ? AND l.status = 'approved' ORDER BY l.created_at DESC"
    );
    $stmt->execute([$owner['id']]);
    $listings = $stmt->fetchAll();
} catch (PDOException $ex) {
    error_log('Owner listings failed: ' . $ex->getMessage());
}

$isMe      = $viewer && (int) $viewer['id'] === (int) $owner['id'];
$pageTitle = $owner['full_name'];
require_once __DIR__ . '/includes/header.php';
?>

<div class="container py-5">
  <a class="text-decoration-none" href="javascript:history.back()"><i class="bi bi-arrow-left" aria-hidden="true"></i> Back</a>

  <div class="row g-5 align-items-center mt-1 mb-5">
    <div class="col-md-4 text-center">
      <?php if (!empty($owner['avatar'])): ?>
        <img src="<?= e(url($owner['avatar'])) ?>" alt="Profile photo of <?= e($owner['full_name']) ?>"
             class="rounded-circle border" width="160" height="160" style="object-fit: cover;">
      <?php else: ?>
        <i class="bi bi-person-circle" style="font-size: 10rem; line-height: 1;" aria-hidden="true"></i>
      <?php endif; ?>
    </div>
    <div class="col-md-8">
      <span class="badge text-bg-primary mb-2">Owner</span>
      <h1 class="h2"><?= e($owner['full_name']) ?></h1>
      <p class="text-body-secondary mb-1"><i class="bi bi-houses" aria-hidden="true"></i>
        <?= count($listings) ?> <?= count($listings) === 1 ? 'listing' : 'listings' ?></p>
      <p class="text-body-secondary mb-3"><i class="bi bi-calendar3" aria-hidden="true"></i>
        Member since <?= e(date('F Y', strtotime($owner['created_at']))) ?></p>

      <?php if ($viewer): ?>
        <ul class="list-unstyled mb-3">
          <li><i class="bi bi-telephone" aria-hidden="true"></i>
            <?php if ($owner['phone']): ?>
              <a href="tel:<?= e(preg_replace('/[^0-9+]/', '', $owner['phone'])) ?>"><?= e($owner['phone']) ?></a>
            <?php else: ?><span class="text-body-secondary">not provided</span><?php endif; ?>
          </li>
          <li><i class="bi bi-envelope" aria-hidden="true"></i> <a href="mailto:<?= e($owner['email']) ?>"><?= e($owner['email']) ?></a></li>
        </ul>
      <?php else: ?>
        <p class="mb-3"><a href="<?= url('login.php') ?>">Log in</a> to see this owner's phone number and email.</p>
      <?php endif; ?>

      <?php if ($isMe): ?>
        <a class="btn btn-outline-secondary" href="<?= url('profile.php') ?>">Edit my profile</a>
      <?php endif; ?>
    </div>
  </div>

  <h2 class="h4 mb-3">Listings by <?= e($owner['full_name']) ?></h2>
  <?php if (!$listings): ?>
    <div class="card shadow-sm"><div class="card-body text-center py-5 text-body-secondary">No approved listings yet.</div></div>
  <?php else: ?>
    <div class="row g-4">
      <?php foreach ($listings as $listing): ?>
        <div class="col-md-6 col-lg-4"><?php include __DIR__ . '/includes/listing_card.php'; ?></div>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
