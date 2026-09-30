<?php
require_once __DIR__ . '/../../require_professor.php';
require_professor(true);
require_once __DIR__ . '/../../db.php';
require_once __DIR__ . '/../../csrf.php';
require_once __DIR__ . '/../../Quizzes/text_extraction.php';
require_once __DIR__ . '/../../Professor/quiz_data.php';
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed.']);
    exit;
}

csrf_verify();

$quizId = (int)($_POST['quiz_id'] ?? 0);
$questionsPerAttempt = !empty($_POST['questions_per_attempt']) ? (int)$_POST['questions_per_attempt'] : null;

if (!$quizId) {
    echo json_encode(['error' => 'Missing quiz_id.']);
    exit;
}

$extracted = extract_text_from_upload($_FILES['lesson_file'] ?? []);
if (isset($extracted['error'])) {
    echo json_encode(['error' => $extracted['error']]);
    exit;
}

$db   = new Database();
$conn = $db->connect();

$result = generate_questions_from_text($conn, $quizId, (int)$_SESSION['professor_id'], $questionsPerAttempt, $extracted['text']);

if (isset($result['error'])) {
    $db->close();
    echo json_encode(['error' => $result['error']]);
    exit;
}

$quiz = get_quiz_detail($conn, $quizId, (int)$_SESSION['professor_id']);
$db->close();

$response = ['success' => true, 'quiz' => $quiz, 'generated_count' => $result['generated_count']];
if ($result['requested_count'] !== null && $result['generated_count'] < $result['requested_count']) {
    $response['warning'] = "Only {$result['generated_count']} usable question(s) could be generated from this file — fewer than the {$result['requested_count']} you requested per attempt. Every attempt will use all {$result['generated_count']} until you add more content or lower that number.";
}

echo json_encode($response);
