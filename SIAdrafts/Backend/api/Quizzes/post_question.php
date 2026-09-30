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

$quizId       = (int)($_POST['quiz_id'] ?? 0);
$questionText = (string)($_POST['question_text'] ?? '');
$points       = (float)($_POST['points'] ?? 1.0);
$choices      = json_decode((string)($_POST['choices'] ?? '[]'), true);

if (!$quizId || !is_array($choices)) {
    echo json_encode(['error' => 'Missing or invalid input.']);
    exit;
}

$db   = new Database();
$conn = $db->connect();

$result = post_question($conn, $quizId, (int)$_SESSION['professor_id'], $questionText, $points, $choices);

if (isset($result['error'])) {
    $db->close();
    echo json_encode(['error' => $result['error']]);
    exit;
}

$quiz = get_quiz_detail($conn, $quizId, (int)$_SESSION['professor_id']);
$db->close();

echo json_encode(['success' => true, 'quiz' => $quiz]);
