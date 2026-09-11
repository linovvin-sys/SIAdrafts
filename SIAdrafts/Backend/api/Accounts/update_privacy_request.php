<?php
require_once __DIR__ . '/../../roles.php';
require_once __DIR__ . '/../../require_role.php';
require_role([ROLE_ADMIN], true);
require_once __DIR__ . '/../../db.php';
require_once __DIR__ . '/../../csrf.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed.']);
    exit;
}

csrf_verify();

$id     = (int)($_POST['id'] ?? 0);
$status = $_POST['status'] ?? '';
$notes  = trim($_POST['notes'] ?? '');

if (!$id || !in_array($status, ['reviewed', 'completed', 'denied'], true)) {
    echo json_encode(['error' => 'Invalid request.']);
    exit;
}

$db   = new Database();
$conn = $db->connect();

$stmt = $conn->prepare(
    "UPDATE data_privacy_request SET status = ?, notes = ?, reviewed_by = ? WHERE id = ?"
);
$reviewedBy = (int)$_SESSION['user_id'];
$stmt->bind_param('ssii', $status, $notes, $reviewedBy, $id);
$stmt->execute();
$stmt->close();
$db->close();

echo json_encode(['success' => true]);
