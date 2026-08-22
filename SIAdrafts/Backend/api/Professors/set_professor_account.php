<?php
session_start();
require_once '../../db.php';
require_once '../../require_role.php';
require_once '../../csrf.php';
header('Content-Type: application/json');

if (empty($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized.']);
    exit;
}

require_role(['Registrar Staff', 'Head Registrar', 'Admin'], true);

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
$email        = trim($data['email']    ?? '');
$username     = trim($data['username'] ?? '');
$password     = $data['password'] ?? '';

if (!$professor_id) {
    echo json_encode(['error' => 'Invalid request.']);
    exit;
}

if ($email === '' || $username === '' || $password === '') {
    echo json_encode(['error' => 'Email, username, and password are all required.']);
    exit;
}
if (strlen($password) < 8) {
    echo json_encode(['error' => 'Password must be at least 8 characters.']);
    exit;
}

$profStmt = $conn->prepare("SELECT professor_id FROM professor WHERE professor_id = ? LIMIT 1");
$profStmt->bind_param('i', $professor_id);
$profStmt->execute();
if (!$profStmt->get_result()->fetch_assoc()) {
    $profStmt->close();
    echo json_encode(['error' => 'Professor not found.']);
    exit;
}
$profStmt->close();

$dupStmt = $conn->prepare("SELECT professor_id FROM professor WHERE (email = ? OR username = ?) AND professor_id != ?");
$dupStmt->bind_param('ssi', $email, $username, $professor_id);
$dupStmt->execute();
$dupStmt->store_result();
if ($dupStmt->num_rows > 0) {
    $dupStmt->close();
    echo json_encode(['error' => 'Email or username already in use.']);
    exit;
}
$dupStmt->close();

$hashed = password_hash($password, PASSWORD_DEFAULT);

$stmt = $conn->prepare("UPDATE professor SET username = ?, email = ?, password = ? WHERE professor_id = ?");
$stmt->bind_param('sssi', $username, $email, $hashed, $professor_id);

if (!$stmt->execute()) {
    http_response_code(500);
    echo json_encode(['error' => 'Database error: ' . $stmt->error]);
    exit;
}

$stmt->close();
$db->close();

echo json_encode(['success' => true, 'message' => 'Portal account saved.']);
