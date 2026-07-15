<?php
header('Content-Type: application/json');
session_start();
require_once '../db.php';

$db   = new Database();
$conn = $db->connect();

if (empty($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Unauthorized.']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Invalid request method.']);
    exit;
}

$staffStmt = $conn->prepare("SELECT staff_id FROM users WHERE user_id = ? LIMIT 1");
$staffStmt->bind_param('i', $_SESSION['user_id']);
$staffStmt->execute();
$staffRow = $staffStmt->get_result()->fetch_assoc();
$staffStmt->close();

if (!$staffRow || empty($staffRow['staff_id'])) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Your account is missing a StaffID.']);
    exit;
}
$submitted_by = $staffRow['staff_id'];

$student_no    = trim($_POST['student_no'] ?? '');
$reason        = trim($_POST['reason'] ?? '');
$school_year   = trim($_POST['school_year'] ?? '');
$semester      = (int)($_POST['semester'] ?? 0);
$is_shifting   = !empty($_POST['is_shifting']) ? 1 : 0;
$new_course_id = $is_shifting ? (int)($_POST['new_course_id'] ?? 0) : null;

if ($student_no === '' || $reason === '' || !$school_year || !$semester) {
    echo json_encode(['success' => false, 'error' => 'Missing required fields.']);
    exit;
}
if (!preg_match('/^\d{4}-\d{4}$/', $school_year)) {
    echo json_encode(['success' => false, 'error' => 'School year must be YYYY-YYYY.']);
    exit;
}
if ($is_shifting && !$new_course_id) {
    echo json_encode(['success' => false, 'error' => 'Select the program being shifted into.']);
    exit;
}

$stmt = $conn->prepare(
    "SELECT student_id, applicant_id FROM student WHERE student_no = ? LIMIT 1"
);
$stmt->bind_param('s', $student_no);
$stmt->execute();
$student = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$student) {
    echo json_encode(['success' => false, 'error' => 'No student found with that Student ID.']);
    exit;
}
$student_id    = (int)$student['student_id'];
$applicant_id  = (int)$student['applicant_id'];

// Block students who are currently enrolled — readmission is only for
// students who have previously stopped out (LOA, dropped, etc.)
// NOTE: enrollment.student_id is misleadingly named — its FK actually
// references applicants.applicant_id, not student.student_id.
$statusStmt = $conn->prepare(
    "SELECT e.status, est.is_active
     FROM enrollment e
     LEFT JOIN enrollment_status_type est ON est.status_name = e.status
     WHERE e.student_id = ?
     ORDER BY e.school_year DESC, e.semester DESC, e.created_at DESC
     LIMIT 1"
);
$statusStmt->bind_param('i', $applicant_id);
$statusStmt->execute();
$latestEnrollment = $statusStmt->get_result()->fetch_assoc();
$statusStmt->close();

if ($latestEnrollment && (int)$latestEnrollment['is_active'] === 1) {
    echo json_encode(['success' => false, 'error' => 'This student has an active enrollment (' . $latestEnrollment['status'] . ') and is not eligible for readmission.']);
    exit;
}

// Block a duplicate *pending* request for the same student/term.
// A previously rejected request shouldn't block refiling.
$dupStmt = $conn->prepare(
    "SELECT request_id FROM readmission_request
     WHERE student_id = ? AND requested_school_year = ? AND requested_semester = ? AND status = 'Pending'
     LIMIT 1"
);
$dupStmt->bind_param('isi', $student_id, $school_year, $semester);
$dupStmt->execute();
if ($dupStmt->get_result()->fetch_assoc()) {
    $dupStmt->close();
    echo json_encode(['success' => false, 'error' => 'This student already has a pending readmission request for that school year and semester.']);
    exit;
}
$dupStmt->close();

$ins = $conn->prepare(
    "INSERT INTO readmission_request
        (student_id, reason, requested_school_year, requested_semester, is_shifting, new_course_id, processed_by)
     VALUES (?, ?, ?, ?, ?, ?, ?)"
);
$ins->bind_param('issiiis', $student_id, $reason, $school_year, $semester, $is_shifting, $new_course_id, $submitted_by);

if (!$ins->execute()) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Database error: ' . $ins->error]);
    exit;
}

echo json_encode(['success' => true, 'request_id' => $ins->insert_id]);
$ins->close();
$conn->close();