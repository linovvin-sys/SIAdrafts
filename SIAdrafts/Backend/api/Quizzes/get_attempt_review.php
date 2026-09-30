<?php
require_once __DIR__ . '/../../require_professor.php';
require_professor(true);
require_once __DIR__ . '/../../db.php';
require_once __DIR__ . '/../../Professor/quiz_data.php';
header('Content-Type: application/json');

$attemptId = (int)($_GET['attempt_id'] ?? 0);
if (!$attemptId) {
    echo json_encode(['error' => 'Missing attempt_id.']);
    exit;
}

$db = new Database();
$conn = $db->connect();
$review = get_attempt_review($conn, $attemptId, (int)$_SESSION['professor_id']);
$db->close();

if ($review === null) {
    echo json_encode(['error' => 'Attempt not found.']);
    exit;
}

echo json_encode(['success' => true, 'attempt' => $review]);
