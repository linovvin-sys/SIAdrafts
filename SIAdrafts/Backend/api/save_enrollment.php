<?php
session_start();
require_once '../db.php';


header('Content-Type: application/json');

if (empty($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized.']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed.']);
    exit;
}

$db   = new Database();
$conn = $db->connect();

$raw  = file_get_contents('php://input');
$data = json_decode($raw, true);


if (!is_array($data)) {
    echo json_encode(['error' => 'Invalid request body.']);
    exit;
}

$student_id  = (int)($data['student_id']  ?? 0);
error_log("Applicant ID received: " . $student_id);
$school_year = trim($data['school_year']  ?? '');
$semester    = (int)($data['semester']    ?? 0);
$year_level  = (int)($data['year_level']  ?? 0);
$type_id     = (int)($data['type_id']     ?? 1);
$section_id  = (int)($data['section_id']  ?? 0);

$subject_ids = array_values(array_unique(
    array_filter(array_map('intval', $data['subject_ids'] ?? []))
));

if (!$student_id || !$school_year || !$semester || !$year_level || !$type_id || !$section_id || empty($subject_ids)) {
    echo json_encode(['error' => 'Missing required fields.']);
    exit;
}

if (!preg_match('/^\d{4}-\d{4}$/', $school_year)) {
    echo json_encode(['error' => 'Invalid school year format. Use YYYY-YYYY.']);
    exit;
}

// Payment is now fully automated: amount_due comes from fee_schedule
// (keyed by year_level + school_year), the breakdown is copied from
// fee_schedule_item into payment_breakdown as a point-in-time snapshot,
// and due_date is fixed at "today + PAYMENT_DUE_DAYS". There is no
// manual treasury setup step anymore — setup_payment.php is kept only
// as a fallback for legacy enrollments created before this change.
const PAYMENT_DUE_DAYS = 3;

$conn->begin_transaction();

try {
    // Section must exist and actually be offering this exact subject load
    // for this year level / semester / school year. Re-validating here
    // (rather than trusting the client) matches the all-or-nothing section
    // package model from the subject-selection step.
    $secCheck = $conn->prepare(
        "SELECT COUNT(DISTINCT sch.subject_id)
         FROM schedule sch
         JOIN subject sub ON sub.subject_id = sch.subject_id
         WHERE sch.section_id = ? AND sch.school_year = ? AND sch.semester = ?
           AND sub.year_level = ? AND sub.semester = ?"
    );
    if (!$secCheck) {
        throw new RuntimeException('Database error: ' . $conn->error);
    }
    $secCheck->bind_param('isiii', $section_id, $school_year, $semester, $year_level, $semester);
    $secCheck->execute();
    $offered_count = (int)$secCheck->get_result()->fetch_row()[0];
    $secCheck->close();

    if ($offered_count === 0) {
        throw new RuntimeException('Selected section is not offered for this year level / semester.');
    }
    if ($offered_count !== count($subject_ids)) {
        throw new RuntimeException('Subject selection does not match the section\'s current offering. Please go back and reselect the section.');
    }

    // Duplicate guard
    $dup = $conn->prepare(
        "SELECT enrollment_id FROM enrollment
         WHERE student_id = ? AND school_year = ? AND semester = ?
         LIMIT 1"
    );
    if (!$dup) {
        throw new RuntimeException('Database error: ' . $conn->error);
    }
    $dup->bind_param('isi', $student_id, $school_year, $semester);
    $dup->execute();
    $dup_result = $dup->get_result();
    if ($dup_result->fetch_assoc()) {
        $dup->close();
        throw new RuntimeException('Student is already enrolled for this term.');
    }
    $dup->close();

    // Look up the fee schedule for this year level / school year BEFORE
    // creating the enrollment, so we fail fast (and roll back nothing)
    // if treasury hasn't configured fees for this year level yet.
    $feeStmt = $conn->prepare(
        "SELECT fee_schedule_id, total_amount FROM fee_schedule
         WHERE year_level = ? AND school_year = ? AND is_active = 1
         LIMIT 1"
    );
    if (!$feeStmt) {
        throw new RuntimeException('Database error: ' . $conn->error);
    }
    $feeStmt->bind_param('is', $year_level, $school_year);
    $feeStmt->execute();
    $feeSchedule = $feeStmt->get_result()->fetch_assoc();
    $feeStmt->close();

    if (!$feeSchedule) {
        throw new RuntimeException(
            "No fee schedule has been set up for Year $year_level, SY $school_year. " .
            "Please ask Treasury to configure it before this student can be enrolled."
        );
    }

    $itemsStmt = $conn->prepare(
        "SELECT label, amount, sort_order FROM fee_schedule_item
         WHERE fee_schedule_id = ? ORDER BY sort_order"
    );
    if (!$itemsStmt) {
        throw new RuntimeException('Database error: ' . $conn->error);
    }
    $itemsStmt->bind_param('i', $feeSchedule['fee_schedule_id']);
    $itemsStmt->execute();
    $feeItems = $itemsStmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $itemsStmt->close();

    if (empty($feeItems)) {
        throw new RuntimeException(
            "The fee schedule for Year $year_level, SY $school_year has no breakdown items configured."
        );
    }

    // Insert enrollment. Status starts as "Pending Payment" — the payment
    // row below is auto-created with amount owed, but the student still
    // needs to actually pay; record_payment.php is what moves this to
    // "Enrolled" once treasury records the payment.
    $ins = $conn->prepare(
        "INSERT INTO enrollment (student_id, school_year, semester, year_level, section_id, status, type_id)
         VALUES (?, ?, ?, ?, ?, 'Pending Payment', ?)"
    );
    if (!$ins) {
        throw new RuntimeException('Database error: ' . $conn->error);
    }
    $ins->bind_param('isiiii', $student_id, $school_year, $semester, $year_level, $section_id, $type_id);
    if (!$ins->execute()) {
        $ins->close();
        throw new RuntimeException('Database error: ' . $conn->error);
    }
    $enrollment_id = (int)$conn->insert_id;
    $ins->close();

    // Insert subjects
    $sub_stmt = $conn->prepare(
        "INSERT INTO enrollment_subject (enrollment_id, subject_id, status) VALUES (?, ?, 'Enrolled')"
    );
    if (!$sub_stmt) {
        throw new RuntimeException('Database error: ' . $conn->error);
    }
    foreach ($subject_ids as $sid) {
        $sub_stmt->bind_param('ii', $enrollment_id, $sid);
        if (!$sub_stmt->execute()) {
            $sub_stmt->close();
            throw new RuntimeException('Database error: ' . $conn->error);
        }
    }
    $sub_stmt->close();

    // Auto-create the payment row from the fee schedule total.
    $amount_due = round((float)$feeSchedule['total_amount'], 2);
    $due_date   = date('Y-m-d', strtotime('+' . PAYMENT_DUE_DAYS . ' days'));

    $payStmt = $conn->prepare(
        "INSERT INTO payment (enrollment_id, amount_due, downpayment, due_date, payment_status)
         VALUES (?, ?, 0, ?, 'Unpaid')"
    );
    if (!$payStmt) {
        throw new RuntimeException('Database error: ' . $conn->error);
    }
    $payStmt->bind_param('ids', $enrollment_id, $amount_due, $due_date);
    if (!$payStmt->execute()) {
        $payStmt->close();
        throw new RuntimeException('Database error: ' . $conn->error);
    }
    $payment_id = (int)$conn->insert_id;
    $payStmt->close();

    // Snapshot the fee breakdown onto this payment, so later changes to
    // fee_schedule don't retroactively change what this student owes.
    $bdStmt = $conn->prepare(
        "INSERT INTO payment_breakdown (payment_id, label, amount, sort_order) VALUES (?, ?, ?, ?)"
    );
    if (!$bdStmt) {
        throw new RuntimeException('Database error: ' . $conn->error);
    }
    foreach ($feeItems as $item) {
        $bdStmt->bind_param('isdi', $payment_id, $item['label'], $item['amount'], $item['sort_order']);
        if (!$bdStmt->execute()) {
            $bdStmt->close();
            throw new RuntimeException('Database error: ' . $conn->error);
        }
    }
    $bdStmt->close();

    $conn->commit();
    unset($_SESSION['enroll']);

    echo json_encode([
        'success'       => true,
        'enrollment_id' => $enrollment_id,
        'payment'       => [
            'payment_id' => $payment_id,
            'amount_due' => $amount_due,
            'due_date'   => $due_date,
            'breakdown'  => $feeItems,
        ],
    ]);

} catch (RuntimeException $e) {
    $conn->rollback();
    $msg = $conn->errno === 1062
        ? 'Student is already enrolled for this term.'
        : $e->getMessage();
    echo json_encode(['error' => $msg]);
} catch (mysqli_sql_exception $e) {
    $conn->rollback();
    $msg = $e->getCode() === 1062
        ? 'Student is already enrolled for this term.'
        : 'A database error occurred. Please try again.';
    echo json_encode(['error' => $msg]);
}

$db->close();