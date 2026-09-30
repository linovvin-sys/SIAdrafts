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

$attemptId     = (int)($_POST['attempt_id'] ?? 0);
$violationType = (string)($_POST['violation_type'] ?? '');

if (!$attemptId || !$violationType) {
    echo json_encode(['error' => 'Missing input.']);
    exit;
}

$ip        = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
$userAgent = substr($_SERVER['HTTP_USER_AGENT'] ?? '', 0, 255);

$db   = new Database();
$conn = $db->connect();
$result = log_violation($conn, $attemptId, (int)$_SESSION['student_id'], $violationType, $ip, $userAgent);
$db->close();

echo json_encode($result);
