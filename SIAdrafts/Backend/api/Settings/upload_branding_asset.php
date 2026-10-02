<?php
/**
 * Lets an Admin/Head Registrar upload a replacement logo or favicon --
 * the file-based counterpart to update_setting.php (which only ever
 * handles plain text values). Saves to Frontend/assets/branding/ (a
 * public, directly-servable path -- a logo/favicon has to be embeddable
 * as a plain <img src>/<link href>, unlike Backend/uploads' documents,
 * which are deliberately gated behind a PHP script) and records the
 * resulting path via the same settings table school_branding.php reads.
 */
require_once __DIR__ . '/../../../Backend/session_bootstrap.php';
app_session_start();
header('Content-Type: application/json');

require_once '../../db.php';
require_once '../../roles.php';
require_once '../../require_role.php';
require_once '../../csrf.php';
require_once '../../settings.php';

if (empty($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized.']);
    exit;
}

require_role([ROLE_ADMIN, ROLE_HEAD_REGISTRAR], true);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed.']);
    exit;
}

csrf_verify();

$assetType = $_POST['asset_type'] ?? '';
$settingKeyByType = [
    'logo'    => 'school_logo_path',
    'favicon' => 'school_favicon_path',
];
if (!isset($settingKeyByType[$assetType])) {
    http_response_code(422);
    echo json_encode(['error' => 'Invalid asset type.']);
    exit;
}

if (empty($_FILES['file']) || $_FILES['file']['error'] !== UPLOAD_ERR_OK) {
    http_response_code(422);
    echo json_encode(['error' => 'No file uploaded, or the upload failed.']);
    exit;
}

$tmpPath = $_FILES['file']['tmp_name'];

// A favicon is realistically .ico/.png/.svg; a logo is realistically
// .png/.jpg/.svg -- allowing the same set for both rather than two
// separate whitelists, since either is a reasonable choice for either
// slot and a stricter split buys nothing real here.
$mime = mime_content_type($tmpPath);
$allowedExtByMime = [
    'image/svg+xml' => 'svg',
    'image/png'     => 'png',
    'image/jpeg'    => 'jpg',
    'image/x-icon'  => 'ico',
    'image/vnd.microsoft.icon' => 'ico',
];
if (!isset($allowedExtByMime[$mime])) {
    http_response_code(422);
    echo json_encode(['error' => 'File must be an SVG, PNG, JPG, or ICO image.']);
    exit;
}

if (filesize($tmpPath) > 2 * 1024 * 1024) {
    http_response_code(422);
    echo json_encode(['error' => 'File is too large (max 2MB) -- a logo/favicon should be a small, simple image.']);
    exit;
}

// SVG specifically can carry a <script> -- strip it down to safe markup
// rather than trusting an uploaded file to not be a stored-XSS vector the
// moment it's served back inline to every single page's <head>.
$ext = $allowedExtByMime[$mime];
if ($ext === 'svg') {
    $svg = file_get_contents($tmpPath);
    $svg = preg_replace('#<script\b[^>]*>.*?</script>#is', '', $svg);
    $svg = preg_replace('/\son[a-z]+\s*=\s*(".*?"|\'.*?\')/is', '', $svg);
    if ($svg === null || stripos($svg, '<svg') === false) {
        http_response_code(422);
        echo json_encode(['error' => 'That SVG file could not be read.']);
        exit;
    }
    file_put_contents($tmpPath, $svg);
}

// Backend/uploads/, not Frontend/assets/ -- the former has a persistent
// volume mounted on Railway (see branding_asset.php's own comment); the
// latter doesn't, which is exactly the bug that silently wiped applicant
// document uploads on every redeploy before that volume was added.
$uploadDir = __DIR__ . '/../../uploads/branding/';
if (!is_dir($uploadDir)) {
    mkdir($uploadDir, 0755, true);
}
$storedName = $assetType . '_' . bin2hex(random_bytes(8)) . '.' . $ext;
if (!move_uploaded_file($tmpPath, $uploadDir . $storedName)) {
    http_response_code(500);
    echo json_encode(['error' => 'Could not save the uploaded file.']);
    exit;
}

// Just the filename, not a path -- Backend/uploads/ is .htaccess-blocked
// from direct access, so nothing should ever construct a browser-facing
// URL out of this value. branding_asset.php is the only reader, and it
// already knows which subdirectory to look in.
set_setting($settingKeyByType[$assetType], $storedName, (int)$_SESSION['user_id']);

$publicPath = '/SIAdrafts/Backend/branding_asset.php?type=' . urlencode($assetType);
echo json_encode(['success' => true, 'path' => $publicPath]);
