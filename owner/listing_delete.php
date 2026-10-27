<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/uploads.php';
require_role('owner');

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !verify_csrf()) {
    set_flash('danger', 'Invalid request. Please try again.');
    redirect('owner/my-listings.php');
}

$user = current_user();
$id   = (int) ($_POST['id'] ?? 0);

try {
    $pdo = getDB();

    // Only the owner of the listing can delete it.
    $photoStmt = $pdo->prepare(
        'SELECT lp.file_path FROM listing_photos lp
           JOIN listings l ON l.id = lp.listing_id
          WHERE l.id = ? AND l.owner_id = ?'
    );
    $photoStmt->execute([$id, $user['id']]);
    $paths = $photoStmt->fetchAll(PDO::FETCH_COLUMN);

    $delete = $pdo->prepare('DELETE FROM listings WHERE id = ? AND owner_id = ?');
    $delete->execute([$id, $user['id']]);

    if ($delete->rowCount() > 0) {
        foreach ($paths as $path) {
            delete_photo_file($path);
        }
        set_flash('success', 'Listing deleted.');
    } else {
        set_flash('danger', 'Listing not found.');
    }
} catch (PDOException $ex) {
    error_log('Delete listing failed: ' . $ex->getMessage());
    set_flash('danger', 'Could not delete the listing. Please try again.');
}

redirect('owner/my-listings.php');
