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

$materialId = (int)($_POST['material_id'] ?? 0);

if (!$materialId) {
    echo json_encode(['error' => 'Missing material_id.']);
    exit;
}

$db   = new Database();
$conn = $db->connect();

$filePath = delete_material($conn, $materialId, (int)$_SESSION['professor_id']);
$db->close();

if ($filePath === false) {
    echo json_encode(['error' => 'Material not found.']);
    exit;
}

if (!empty($filePath)) {
    delete_material_file($filePath);
}

echo json_encode(['success' => true]);
