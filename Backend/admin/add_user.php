<?php
session_start();
require_once __DIR__ . '/../db.php';
require_once __DIR__ . '/generate_staff_id.php';

$db   = new Database();
$conn = $db->connect();

if (empty($_SESSION['user_id'])) {
    http_response_code(401);
    exit('Unauthorized.');
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    exit('Invalid request method.');
}

function clean($value) {
    return htmlspecialchars(trim($value ?? ''), ENT_QUOTES, 'UTF-8');
}

$first_name    = clean($_POST['first_name'] ?? '');
$last_name     = clean($_POST['last_name'] ?? '');
$middle_name   = clean($_POST['middle_name'] ?? '');
$email         = clean($_POST['email'] ?? '');
$username      = clean($_POST['username'] ?? '');
$phone_number  = clean($_POST['phone_number'] ?? '');
$role_name     = clean($_POST['role'] ?? '');
$password      = $_POST['password'] ?? '';
$status_name   = !empty($_POST['status']) ? 'Active' : 'Inactive';

$errors = [];

if ($first_name === '') $errors[] = 'First name is required.';
if ($last_name === '')  $errors[] = 'Last name is required.';
if ($email === '')      $errors[] = 'Email is required.';
if ($username === '')   $errors[] = 'Username is required.';
if ($role_name === '')  $errors[] = 'Role is required.';
if ($password === '' || strlen($password) < 8) {
    $errors[] = 'Password is required and must be at least 8 characters.';
}

if (!empty($errors)) {
    http_response_code(422);
    echo implode(' ', $errors);
    exit;
}

// look up role_id from role_name
$roleStmt = $conn->prepare("SELECT role_id FROM roles WHERE role_name = ? LIMIT 1");
$roleStmt->bind_param('s', $role_name);
$roleStmt->execute();
$roleRow = $roleStmt->get_result()->fetch_assoc();
$roleStmt->close();

if (!$roleRow) {
    http_response_code(422);
    echo 'Invalid role selected.';
    exit;
}
$role_id = $roleRow['role_id'];

// look up status_id from status_name
$statusStmt = $conn->prepare("SELECT status_id FROM statuses WHERE status_name = ? LIMIT 1");
$statusStmt->bind_param('s', $status_name);
$statusStmt->execute();
$statusRow = $statusStmt->get_result()->fetch_assoc();
$statusStmt->close();

if (!$statusRow) {
    http_response_code(422);
    echo 'Invalid status.';
    exit;
}
$status_id = $statusRow['status_id'];

// auto-generate the StaffID: YYYY-NNNN
$staff_id = generate_staff_id($conn);

$hashed_password = password_hash($password, PASSWORD_DEFAULT);

$stmt = $conn->prepare("
    INSERT INTO users
        (staff_id, first_name, middle_name, last_name, email, username,
         password, phone_number, role_id, status_id, created_at, updated_at)
    VALUES (?,?,?,?,?,?,?,?,?,?, NOW(), NOW())
");

if (!$stmt) {
    http_response_code(500);
    echo 'Database error: ' . $conn->error;
    exit;
}

$stmt->bind_param(
    'ssssssssii',
    $staff_id, $first_name, $middle_name, $last_name, $email, $username,
    $hashed_password, $phone_number, $role_id, $status_id
);

if (!$stmt->execute()) {
    http_response_code(500);
    echo 'Database error: ' . $stmt->error;
    exit;
}

$stmt->close();
$conn->close();



exit;