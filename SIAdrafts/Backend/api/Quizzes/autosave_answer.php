<?php
require_once __DIR__ . '/../../require_student.php';
require_student(true);
require_once __DIR__ . '/../../db.php';
require_once __DIR__ . '/../../csrf.php';
require_once __DIR__ . '/../../Student/quiz_data.php';
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed.']);
    exit;
}

csrf_verify();

$attemptId  = (int)($_POST['attempt_id'] ?? 0);
$questionId = (int)($_POST['question_id'] ?? 0);
$choiceId   = (int)($_POST['choice_id'] ?? 0);

if (!$attemptId || !$questionId || !$choiceId) {
    echo json_encode(['error' => 'Missing input.']);
    exit;
}

$db   = new Database();
$conn = $db->connect();
$result = autosave_answer($conn, $attemptId, (int)$_SESSION['student_id'], $questionId, $choiceId);
$db->close();

// Same UTC-vs-local fix as get_attempt_state.php -- see parse_db_utc_datetime().
if (!empty($result['deadline_at'])) {
    $result['deadline_at'] = utc_datetime_to_iso($result['deadline_at']);
}

echo json_encode($result);
