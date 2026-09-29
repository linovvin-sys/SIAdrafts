<?php
require_once __DIR__ . '/../../db.php';
require_once __DIR__ . '/../../csrf.php';
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

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed.']);
    exit;
}

csrf_verify();

$submissionId = (int)($_POST['submission_id'] ?? 0);
$body         = (string)($_POST['body'] ?? '');

if (!$submissionId) {
    echo json_encode(['error' => 'Missing submission_id.']);
    exit;
}

$db   = new Database();
$conn = $db->connect();

if ($isProfessor) {
    $authorized = professor_owns_submission($conn, $submissionId, (int)$_SESSION['professor_id']);
    $role = 'professor';
} else {
    $authorized = student_owns_submission($conn, $submissionId, (int)$_SESSION['student_id']);
    $role = 'student';
}

if (!$authorized) {
    $db->close();
    http_response_code(403);
    echo json_encode(['error' => 'You are not permitted to comment on this submission.']);
    exit;
}

$error = post_submission_comment($conn, $submissionId, $role, $body);

if ($error !== null) {
    $db->close();
    echo json_encode(['error' => $error]);
    exit;
}

$comments = get_submission_comments($conn, $submissionId);
$db->close();

echo json_encode(['success' => true, 'comments' => $comments]);
