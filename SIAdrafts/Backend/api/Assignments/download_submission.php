<?php
require_once __DIR__ . '/../../session_security.php';
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/../../db.php';

$submissionId = (int)($_GET['submission_id'] ?? 0);
if (!$submissionId) {
    http_response_code(400);
    exit('Missing submission_id.');
}

$db   = new Database();
$conn = $db->connect();

$stmt = $conn->prepare("
    SELECT sub.file_path, sub.file_name, sub.file_type, a.professor_id, e.applicant_id
    FROM assignment_submission sub
    JOIN assignment a ON a.assignment_id = sub.assignment_id
    JOIN enrollment_subject es ON es.enrollment_subject_id = sub.enrollment_subject_id
    JOIN enrollment e ON e.enrollment_id = es.enrollment_id
    WHERE sub.submission_id = ?
");
$stmt->bind_param('i', $submissionId);
$stmt->execute();
$submission = $stmt->get_result()->fetch_assoc();
$stmt->close();
$conn->close();

if (!$submission) {
    http_response_code(404);
    exit('Submission not found.');
}

$isOwningProfessor = !empty($_SESSION['professor_id']) && (int)$_SESSION['professor_id'] === (int)$submission['professor_id'];
$isSubmittingStudent = !empty($_SESSION['student_id']) && (int)$_SESSION['student_id'] === (int)$submission['applicant_id'];

if (!$isOwningProfessor && !$isSubmittingStudent) {
    http_response_code(403);
    exit('You are not permitted to view this file.');
}

$fullPath = __DIR__ . '/../../uploads/submissions/' . basename($submission['file_path']);
if (!is_file($fullPath)) {
    http_response_code(404);
    exit('File no longer exists.');
}

header('Content-Type: ' . $submission['file_type']);
header('Content-Length: ' . filesize($fullPath));
header('Content-Disposition: inline; filename="' . rawurlencode($submission['file_name']) . '"');
header('X-Content-Type-Options: nosniff');
readfile($fullPath);
