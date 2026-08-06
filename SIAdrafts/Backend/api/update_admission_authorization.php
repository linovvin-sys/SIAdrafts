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

if ($applicant_id <= 0 || !in_array($action, ['set', 'clear'], true)) {
    http_response_code(422);
    echo json_encode(['success' => false, 'errors' => ['Invalid request.']]);
    exit;
}

$db   = new Database();
$conn = $db->connect();

if ($action === 'set') {
    $note = trim($_POST['note'] ?? '');
    if ($note === '') {
        echo json_encode(['success' => false, 'errors' => ['Authorization note cannot be empty.']]);
        exit;
    }
    $stmt = $conn->prepare("
        UPDATE applicants
        SET authorization_note = ?, authorized_by = ?, authorized_at = NOW(), cleared_by = NULL, cleared_at = NULL
        WHERE applicant_id = ?
    ");
    $stmt->bind_param('sii', $note, $_SESSION['user_id'], $applicant_id);
} else {
    $stmt = $conn->prepare("
        UPDATE applicants
        SET cleared_by = ?, cleared_at = NOW()
        WHERE applicant_id = ?
    ");
    $stmt->bind_param('ii', $_SESSION['user_id'], $applicant_id);
}

if (!$stmt->execute()) {
    http_response_code(500);
    echo json_encode(['success' => false, 'errors' => ['Database error: ' . $stmt->error]]);
    exit;
}
$stmt->close();
$db->close();

echo json_encode(['success' => true]);
