<?php
session_start();
require_once '../db.php';
require_once '../require_role.php';
header('Content-Type: application/json');

if (empty($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized.']);
    exit;
}

require_role(['Head Registrar', 'Registrar Staff'], true);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed.']);
    exit;
}

$db   = new Database();
$conn = $db->connect();

$raw  = file_get_contents('php://input');
$data = json_decode($raw, true) ?? $_POST;

$enrollment_subject_id = (int)($data['enrollment_subject_id'] ?? 0);

if (!$enrollment_subject_id) {
    echo json_encode(['error' => 'Missing subject reference.']);
    exit;
}

$stmt = $conn->prepare(
    "UPDATE enrollment_subject SET status = 'Dropped' WHERE enrollment_subject_id = ?"
);
if (!$stmt) {
    http_response_code(500);
    echo json_encode(['error' => 'Database error: ' . $conn->error]);
    exit;
}
$stmt->bind_param('i', $enrollment_subject_id);

try {
    $stmt->execute();
    if ($stmt->affected_rows === 0) {
        echo json_encode(['error' => 'Subject enrollment record not found.']);
        exit;
    }
    echo json_encode(['success' => true, 'message' => 'Subject dropped.']);
} catch (mysqli_sql_exception $e) {
    echo json_encode(['error' => 'Could not drop subject. Please try again.']);
}

$stmt->close();
$db->close();