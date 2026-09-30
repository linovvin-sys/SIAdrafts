<?php
require_once __DIR__ . '/../../require_student.php';
require_student(true);
require_once __DIR__ . '/../../db.php';
require_once __DIR__ . '/../../Student/quiz_data.php';
header('Content-Type: application/json');

$attemptId = (int)($_GET['attempt_id'] ?? 0);
if (!$attemptId) {
    echo json_encode(['error' => 'Missing attempt_id.']);
    exit;
}

$db = new Database();
$conn = $db->connect();
$state = get_attempt_state($conn, $attemptId, (int)$_SESSION['student_id']);
$db->close();

if ($state === null) {
    echo json_encode(['error' => 'Attempt not found.']);
    exit;
}

echo json_encode(['success' => true, 'attempt' => $state]);
