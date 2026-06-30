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
$amount_due  = round((float)($data['amount_due']  ?? 0), 2);
$downpayment = round((float)($data['downpayment'] ?? 0), 2);
$due_date    = trim($data['due_date'] ?? '');

$subject_ids = array_values(array_unique(
    array_filter(array_map('intval', $data['subject_ids'] ?? []))
));

if (!$student_id || !$school_year || !$semester || !$year_level || !$type_id || empty($subject_ids)) {
    echo json_encode(['error' => 'Missing required fields.']);
    exit;
}

if (!preg_match('/^\d{4}-\d{4}$/', $school_year)) {
    echo json_encode(['error' => 'Invalid school year format. Use YYYY-YYYY.']);
    exit;
}

if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $due_date)) {
    echo json_encode(['error' => 'Invalid due date.']);
    exit;
}

if ($amount_due < 0 || $downpayment < 0 || $downpayment > $amount_due) {
    echo json_encode(['error' => 'Invalid payment amounts.']);
    exit;
}

$conn->begin_transaction();

try {
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

    // Insert enrollment
    $ins = $conn->prepare(
        "INSERT INTO enrollment (student_id, school_year, semester, year_level, status, type_id)
         VALUES (?, ?, ?, ?, 'Enrolled', ?)"
    );
    if (!$ins) {
        throw new RuntimeException('Database error: ' . $conn->error);
    }
    $ins->bind_param('isiii', $student_id, $school_year, $semester, $year_level, $type_id);
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

    // Insert payment
    $pay = $conn->prepare(
        "INSERT INTO payment (enrollment_id, amount_due, downpayment, due_date, payment_status)
         VALUES (?, ?, ?, ?, 'Unpaid')"
    );
    if (!$pay) {
        throw new RuntimeException('Database error: ' . $conn->error);
    }
    $pay->bind_param('idds', $enrollment_id, $amount_due, $downpayment, $due_date);
    if (!$pay->execute()) {
        $pay->close();
        throw new RuntimeException('Database error: ' . $conn->error);
    }
    $pay->close();

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