<?php
require_once __DIR__ . '/../../require_professor.php';
require_professor(true);
require_once __DIR__ . '/../../db.php';
require_once __DIR__ . '/../../Professor/quiz_data.php';
header('Content-Type: application/json');

$quizId = (int)($_GET['quiz_id'] ?? 0);
if (!$quizId) {
    echo json_encode(['error' => 'Missing quiz_id.']);
    exit;
}

$db = new Database();
$conn = $db->connect();
$quiz = get_quiz_detail($conn, $quizId, (int)$_SESSION['professor_id']);
$db->close();

if ($quiz === null) {
    echo json_encode(['error' => 'Quiz not found.']);
    exit;
}

echo json_encode(['success' => true, 'quiz' => $quiz]);
