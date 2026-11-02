<?php
// Safe profile photo upload: checks the file, crops it to a square, and saves a small JPEG.

const AVATAR_DIR_REL   = 'uploads/avatars';
const AVATAR_MAX_BYTES = 2 * 1024 * 1024;   // 2 MB
const AVATAR_SIZE      = 400;               // saved as 400 x 400 pixels

function avatar_dir_path(): string
{
    return dirname(__DIR__) . '/' . AVATAR_DIR_REL;
}

/**
 * Validate and save an uploaded profile photo. Returns the saved path (for example "uploads/avatars/abc.jpg").
 * Throws RuntimeException with a message that is safe to show to the user.
 */
function process_avatar_upload(array $file): string
{
    $error = $file['error'] ?? UPLOAD_ERR_NO_FILE;
    if ($error === UPLOAD_ERR_NO_FILE) {
        throw new RuntimeException('Please choose a photo first.');
    }
    if ($error === UPLOAD_ERR_INI_SIZE || $error === UPLOAD_ERR_FORM_SIZE) {
        throw new RuntimeException('That photo is too large. The maximum is 2 MB.');
    }
    if ($error !== UPLOAD_ERR_OK) {
        throw new RuntimeException('The photo could not be uploaded. Please try again.');
    }

    $tmp = $file['tmp_name'] ?? '';
    if (!is_uploaded_file($tmp)) {
        throw new RuntimeException('That is not a valid upload.');
    }
    if (($file['size'] ?? 0) > AVATAR_MAX_BYTES) {
        throw new RuntimeException('That photo is too large. The maximum is 2 MB.');
    }

    // Check the real file content, not the name the browser reports.
    $mimeToExt = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'];
    $mime = (new finfo(FILEINFO_MIME_TYPE))->file($tmp);
    $info = @getimagesize($tmp);
    if (!isset($mimeToExt[$mime]) || $info === false) {
        throw new RuntimeException('Please choose a JPG, PNG, or WebP image.');
    }
    [$width, $height] = $info;
    if ($width < 50 || $height < 50 || $width * $height > 25000000) {
        throw new RuntimeException('That image is too small or too big in pixels.');
    }

    $dir = avatar_dir_path();
    if (!is_dir($dir) && !mkdir($dir, 0755, true)) {
        throw new RuntimeException('The upload folder could not be created.');
    }
    $name = bin2hex(random_bytes(16));

    // Preferred: re-draw the image as a square JPEG. This also removes any hidden data in the file.
    $loaders = ['image/jpeg' => 'imagecreatefromjpeg', 'image/png' => 'imagecreatefrompng', 'image/webp' => 'imagecreatefromwebp'];
    $loader  = $loaders[$mime];
    if (extension_loaded('gd') && function_exists($loader)) {
        $source = @$loader($tmp);
        if ($source !== false) {
            $side = min($width, $height);
            $canvas = imagecreatetruecolor(AVATAR_SIZE, AVATAR_SIZE);
            imagefill($canvas, 0, 0, imagecolorallocate($canvas, 255, 255, 255));
            imagecopyresampled($canvas, $source, 0, 0, (int) (($width - $side) / 2), (int) (($height - $side) / 2),
                AVATAR_SIZE, AVATAR_SIZE, $side, $side);
            if (imagejpeg($canvas, $dir . '/' . $name . '.jpg', 85)) {
                return AVATAR_DIR_REL . '/' . $name . '.jpg';
            }
        }
    }

    // Fallback when the image library is not available: keep the checked original.
    $target = $name . '.' . $mimeToExt[$mime];
    if (!move_uploaded_file($tmp, $dir . '/' . $target)) {
        throw new RuntimeException('The photo could not be saved. Please try again.');
    }
    return AVATAR_DIR_REL . '/' . $target;
}

// Delete a saved photo, but only if it really lives inside uploads/avatars.
function delete_avatar_file(string $relativePath): void
{
    $folder = realpath(avatar_dir_path());
    $file   = realpath(dirname(__DIR__) . '/' . $relativePath);
    if ($folder && $file && strpos($file, $folder) === 0 && is_file($file)) {
        @unlink($file);
    }
}
