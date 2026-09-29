<?php
require_once __DIR__ . '/../../db.php';
require_once __DIR__ . '/can_message.php';
require_once __DIR__ . '/../Messaging/message_attachments.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$isProfessor = !empty($_SESSION['professor_id']);
$isStudent   = !empty($_SESSION['student_id']);

if (!$isProfessor && !$isStudent) {
    http_response_code(401);
    exit('Unauthorized.');
}

$message_id = (int)($_GET['message_id'] ?? 0);
if (!$message_id) {
    http_response_code(400);
    exit('Missing message_id.');
}

$db   = new Database();
$conn = $db->connect();

$stmt = $conn->prepare(
    "SELECT schedule_id, applicant_id, attachment_path, attachment_name, attachment_type
     FROM class_message WHERE class_message_id = ?"
);
$stmt->bind_param('i', $message_id);
$stmt->execute();
$message = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$message || empty($message['attachment_path'])) {
    http_response_code(404);
    exit('Attachment not found.');
}

if ($isProfessor) {
    $authorized = professor_can_message_thread($conn, (int)$_SESSION['professor_id'], (int)$message['schedule_id'], (int)$message['applicant_id']);
} else {
    $authorized = (int)$_SESSION['student_id'] === (int)$message['applicant_id']
        && student_can_message_thread($conn, (int)$_SESSION['student_id'], (int)$message['schedule_id']);
}

if (!$authorized) {
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
