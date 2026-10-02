<?php
/**
 * Public, unauthenticated passthrough for an uploaded logo/favicon --
 * every page's <img>/<link> references this fixed URL rather than the
 * real stored path, which lives under Backend/uploads/branding/
 * (deliberately .htaccess-blocked from direct access, same as every
 * other upload in this app) because that's the one path with a
 * persistent volume mounted on Railway. Unlike document downloads
 * (view_requirement_document.php), this needs no login check -- a
 * school's logo and favicon are meant to be public, the same as any
 * other static asset this app already serves straight from Frontend/
 * assets/.
 *
 * Takes `type` (logo|favicon), never a raw filename/path from the
 * client -- the real filename is looked up from the settings table,
 * so there's no path-traversal surface here at all.
 */
require_once __DIR__ . '/settings.php';

$type = $_GET['type'] ?? '';
$settingKeyByType = [
    'logo'    => 'school_logo_path',
    'favicon' => 'school_favicon_path',
];
if (!isset($settingKeyByType[$type])) {
    http_response_code(404);
    exit;
}

// Stored as the public-facing URL this same file used to be reachable
// at before the passthrough existed (/SIAdrafts/Backend/uploads/
// branding/<name>) -- basename() here both recovers the real filename
// and, as a side effect, strips any directory traversal a corrupted
// setting value might otherwise carry.
$storedUrl = get_setting($settingKeyByType[$type]);
if (!$storedUrl) {
    http_response_code(404);
    exit;
}
$fileName = basename(parse_url($storedUrl, PHP_URL_PATH) ?? '');
$fullPath = __DIR__ . '/uploads/branding/' . $fileName;

if ($fileName === '' || !is_file($fullPath)) {
    http_response_code(404);
    exit;
}

$ext = strtolower(pathinfo($fullPath, PATHINFO_EXTENSION));
$mimeByExt = [
    'svg' => 'image/svg+xml',
    'png' => 'image/png',
    'jpg' => 'image/jpeg',
    'jpeg' => 'image/jpeg',
    'ico' => 'image/x-icon',
];
$contentType = $mimeByExt[$ext] ?? 'application/octet-stream';

header('Content-Type: ' . $contentType);
header('Content-Length: ' . filesize($fullPath));
// A new upload gets a brand new random filename (see
// upload_branding_asset.php), so aggressively caching the OLD filename
// forever is safe -- a changed logo is a changed URL by construction,
// never a stale cache of the same one.
header('Cache-Control: public, max-age=31536000, immutable');
header('X-Content-Type-Options: nosniff');
readfile($fullPath);
