<?php
session_start();
require_once '../../db.php';
require_once '../../session_security.php';
require_once '../../rate_limit.php';
require_once '../../csrf.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed.']);
    exit;
}

csrf_verify();

// $table/$idCol are one of these two fixed, hardcoded literals chosen by
// which session is active — never derived from client input — before
// being interpolated into the SQL below.
if (!empty($_SESSION['user_id']) && !empty($_SESSION['role_name'])) {
    session_touch_or_expire();
    $loginType = 'staff';
    $accountId = (int)$_SESSION['user_id'];
    $table = 'users';
    $idCol = 'user_id';
} elseif (!empty($_SESSION['professor_id'])) {
    session_touch_or_expire();
    $loginType = 'professor';
    $accountId = (int)$_SESSION['professor_id'];
    $table = 'professor';
    $idCol = 'professor_id';
} else {
    http_response_code(401);
    echo json_encode(['error' => 'Please log in.']);
    exit;
}

if (!rate_limit_check('mfa_disable', 10, 300)) {
    http_response_code(429);
    echo json_encode(['error' => 'Too many attempts. Please wait a few minutes and try again.']);
    exit;
}

$password = $_POST['password'] ?? '';

$db   = new Database();
$conn = $db->connect();

// Re-confirm the account's own password before turning MFA off — this is
// the one action in this endpoint set that lowers a session's security
// posture rather than raising it, so it gets its own re-auth step rather
// than trusting the existing session alone.
$pwStmt = $conn->prepare("SELECT password FROM `$table` WHERE `$idCol` = ?");
$pwStmt->bind_param('i', $accountId);
$pwStmt->execute();
$current = $pwStmt->get_result()->fetch_assoc();
$pwStmt->close();

if (!$current || !password_verify($password, $current['password'])) {
    echo json_encode(['error' => 'Incorrect password.']);
    exit;
}

$del = $conn->prepare("DELETE FROM staff_mfa WHERE login_type = ? AND account_id = ?");
$del->bind_param('si', $loginType, $accountId);
$del->execute();
$del->close();
$db->close();

echo json_encode(['success' => true]);
