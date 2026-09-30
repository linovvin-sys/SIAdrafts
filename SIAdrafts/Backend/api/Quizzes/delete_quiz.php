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

$quizId = (int)($_POST['quiz_id'] ?? 0);
if (!$quizId) {
    echo json_encode(['error' => 'Missing quiz_id.']);
    exit;
}

$db   = new Database();
$conn = $db->connect();

$error = delete_quiz($conn, $quizId, (int)$_SESSION['professor_id']);
$db->close();

if ($error !== null) {
    echo json_encode(['error' => $error]);
    exit;
}

echo json_encode(['success' => true]);
