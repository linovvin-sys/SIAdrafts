<?php
require_once __DIR__ . '/../../require_professor.php';
require_professor(true);
require_once __DIR__ . '/../../db.php';
require_once __DIR__ . '/../../csrf.php';
require_once __DIR__ . '/../../Professor/assignment_data.php';
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed.']);
    exit;
}

csrf_verify();

$assignmentId = (int)($_POST['assignment_id'] ?? 0);
$scoresJson   = (string)($_POST['scores'] ?? '');

if (!$assignmentId) {
    echo json_encode(['error' => 'Missing assignment_id.']);
    exit;
}

$scores = json_decode($scoresJson, true);
if (!is_array($scores) || empty($scores)) {
    echo json_encode(['error' => 'No scores submitted.']);
    exit;
}

$db   = new Database();
$conn = $db->connect();

$professorId = (int)$_SESSION['professor_id'];
$errors = save_submission_scores_batch($conn, $assignmentId, $professorId, $scores);

$db->close();

if (!empty($errors)) {
    echo json_encode(['error' => implode(' ', $errors)]);
    exit;
}

echo json_encode(['success' => true]);
