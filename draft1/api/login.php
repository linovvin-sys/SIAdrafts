<?php
session_start();
require_once '../includes/db.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed.']);
    exit;
}

$username = trim($_POST['username'] ?? '');
$password = $_POST['password'] ?? '';

if ($username === '' || $password === '') {
    echo json_encode(['error' => 'Username and password are required.']);
    exit;
}

$stmt = $pdo->prepare(
    "SELECT u.user_id, u.first_name, u.last_name, u.username, u.password,
            u.role_id, r.role_name
     FROM users u
     JOIN roles r ON u.role_id = r.role_id
     WHERE (u.username = ? OR u.email = ?) AND u.status_id = 1
     LIMIT 1"
);
$stmt->execute([$username, $username]);
$user = $stmt->fetch();

if (!$user || !password_verify($password, $user['password'])) {
    echo json_encode(['error' => 'Invalid username or password.']);
    exit;
}

session_regenerate_id(true);
$_SESSION['user_id']   = $user['user_id'];
$_SESSION['username']  = $user['username'];
$_SESSION['role_id']   = $user['role_id'];
$_SESSION['role_name'] = $user['role_name'];
$_SESSION['full_name'] = trim($user['first_name'] . ' ' . $user['last_name']);

echo json_encode(['success' => true, 'redirect' => 'enrollment.php']);
