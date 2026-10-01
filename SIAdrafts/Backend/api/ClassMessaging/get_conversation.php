<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../../db.php';
require_once __DIR__ . '/can_message.php';

require_once __DIR__ . '/../../../Backend/session_bootstrap.php';
app_session_start();

$isProfessor = !empty($_SESSION['professor_id']);
$isStudent   = !empty($_SESSION['student_id']);

if (!$isProfessor && !$isStudent) {
    http_response_code(401);
    echo json_encode(['error' => 'Please log in.']);
    exit;
}

$schedule_id = (int)($_GET['schedule_id'] ?? 0);

if (!$schedule_id) {
    echo json_encode(['error' => 'Missing schedule_id.']);
    exit;
}

$db   = new Database();
$conn = $db->connect();

if ($isProfessor) {
    $professor_id = (int)$_SESSION['professor_id'];
    $applicant_id = (int)($_GET['applicant_id'] ?? 0);
    if (!$applicant_id || !professor_can_message_thread($conn, $professor_id, $schedule_id, $applicant_id)) {
        http_response_code(403);
        echo json_encode(['error' => 'You are not permitted to view this conversation.']);
        exit;
    }
    $unreadSenderRole = 'student';
} else {
    $applicant_id = (int)$_SESSION['student_id'];
    if (!student_can_message_thread($conn, $applicant_id, $schedule_id)) {
        http_response_code(403);
        echo json_encode(['error' => 'You are not permitted to view this conversation.']);
        exit;
    }
    $unreadSenderRole = 'professor';
}

$stmt = $conn->prepare(
    "SELECT class_message_id, schedule_id, applicant_id, sender_role, body,
            attachment_name, attachment_type, attachment_size,
            sent_at, read_at
     FROM class_message
     WHERE schedule_id = ? AND applicant_id = ?
     ORDER BY sent_at ASC"
);
$stmt->bind_param('ii', $schedule_id, $applicant_id);
$stmt->execute();
$messages = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// Mark incoming messages (from the other party) as read now that they've been fetched.
$mark = $conn->prepare(
    "UPDATE class_message SET read_at = NOW()
     WHERE schedule_id = ? AND applicant_id = ? AND sender_role = ? AND read_at IS NULL"
);
$mark->bind_param('iis', $schedule_id, $applicant_id, $unreadSenderRole);
$mark->execute();
$mark->close();

$db->close();

echo json_encode(['messages' => $messages]);
