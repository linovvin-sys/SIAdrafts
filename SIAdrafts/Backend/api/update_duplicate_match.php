<?php
session_start();
require '../db.php';
require_once '../roles.php';
require_once '../require_role.php';
require_once '../csrf.php';

header('Content-Type: application/json');

if (empty($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'errors' => ['Unauthorized.']]);
    exit;
}

// Admin is deliberately excluded — read-only monitoring only (see admission_confirm.php).
require_role([ROLE_ADMISSION], true);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'errors' => ['Invalid request method.']]);
    exit;
}

csrf_verify();

$applicant_id = (int)($_POST['applicant_id'] ?? 0);
$action       = $_POST['action'] ?? '';

if ($applicant_id <= 0 || !in_array($action, ['confirm', 'dismiss'], true)) {
    http_response_code(422);
    echo json_encode(['success' => false, 'errors' => ['Invalid request.']]);
    exit;
}

$newStatus = $action === 'confirm' ? 'confirmed' : 'dismissed';

$db   = new Database();
$conn = $db->connect();

$stmt = $conn->prepare("
    UPDATE applicants
    SET duplicate_match_status = ?
    WHERE applicant_id = ? AND duplicate_match_status = 'pending_review'
");
$stmt->bind_param('si', $newStatus, $applicant_id);

if (!$stmt->execute()) {
    http_response_code(500);
    echo json_encode(['success' => false, 'errors' => ['Database error: ' . $stmt->error]]);
    exit;
}

if ($stmt->affected_rows === 0) {
    $stmt->close();
    $db->close();
    http_response_code(409);
    echo json_encode(['success' => false, 'errors' => ['This match was already reviewed.']]);
    exit;
}

$stmt->close();
$db->close();

echo json_encode(['success' => true]);
