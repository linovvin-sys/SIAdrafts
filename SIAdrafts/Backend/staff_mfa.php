<?php

// DB-facing helpers shared between login.php, verify_mfa.php, and the
// mfa_setup_*/mfa_disable endpoints. Kept separate from totp.php (pure
// algorithm, no DB dependency) so the crypto can be unit-tested in
// isolation from the schema.

function staff_mfa_enabled(mysqli $conn, string $loginType, int $accountId): bool
{
    $stmt = $conn->prepare(
        "SELECT 1 FROM staff_mfa WHERE login_type = ? AND account_id = ? AND enabled = 1"
    );
    $stmt->bind_param('si', $loginType, $accountId);
    $stmt->execute();
    $found = (bool)$stmt->get_result()->fetch_assoc();
    $stmt->close();

    return $found;
}
