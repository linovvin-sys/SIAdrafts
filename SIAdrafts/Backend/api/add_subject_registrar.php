<?php
session_start();
require_once '../db.php';
require_once '../require_role.php';
require_once '../csrf.php';
require_once '../prereq.php';
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

csrf_verify();

$db   = new Database();
$conn = $db->connect();

define('ADDROP_FEE_PER_UNIT', 50.00);

$raw  = file_get_contents('php://input');
$data = json_decode($raw, true) ?? $_POST;

$enrollment_id = (int)($data['enrollment_id'] ?? 0);
$subject_id    = (int)($data['subject_id'] ?? 0);
$schedule_id   = !empty($data['schedule_id']) ? (int)$data['schedule_id'] : null;

if (!$enrollment_id || !$subject_id) {
    echo json_encode(['error' => 'Missing required fields.']);
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

// Make sure the enrollment actually exists.
$check = $conn->prepare("SELECT enrollment_id, student_id FROM enrollment WHERE enrollment_id = ? LIMIT 1");
$check->bind_param('i', $enrollment_id);
$check->execute();
$enrollmentRow = $check->get_result()->fetch_assoc();
if (!$enrollmentRow) {
    echo json_encode(['error' => 'Enrollment not found.']);
    exit;
}
$check->close();

// Pull the subject's unit count to compute the add fee. Also re-checked
// server-side that it's Approved — a still-pending subject shouldn't be
// addable just because a client bypassed the (already-filtered) picker.
$subjStmt = $conn->prepare("SELECT units FROM subject WHERE subject_id = ? AND status = 'Approved' LIMIT 1");
$subjStmt->bind_param('i', $subject_id);
$subjStmt->execute();
$subjectRow = $subjStmt->get_result()->fetch_assoc();
$subjStmt->close();

if (!$subjectRow) {
    echo json_encode(['error' => 'Subject not found.']);
    exit;
}

if (!subject_prereq_met($conn, (int)$enrollmentRow['student_id'], $subject_id)) {
    $prereqLabel = subject_prereq_label($conn, $subject_id);
    echo json_encode(['error' => 'Prerequisite not yet completed' . ($prereqLabel ? ": $prereqLabel" : '.') . '.']);
    exit;
}

$units  = (float)$subjectRow['units'];
$amount = round($units * ADDROP_FEE_PER_UNIT, 2);

// Guard against re-adding a subject the student is already active in.
$dupe = $conn->prepare(
    "SELECT enrollment_subject_id FROM enrollment_subject
     WHERE enrollment_id = ? AND subject_id = ? AND status != 'Dropped'
     LIMIT 1"
);
$dupe->bind_param('ii', $enrollment_id, $subject_id);
$dupe->execute();
if ($dupe->get_result()->fetch_assoc()) {
    echo json_encode(['error' => 'Student is already enrolled in this subject.']);
    exit;
}
$dupe->close();

$conn->begin_transaction();

try {
    // Subject stays "Pending Payment" — it only becomes Enrolled once
    // Treasury records the add fee (see record_subject_fee_payment.php).
    $stmt = $conn->prepare(
        "INSERT INTO enrollment_subject (enrollment_id, subject_id, schedule_id, status)
         VALUES (?, ?, ?, 'Pending Payment')"
    );
    if (!$stmt) {
        throw new Exception($conn->error);
    }
    $stmt->bind_param('iii', $enrollment_id, $subject_id, $schedule_id);
    $stmt->execute();
    $enrollment_subject_id = $conn->insert_id;
    $stmt->close();

    $feeStmt = $conn->prepare(
        "INSERT INTO subject_change_fee
            (enrollment_subject_id, enrollment_id, action, units, amount, requested_by)
         VALUES (?, ?, 'Add', ?, ?, ?)"
    );
    if (!$feeStmt) {
        throw new Exception($conn->error);
    }
    $feeStmt->bind_param('iidds', $enrollment_subject_id, $enrollment_id, $units, $amount, $requested_by);
    $feeStmt->execute();
    $feeStmt->close();

    $conn->commit();

    echo json_encode([
        'success'               => true,
        'enrollment_subject_id' => $enrollment_subject_id,
        'fee_amount'            => $amount,
        'message'               => 'Subject added — pending ₱' . number_format($amount, 2) . ' payment at Treasury.',
    ]);
} catch (Exception $e) {
    $conn->rollback();
    echo json_encode(['error' => 'Could not add subject. Please try again.']);
}

$db->close();