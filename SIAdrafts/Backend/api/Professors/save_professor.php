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

require_min_role(role_level(ROLE_REGISTRAR_STAFF), true);

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
$email         = trim($data['email']    ?? '');
$username      = trim($data['username'] ?? '');
$password      = $data['password'] ?? '';

if ($first_name === '' || $last_name === '' || !$department_id) {
    echo json_encode(['error' => 'First name, last name, and department are required.']);
    exit;
}

// Portal account fields are optional as a set, but if any one of them is
// filled in, require all three so we never end up with a half-created login.
$wantsAccount = ($email !== '' || $username !== '' || $password !== '');
if ($wantsAccount) {
    if ($email === '' || $username === '' || $password === '') {
        echo json_encode(['error' => 'To create a portal account, email, username, and password are all required.']);
        exit;
    }
    if (strlen($password) < 8) {
        echo json_encode(['error' => 'Password must be at least 8 characters.']);
        exit;
    }
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

if ($wantsAccount) {
    $dupStmt = $conn->prepare("SELECT professor_id FROM professor WHERE email = ? OR username = ?");
    $dupStmt->bind_param('ss', $email, $username);
    $dupStmt->execute();
    $dupStmt->store_result();
    if ($dupStmt->num_rows > 0) {
        $dupStmt->close();
        echo json_encode(['error' => 'Email or username already in use.']);
        exit;
    }
    $dupStmt->close();
}

$middle_name_or_null = $middle_name !== '' ? $middle_name : null;
$username_or_null    = $wantsAccount ? $username : null;
$email_or_null       = $wantsAccount ? $email : null;
$password_or_null    = $wantsAccount ? password_hash($password, PASSWORD_DEFAULT) : null;

$stmt = $conn->prepare("
    INSERT INTO professor (first_name, middle_name, last_name, department_id, status_id, username, password, email)
    VALUES (?, ?, ?, ?, 1, ?, ?, ?)
");
if (!$stmt) {
    http_response_code(500);
    echo json_encode(['error' => 'Database error: ' . $conn->error]);
    exit;
}
$stmt->bind_param(
    'sssisss',
    $first_name, $middle_name_or_null, $last_name, $department_id, $username_or_null, $password_or_null, $email_or_null
);

if (!$stmt->execute()) {
    http_response_code(500);
    echo json_encode(['error' => 'Database error: ' . $stmt->error]);
    exit;
}

echo json_encode([
    'success'      => true,
    'professor_id' => $conn->insert_id,
    'message'      => $wantsAccount ? 'Professor added with portal access.' : 'Professor added.',
]);

$stmt->close();
$db->close();
