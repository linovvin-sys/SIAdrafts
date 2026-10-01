<?php
/**
 * Replaces a bare session_start() at the top of every entry file. Fixes a
 * real gap: Backend/config.php's apply_https_cookie_security() only works
 * when it runs BEFORE session_start(), but nearly every entry file called
 * session_start() as its literal first statement, before config.php was
 * ever required -- so in production over HTTPS, the session cookie never
 * actually got the Secure flag on those files, despite the ini-level
 * setting existing and working correctly everywhere it had a chance to run.
 *
 * Deliberately has zero dependency on config.php/Dotenv -- env_security.php
 * is a single standalone function with no requires of its own -- so this
 * stays just as cheap as a bare session_start() call was, with nothing
 * pulled in early that the file wasn't already going to load via its own
 * subsequent require_once of db.php/csrf.php/etc.
 */
require_once __DIR__ . '/env_security.php';

function app_session_start(): void
{
    apply_https_cookie_security();
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
}
