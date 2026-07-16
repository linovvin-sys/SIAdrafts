<?php
session_start();
header('Content-Type: application/json');
require_once '../db.php';
require_once '../require_role.php';

if (empty($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized.']);
    exit;
}

require_role(['Head Registrar', 'Registrar Staff'], true);

$db   = new Database();
$conn = $db->connect();

$enrollment_id = (int)($_GET['enrollment_id'] ?? 0);

if (!$enrollment_id) {
    echo json_encode(['error' => 'Missing enrollment.']);
    exit;
}

$stmt = $conn->prepare(
    "SELECT e.school_year, e.semester, e.section_id, sec.course_id
     FROM enrollment e
     LEFT JOIN section sec ON sec.section_id = e.section_id
     WHERE e.enrollment_id = ?
     LIMIT 1"
);
$stmt->bind_param('i', $enrollment_id);
$stmt->execute();
$enr = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$enr || !$enr['course_id']) {
    echo json_encode(['error' => 'Could not resolve this student\'s course/section.']);
    exit;
}

// Subjects that belong to the student's course, are not already active on
// this enrollment, matched with a schedule (if one has been set for their
// section/school year/semester) so the staff member can see when it meets.
$stmt = $conn->prepare(
    "SELECT sub.subject_id, sub.subject_code, sub.subject_name, sub.units,
            sch.schedule_id, sch.day, sch.time_start, sch.time_end,
            CONCAT(p.first_name, ' ', p.last_name) AS professor_name
     FROM subject sub
     LEFT JOIN schedule sch ON sch.subject_id = sub.subject_id
         AND sch.school_year = ?
         AND sch.semester    = ?
         AND sch.section_id  = ?
         AND sch.status      = 'Approved'
     LEFT JOIN professor p ON p.professor_id = sch.professor_id
     WHERE sub.course_id = ?
       AND sub.subject_id NOT IN (
           SELECT subject_id FROM enrollment_subject
           WHERE enrollment_id = ? AND status != 'Dropped'
       )
     ORDER BY sub.subject_code"
);
$stmt->bind_param(
    'siiii',
    $enr['school_year'],
    $enr['semester'],
    $enr['section_id'],
    $enr['course_id'],
    $enrollment_id
);
$stmt->execute();
$subjects = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

$db->close();

echo json_encode(['subjects' => $subjects]);