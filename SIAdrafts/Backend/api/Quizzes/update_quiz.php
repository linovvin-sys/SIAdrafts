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

$quizId           = (int)($_POST['quiz_id'] ?? 0);
$title            = (string)($_POST['title'] ?? '');
$instructions     = $_POST['instructions'] ?? null;
$timeLimitMinutes = (int)($_POST['time_limit_minutes'] ?? 0);
$availableFrom    = $_POST['available_from'] ?? null;
$availableUntil   = $_POST['available_until'] ?? null;
$questionsPerAttempt = !empty($_POST['questions_per_attempt']) ? (int)$_POST['questions_per_attempt'] : null;

if (!$quizId) {
    echo json_encode(['error' => 'Missing quiz_id.']);
    exit;
}

$db   = new Database();
$conn = $db->connect();

$error = update_quiz($conn, $quizId, (int)$_SESSION['professor_id'], $title, $instructions, $timeLimitMinutes, $availableFrom, $availableUntil, $questionsPerAttempt);

if ($error !== null) {
    $db->close();
    echo json_encode(['error' => $error]);
    exit;
}

$quiz = get_quiz_detail($conn, $quizId, (int)$_SESSION['professor_id']);
$quizzes = get_class_quizzes($conn, $quiz['schedule_id'] ?? 0, (int)$_SESSION['professor_id']);
$db->close();

echo json_encode(['success' => true, 'quizzes' => $quizzes]);
