<?php
header('Content-Type: application/json');
session_start();
require_once '../db.php';
require_once 'can_message.php';

$db   = new Database();
$conn = $db->connect();

if (empty($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Unauthorized.']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Invalid request method.']);
    exit;
}

$sender_id    = (int)$_SESSION['user_id'];
$recipient_id = (int)($_POST['recipient_id'] ?? 0);
$body         = trim($_POST['body'] ?? '');

if (!$recipient_id || $body === '') {
    echo json_encode(['success' => false, 'error' => 'Recipient and message body are required.']);
    exit;
}

if (mb_strlen($body) > 2000) {
    echo json_encode(['success' => false, 'error' => 'Message is too long.']);
    exit;
}

if (!users_can_message($conn, $sender_id, $recipient_id)) {
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => 'You are not permitted to message this user.']);
    exit;
}

$stmt = $conn->prepare(
    "INSERT INTO messages (sender_id, recipient_id, body) VALUES (?, ?, ?)"
);
$stmt->bind_param('iis', $sender_id, $recipient_id, $body);

if (!$stmt->execute()) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Database error: ' . $stmt->error]);
    exit;
}

$message_id = $stmt->insert_id;
$stmt->close();
$conn->close();

echo json_encode([
    'success'    => true,
    'message_id' => $message_id,
    'sent_at'    => date('Y-m-d H:i:s'),
]);