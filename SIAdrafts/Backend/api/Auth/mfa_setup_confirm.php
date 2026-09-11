<?php
session_start();
require_once '../../db.php';
require_once '../../session_security.php';
require_once '../../totp.php';
require_once '../../rate_limit.php';
require_once '../../csrf.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed.']);
    exit;
}

csrf_verify();

if (!empty($_SESSION['user_id']) && !empty($_SESSION['role_name'])) {
    session_touch_or_expire();
    $loginType = 'staff';
    $accountId = (int)$_SESSION['user_id'];
} elseif (!empty($_SESSION['professor_id'])) {
    session_touch_or_expire();
    $loginType = 'professor';
    $accountId = (int)$_SESSION['professor_id'];
} else {
    http_response_code(401);
    echo json_encode(['error' => 'Please log in.']);
    exit;
}

if (!rate_limit_check('mfa_setup_confirm', 10, 300)) {
    http_response_code(429);
    echo json_encode(['error' => 'Too many attempts. Please wait a few minutes and try again.']);
    exit;
}

$code = trim($_POST['code'] ?? '');

$db   = new Database();
$conn = $db->connect();

$stmt = $conn->prepare("SELECT secret, enabled FROM staff_mfa WHERE login_type = ? AND account_id = ?");
$stmt->bind_param('si', $loginType, $accountId);
$stmt->execute();
$row = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$row || (int)$row['enabled'] === 1) {
    echo json_encode(['error' => 'No pending MFA setup found. Start setup again.']);
    exit;
}

if (!totp_verify_code($row['secret'], $code)) {
    echo json_encode(['error' => 'Incorrect code. Check the time on your device and try again.']);
    exit;
}

$recoveryCodes = totp_generate_recovery_codes();
$hashed = array_map(fn($c) => password_hash($c, PASSWORD_DEFAULT), $recoveryCodes);
$json   = json_encode($hashed);

$update = $conn->prepare(
    "UPDATE staff_mfa SET enabled = 1, recovery_codes = ? WHERE login_type = ? AND account_id = ?"
);
$update->bind_param('ssi', $json, $loginType, $accountId);
$update->execute();
$update->close();
$db->close();

echo json_encode([
    'success'        => true,
    // Shown to the user exactly once — only the bcrypt hashes are ever
    // stored, so if they're lost there is no way to recover them; the
    // account owner would need to disable and re-enroll MFA instead.
    'recovery_codes' => $recoveryCodes,
]);
