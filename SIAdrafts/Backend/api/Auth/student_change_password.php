<?php
session_start();
require_once '../../db.php';
require_once '../../csrf.php';

header('Content-Type: application/json');

if (empty($_SESSION['student_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Please log in.']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed.']);
    exit;
}

csrf_verify();

$current  = $_POST['current_password'] ?? '';
$new      = $_POST['new_password'] ?? '';
$confirm  = $_POST['confirm_password'] ?? '';

if ($new === '' || strlen($new) < 8) {
    echo json_encode(['error' => 'New password must be at least 8 characters.']);
    exit;
}
if ($new !== $confirm) {
    echo json_encode(['error' => 'New password and confirmation do not match.']);
    exit;
}

$db   = new Database();
$conn = $db->connect();

$stmt = $conn->prepare("SELECT password_hash FROM student_portal_account WHERE student_portal_account_id = ? LIMIT 1");
$stmt->bind_param('i', $_SESSION['student_portal_account_id']);
$stmt->execute();
$account = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$account || !password_verify($current, $account['password_hash'])) {
    echo json_encode(['error' => 'Current password is incorrect.']);
    exit;
}

$newHash = password_hash($new, PASSWORD_DEFAULT);

$upd = $conn->prepare("UPDATE student_portal_account SET password_hash = ?, must_change_password = 0 WHERE student_portal_account_id = ?");
$upd->bind_param('si', $newHash, $_SESSION['student_portal_account_id']);
$upd->execute();
$upd->close();
$db->close();

$_SESSION['must_change_password'] = false;

echo json_encode(['success' => true, 'redirect' => '/SIAdrafts/Frontend/View/Student/dashboard.php']);
