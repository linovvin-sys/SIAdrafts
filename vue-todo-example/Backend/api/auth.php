<?php
session_start();
require_once __DIR__ . '/../db.php';
header('Content-Type: application/json');

$action = $_POST['action'] ?? $_GET['action'] ?? '';
$conn = get_db_connection();

if ($action === 'register') {
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

    $stmt = $conn->prepare('SELECT id FROM users WHERE email = ?');
    $stmt->bind_param('s', $email);
    $stmt->execute();
    if ($stmt->get_result()->fetch_assoc()) {
        echo json_encode(['error' => 'An account with that email already exists.']);
        exit;
    }

    $hash = password_hash($password, PASSWORD_DEFAULT);
    $stmt = $conn->prepare('INSERT INTO users (name, email, password_hash) VALUES (?, ?, ?)');
    $stmt->bind_param('sss', $name, $email, $hash);
    $stmt->execute();

    $_SESSION['user_id'] = $stmt->insert_id;
    $_SESSION['user_name'] = $name;
    $_SESSION['user_email'] = $email;

    echo json_encode(['success' => true, 'user' => ['id' => $_SESSION['user_id'], 'name' => $name, 'email' => $email]]);
    exit;
}

if ($action === 'login') {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    $stmt = $conn->prepare('SELECT id, name, email, password_hash FROM users WHERE email = ?');
    $stmt->bind_param('s', $email);
    $stmt->execute();
    $user = $stmt->get_result()->fetch_assoc();

    if (!$user || !password_verify($password, $user['password_hash'])) {
        echo json_encode(['error' => 'Invalid email or password.']);
        exit;
    }

    session_regenerate_id(true);
    $_SESSION['user_id'] = $user['id'];
    $_SESSION['user_name'] = $user['name'];
    $_SESSION['user_email'] = $user['email'];

    echo json_encode(['success' => true, 'user' => ['id' => $user['id'], 'name' => $user['name'], 'email' => $user['email']]]);
    exit;
}

if ($action === 'logout') {
    $_SESSION = [];
    session_destroy();
    echo json_encode(['success' => true]);
    exit;
}

if ($action === 'session') {
    if (empty($_SESSION['user_id'])) {
        echo json_encode(['user' => null]);
        exit;
    }
    echo json_encode(['user' => ['id' => $_SESSION['user_id'], 'name' => $_SESSION['user_name'], 'email' => $_SESSION['user_email']]]);
    exit;
}

echo json_encode(['error' => 'Unknown action.']);
