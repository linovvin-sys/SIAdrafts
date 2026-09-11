<?php

// Complements rate_limit.php's per-IP throttle, which only ever answers
// "how many attempts recently from this IP" -- that does nothing against
// credential stuffing spread across many IPs at the SAME account. This
// checks the other axis: how many recent failures does THIS identifier
// have, regardless of source IP. Built on login_attempt (see
// Backend/login_attempt.php) rather than its own storage, since that
// table already records exactly what this needs.
function account_locked_out(
    mysqli $conn,
    string $loginType,
    string $identifier,
    int $maxFailures = 5,
    int $windowSeconds = 900
): bool {
    $stmt = $conn->prepare(
        "SELECT COUNT(*) AS failures
         FROM login_attempt
         WHERE login_type = ?
           AND identifier = ?
           AND success = 0
           AND created_at > (NOW() - INTERVAL ? SECOND)"
    );

    if (!$stmt) {
        error_log('account_locked_out: prepare failed: ' . $conn->error);
        return false; // fail open rather than lock out real users on a DB hiccup
    }

    $stmt->bind_param('ssi', $loginType, $identifier, $windowSeconds);
    $stmt->execute();
    $failures = (int)($stmt->get_result()->fetch_assoc()['failures'] ?? 0);
    $stmt->close();

    return $failures >= $maxFailures;
}
