<?php
require_once __DIR__ . '/settings.php';

/**
 * Single source of truth for this deployment's identity -- school name,
 * logo, favicon. Backed by the existing generic settings table (no new
 * schema needed), with hardcoded defaults so an install that's never
 * touched branding still renders exactly as it always has.
 *
 * This exists because every one of those three things used to be a
 * literal "EduSchool" string / crest.svg path baked into ~15 files --
 * fine for one school, but it meant selling this system to a second
 * school meant a developer manually editing code and redeploying for
 * every sale. Admin/settings.php now writes these same setting keys
 * through the existing update_setting.php / upload_branding_asset.php
 * endpoints, so a client can rebrand it themselves.
 *
 * @return array{name:string, logo:string, favicon:string}
 */
function get_school_branding(): array
{
    static $cache = null;
    if ($cache !== null) {
        return $cache;
    }

    // An uploaded logo/favicon is stored under Backend/uploads/branding/
    // (the one path with a persistent volume mounted on Railway -- see
    // upload_branding_asset.php's own comment) and served back out only
    // through branding_asset.php, never as a direct path to that
    // deliberately web-blocked directory. The default (nothing uploaded
    // yet) stays a plain static path -- that file is committed to git,
    // not uploaded, so it's never at risk of vanishing on a redeploy the
    // way an uploaded file in a non-persisted path would be.
    //
    // branding_asset.php's URL includes a short hash of the real stored
    // filename (the setting value itself, e.g. "logo_a1b2c3d4e5f6.png")
    // as a cache-busting query param -- without this, the URL would stay
    // byte-for-byte identical ("?type=logo") across a re-upload even
    // though the file behind it changed, which combined with the long
    // immutable cache lifetime branding_asset.php sets would mean every
    // browser that had ever loaded the old logo keeps showing it,
    // forever, until a hard refresh.
    $logoFile = get_setting('school_logo_path');
    $faviconFile = get_setting('school_favicon_path');

    return $cache = [
        'name'    => get_setting('school_name') ?: 'EduSchool',
        'logo'    => $logoFile ? '/SIAdrafts/Backend/branding_asset.php?type=logo&v=' . substr(md5($logoFile), 0, 8) : '/SIAdrafts/Frontend/assets/crest.svg',
        'favicon' => $faviconFile ? '/SIAdrafts/Backend/branding_asset.php?type=favicon&v=' . substr(md5($faviconFile), 0, 8) : '/SIAdrafts/Frontend/assets/crest.svg',
    ];
}
