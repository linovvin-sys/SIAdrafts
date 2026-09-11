<?php

// Auto-prepended ahead of every request (see auto_prepend_file in
// .user.ini) so this runs before any entry file's own session_start() —
// entry files call session_start() in inconsistent order relative to
// their own require_once list (e.g. login.php calls it before requiring
// db.php/config.php at all), so a check placed in config.php would
// often run too late to affect the cookie already being issued.
//
// Turns session.cookie_secure on the moment the app is actually served
// over HTTPS, so nobody has to remember to flip it by hand in .user.ini
// when this leaves localhost.
//
// Deliberately checks only Apache/mod_ssl's own $_SERVER['HTTPS'] (or
// port 443) — NOT X-Forwarded-Proto or any other client-supplied header.
// There is no reverse proxy in front of this app today; trusting a proxy
// header without first pinning which upstream IP is allowed to set it
// would let any client just claim "I'm on HTTPS" and silently break
// every session instead of protecting it. If a reverse proxy is added
// later, replace this with a check that validates REMOTE_ADDR against
// that proxy first, then trusts its forwarded-proto header.
$isHttps = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
    || (($_SERVER['SERVER_PORT'] ?? null) === '443');

if ($isHttps) {
    ini_set('session.cookie_secure', '1');
}
