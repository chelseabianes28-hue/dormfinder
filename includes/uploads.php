<?php
// Safe image upload helpers for listing photos.

const UPLOAD_MAX_BYTES    = 2 * 1024 * 1024; // 2 MB per photo
const MAX_LISTING_PHOTOS  = 5;
const UPLOAD_DIR_REL      = 'uploads/listings';

function upload_dir_path(): string
{
    return dirname(__DIR__) . '/' . UPLOAD_DIR_REL;
}

/**
 * Check every uploaded file in $_FILES[$field]. Returns ['files' => [...], 'errors' => [...]].
 * Nothing is saved yet; save_uploaded_image() does that after everything is valid.
 */
function collect_uploaded_images(string $field, int $slotsLeft): array
{
    $result = ['files' => [], 'errors' => []];

    if (empty($_FILES[$field]) || !is_array($_FILES[$field]['name'])) {
        return $result;
    }

    $mimeToExt = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'];
    $finfo     = new finfo(FILEINFO_MIME_TYPE);
    $total     = count($_FILES[$field]['name']);
    $picked    = 0;

    for ($i = 0; $i < $total; $i++) {
        $error = $_FILES[$field]['error'][$i];
        if ($error === UPLOAD_ERR_NO_FILE) {
            continue;
        }
        $picked++;
        $name = $_FILES[$field]['name'][$i];

        if ($error === UPLOAD_ERR_INI_SIZE || $error === UPLOAD_ERR_FORM_SIZE) {
            $result['errors'][] = '"' . $name . '" is too large (maximum 2 MB).';
            continue;
        }
        if ($error !== UPLOAD_ERR_OK) {
            $result['errors'][] = '"' . $name . '" could not be uploaded. Please try again.';
            continue;
        }

        $tmp = $_FILES[$field]['tmp_name'][$i];
        if (!is_uploaded_file($tmp)) {
            $result['errors'][] = '"' . $name . '" is not a valid upload.';
            continue;
        }
        if ($_FILES[$field]['size'][$i] > UPLOAD_MAX_BYTES) {
            $result['errors'][] = '"' . $name . '" is too large (maximum 2 MB).';
            continue;
        }

        // Check the real file content, not the file name the browser reports.
        $mime = $finfo->file($tmp);
        if (!isset($mimeToExt[$mime]) || @getimagesize($tmp) === false) {
            $result['errors'][] = '"' . $name . '" is not a valid JPG, PNG, or WebP image.';
            continue;
        }

        $result['files'][] = ['tmp' => $tmp, 'ext' => $mimeToExt[$mime]];
    }

    if ($picked > $slotsLeft) {
        $message = $slotsLeft > 0
            ? 'You can add only ' . $slotsLeft . ' more photo(s).'
            : 'This listing already has the maximum of ' . MAX_LISTING_PHOTOS . ' photos.';
        return ['files' => [], 'errors' => array_merge($result['errors'], [$message])];
    }

    return $result;
}

// Move a validated file into uploads/listings with a random name. Returns the relative path.
function save_uploaded_image(array $file): string
{
    $dir = upload_dir_path();
    if (!is_dir($dir) && !mkdir($dir, 0755, true)) {
        throw new RuntimeException('Could not create the upload folder.');
    }

    $filename = bin2hex(random_bytes(16)) . '.' . $file['ext'];
    if (!move_uploaded_file($file['tmp'], $dir . '/' . $filename)) {
        throw new RuntimeException('Could not save the uploaded file.');
    }

    return UPLOAD_DIR_REL . '/' . $filename;
}

// Delete a photo file, but only if it really lives inside the uploads folder.
function delete_photo_file(string $relativePath): void
{
    $uploadDir = realpath(upload_dir_path());
    $file      = realpath(dirname(__DIR__) . '/' . $relativePath);

    if ($uploadDir && $file && strpos($file, $uploadDir) === 0 && is_file($file)) {
        @unlink($file);
    }
}
