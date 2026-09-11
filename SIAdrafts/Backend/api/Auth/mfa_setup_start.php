<?php
session_start();
require_once '../../db.php';
require_once '../../session_security.php';
require_once '../../totp.php';
require_once '../../csrf.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed.']);
    exit;
}

csrf_verify();

// MFA setup is identical for a staff account or a professor account, but
// they're still two separate id spaces (see staff_mfa's migration) — this
// resolves which one is actually signed in rather than merging the two
// otherwise-isolated auth systems into one check.
if (!empty($_SESSION['user_id']) && !empty($_SESSION['role_name'])) {
    session_touch_or_expire();
    $loginType = 'staff';
    $accountId = (int)$_SESSION['user_id'];
    $label     = $_SESSION['username'] ?? 'staff';
} elseif (!empty($_SESSION['professor_id'])) {
    session_touch_or_expire();
    $loginType = 'professor';
    $accountId = (int)$_SESSION['professor_id'];
    $label     = $_SESSION['username'] ?? 'professor';
} else {
    http_response_code(401);
    echo json_encode(['error' => 'Please log in.']);
    exit;
}

$db   = new Database();
$conn = $db->connect();

$existing = $conn->prepare("SELECT enabled FROM staff_mfa WHERE login_type = ? AND account_id = ?");
$existing->bind_param('si', $loginType, $accountId);
$existing->execute();
$row = $existing->get_result()->fetch_assoc();
$existing->close();

if ($row && (int)$row['enabled'] === 1) {
    echo json_encode(['error' => 'MFA is already enabled on this account. Disable it first to re-enroll.']);
    exit;
}

$secret = totp_generate_secret();

// Overwrites any earlier, never-confirmed secret for this account —
// enabled stays 0 until mfa_setup_confirm.php proves the user actually
// captured this new one in their authenticator app.
$stmt = $conn->prepare(
    "INSERT INTO staff_mfa (login_type, account_id, secret, enabled)
     VALUES (?, ?, ?, 0)
     ON DUPLICATE KEY UPDATE secret = VALUES(secret), enabled = 0, recovery_codes = NULL"
);
$stmt->bind_param('sis', $loginType, $accountId, $secret);
$stmt->execute();
$stmt->close();
$db->close();

echo json_encode([
    'success'        => true,
    'secret'         => $secret,
    'secret_grouped' => trim(chunk_split($secret, 4, ' ')),
    'otpauth_uri'    => totp_qr_uri($secret, $label),
]);
