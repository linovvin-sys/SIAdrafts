<?php
session_start();
require_once '../db.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed.']);
    exit;
}

$name = trim($_POST['name'] ?? '');
$email = trim($_POST['email'] ?? '');
$password = $_POST['password'] ?? '';
$confirmPassword = $_POST['confirm_password'] ?? '';

if ($name === '' || $email === '' || $password === '') {
    echo json_encode(['error' => 'Name, email, and password are required.']);
    exit;
}

if ($password !== $confirmPassword) {
    echo json_encode(['error' => 'Passwords do not match.']);
    exit;
}

$conn = get_db_connection();

$checkStmt = $conn->prepare('SELECT id FROM users WHERE email = ?');
$checkStmt->bind_param('s', $email);
$checkStmt->execute();
$existing = $checkStmt->get_result()->fetch_assoc();
$checkStmt->close();

if ($existing) {
    echo json_encode(['error' => 'An account with that email already exists.']);
    $conn->close();
    exit;
}

$passwordHash = password_hash($password, PASSWORD_DEFAULT);

$insertStmt = $conn->prepare('INSERT INTO users (name, email, password_hash) VALUES (?, ?, ?)');
$insertStmt->bind_param('sss', $name, $email, $passwordHash);
$insertStmt->execute();
$userId = $insertStmt->insert_id;
$insertStmt->close();
$conn->close();

$_SESSION['user_id'] = $userId;
$_SESSION['user_name'] = $name;
$_SESSION['user_email'] = $email;

echo json_encode([
    'success' => true,
    'user' => ['id' => $userId, 'name' => $name, 'email' => $email],
]);
