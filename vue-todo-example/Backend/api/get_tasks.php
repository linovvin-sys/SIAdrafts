<?php
require_once '../require_auth.php';
require_once '../db.php';

header('Content-Type: application/json');
require_auth();

$conn = get_db_connection();

$stmt = $conn->prepare('SELECT id, title, notes, category, status, created_at, updated_at FROM tasks WHERE user_id = ? ORDER BY created_at DESC');
$stmt->bind_param('i', $_SESSION['user_id']);
$stmt->execute();
$result = $stmt->get_result();

$tasks = [];
while ($row = $result->fetch_assoc()) {
    $tasks[] = $row;
}

$stmt->close();
$conn->close();

echo json_encode(['tasks' => $tasks]);
