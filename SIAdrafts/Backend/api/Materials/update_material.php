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

$materialId  = (int)($_POST['material_id'] ?? 0);
$title       = (string)($_POST['title'] ?? '');
$url         = $_POST['url'] ?? null;
$body        = $_POST['body'] ?? null;
$visibleFrom = $_POST['visible_from'] ?? null;

if (!$materialId) {
    echo json_encode(['error' => 'Missing material_id.']);
    exit;
}

$db   = new Database();
$conn = $db->connect();

$error = update_material($conn, $materialId, (int)$_SESSION['professor_id'], $title, $url, $body, $visibleFrom, $_FILES['material_file'] ?? null);

if ($error !== null) {
    $db->close();
    echo json_encode(['error' => $error]);
    exit;
}

$stmt = $conn->prepare("SELECT schedule_id FROM class_material WHERE material_id = ?");
$stmt->bind_param('i', $materialId);
$stmt->execute();
$row = $stmt->get_result()->fetch_assoc();
$stmt->close();

$materials = get_class_materials($conn, (int)$row['schedule_id'], (int)$_SESSION['professor_id']);
$db->close();

echo json_encode(['success' => true, 'materials' => $materials]);
