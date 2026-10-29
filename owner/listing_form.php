<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/uploads.php';
require_role('owner');

$user    = current_user();
$pdo     = getDB();
$id      = (int) ($_GET['id'] ?? $_POST['id'] ?? 0);
$editing = $id > 0;
$photos  = [];

$types   = ['dormitory' => 'Dormitory', 'bedspace' => 'Bedspace', 'boarding_house' => 'Boarding house'];
$genders = ['any' => 'All genders', 'male_only' => 'Male only', 'female_only' => 'Female only'];

$data = [
    'title' => '', 'description' => '', 'type' => 'dormitory', 'price_per_month' => '',
    'slots_total' => '1', 'slots_available' => '1', 'gender_policy' => 'any',
    'address' => '', 'city' => '', 'latitude' => '', 'longitude' => '',
    'rules' => '', 'amenities' => '',
];

if ($editing) {
    $stmt = $pdo->prepare('SELECT * FROM listings WHERE id = ? AND owner_id = ?');
    $stmt->execute([$id, $user['id']]);
    $existing = $stmt->fetch();
    if (!$existing) {
        set_flash('danger', 'Listing not found.');
        redirect('owner/my-listings.php');
    }
    foreach (array_keys($data) as $key) {
        $data[$key] = (string) ($existing[$key] ?? '');
    }
    $photoStmt = $pdo->prepare(
        'SELECT id, file_path, is_primary FROM listing_photos WHERE listing_id = ? ORDER BY is_primary DESC, id ASC'
    );
    $photoStmt->execute([$id]);
    $photos = $photoStmt->fetchAll();
}

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf()) {
        $errors[] = (empty($_POST) && !empty($_SERVER['CONTENT_LENGTH']))
            ? 'The photos you selected are too large in total. Try fewer or smaller photos.'
            : 'Your session expired. Please try again.';
    } else {
        foreach (array_keys($data) as $key) {
            $data[$key] = trim((string) ($_POST[$key] ?? ''));
        }

        // ---- Validate the text fields (server-side) ----
        $titleLength = mb_strlen($data['title']);
        if ($titleLength < 3 || $titleLength > 150) {
            $errors[] = 'Title must be 3 to 150 characters.';
        }
        if (mb_strlen($data['description']) > 2000) {
            $errors[] = 'Description must be 2000 characters or fewer.';
        }
        if (!isset($types[$data['type']])) {
            $errors[] = 'Please choose a valid type.';
        }
        if (!isset($genders[$data['gender_policy']])) {
            $errors[] = 'Please choose a valid gender policy.';
        }
        if (!is_numeric($data['price_per_month']) || (float) $data['price_per_month'] <= 0
            || (float) $data['price_per_month'] > 1000000) {
            $errors[] = 'Enter a monthly price greater than 0.';
        }
        $slotsOk = ctype_digit($data['slots_total']) && (int) $data['slots_total'] >= 1
            && (int) $data['slots_total'] <= 500;
        if (!$slotsOk) {
            $errors[] = 'Total slots must be a whole number from 1 to 500.';
        } elseif (!ctype_digit($data['slots_available']) || (int) $data['slots_available'] > (int) $data['slots_total']) {
            $errors[] = 'Available slots must be a whole number that is not more than the total slots.';
        }
        if ($data['address'] === '' || mb_strlen($data['address']) > 255) {
            $errors[] = 'Address is required (up to 255 characters).';
        }
        if ($data['city'] === '' || mb_strlen($data['city']) > 100) {
            $errors[] = 'City is required (up to 100 characters).';
        }
        if (!is_numeric($data['latitude']) || !is_numeric($data['longitude'])
            || abs((float) $data['latitude']) > 90 || abs((float) $data['longitude']) > 180) {
            $errors[] = 'Please pin the location on the map.';
        }
        if (mb_strlen($data['rules']) > 1000) {
            $errors[] = 'House rules must be 1000 characters or fewer.';
        }
        if (mb_strlen($data['amenities']) > 255) {
            $errors[] = 'Amenities must be 255 characters or fewer.';
        }

        // ---- Photos: which existing ones stay, and are the new ones valid? ----
        $existingIds = array_map('intval', array_column($photos, 'id'));
        $removeIds   = array_values(array_intersect($existingIds, array_map('intval', (array) ($_POST['delete_photos'] ?? []))));
        $remaining   = count($photos) - count($removeIds);
        $slotsLeft   = max(0, MAX_LISTING_PHOTOS - $remaining);

        $upload = collect_uploaded_images('photos', $slotsLeft);
        $errors = array_merge($errors, $upload['errors']);

        if (!$upload['errors'] && $remaining + count($upload['files']) === 0) {
            $errors[] = 'Please add at least one photo.';
        }

        // ---- Save ----
        if (!$errors) {
            $movedPaths    = [];
            $filesToDelete = [];

            try {
                $pdo->beginTransaction();

                $values = [
                    $data['title'],
                    $data['description'] !== '' ? $data['description'] : null,
                    $data['type'],
                    number_format((float) $data['price_per_month'], 2, '.', ''),
                    (int) $data['slots_total'],
                    (int) $data['slots_available'],
                    $data['gender_policy'],
                    $data['address'],
                    $data['city'],
                    (float) $data['latitude'],
                    (float) $data['longitude'],
                    $data['rules'] !== '' ? $data['rules'] : null,
                    $data['amenities'] !== '' ? $data['amenities'] : null,
                ];

                if ($editing) {
                    // A rejected listing goes back to "pending" when the owner fixes and resubmits it.
                    $save = $pdo->prepare(
                        "UPDATE listings SET title = ?, description = ?, type = ?, price_per_month = ?,
                                slots_total = ?, slots_available = ?, gender_policy = ?, address = ?, city = ?,
                                latitude = ?, longitude = ?, rules = ?, amenities = ?,
                                status = CASE WHEN status = 'rejected' THEN 'pending' ELSE status END
                          WHERE id = ? AND owner_id = ?"
                    );
                    $save->execute(array_merge($values, [$id, $user['id']]));
                    $listingId = $id;
                } else {
                    $save = $pdo->prepare(
                        "INSERT INTO listings (title, description, type, price_per_month, slots_total, slots_available,
                                gender_policy, address, city, latitude, longitude, rules, amenities, owner_id, status)
                         VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'pending')"
                    );
                    $save->execute(array_merge($values, [$user['id']]));
                    $listingId = (int) $pdo->lastInsertId();
                }

                // Remove photos the owner ticked
                foreach ($photos as $photo) {
                    if (in_array((int) $photo['id'], $removeIds, true)) {
                        $filesToDelete[] = $photo['file_path'];
                        $pdo->prepare('DELETE FROM listing_photos WHERE id = ? AND listing_id = ?')
                            ->execute([$photo['id'], $listingId]);
                    }
                }

                // Add new photos
                foreach ($upload['files'] as $file) {
                    $path         = save_uploaded_image($file);
                    $movedPaths[] = $path;
                    $pdo->prepare('INSERT INTO listing_photos (listing_id, file_path, is_primary) VALUES (?, ?, 0)')
                        ->execute([$listingId, $path]);
                }

                // Choose the main photo (the one picked, or the first one)
                $idStmt = $pdo->prepare('SELECT id FROM listing_photos WHERE listing_id = ? ORDER BY id ASC');
                $idStmt->execute([$listingId]);
                $validIds = array_map('intval', $idStmt->fetchAll(PDO::FETCH_COLUMN));
                $wanted   = (int) ($_POST['primary_photo'] ?? 0);
                if (!in_array($wanted, $validIds, true)) {
                    $wanted = $validIds[0] ?? 0;
                }
                if ($wanted) {
                    $pdo->prepare('UPDATE listing_photos SET is_primary = (id = ?) WHERE listing_id = ?')
                        ->execute([$wanted, $listingId]);
                }

                $pdo->commit();
            } catch (Throwable $ex) {
                if ($pdo->inTransaction()) {
                    $pdo->rollBack();
                }
                foreach ($movedPaths as $path) {
                    delete_photo_file($path);
                }
                error_log('Save listing failed: ' . $ex->getMessage());
                $errors[] = 'Something went wrong while saving. Please try again.';
            }

            if (!$errors) {
                foreach ($filesToDelete as $path) {
                    delete_photo_file($path);
                }
                set_flash('success', $editing
                    ? 'Listing updated.'
                    : 'Listing submitted! It will appear on the site after an admin approves it.');
                redirect('owner/my-listings.php');
            }
        }
    }
}

$photoSlotsLeft = max(0, MAX_LISTING_PHOTOS - count($photos));
$pageTitle      = $editing ? 'Edit listing' : 'Add listing';
$pageStyles     = ['https://cdn.jsdelivr.net/npm/leaflet@1.9.4/dist/leaflet.css'];
$pageScripts    = ['https://cdn.jsdelivr.net/npm/leaflet@1.9.4/dist/leaflet.js', 'assets/js/listing-form.js'];
require_once __DIR__ . '/../includes/header.php';
?>

<div class="container py-5">
  <div class="row justify-content-center">
    <div class="col-lg-9">
      <a class="text-decoration-none" href="<?= url('owner/my-listings.php') ?>">
        <i class="bi bi-arrow-left" aria-hidden="true"></i> Back to my listings
      </a>
      <h1 class="h3 mt-2 mb-4"><?= $editing ? 'Edit listing' : 'Add a listing' ?></h1>

      <?php if ($errors): ?>
        <div class="alert alert-danger" role="alert">
          <ul class="mb-0">
            <?php foreach ($errors as $error): ?>
              <li><?= e($error) ?></li>
            <?php endforeach; ?>
          </ul>
        </div>
      <?php endif; ?>

      <form method="post" enctype="multipart/form-data" action="<?= url('owner/listing_form.php') ?>">
        <?= csrf_field() ?>
        <?php if ($editing): ?>
          <input type="hidden" name="id" value="<?= (int) $id ?>">
        <?php endif; ?>

        <div class="card shadow-sm mb-4">
          <div class="card-body p-4">
            <h2 class="h5 mb-3">Details</h2>

            <div class="mb-3">
              <label for="title" class="form-label">Title</label>
              <input type="text" class="form-control" id="title" name="title" required maxlength="150"
                     value="<?= e($data['title']) ?>">
            </div>

            <div class="row g-3 mb-3">
              <div class="col-md-6">
                <label for="type" class="form-label">Type</label>
                <select class="form-select" id="type" name="type" required>
                  <?php foreach ($types as $value => $label): ?>
                    <option value="<?= e($value) ?>" <?= $data['type'] === $value ? 'selected' : '' ?>><?= e($label) ?></option>
                  <?php endforeach; ?>
                </select>
              </div>
              <div class="col-md-6">
                <label for="gender_policy" class="form-label">Who can stay</label>
                <select class="form-select" id="gender_policy" name="gender_policy" required>
                  <?php foreach ($genders as $value => $label): ?>
                    <option value="<?= e($value) ?>" <?= $data['gender_policy'] === $value ? 'selected' : '' ?>><?= e($label) ?></option>
                  <?php endforeach; ?>
                </select>
              </div>
            </div>

            <div class="row g-3 mb-3">
              <div class="col-md-4">
                <label for="price_per_month" class="form-label">Price per month (&#8369;)</label>
                <input type="number" class="form-control" id="price_per_month" name="price_per_month" required
                       min="1" max="1000000" step="0.01" value="<?= e($data['price_per_month']) ?>">
              </div>
              <div class="col-md-4">
                <label for="slots_total" class="form-label">Total slots</label>
                <input type="number" class="form-control" id="slots_total" name="slots_total" required
                       min="1" max="500" step="1" value="<?= e($data['slots_total']) ?>">
              </div>
              <div class="col-md-4">
                <label for="slots_available" class="form-label">Slots available</label>
                <input type="number" class="form-control" id="slots_available" name="slots_available" required
                       min="0" max="500" step="1" value="<?= e($data['slots_available']) ?>">
              </div>
            </div>

            <div class="mb-3">
              <label for="description" class="form-label">Description</label>
              <textarea class="form-control" id="description" name="description" rows="4"
                        maxlength="2000"><?= e($data['description']) ?></textarea>
              <div class="form-text"><span id="descCount">0</span> / 2000 characters</div>
            </div>

            <div class="mb-3">
              <label for="amenities" class="form-label">Amenities <span class="text-body-secondary">(separate with commas)</span></label>
              <input type="text" class="form-control" id="amenities" name="amenities" maxlength="255"
                     placeholder="Wi-Fi, Aircon, Laundry area" value="<?= e($data['amenities']) ?>">
            </div>

            <div>
              <label for="rules" class="form-label">House rules <span class="text-body-secondary">(optional)</span></label>
              <textarea class="form-control" id="rules" name="rules" rows="3" maxlength="1000"><?= e($data['rules']) ?></textarea>
            </div>
          </div>
        </div>

        <div class="card shadow-sm mb-4">
          <div class="card-body p-4">
            <h2 class="h5 mb-3">Location</h2>

            <div class="row g-3 mb-3">
              <div class="col-md-8">
                <label for="address" class="form-label">Street address</label>
                <input type="text" class="form-control" id="address" name="address" required maxlength="255"
                       value="<?= e($data['address']) ?>">
              </div>
              <div class="col-md-4">
                <label for="city" class="form-label">City</label>
                <input type="text" class="form-control" id="city" name="city" required maxlength="100"
                       value="<?= e($data['city']) ?>">
              </div>
            </div>

            <div class="d-flex flex-wrap align-items-center gap-2 mb-2">
              <button type="button" class="btn btn-outline-secondary btn-sm" id="findOnMap">
                <i class="bi bi-search" aria-hidden="true"></i> Find on map
              </button>
              <span class="small text-body-secondary" id="findMsg" aria-live="polite">The pin is placed automatically when you finish typing the address and city. You can then move it a little (within 300 m).</span>
            </div>
            <div id="map" class="map-picker mb-2" data-geocode-url="<?= e(url('api/geocode.php')) ?>" role="application" aria-label="Map for pinning the dorm location"></div>
            <input type="hidden" id="latitude" name="latitude" value="<?= e($data['latitude']) ?>">
            <input type="hidden" id="longitude" name="longitude" value="<?= e($data['longitude']) ?>">
            <div class="d-flex flex-wrap align-items-center gap-2">
              <button type="button" class="btn btn-outline-secondary btn-sm" id="locateBtn">
                <i class="bi bi-crosshair" aria-hidden="true"></i> Use my current location
              </button>
              <span class="small text-body-secondary" id="coordsText" aria-live="polite">No pin yet.</span>
            </div>
            <div class="small text-danger mt-1" id="locateMsg" role="alert"></div>
          </div>
        </div>

        <div class="card shadow-sm mb-4">
          <div class="card-body p-4">
            <h2 class="h5 mb-3">Photos</h2>

            <?php if ($photos): ?>
              <div class="row g-3 mb-3">
                <?php foreach ($photos as $photo): ?>
                  <div class="col-6 col-md-3">
                    <img class="photo-thumb mb-2" src="<?= e(url($photo['file_path'])) ?>"
                         alt="Current photo of <?= e($data['title']) ?>">
                    <div class="form-check">
                      <input class="form-check-input" type="radio" name="primary_photo"
                             id="primary<?= (int) $photo['id'] ?>" value="<?= (int) $photo['id'] ?>"
                             <?= $photo['is_primary'] ? 'checked' : '' ?>>
                      <label class="form-check-label small" for="primary<?= (int) $photo['id'] ?>">Main photo</label>
                    </div>
                    <div class="form-check">
                      <input class="form-check-input" type="checkbox" name="delete_photos[]"
                             id="delete<?= (int) $photo['id'] ?>" value="<?= (int) $photo['id'] ?>">
                      <label class="form-check-label small" for="delete<?= (int) $photo['id'] ?>">Remove</label>
                    </div>
                  </div>
                <?php endforeach; ?>
              </div>
            <?php endif; ?>

            <label for="photos" class="form-label">
              <?= $photos ? 'Add more photos' : 'Upload photos' ?>
            </label>
            <input type="file" class="form-control" id="photos" name="photos[]" multiple
                   accept="image/jpeg,image/png,image/webp" data-max-files="<?= (int) $photoSlotsLeft ?>"
                   aria-describedby="photoHelp">
            <div id="photoHelp" class="form-text">
              JPG, PNG, or WebP, up to 2 MB each, with a maximum of <?= (int) MAX_LISTING_PHOTOS ?> photos per listing.
              The first photo you upload becomes the main photo.
            </div>
            <div class="small text-danger mt-1" id="photoError" role="alert"></div>
            <div class="d-flex flex-wrap gap-2 mt-3" id="photoPreview"></div>
          </div>
        </div>

        <div class="d-flex gap-2">
          <button type="submit" class="btn btn-primary">
            <?= $editing ? 'Save changes' : 'Submit listing' ?>
          </button>
          <a class="btn btn-outline-secondary" href="<?= url('owner/my-listings.php') ?>">Cancel</a>
        </div>
      </form>
    </div>
  </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
