<?php
session_start();
require_once '../db.php';
require_once '../require_role.php';
require_once '../csrf.php';
header('Content-Type: application/json');

if (empty($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized.']);
    exit;
}

require_role(['Registrar Staff', 'Head Registrar'], true);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed.']);
    exit;
}

csrf_verify();

$db   = new Database();
$conn = $db->connect();

$raw  = file_get_contents('php://input');
$data = json_decode($raw, true) ?? $_POST;

$professor_id = (int)($data['professor_id'] ?? 0);
$action       = $data['action'] ?? '';

if (!$professor_id || !in_array($action, ['activate', 'deactivate'], true)) {
    echo json_encode(['error' => 'Invalid request.']);
    exit;
}

$status_id = $action === 'activate' ? 1 : 2;

$stmt = $conn->prepare("UPDATE professor SET status_id = ? WHERE professor_id = ?");
$stmt->bind_param('ii', $status_id, $professor_id);

if (!$stmt->execute()) {
    http_response_code(500);
    echo json_encode(['error' => 'Database error: ' . $conn->error]);
    exit;
}

$affected = $stmt->affected_rows;
$stmt->close();
$db->close();

if ($affected === 0) {
    echo json_encode(['error' => 'Professor not found.']);
    exit;
}

echo json_encode(['success' => true, 'message' => 'Status updated.']);
