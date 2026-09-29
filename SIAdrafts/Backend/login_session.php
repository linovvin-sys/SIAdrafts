<?php
/**
 * Shared "grant the real session" step for every login success path --
 * staff, professor, and student alike. Regenerates the session id
 * (fixation defense), stamps the caller's session fields plus a fresh
 * tab_token, records last_login, and logs the attempt. Previously this
 * exact sequence was repeated four times: login.php's staff branch,
 * login.php's professor branch, and verify_mfa.php's finish_pending_login()
 * staff/professor branches -- differing only in which session fields,
 * table, and id column apply.
 */

require_once __DIR__ . '/login_attempt.php';

/**
 * $table/$idCol are always one of a small set of fixed, hardcoded
 * literals chosen by the caller -- never derived from client input --
 * before being interpolated into the UPDATE below.
 */
function issue_login_session(mysqli $conn, array $sessionFields, string $table, string $idCol, int $accountId, string $loginType, string $identifier): void
{
    session_regenerate_id(true);

    foreach ($sessionFields as $key => $value) {
        $_SESSION[$key] = $value;
    }
    $_SESSION['tab_token'] = bin2hex(random_bytes(16));

    $stmt = $conn->prepare("UPDATE `$table` SET last_login = NOW() WHERE `$idCol` = ?");
    $stmt->bind_param('i', $accountId);
    $stmt->execute();
    $stmt->close();

    log_login_attempt($conn, $loginType, $identifier, true, $accountId);
}
