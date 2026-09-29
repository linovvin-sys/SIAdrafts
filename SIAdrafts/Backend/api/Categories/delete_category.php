<?php
require_once __DIR__ . '/../../require_professor.php';
require_professor(true);
require_once __DIR__ . '/../../db.php';
require_once __DIR__ . '/../../csrf.php';
require_once __DIR__ . '/../../Professor/category_data.php';
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed.']);
    exit;
}

csrf_verify();

$categoryId = (int)($_POST['category_id'] ?? 0);
$scheduleId = (int)($_POST['schedule_id'] ?? 0);

if (!$categoryId) {
    echo json_encode(['error' => 'Missing category_id.']);
    exit;
}

$db   = new Database();
$conn = $db->connect();

$error = delete_category($conn, $categoryId, (int)$_SESSION['professor_id']);

if ($error !== null) {
    $db->close();
    echo json_encode(['error' => $error]);
    exit;
}

$categories = get_categories($conn, $scheduleId, (int)$_SESSION['professor_id']);
$db->close();

echo json_encode(['success' => true, 'categories' => $categories]);
