<?php
require_once __DIR__ . '/../../require_professor.php';
require_professor(true);
require_once __DIR__ . '/../../db.php';
require_once __DIR__ . '/../../csrf.php';
require_once __DIR__ . '/../../Professor/quiz_data.php';
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed.']);
    exit;
}

csrf_verify();

$questionId = (int)($_POST['question_id'] ?? 0);
$quizId     = (int)($_POST['quiz_id'] ?? 0);

if (!$questionId || !$quizId) {
    echo json_encode(['error' => 'Missing input.']);
    exit;
}

$db   = new Database();
$conn = $db->connect();

$error = delete_question($conn, $questionId, (int)$_SESSION['professor_id']);

if ($error !== null) {
    $db->close();
    echo json_encode(['error' => $error]);
    exit;
}

$quiz = get_quiz_detail($conn, $quizId, (int)$_SESSION['professor_id']);
$db->close();

echo json_encode(['success' => true, 'quiz' => $quiz]);
