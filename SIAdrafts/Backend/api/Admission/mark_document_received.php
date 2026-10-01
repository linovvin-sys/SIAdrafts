<?php
require_once __DIR__ . '/../../../Backend/session_bootstrap.php';
app_session_start();
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

// Admin is deliberately excluded — read-only monitoring only (see pending_documents.php).
require_role([ROLE_ADMISSION], true);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed.']);
    exit;
}

csrf_verify();

$db   = new Database();
$conn = $db->connect();

$document_id = (int)($_POST['document_id'] ?? 0);
$physical_location = trim($_POST['physical_location'] ?? '');

if (!$document_id) {
    echo json_encode(['error' => 'No document specified.']);
    exit;
}

if ($physical_location === '') {
    echo json_encode(['error' => 'Where was the hard copy filed? Enter a storage location.']);
    exit;
}

// uploaded_at is refreshed to NOW() so it reflects when the document was
// actually received, not when the deferred placeholder row was first
// created back at walk-in confirmation. storage_location/storage_recorded_at
// record where staff actually filed this specific hard copy -- required
// here (unlike confirm_admission.php's optional version) because this is
// the one moment staff have the physical document in hand to file it;
// there's no later step that would ever fill it in otherwise.
try {
    $stmt = $conn->prepare("
        UPDATE applicant_documents
        SET status = 'submitted', verified_by = ?, uploaded_at = NOW(),
            storage_location = ?, storage_recorded_at = NOW()
        WHERE document_id = ? AND status = 'will_submit_later'
    ");
    $stmt->bind_param('isi', $_SESSION['user_id'], $physical_location, $document_id);
    $stmt->execute();
    $affected = $stmt->affected_rows;
    $stmt->close();
} catch (mysqli_sql_exception $e) {
    error_log('mark_document_received.php: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'A database error occurred. Please try again.']);
    exit;
}
$db->close();

if ($affected === 0) {
    echo json_encode(['error' => 'This document is no longer marked as pending.']);
    exit;
}

echo json_encode(['success' => true, 'message' => 'Document marked as received.']);
