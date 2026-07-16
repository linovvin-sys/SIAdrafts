<?php
session_start();
require_once '../db.php';
require_once '../require_role.php';
header('Content-Type: application/json');

if (empty($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized.']);
    exit;
}

require_role(['Head Registrar', 'Registrar Staff'], true);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed.']);
    exit;
}

$db   = new Database();
$conn = $db->connect();

$raw  = file_get_contents('php://input');
$data = json_decode($raw, true) ?? $_POST;

$enrollment_subject_id = (int)($data['enrollment_subject_id'] ?? 0);

if (!$enrollment_subject_id) {
    echo json_encode(['error' => 'Missing subject reference.']);
    exit;
}

$feeStmt = $conn->prepare(
    "SELECT fee_id, action FROM subject_change_fee
     WHERE enrollment_subject_id = ? AND status = 'Pending'
     ORDER BY fee_id DESC LIMIT 1"
);
$feeStmt->bind_param('i', $enrollment_subject_id);
$feeStmt->execute();
$fee = $feeStmt->get_result()->fetch_assoc();
$feeStmt->close();

if (!$fee) {
    echo json_encode(['error' => 'No pending fee found for this subject.']);
    exit;
}

$conn->begin_transaction();

try {
    $cancelStmt = $conn->prepare("UPDATE subject_change_fee SET status = 'Cancelled' WHERE fee_id = ?");
    if (!$cancelStmt) {
        throw new Exception($conn->error);
    }
    $cancelStmt->bind_param('i', $fee['fee_id']);
    $cancelStmt->execute();
    $cancelStmt->close();

    if ($fee['action'] === 'Add') {
        // Never actually enrolled — remove the placeholder row entirely.
        $delStmt = $conn->prepare("DELETE FROM enrollment_subject WHERE enrollment_subject_id = ?");
        if (!$delStmt) {
            throw new Exception($conn->error);
        }
        $delStmt->bind_param('i', $enrollment_subject_id);
        $delStmt->execute();
        $delStmt->close();
    } else {
        // Drop request cancelled — student keeps the subject.
        $revertStmt = $conn->prepare("UPDATE enrollment_subject SET status = 'Enrolled' WHERE enrollment_subject_id = ?");
        if (!$revertStmt) {
            throw new Exception($conn->error);
        }
        $revertStmt->bind_param('i', $enrollment_subject_id);
        $revertStmt->execute();
        $revertStmt->close();
    }

    $conn->commit();
    echo json_encode(['success' => true, 'message' => 'Request cancelled.']);
} catch (Exception $e) {
    $conn->rollback();
    echo json_encode(['error' => 'Could not cancel request. Please try again.']);
}

$db->close();
