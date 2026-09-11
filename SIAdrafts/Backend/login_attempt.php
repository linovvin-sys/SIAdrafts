<?php

// Records who/when/from where/success-or-fail for every real login
// attempt, so an incident can actually be reconstructed afterwards --
// rate_limit.php only ever answers "how many recently from this IP".
//
// Deliberately best-effort: a logging failure (e.g. the migration hasn't
// been run yet) must never block or break an actual login, so errors are
// swallowed here rather than surfaced to the caller.
function log_login_attempt(
    mysqli $conn,
    string $loginType,
    string $identifier,
    bool $success,
    ?int $accountId = null
): void {
    $stmt = $conn->prepare(
        "INSERT INTO login_attempt (login_type, identifier, success, account_id, ip_address, user_agent)
         VALUES (?, ?, ?, ?, ?, ?)"
    );

    if (!$stmt) {
        error_log('log_login_attempt: prepare failed: ' . $conn->error);
        return;
    }

    $successInt = $success ? 1 : 0;
    $ip         = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    $userAgent  = substr($_SERVER['HTTP_USER_AGENT'] ?? '', 0, 255);

    $stmt->bind_param('ssiiss', $loginType, $identifier, $successInt, $accountId, $ip, $userAgent);
    $stmt->execute();
    $stmt->close();
}
