<?php

/**
 * Turns session.cookie_secure on the moment the app is actually served
 * over HTTPS, so nobody has to remember to flip it by hand in .user.ini
 * when this leaves localhost. Also switches SameSite from Lax to None
 * when HTTPS is active -- see below.
 *
 * MUST be called before session_start() — ini_set('session.cookie_secure')
 * has no effect once a session has already started, since that's when PHP
 * builds the Set-Cookie header from the current ini values. Backend/
 * config.php calls this as early as it can, but a number of entry files
 * call session_start() as literally their first line, before requiring
 * config.php/db.php at all — those aren't covered by this call, only by
 * writing new entry files in the safer order (config/db required first).
 *
 * This used to run via .user.ini's auto_prepend_file instead, which
 * doesn't have that ordering problem (it runs before any script code at
 * all) — but auto_prepend_file needs a real filesystem path, and .user.ini
 * is static text with no way to compute one portably: an absolute path is
 * specific to one machine (this is literally how the site went down —
 * it pointed at one teammate's Windows/XAMPP path) and a relative one
 * resolves against whatever the PHP process's working directory happens
 * to be, which isn't guaranteed to be this project's root either.
 *
 * Deliberately checks only Apache/mod_ssl's own $_SERVER['HTTPS'] (or
 * port 443) — NOT X-Forwarded-Proto or any other client-supplied header.
 * There is no reverse proxy in front of this app today; trusting a proxy
 * header without first pinning which upstream IP is allowed to set it
 * would let any client just claim "I'm on HTTPS" and silently break
 * every session instead of protecting it. If a reverse proxy is added
 * later, replace this with a check that validates REMOTE_ADDR against
 * that proxy first, then trusts its forwarded-proto header.
 *
 * (Railway's Docker image IS that later reverse-proxy case -- it sets
 * $_SERVER['HTTPS'] itself via the Apache-level X-Forwarded-Proto trust
 * in its own conf, specifically scoped to that deployment; see
 * Dockerfile. This function still only ever reads $_SERVER['HTTPS'], the
 * same as always -- it has no idea whether that came from a real TLS
 * handshake or a trusted proxy header, by design.)
 */
function apply_https_cookie_security(): void
{
    if (session_status() === PHP_SESSION_ACTIVE) {
        return; // too late — session already started, nothing to do
    }

    $isHttps = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
        || (($_SERVER['SERVER_PORT'] ?? null) === '443');

    if ($isHttps) {
        ini_set('session.cookie_secure', '1');

        // SameSite=Lax drops the session cookie on some cross-site
        // top-level navigations back from PayMongo's hosted checkout --
        // confirmed live: clicking PayMongo's own "Return to Merchant"
        // button lost the Treasury staff's session (landed on the login
        // page instead of the payment confirmation), while waiting for
        // PayMongo's own auto-redirect timer to fire did not. The two
        // paths likely differ in exactly how PayMongo performs that
        // redirect (e.g. a POST-based return vs. a plain GET), which
        // Lax treats differently -- POST-originated top-level navigations
        // are exactly the case Lax does NOT send the cookie for, unlike
        // a plain link/GET redirect. None is only safe with Secure also
        // set (browsers reject a None cookie without it), which only
        // happens together with this flag, right here.
        //
        // The CSRF protection this trades away (SameSite's own
        // cross-site-POST defense) isn't load-bearing here: every
        // state-changing endpoint already requires its own CSRF token
        // (Backend/csrf.php), which is the actual defense against that.
        ini_set('session.cookie_samesite', 'None');
    }
}
