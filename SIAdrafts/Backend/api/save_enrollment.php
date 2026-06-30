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

// NOTE: payment (amount_due / downpayment / due_date) is no longer collected
// here. Enrollment confirmation just enrolls the student in subjects.
// Treasury sets up and records payment separately via setup_payment.php
// and record_payment.php.

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

    // Insert enrollment. Status starts as "Pending Payment" — treasury is
    // responsible for setting up and confirming payment, which is what
    // moves this to "Enrolled" (see setup_payment.php / record_payment.php).
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

    $conn->commit();
    unset($_SESSION['enroll']);

    echo json_encode(['success' => true, 'enrollment_id' => $enrollment_id]);

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