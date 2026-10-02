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

$quizId = (int)($_POST['quiz_id'] ?? 0);
if (!$quizId) {
    echo json_encode(['error' => 'Missing quiz_id.']);
    exit;
}

$db   = new Database();
$conn = $db->connect();
$result = start_attempt($conn, $quizId, (int)$_SESSION['student_id']);
$db->close();

// Same UTC-vs-local fix as get_attempt_state.php -- see parse_db_utc_datetime().
if (!empty($result['attempt']['deadline_at'])) {
    $result['attempt']['deadline_at'] = utc_datetime_to_iso($result['attempt']['deadline_at']);
}

echo json_encode($result);
