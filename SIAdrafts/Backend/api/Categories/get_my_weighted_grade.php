<?php
require_once __DIR__ . '/../../require_student.php';
require_student(true);
require_once __DIR__ . '/../../db.php';
require_once __DIR__ . '/../../Professor/category_data.php';
header('Content-Type: application/json');

$scheduleId = (int)($_GET['schedule_id'] ?? 0);
if (!$scheduleId) {
    echo json_encode(['error' => 'Missing schedule_id.']);
    exit;
}

$db   = new Database();
$conn = $db->connect();

$applicantId = (int)$_SESSION['student_id'];
$enrollmentSubjectId = resolve_enrollment_subject_id($conn, $scheduleId, $applicantId);

if ($enrollmentSubjectId === null) {
    $db->close();
    echo json_encode(['error' => 'You are not enrolled in that class.']);
    exit;
}

$weighted = compute_weighted_grade($conn, $scheduleId, $enrollmentSubjectId);
$db->close();

echo json_encode(['weighted' => $weighted]);
