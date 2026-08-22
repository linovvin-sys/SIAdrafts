<?php
header('Content-Type: application/json');
session_start();
require_once '../../db.php';
require_once '../../roles.php';
require_once '../../require_role.php';
require_once '../../csrf.php';
require_once 'can_message.php';
require_once 'message_attachments.php';

$db   = new Database();
$conn = $db->connect();

if (empty($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Unauthorized.']);
    exit;
}

require_role(ROLES_ALL_STAFF, true);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Invalid request method.']);
    exit;
}

csrf_verify();

$sender_id    = (int)$_SESSION['user_id'];
$recipient_id = (int)($_POST['recipient_id'] ?? 0);
$body         = trim($_POST['body'] ?? '');

$attachment = store_message_attachment($_FILES['attachment'] ?? []);
if (isset($attachment['error'])) {
    echo json_encode(['success' => false, 'error' => $attachment['error']]);
    exit;
}

if (!$recipient_id || ($body === '' && empty($attachment))) {
    echo json_encode(['success' => false, 'error' => 'A message or an attachment is required.']);
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

$attachment_path = $attachment['path'] ?? null;
$attachment_name = $attachment['name'] ?? null;
$attachment_type = $attachment['type'] ?? null;
$attachment_size = $attachment['size'] ?? null;

$stmt = $conn->prepare(
    "INSERT INTO messages (sender_id, recipient_id, body, attachment_path, attachment_name, attachment_type, attachment_size)
     VALUES (?, ?, ?, ?, ?, ?, ?)"
);
$stmt->bind_param(
    'iissssi',
    $sender_id,
    $recipient_id,
    $body,
    $attachment_path,
    $attachment_name,
    $attachment_type,
    $attachment_size
);

if (!$stmt->execute()) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Database error: ' . $stmt->error]);
    exit;
}

$message_id = $stmt->insert_id;
$stmt->close();
$conn->close();

echo json_encode([
    'success'         => true,
    'message_id'      => $message_id,
    'sent_at'         => date('Y-m-d H:i:s'),
    'attachment_name' => $attachment_name,
    'attachment_type' => $attachment_type,
    'attachment_size' => $attachment_size,
]);