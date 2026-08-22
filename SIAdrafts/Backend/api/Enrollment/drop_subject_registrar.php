<?php
session_start();
require_once '../../db.php';
require_once '../../require_role.php';
require_once '../../csrf.php';
header('Content-Type: application/json');

if (empty($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized.']);
    exit;
}

require_registrar_tier(REGISTRAR_TIER_STAFF, true);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed.']);
    exit;
}

csrf_verify();

$db   = new Database();
$conn = $db->connect();

define('ADDROP_FEE_PER_UNIT', 50.00);

$raw  = file_get_contents('php://input');
$data = json_decode($raw, true) ?? $_POST;

$enrollment_subject_id = (int)($data['enrollment_subject_id'] ?? 0);

if (!$enrollment_subject_id) {
    echo json_encode(['error' => 'Missing subject reference.']);
    exit;
}

$staffStmt = $conn->prepare("SELECT staff_id FROM users WHERE user_id = ? LIMIT 1");
$staffStmt->bind_param('i', $_SESSION['user_id']);
$staffStmt->execute();
$staffRow = $staffStmt->get_result()->fetch_assoc();
$staffStmt->close();

if (!$staffRow || empty($staffRow['staff_id'])) {
    http_response_code(500);
    echo json_encode(['error' => 'Your account is missing a StaffID.']);
    exit;
}
$requested_by = $staffRow['staff_id'];

$esStmt = $conn->prepare(
    "SELECT es.enrollment_id, es.status, sub.units
     FROM enrollment_subject es
     JOIN subject sub ON sub.subject_id = es.subject_id
     WHERE es.enrollment_subject_id = ?
     LIMIT 1"
);
$esStmt->bind_param('i', $enrollment_subject_id);
$esStmt->execute();
$es = $esStmt->get_result()->fetch_assoc();
$esStmt->close();

if (!$es) {
    echo json_encode(['error' => 'Subject enrollment record not found.']);
    exit;
}
if ($es['status'] === 'Dropped' || $es['status'] === 'Pending Drop') {
    echo json_encode(['error' => 'This subject is already dropped or pending drop.']);
    exit;
}

$units  = (float)$es['units'];
$amount = round($units * ADDROP_FEE_PER_UNIT, 2);

$conn->begin_transaction();

try {
    if ($es['status'] === 'Pending Payment') {
        // This subject was added but its add fee was never actually paid —
        // "dropping" it now is really just cancelling that add. Charging a
        // drop fee here would bill the student for a subject they never
        // paid to add in the first place (see cancel_subject_fee.php's
        // identical no-charge handling of an unpaid Add).
        $cancelAddStmt = $conn->prepare(
            "UPDATE subject_change_fee SET status = 'Cancelled'
             WHERE enrollment_subject_id = ? AND action = 'Add' AND status = 'Pending'"
        );
        if (!$cancelAddStmt) {
            throw new Exception($conn->error);
        }
        $cancelAddStmt->bind_param('i', $enrollment_subject_id);
        $cancelAddStmt->execute();
        $cancelAddStmt->close();

        $delStmt = $conn->prepare("DELETE FROM enrollment_subject WHERE enrollment_subject_id = ?");
        if (!$delStmt) {
            throw new Exception($conn->error);
        }
        $delStmt->bind_param('i', $enrollment_subject_id);
        $delStmt->execute();
        if ($delStmt->affected_rows === 0) {
            throw new Exception('Subject enrollment record not found.');
        }
        $delStmt->close();

        $conn->commit();

        echo json_encode([
            'success'    => true,
            'fee_amount' => 0,
            'message'    => 'Add request cancelled — no fee charged since it was never paid.',
        ]);
    } else {
        // Subject moves to "Pending Drop" — it only becomes Dropped once
        // Treasury records the drop fee (see record_subject_fee_payment.php).
        $stmt = $conn->prepare(
            "UPDATE enrollment_subject SET status = 'Pending Drop' WHERE enrollment_subject_id = ?"
        );
        if (!$stmt) {
            throw new Exception($conn->error);
        }
        $stmt->bind_param('i', $enrollment_subject_id);
        $stmt->execute();
        if ($stmt->affected_rows === 0) {
            throw new Exception('Subject enrollment record not found.');
        }
        $stmt->close();

        $feeStmt = $conn->prepare(
            "INSERT INTO subject_change_fee
                (enrollment_subject_id, enrollment_id, action, units, amount, requested_by)
             VALUES (?, ?, 'Drop', ?, ?, ?)"
        );
        if (!$feeStmt) {
            throw new Exception($conn->error);
        }
        $feeStmt->bind_param('iidds', $enrollment_subject_id, $es['enrollment_id'], $units, $amount, $requested_by);
        $feeStmt->execute();
        $feeStmt->close();

        $conn->commit();

        echo json_encode([
            'success'    => true,
            'fee_amount' => $amount,
            'message'    => 'Drop requested — pending ₱' . number_format($amount, 2) . ' payment at Treasury.',
        ]);
    }
} catch (Exception $e) {
    $conn->rollback();
    echo json_encode(['error' => 'Could not drop subject. Please try again.']);
}

$db->close();