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

$first_name    = trim($data['first_name']    ?? '');
$middle_name   = trim($data['middle_name']   ?? '');
$last_name     = trim($data['last_name']     ?? '');
$department_id = (int)($data['department_id'] ?? 0);

if ($first_name === '' || $last_name === '' || !$department_id) {
    echo json_encode(['error' => 'First name, last name, and department are required.']);
    exit;
}

$deptStmt = $conn->prepare("SELECT department_id FROM department WHERE department_id = ? LIMIT 1");
$deptStmt->bind_param('i', $department_id);
$deptStmt->execute();
if (!$deptStmt->get_result()->fetch_assoc()) {
    $deptStmt->close();
    echo json_encode(['error' => 'Invalid department.']);
    exit;
}
$deptStmt->close();

$stmt = $conn->prepare("
    INSERT INTO professor (first_name, middle_name, last_name, department_id, status_id)
    VALUES (?, ?, ?, ?, 1)
");
if (!$stmt) {
    http_response_code(500);
    echo json_encode(['error' => 'Database error: ' . $conn->error]);
    exit;
}
$middle_name_or_null = $middle_name !== '' ? $middle_name : null;
$stmt->bind_param('sssi', $first_name, $middle_name_or_null, $last_name, $department_id);

if (!$stmt->execute()) {
    http_response_code(500);
    echo json_encode(['error' => 'Database error: ' . $stmt->error]);
    exit;
}

echo json_encode([
    'success'      => true,
    'professor_id' => $conn->insert_id,
    'message'      => 'Professor added.',
]);

$stmt->close();
$db->close();
