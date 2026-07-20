<?php
header('Content-Type: application/json');
session_start();
require_once '../db.php';
require_once '../roles.php';
require_once '../require_role.php';
require_once 'can_message.php';

$db   = new Database();
$conn = $db->connect();

if (empty($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized.']);
    exit;
}

require_role(ROLES_ALL_STAFF, true);

$my_id   = (int)$_SESSION['user_id'];
$with_id = (int)($_GET['with'] ?? 0);

if (!$with_id) {
    echo json_encode(['error' => 'Missing "with" user ID.']);
    exit;
}

if (!users_can_message($conn, $my_id, $with_id)) {
    http_response_code(403);
    echo json_encode(['error' => 'You are not permitted to view this conversation.']);
    exit;
}

$stmt = $conn->prepare(
    "SELECT message_id, sender_id, recipient_id, body,
            attachment_name, attachment_type, attachment_size,
            sent_at, read_at
     FROM messages
     WHERE (sender_id = ? AND recipient_id = ?) OR (sender_id = ? AND recipient_id = ?)
     ORDER BY sent_at ASC"
);
$stmt->bind_param('iiii', $my_id, $with_id, $with_id, $my_id);
$stmt->execute();
$messages = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// Mark incoming messages as read now that they've been fetched
$mark = $conn->prepare(
    "UPDATE messages SET read_at = NOW()
     WHERE sender_id = ? AND recipient_id = ? AND read_at IS NULL"
);
$mark->bind_param('ii', $with_id, $my_id);
$mark->execute();
$mark->close();

$conn->close();

echo json_encode(['messages' => $messages]);