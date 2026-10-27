<?php
require_once __DIR__ . '/includes/auth.php';

// Latest approved listings (empty until owners post some).
$latest = [];
try {
    $latest = getDB()->query(
        "SELECT l.id, l.title, l.address, l.city, l.type, l.price_per_month,
                l.slots_available, l.gender_policy,
                (SELECT p.file_path FROM listing_photos p
                  WHERE p.listing_id = l.id
                  ORDER BY p.is_primary DESC, p.id ASC LIMIT 1) AS photo
           FROM listings l
          WHERE l.status = 'approved'
          ORDER BY l.created_at DESC
          LIMIT 3"
    )->fetchAll();
} catch (PDOException $ex) {
    error_log('Landing page query failed: ' . $ex->getMessage());
}

$pageTitle = 'Home';
require_once __DIR__ . '/includes/header.php';
?>

<section class="hero text-center">
  <div class="container">
    <h1 class="display-5 fw-semibold">Your next home is just a click away</h1>
    <p class="lead text-body-secondary mb-4">
      Find a dorm or bedspace near your school. Search, compare, and contact owners in one place.
    </p>

    <form class="search-bar mx-auto" action="<?= url('listings.php') ?>" method="get" role="search">
      <div class="input-group input-group-lg">
        <span class="input-group-text bg-body"><i class="bi bi-search" aria-hidden="true"></i></span>
        <input type="search" class="form-control" name="q"
               placeholder="Search by school, area, or dorm name" aria-label="Search listings">
        <button class="btn btn-primary" type="submit">Search</button>
      </div>
    </form>

    <div class="d-flex flex-wrap justify-content-center gap-2 mt-4">
      <a class="chip" href="<?= url('listings.php?type=dormitory') ?>">Dormitory</a>
      <a class="chip" href="<?= url('listings.php?type=bedspace') ?>">Bedspace</a>
      <a class="chip" href="<?= url('listings.php?type=boarding_house') ?>">Boarding house</a>
    </div>
  </div>
</section>

<section class="container py-5" aria-labelledby="how-title">
  <h2 id="how-title" class="h3 text-center mb-4">How it works</h2>
  <div class="row g-4 text-center">
    <div class="col-md-4">
      <span class="step-icon mb-3"><i class="bi bi-search" aria-hidden="true"></i></span>
      <h3 class="h5">Search</h3>
      <p class="text-body-secondary">Filter by price, location, and available slots to find places that fit your budget.</p>
    </div>
    <div class="col-md-4">
      <span class="step-icon mb-3"><i class="bi bi-chat-dots" aria-hidden="true"></i></span>
      <h3 class="h5">Contact the owner</h3>
      <p class="text-body-secondary">Send an inquiry and ask for a visit directly from the listing page.</p>
    </div>
    <div class="col-md-4">
      <span class="step-icon mb-3"><i class="bi bi-key" aria-hidden="true"></i></span>
      <h3 class="h5">Move in</h3>
      <p class="text-body-secondary">Visit, confirm with the owner, and settle into your new place.</p>
    </div>
  </div>
</section>

<section class="container pb-5" aria-labelledby="latest-title">
  <div class="d-flex justify-content-between align-items-center mb-3">
    <h2 id="latest-title" class="h3 mb-0">Latest listings</h2>
    <a href="<?= url('listings.php') ?>">See all</a>
  </div>

  <?php if ($latest): ?>
    <div class="row g-4">
      <?php foreach ($latest as $listing): ?>
        <div class="col-md-6 col-lg-4">
          <?php include __DIR__ . '/includes/listing_card.php'; ?>
        </div>
      <?php endforeach; ?>
    </div>
  <?php else: ?>
    <div class="card shadow-sm">
      <div class="card-body text-center py-5">
        <i class="bi bi-houses fs-1 text-body-secondary" aria-hidden="true"></i>
        <p class="mt-3 mb-0 text-body-secondary">No listings yet. Check back soon!</p>
      </div>
    </div>
  <?php endif; ?>
</section>

<?php if (!$user): ?>
<section class="container pb-5">
  <div class="card shadow-sm">
    <div class="card-body text-center p-5">
      <h2 class="h3">Own a dorm or bedspace?</h2>
      <p class="text-body-secondary">Post your place for free and reach students looking for a home.</p>
      <a class="btn btn-primary" href="<?= url('register.php') ?>">List your place</a>
    </div>
  </div>
</section>
<?php endif; ?>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
