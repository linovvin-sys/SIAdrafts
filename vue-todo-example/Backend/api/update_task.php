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
$title = trim($_POST['title'] ?? '');
$notes = trim($_POST['notes'] ?? '');
$category = trim($_POST['category'] ?? '');
$status = $_POST['status'] ?? 'pending';

if ($id <= 0 || $title === '') {
    echo json_encode(['error' => 'A valid task id and title are required.']);
    exit;
}

$allowedStatuses = ['pending', 'in_progress', 'completed'];
if (!in_array($status, $allowedStatuses)) {
    $status = 'pending';
}

$conn = get_db_connection();

$checkStmt = $conn->prepare('SELECT id FROM tasks WHERE id = ? AND user_id = ?');
$checkStmt->bind_param('ii', $id, $_SESSION['user_id']);
$checkStmt->execute();
$owned = $checkStmt->get_result()->fetch_assoc();
$checkStmt->close();

if (!$owned) {
    http_response_code(404);
    echo json_encode(['error' => 'Task not found.']);
    $conn->close();
    exit;
}

$stmt = $conn->prepare('UPDATE tasks SET title = ?, notes = ?, category = ?, status = ? WHERE id = ? AND user_id = ?');
$stmt->bind_param('ssssii', $title, $notes, $category, $status, $id, $_SESSION['user_id']);
$stmt->execute();
$stmt->close();
$conn->close();

echo json_encode([
    'success' => true,
    'task' => [
        'id' => $id,
        'title' => $title,
        'notes' => $notes,
        'category' => $category,
        'status' => $status,
    ],
]);
