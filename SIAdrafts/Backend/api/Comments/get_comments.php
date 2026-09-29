<?php
require_once __DIR__ . '/../../db.php';
require_once __DIR__ . '/../../comment_data.php';
header('Content-Type: application/json');

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$isProfessor = !empty($_SESSION['professor_id']);
$isStudent   = !empty($_SESSION['student_id']);
if (!$isProfessor && !$isStudent) {
    http_response_code(401);
    echo json_encode(['error' => 'Please log in.']);
    exit;
}

$submissionId = (int)($_GET['submission_id'] ?? 0);
if (!$submissionId) {
    echo json_encode(['error' => 'Missing submission_id.']);
    exit;
}

$db   = new Database();
$conn = $db->connect();

$authorized = $isProfessor
    ? professor_owns_submission($conn, $submissionId, (int)$_SESSION['professor_id'])
    : student_owns_submission($conn, $submissionId, (int)$_SESSION['student_id']);

if (!$authorized) {
    $db->close();
    http_response_code(403);
    echo json_encode(['error' => 'You are not permitted to view these comments.']);
    exit;
}

$comments = get_submission_comments($conn, $submissionId);
$db->close();

echo json_encode(['comments' => $comments]);
