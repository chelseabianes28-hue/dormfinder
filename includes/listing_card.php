<?php
// Reusable listing card. Expects $listing with: id, title, address, city, type,
// price_per_month, slots_available, gender_policy, photo (may be null).
$typeLabels   = ['dormitory' => 'Dormitory', 'bedspace' => 'Bedspace', 'boarding_house' => 'Boarding house'];
$genderLabels = ['any' => 'All genders', 'male_only' => 'Male only', 'female_only' => 'Female only'];
$slots        = (int) $listing['slots_available'];
?>
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
    <span class="badge text-bg-light listing-badge">
      <?= e($typeLabels[$listing['type']] ?? $listing['type']) ?>
    </span>
  </div>

  <div class="card-body d-flex flex-column">
    <h3 class="h5 mb-1"><?= e($listing['title']) ?></h3>
    <p class="text-body-secondary small mb-2">
      <i class="bi bi-geo-alt" aria-hidden="true"></i>
      <?= e($listing['address']) ?>, <?= e($listing['city']) ?>
    </p>

    <div class="d-flex flex-wrap gap-2 mb-3">
      <span class="tag"><?= $slots ?> <?= $slots === 1 ? 'slot' : 'slots' ?> left</span>
      <span class="tag"><?= e($genderLabels[$listing['gender_policy']] ?? '') ?></span>
    </div>

    <div class="d-flex justify-content-between align-items-center mt-auto">
      <div>
        <span class="fs-5 fw-semibold">&#8369;<?= number_format((float) $listing['price_per_month'], 2) ?></span>
        <span class="text-body-secondary small">/ month</span>
      </div>
      <a class="circle-btn" href="<?= url('listing.php?id=' . (int) $listing['id']) ?>"
         aria-label="View <?= e($listing['title']) ?>">
        <i class="bi bi-arrow-up-right" aria-hidden="true"></i>
      </a>
    </div>
  </div>
</article>
