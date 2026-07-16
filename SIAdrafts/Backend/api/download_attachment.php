<?php
session_start();
require_once '../db.php';
require_once 'can_message.php';
require_once 'message_attachments.php';

if (empty($_SESSION['user_id'])) {
    http_response_code(401);
    exit('Unauthorized.');
}

$my_id      = (int)$_SESSION['user_id'];
$message_id = (int)($_GET['message_id'] ?? 0);

if (!$message_id) {
    http_response_code(400);
    exit('Missing message_id.');
}

$db   = new Database();
$conn = $db->connect();

$stmt = $conn->prepare(
    "SELECT sender_id, recipient_id, attachment_path, attachment_name, attachment_type
     FROM messages WHERE message_id = ?"
);
$stmt->bind_param('i', $message_id);
$stmt->execute();
$message = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$message || empty($message['attachment_path'])) {
    http_response_code(404);
    exit('Attachment not found.');
}

$isParticipant = $my_id === (int)$message['sender_id'] || $my_id === (int)$message['recipient_id'];
if (!$isParticipant || !users_can_message($conn, (int)$message['sender_id'], (int)$message['recipient_id'])) {
    http_response_code(403);
    exit('You are not permitted to view this file.');
}

$conn->close();

$fullPath = message_attachment_upload_dir() . basename($message['attachment_path']);
if (!is_file($fullPath)) {
    http_response_code(404);
    exit('File no longer exists.');
}

header('Content-Type: ' . $message['attachment_type']);
header('Content-Length: ' . filesize($fullPath));
header('Content-Disposition: inline; filename="' . rawurlencode($message['attachment_name']) . '"');
header('X-Content-Type-Options: nosniff');
readfile($fullPath);