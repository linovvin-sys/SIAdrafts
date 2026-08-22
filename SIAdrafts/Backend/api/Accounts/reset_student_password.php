<?php
session_start();
require_once '../../db.php';
require_once '../../roles.php';
require_once '../../require_role.php';
require_once '../../csrf.php';
header('Content-Type: application/json');

if (empty($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized.']);
    exit;
}

require_role([ROLE_ADMIN], true);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed.']);
    exit;
}

csrf_verify();

$db   = new Database();
$conn = $db->connect();

$raw  = file_get_contents('php://input');
$data = json_decode($raw, true) ?? $_POST;

$student_portal_account_id = (int)($data['student_portal_account_id'] ?? 0);
if (!$student_portal_account_id) {
    http_response_code(400);
    echo json_encode(['error' => 'Missing student account.']);
    exit;
}

// Same deterministic temp-password formula used at first enrollment
// (see save_enrollment.php): lowercased last name + last 5 digits of the
// student number. Keeps the recovery formula consistent for front-desk
// staff regardless of whether it's the original mint or a later reset.
$stmt = $conn->prepare("
    SELECT spa.student_portal_account_id, spa.student_no, a.last_name
    FROM student_portal_account spa
    JOIN applicants a ON a.applicant_id = spa.applicant_id
    WHERE spa.student_portal_account_id = ?
    LIMIT 1
");
$stmt->bind_param('i', $student_portal_account_id);
$stmt->execute();
$account = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$account) {
    http_response_code(404);
    echo json_encode(['error' => 'Student account not found.']);
    exit;
}

$lastNameKey = strtolower(preg_replace('/[^A-Za-z]/', '', $account['last_name']));
$studentNoDigits = preg_replace('/[^0-9]/', '', $account['student_no']);
$tempPassword = $lastNameKey . substr($studentNoDigits, -5);
$tempPasswordHash = password_hash($tempPassword, PASSWORD_DEFAULT);

$upd = $conn->prepare("
    UPDATE student_portal_account
    SET password_hash = ?, must_change_password = 1
    WHERE student_portal_account_id = ?
");
$upd->bind_param('si', $tempPasswordHash, $student_portal_account_id);

if (!$upd->execute()) {
    $upd->close();
    http_response_code(500);
    echo json_encode(['error' => 'Database error: ' . $conn->error]);
    exit;
}
$upd->close();
$conn->close();

echo json_encode(['message' => 'Password reset.', 'temp_password' => $tempPassword]);
