<?php
require_once '../../require_professor.php';
require_professor(true);
require_once '../../db.php';
require_once '../../csrf.php';
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed.']);
    exit;
}

csrf_verify();

$db   = new Database();
$conn = $db->connect();

$raw    = file_get_contents('php://input');
$data   = json_decode($raw, true) ?? $_POST;
$action = $data['action'] ?? '';

if ($action === 'update_email') {
    $email = trim($data['email'] ?? '');
    if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        echo json_encode(['error' => 'A valid email is required.']);
        exit;
    }

    $dupStmt = $conn->prepare("SELECT professor_id FROM professor WHERE email = ? AND professor_id != ?");
    $dupStmt->bind_param('si', $email, $_SESSION['professor_id']);
    $dupStmt->execute();
    $dupStmt->store_result();
    if ($dupStmt->num_rows > 0) {
        $dupStmt->close();
        echo json_encode(['error' => 'That email is already in use.']);
        exit;
    }
    $dupStmt->close();

    $stmt = $conn->prepare("UPDATE professor SET email = ? WHERE professor_id = ?");
    $stmt->bind_param('si', $email, $_SESSION['professor_id']);
    if (!$stmt->execute()) {
        http_response_code(500);
        echo json_encode(['error' => 'Could not update email.']);
        exit;
    }
    $stmt->close();
    $db->close();
    echo json_encode(['success' => true, 'message' => 'Email updated.']);
    exit;
}

if ($action === 'change_password') {
    $current_password = $data['current_password'] ?? '';
    $new_password      = $data['new_password'] ?? '';

    if ($current_password === '' || $new_password === '') {
        echo json_encode(['error' => 'Current and new password are required.']);
        exit;
    }
    if (strlen($new_password) < 8) {
        echo json_encode(['error' => 'New password must be at least 8 characters.']);
        exit;
    }

    $stmt = $conn->prepare("SELECT password FROM professor WHERE professor_id = ? LIMIT 1");
    $stmt->bind_param('i', $_SESSION['professor_id']);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$row || $row['password'] === null || !password_verify($current_password, $row['password'])) {
        echo json_encode(['error' => 'Current password is incorrect.']);
        exit;
    }

    $hashed = password_hash($new_password, PASSWORD_DEFAULT);
    $updStmt = $conn->prepare("UPDATE professor SET password = ? WHERE professor_id = ?");
    $updStmt->bind_param('si', $hashed, $_SESSION['professor_id']);
    if (!$updStmt->execute()) {
        http_response_code(500);
        echo json_encode(['error' => 'Could not update password.']);
        exit;
    }
    $updStmt->close();
    $db->close();
    echo json_encode(['success' => true, 'message' => 'Password updated.']);
    exit;
}

echo json_encode(['error' => 'Unknown action.']);
