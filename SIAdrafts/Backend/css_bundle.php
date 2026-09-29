<?php
/**
 * Concatenates a named group of local CSS files into one response, so a
 * page pays for one HTTP round-trip instead of one per file (the Admin/
 * Registrar/Admission shared header previously loaded 6 separate
 * stylesheets on every single page load). Regenerates automatically
 * whenever any source file's mtime changes (baked into the cache
 * filename, and into the ?v= the browser sees -- see css_bundle_url() in
 * css_bundle_config.php), so editing a source CSS file needs no separate
 * build step.
 */

require_once __DIR__ . '/css_bundle_config.php';

$name = $_GET['name'] ?? '';
if (!isset(CSS_BUNDLES[$name])) {
    http_response_code(404);
    exit;
}

$files = CSS_BUNDLES[$name];
foreach ($files as $f) {
    if (!is_file($f)) {
        http_response_code(500);
        exit;
    }
}

$hash = css_bundle_hash($name);
$cacheDir = __DIR__ . '/../Frontend/Css/.cache';
$cacheFile = $cacheDir . "/{$name}-{$hash}.css";

header('Content-Type: text/css; charset=UTF-8');
// Safe to cache forever: the URL's ?v= (see css_bundle_url()) changes the
// moment any source file's mtime does, so a stale cached copy is never
// served under the URL a page currently links to.
header('Cache-Control: public, max-age=31536000, immutable');

if (is_file($cacheFile)) {
    readfile($cacheFile);
    exit;
}

$css = '';
foreach ($files as $f) {
    $chunk = file_get_contents($f);
    // tokens.css is inlined first (above), so admin.css's own @import of
    // it would otherwise pull it in a second time via a separate blocking
    // request once served from this different URL path (a relative
    // @import resolves against the RESPONSE's URL, not the original
    // file's folder).
    if (basename($f) === 'admin.css') {
        $chunk = preg_replace('/@import\s+url\(["\']\.\.\/tokens\.css["\']\)\s*;?/', '', $chunk, 1);
    }
    $css .= "/* ---- " . basename($f) . " ---- */\n" . $chunk . "\n";
}

if (!is_dir($cacheDir)) {
    @mkdir($cacheDir, 0755, true);
}
@file_put_contents($cacheFile, $css);

echo $css;
