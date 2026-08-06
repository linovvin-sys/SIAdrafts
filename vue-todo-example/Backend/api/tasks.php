<?php
session_start();
require_once __DIR__ . '/../db.php';
header('Content-Type: application/json');

if (empty($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Please log in.']);
    exit;
}

$userId = $_SESSION['user_id'];
$action = $_POST['action'] ?? $_GET['action'] ?? '';
$conn = get_db_connection();

if ($action === 'list') {
    $stmt = $conn->prepare('SELECT id, title, notes, category, status, created_at, updated_at FROM tasks WHERE user_id = ? ORDER BY created_at DESC');
    $stmt->bind_param('i', $userId);
    $stmt->execute();
    echo json_encode(['tasks' => $stmt->get_result()->fetch_all(MYSQLI_ASSOC)]);
    exit;
}

if ($action === 'create') {
    $title = trim($_POST['title'] ?? '');
    $notes = trim($_POST['notes'] ?? '');
    $category = trim($_POST['category'] ?? '');
    $status = in_array($_POST['status'] ?? '', ['pending', 'in_progress', 'completed']) ? $_POST['status'] : 'pending';

    if ($title === '') {
        echo json_encode(['error' => 'Title is required.']);
        exit;
    }

    $stmt = $conn->prepare('INSERT INTO tasks (user_id, title, notes, category, status) VALUES (?, ?, ?, ?, ?)');
    $stmt->bind_param('issss', $userId, $title, $notes, $category, $status);
    $stmt->execute();

    echo json_encode(['success' => true, 'task' => ['id' => $stmt->insert_id, 'title' => $title, 'notes' => $notes, 'category' => $category, 'status' => $status]]);
    exit;
}

if ($action === 'update') {
    $id = (int)($_POST['id'] ?? 0);
    $title = trim($_POST['title'] ?? '');
    $notes = trim($_POST['notes'] ?? '');
    $category = trim($_POST['category'] ?? '');
    $status = in_array($_POST['status'] ?? '', ['pending', 'in_progress', 'completed']) ? $_POST['status'] : 'pending';

    if ($id <= 0 || $title === '') {
        echo json_encode(['error' => 'A valid task id and title are required.']);
        exit;
    }

    $check = $conn->prepare('SELECT id FROM tasks WHERE id = ? AND user_id = ?');
    $check->bind_param('ii', $id, $userId);
    $check->execute();
    if (!$check->get_result()->fetch_assoc()) {
        http_response_code(404);
        echo json_encode(['error' => 'Task not found.']);
        exit;
    }

    $stmt = $conn->prepare('UPDATE tasks SET title = ?, notes = ?, category = ?, status = ? WHERE id = ? AND user_id = ?');
    $stmt->bind_param('ssssii', $title, $notes, $category, $status, $id, $userId);
    $stmt->execute();

    echo json_encode(['success' => true, 'task' => ['id' => $id, 'title' => $title, 'notes' => $notes, 'category' => $category, 'status' => $status]]);
    exit;
}

if ($action === 'delete') {
    $id = (int)($_POST['id'] ?? 0);

    $stmt = $conn->prepare('DELETE FROM tasks WHERE id = ? AND user_id = ?');
    $stmt->bind_param('ii', $id, $userId);
    $stmt->execute();

    if ($stmt->affected_rows === 0) {
        http_response_code(404);
        echo json_encode(['error' => 'Task not found.']);
        exit;
    }

    echo json_encode(['success' => true]);
    exit;
}

echo json_encode(['error' => 'Unknown action.']);
