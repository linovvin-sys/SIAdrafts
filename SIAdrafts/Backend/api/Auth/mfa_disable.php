<?php
session_start();
require_once '../../db.php';
require_once '../../mfa_login_context.php';
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
$ctx       = resolve_mfa_login_context();
$loginType = $ctx['loginType'];
$accountId = $ctx['accountId'];
$table     = $ctx['table'];
$idCol     = $ctx['idCol'];

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
