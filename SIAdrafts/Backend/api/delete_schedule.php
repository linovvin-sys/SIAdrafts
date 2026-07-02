<?php
session_start();
require_once '../db.php';
header('Content-Type: application/json');

if (empty($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized.']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed.']);
    exit;
}

$db   = new Database();
$conn = $db->connect();

$raw  = file_get_contents('php://input');
$data = json_decode($raw, true);

$ids = array_values(array_unique(array_filter(array_map('intval', $data['ids'] ?? []))));

if (empty($ids)) {
    echo json_encode(['error' => 'No schedule rows specified.']);
    exit;
}

$placeholders = implode(',', array_fill(0, count($ids), '?'));
$stmt = $conn->prepare("DELETE FROM schedule WHERE schedule_id IN ($placeholders)");
if (!$stmt) {
    http_response_code(500);
    echo json_encode(['error' => 'Database error: ' . $conn->error]);
    exit;
}
$stmt->bind_param(str_repeat('i', count($ids)), ...$ids);

if (!$stmt->execute()) {
    $stmt->close();
    echo json_encode(['error' => 'Database error: ' . $conn->error]);
    exit;
}
$deleted = $stmt->affected_rows;
$stmt->close();
$db->close();

echo json_encode(['success' => true, 'deleted' => $deleted]);