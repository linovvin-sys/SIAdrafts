<?php
/**
 * Shared by css_bundle.php (which serves a bundle) and any page that needs
 * to build a cache-busting URL for one, so the file list and the hashing
 * logic can't drift out of sync between the two.
 */

const CSS_BUNDLES = [
    'admin' => [
        __DIR__ . '/../Frontend/Css/tokens.css',
        __DIR__ . '/../Frontend/Css/Admin/admin.css',
        __DIR__ . '/../Frontend/Css/Admin/schedule.css',
        __DIR__ . '/../Frontend/Css/Admin/modal.css',
        __DIR__ . '/../Frontend/Css/Registrar/registrar.css',
        __DIR__ . '/../Frontend/Css/required.css',
        __DIR__ . '/../Frontend/Css/Admin/dotty-staff.css',
    ],
];

/** A short hash that changes the instant any file in the bundle is edited. */
function css_bundle_hash(string $name): ?string
{
    if (!isset(CSS_BUNDLES[$name])) {
        return null;
    }
    $mtimes = array_map('filemtime', CSS_BUNDLES[$name]);
    return substr(md5(implode(',', $mtimes)), 0, 12);
}

/**
 * The public URL for a bundle, with a ?v= query string baked from its
 * current hash -- this is what actually busts the browser cache when a
 * source file changes, since the far-future Cache-Control on css_bundle.php
 * only works because the URL itself changes too.
 */
function css_bundle_url(string $name): string
{
    $hash = css_bundle_hash($name) ?? 'nohash';
    return '/SIAdrafts/Backend/css_bundle.php?name=' . urlencode($name) . '&v=' . $hash;
}
