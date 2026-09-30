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

$attemptId = (int)($_POST['attempt_id'] ?? 0);
if (!$attemptId) {
    echo json_encode(['error' => 'Missing attempt_id.']);
    exit;
}

$db   = new Database();
$conn = $db->connect();
$result = submit_attempt($conn, $attemptId, (int)$_SESSION['student_id']);
$db->close();

echo json_encode($result);
