<?php
session_start();
require_once '../db.php';
require_once '../roles.php';
require_once '../require_role.php';
require_once '../csrf.php';
header('Content-Type: application/json');

if (empty($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized.']);
    exit;
}

require_role([ROLE_ADMISSION, ROLE_ADMIN], true);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed.']);
    exit;
}

csrf_verify();

$db   = new Database();
$conn = $db->connect();

$document_id = (int)($_POST['document_id'] ?? 0);

if (!$document_id) {
    echo json_encode(['error' => 'No document specified.']);
    exit;
}

// uploaded_at is refreshed to NOW() so it reflects when the document was
// actually received, not when the deferred placeholder row was first
// created back at walk-in confirmation.
$stmt = $conn->prepare("
    UPDATE applicant_documents
    SET status = 'submitted', verified_by = ?, uploaded_at = NOW()
    WHERE document_id = ? AND status = 'will_submit_later'
");
$stmt->bind_param('ii', $_SESSION['user_id'], $document_id);

if (!$stmt->execute()) {
    $stmt->close();
    echo json_encode(['error' => 'Database error: ' . $conn->error]);
    exit;
}

$affected = $stmt->affected_rows;
$stmt->close();
$db->close();

if ($affected === 0) {
    echo json_encode(['error' => 'This document is no longer marked as pending.']);
    exit;
}

echo json_encode(['success' => true, 'message' => 'Document marked as received.']);
