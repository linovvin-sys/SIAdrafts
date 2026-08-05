<?php
require_once '../require_auth.php';
require_once '../db.php';

header('Content-Type: application/json');
require_auth();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed.']);
    exit;
}

$id = (int)($_POST['id'] ?? 0);

if ($id <= 0) {
    echo json_encode(['error' => 'A valid task id is required.']);
    exit;
}

$conn = get_db_connection();

$stmt = $conn->prepare('DELETE FROM tasks WHERE id = ? AND user_id = ?');
$stmt->bind_param('ii', $id, $_SESSION['user_id']);
$stmt->execute();
$deleted = $stmt->affected_rows > 0;
$stmt->close();
$conn->close();

if (!$deleted) {
    http_response_code(404);
    echo json_encode(['error' => 'Task not found.']);
    exit;
}

echo json_encode(['success' => true]);
