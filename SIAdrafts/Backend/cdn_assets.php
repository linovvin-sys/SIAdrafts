<?php

// Centralizes every third-party script/stylesheet this app loads from a
// CDN, pinned to an exact version with a Subresource Integrity hash
// computed directly from the downloaded file (not copied from a
// third-party page). The CSP header (see .htaccess) already restricts
// *which domains* can serve a script; SRI is the other half — it makes
// the browser refuse to run anything from an allowed domain whose bytes
// don't match what's expected, so a compromised CDN or a MITM on an
// allowed host can no longer silently swap in different code.
//
// One place to bump a version + hash instead of re-typing (and risking
// a typo in) both across every view that loads it.

const CDN_JQUERY             = 'https://code.jquery.com/jquery-3.7.1.min.js';
const CDN_DATATABLES_JS      = 'https://cdn.datatables.net/1.13.8/js/jquery.dataTables.min.js';
const CDN_DATATABLES_BS5_JS  = 'https://cdn.datatables.net/1.13.8/js/dataTables.bootstrap5.min.js';
const CDN_DATATABLES_BS5_CSS = 'https://cdn.datatables.net/1.13.8/css/dataTables.bootstrap5.min.css';
const CDN_BOOTSTRAP_ICONS_CSS = 'https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css';
const CDN_SWEETALERT2        = 'https://cdn.jsdelivr.net/npm/sweetalert2@11.26.25/dist/sweetalert2.all.min.js';
const CDN_CHARTJS            = 'https://cdn.jsdelivr.net/npm/chart.js@4.5.1/dist/chart.umd.min.js';
const CDN_VUE                = 'https://cdn.jsdelivr.net/npm/vue@3.5.42/dist/vue.global.prod.js';
const CDN_ICONIFY            = 'https://code.iconify.design/iconify-icon/3.0.0/iconify-icon.min.js';

const CDN_INTEGRITY = [
    CDN_JQUERY             => 'sha384-1H217gwSVyLSIfaLxHbE7dRb3v4mYCKbpQvzx0cegeju1MVsGrX5xXxAvs/HgeFs',
    CDN_DATATABLES_JS      => 'sha384-Udt767MMeKelGRBxaCfxX88YDLbViYdQ7T/gkRoB197Jf+OviZ+lsaRAOpS/MIjf',
    CDN_DATATABLES_BS5_JS  => 'sha384-PgPBH0hy6DTJwu7pTf6bkRqPlf/+pjUBExpr/eIfzszlGYFlF9Wi9VTAJODPhgCO',
    CDN_DATATABLES_BS5_CSS => 'sha384-ok3J6xA9oQqai5C9ytYveFsBeKgoGk4T+NExsr6hoIKjZdv9SJcmx2mafwUWRNf9',
    CDN_BOOTSTRAP_ICONS_CSS => 'sha384-XGjxtQfXaH2tnPFa9x+ruJTuLE3Aa6LhHSWRr1XeTyhezb4abCG4ccI5AkVDxqC+',
    CDN_SWEETALERT2        => 'sha384-nLoOnA/BDh8A/jxqtckg4DumuCGOBYUnNJLZdQz/zfYNp3wcjGSoWTAzgko06G/2',
    CDN_CHARTJS            => 'sha384-jb8JQMbMoBUzgWatfe6COACi2ljcDdZQ2OxczGA3bGNeWe+6DChMTBJemed7ZnvJ',
    CDN_VUE                => 'sha384-bqTg8NKlUS8kbe8Sak41ZAg1OAW/cc4kyI+FqMhCpzsyG7B39KgZfDOKA3HnKhCV',
    CDN_ICONIFY            => 'sha384-Hj2pe/JEGitdWiVWNPuXWhAdYtdM+ZO+s0KuZHaAk1oAbeAtNas7R6hKlmbInTM4',
];

// A handful of views still pass an older floating-version URL (e.g.
// vue@3, sweetalert2@11) into $extraScripts. Resolving those here too
// means the version pin + SRI protection applies everywhere this asset
// loads without having to hunt down and edit every call site.
const CDN_LEGACY_ALIASES = [
    'https://cdn.jsdelivr.net/npm/vue@3/dist/vue.global.prod.js' => CDN_VUE,
    'https://cdn.jsdelivr.net/npm/sweetalert2@11'                => CDN_SWEETALERT2,
    'https://cdn.jsdelivr.net/npm/chart.js@4'                    => CDN_CHARTJS,
];

/** Renders a <script> tag, adding integrity+crossorigin automatically for any known CDN URL. */
function cdn_script_tag(string $url): string
{
    $url = CDN_LEGACY_ALIASES[$url] ?? $url;
    $integrity = CDN_INTEGRITY[$url] ?? null;

    $html = '<script src="' . htmlspecialchars($url, ENT_QUOTES) . '"';
    if ($integrity) {
        $html .= ' integrity="' . htmlspecialchars($integrity, ENT_QUOTES) . '" crossorigin="anonymous"';
    }
    return $html . '></script>';
}

/** Renders a <link rel="stylesheet"> tag, same integrity handling as cdn_script_tag(). */
function cdn_style_tag(string $url): string
{
    $url = CDN_LEGACY_ALIASES[$url] ?? $url;
    $integrity = CDN_INTEGRITY[$url] ?? null;

    $html = '<link rel="stylesheet" href="' . htmlspecialchars($url, ENT_QUOTES) . '"';
    if ($integrity) {
        $html .= ' integrity="' . htmlspecialchars($integrity, ENT_QUOTES) . '" crossorigin="anonymous"';
    }
    return $html . ' />';
}
