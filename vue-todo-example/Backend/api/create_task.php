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

$title = trim($_POST['title'] ?? '');
$notes = trim($_POST['notes'] ?? '');
$category = trim($_POST['category'] ?? '');
$status = $_POST['status'] ?? 'pending';

if ($title === '') {
    echo json_encode(['error' => 'Title is required.']);
    exit;
}

$allowedStatuses = ['pending', 'in_progress', 'completed'];
if (!in_array($status, $allowedStatuses)) {
    $status = 'pending';
}

$conn = get_db_connection();

$stmt = $conn->prepare('INSERT INTO tasks (user_id, title, notes, category, status) VALUES (?, ?, ?, ?, ?)');
$stmt->bind_param('issss', $_SESSION['user_id'], $title, $notes, $category, $status);
$stmt->execute();
$taskId = $stmt->insert_id;
$stmt->close();
$conn->close();

echo json_encode([
    'success' => true,
    'task' => [
        'id' => $taskId,
        'title' => $title,
        'notes' => $notes,
        'category' => $category,
        'status' => $status,
    ],
]);
