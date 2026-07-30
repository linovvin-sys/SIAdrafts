<?php
header('Content-Type: application/json');
session_start();
require_once '../db.php';
require_once '../roles.php';
require_once '../require_role.php';

$db   = new Database();
$conn = $db->connect();

if (empty($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized.']);
    exit;
}

require_role([ROLE_REGISTRAR_STAFF, ROLE_HEAD_REGISTRAR], true);

$student_no = trim($_GET['student_no'] ?? '');
if ($student_no === '') {
    echo json_encode(['error' => 'Student ID is required.']);
    exit;
}

$stmt = $conn->prepare(
    "SELECT s.student_id, s.student_no, s.first_name, s.last_name, s.applicant_id,
            c.course_id, c.course_name
     FROM student s
     JOIN applicants a ON a.applicant_id = s.applicant_id
     JOIN course c ON c.course_id = a.course_id
     WHERE s.student_no = ? LIMIT 1"
);
$stmt->bind_param('s', $student_no);
$stmt->execute();
$student = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$student) {
    echo json_encode(['error' => 'No student found with that Student ID.']);
    exit;
}

// enrollment.student_id is misleadingly named — it actually references
// applicants.applicant_id, not student.student_id.
$statusStmt = $conn->prepare(
    "SELECT status FROM enrollment
     WHERE student_id = ?
     ORDER BY school_year DESC, semester DESC, created_at DESC
     LIMIT 1"
);
$statusStmt->bind_param('i', $student['applicant_id']);
$statusStmt->execute();
$latestEnrollment = $statusStmt->get_result()->fetch_assoc();
$statusStmt->close();

$activeStatuses = ['Enrolled', 'Pending Payment'];
if ($latestEnrollment && in_array($latestEnrollment['status'], $activeStatuses, true)) {
    echo json_encode(['error' => 'This student is currently enrolled and is not eligible for readmission.']);
    exit;
}

unset($student['applicant_id']);
echo json_encode(['student' => $student]);