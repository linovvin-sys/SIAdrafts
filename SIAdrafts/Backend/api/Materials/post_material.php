<?php
require_once __DIR__ . '/../../require_professor.php';
require_professor(true);
require_once __DIR__ . '/../../db.php';
require_once __DIR__ . '/../../csrf.php';
require_once __DIR__ . '/../../Professor/material_data.php';
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed.']);
    exit;
}

csrf_verify();

$scheduleId   = (int)($_POST['schedule_id'] ?? 0);
$title        = (string)($_POST['title'] ?? '');
$type         = (string)($_POST['type'] ?? '');
$url          = $_POST['url'] ?? null;
$body         = $_POST['body'] ?? null;
$visibleFrom  = $_POST['visible_from'] ?? null;

if (!$scheduleId) {
    echo json_encode(['error' => 'Missing schedule_id.']);
    exit;
}

$db   = new Database();
$conn = $db->connect();

$error = post_material($conn, $scheduleId, (int)$_SESSION['professor_id'], $title, $type, $url, $body, $visibleFrom, $_FILES['material_file'] ?? null);

if ($error !== null) {
    $db->close();
    echo json_encode(['error' => $error]);
    exit;
}

$materials = get_class_materials($conn, $scheduleId, (int)$_SESSION['professor_id']);
$db->close();

echo json_encode(['success' => true, 'materials' => $materials]);
