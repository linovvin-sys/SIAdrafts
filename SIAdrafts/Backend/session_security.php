<?php
/**
 * Idle-session timeout, shared by require_role.php, require_student.php,
 * and require_professor.php — the three chokepoints every protected page
 * across all three auth systems already passes through — so this applies
 * everywhere without touching each individual endpoint file.
 */

const SESSION_IDLE_TIMEOUT_SECONDS = 1800; // 30 minutes of inactivity

/**
 * If the session has been idle longer than the timeout, tears it down
 * (mirrors logout.php's own unset/clear-cookie/destroy sequence) so the
 * caller's own "am I logged in?" check fails naturally right after this
 * runs. Otherwise just refreshes the activity timestamp.
 */
function session_touch_or_expire(int $maxIdleSeconds = SESSION_IDLE_TIMEOUT_SECONDS): void
{
    $now = time();
    $last = $_SESSION['last_activity'] ?? null;

    if ($last !== null && ($now - $last) > $maxIdleSeconds) {
        $_SESSION = [];
        if (ini_get('session.use_cookies')) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000, $params['path'], $params['domain'], $params['secure'], $params['httponly']);
        }
        session_destroy();
        return;
    }

    $_SESSION['last_activity'] = $now;
}
