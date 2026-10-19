<?php
// Temporary landing page that proves the shared layout works. The full landing page comes later.
$pageTitle = 'Home';
require_once __DIR__ . '/includes/header.php';
?>

<section class="hero text-center">
  <div class="container">
    <h1 class="display-5 fw-semibold">Find a dorm or bedspace near your school</h1>
    <p class="lead text-body-secondary">Search, compare, and contact owners in one place.</p>
    <a class="btn btn-primary btn-lg" href="<?= url('listings.php') ?>">Browse dorms</a>
  </div>
</section>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
