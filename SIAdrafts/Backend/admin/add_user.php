<?php
session_start();
require_once __DIR__ . '/../db.php';
require_once __DIR__ . '/../roles.php';
require_once __DIR__ . '/../require_role.php';
require_once __DIR__ . '/../csrf.php';

if (empty($_SESSION['user_id'])) {
    http_response_code(401);
    exit('Unauthorized.');
}

require_role([ROLE_ADMIN, ROLE_HEAD_REGISTRAR], true);

// Included only after this file's own session/role checks have passed,
// so generate_staff_id.php's own require_role() guard (kept for when it
// is requested directly) never gets a chance to fire first here.
require_once __DIR__ . '/generate_staff_id.php';

$db   = new Database();
$conn = $db->connect();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    exit('Invalid request method.');
}

csrf_verify();

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

// A Head Registrar may only create accounts within the staff branches
// below them — never Admin or another Head Registrar.
if (current_user_is([ROLE_HEAD_REGISTRAR]) && !in_array($role_name, ROLES_HEAD_REGISTRAR_MANAGEABLE, true)) {
    $errors[] = 'You can only create Admission, Registrar Staff, Treasury, or Staff accounts.';
}

if (!empty($errors)) {
    header('Location: /SIAdrafts/Frontend/View/Admin/manage_user.php?add_error=' . urlencode(implode(' ', $errors)));
    exit;
}

// look up role_id from role_name
$roleStmt = $conn->prepare("SELECT role_id FROM roles WHERE role_name = ? LIMIT 1");
$roleStmt->bind_param('s', $role_name);
$roleStmt->execute();
$roleRow = $roleStmt->get_result()->fetch_assoc();
$roleStmt->close();

if (!$roleRow) {
    header('Location: /SIAdrafts/Frontend/View/Admin/manage_user.php?add_error=' . urlencode('Invalid role selected.'));
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
    header('Location: /SIAdrafts/Frontend/View/Admin/manage_user.php?add_error=' . urlencode('Invalid status.'));
    exit;
}
$status_id = $statusRow['status_id'];

// Check duplicate email
$dupEmailStmt = $conn->prepare("SELECT user_id FROM users WHERE email = ?");
$dupEmailStmt->bind_param('s', $email);
$dupEmailStmt->execute();
$dupEmailStmt->store_result();
if ($dupEmailStmt->num_rows > 0) {
    $dupEmailStmt->close();
    header('Location: /SIAdrafts/Frontend/View/Admin/manage_user.php?add_error=' . urlencode('Email already exists.'));
    exit;
}
$dupEmailStmt->close();

// Check duplicate username
$dupUserStmt = $conn->prepare("SELECT user_id FROM users WHERE username = ?");
$dupUserStmt->bind_param('s', $username);
$dupUserStmt->execute();
$dupUserStmt->store_result();
if ($dupUserStmt->num_rows > 0) {
    $dupUserStmt->close();
    header('Location: /SIAdrafts/Frontend/View/Admin/manage_user.php?add_error=' . urlencode('Username already exists.'));
    exit;
}
$dupUserStmt->close();

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
    header('Location: /SIAdrafts/Frontend/View/Admin/manage_user.php?add_error=' . urlencode('Database error: ' . $conn->error));
    exit;
}

$stmt->bind_param(
    'ssssssssii',
    $staff_id, $first_name, $middle_name, $last_name, $email, $username,
    $hashed_password, $phone_number, $role_id, $status_id
);

if (!$stmt->execute()) {
    header('Location: /SIAdrafts/Frontend/View/Admin/manage_user.php?add_error=' . urlencode('Database error: ' . $stmt->error));
    exit;
}

$stmt->close();
$conn->close();

header('Location: /SIAdrafts/Frontend/View/Admin/manage_user.php?added=1');
exit;