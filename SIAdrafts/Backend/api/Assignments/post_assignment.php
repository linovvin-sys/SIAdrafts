<?php
require_once __DIR__ . '/../../require_professor.php';
require_professor(true);
require_once __DIR__ . '/../../db.php';
require_once __DIR__ . '/../../csrf.php';
require_once __DIR__ . '/../../Professor/assignment_data.php';
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed.']);
    exit;
}

csrf_verify();

$scheduleId   = (int)($_POST['schedule_id'] ?? 0);
$title        = (string)($_POST['title'] ?? '');
$instructions = (string)($_POST['instructions'] ?? '');
$dueDate      = $_POST['due_date'] ?? null;
$maxScore     = $_POST['max_score'] ?? null;
$categoryId   = !empty($_POST['category_id']) ? (int)$_POST['category_id'] : null;

if (!$scheduleId) {
    echo json_encode(['error' => 'Missing schedule_id.']);
    exit;
}

$db   = new Database();
$conn = $db->connect();

$error = post_assignment($conn, $scheduleId, (int)$_SESSION['professor_id'], $title, $instructions, $dueDate, $maxScore, $categoryId);

if ($error !== null) {
    $db->close();
    echo json_encode(['error' => $error]);
    exit;
}

$assignments = get_class_assignments($conn, $scheduleId, (int)$_SESSION['professor_id']);
$db->close();

echo json_encode(['success' => true, 'assignments' => $assignments]);
