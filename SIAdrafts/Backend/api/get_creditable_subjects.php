<?php
header('Content-Type: application/json');
session_start();
require_once '../db.php';

$db   = new Database();
$conn = $db->connect();

if (empty($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized.']);
    exit;
}

$reference_id = trim($_GET['reference_id'] ?? '');
if ($reference_id === '') {
    echo json_encode(['error' => 'Reference ID is required.']);
    exit;
}

$stmt = $conn->prepare("SELECT applicant_id, course_id FROM applicants WHERE reference_id = ? LIMIT 1");
$stmt->bind_param('s', $reference_id);
$stmt->execute();
$applicant = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$applicant) {
    echo json_encode(['error' => 'No application found with that reference ID.']);
    exit;
}

$applicant_id = (int)$applicant['applicant_id'];
$course_id    = (int)$applicant['course_id'];

// Full curriculum for this applicant's course, all year levels/semesters —
// a transferee may have completed multiple terms elsewhere.
$stmt = $conn->prepare(
    "SELECT sub.subject_id, sub.subject_code, sub.subject_name, sub.units,
            sub.year_level, sub.semester, sc.category_name
     FROM subject sub
     JOIN subject_category sc ON sub.category_id = sc.category_id
     JOIN section sec ON sec.course_id = ?
     JOIN schedule sch ON sch.section_id = sec.section_id AND sch.subject_id = sub.subject_id
     WHERE 1=1
     GROUP BY sub.subject_id
     ORDER BY sub.year_level, sub.semester, sc.category_name, sub.subject_code"
);
$stmt->bind_param('i', $course_id);
$stmt->execute();
$subjects = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// Already-credited subjects for this applicant (so a re-lookup shows prior state)
$stmt = $conn->prepare("SELECT subject_id FROM applicant_subject_credit WHERE applicant_id = ?");
$stmt->bind_param('i', $applicant_id);
$stmt->execute();
$already_credited = array_column($stmt->get_result()->fetch_all(MYSQLI_ASSOC), 'subject_id');
$stmt->close();

$conn->close();

echo json_encode([
    'subjects'          => $subjects,
    'already_credited'  => array_map('intval', $already_credited),
]);