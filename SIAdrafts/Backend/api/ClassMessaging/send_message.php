<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../../db.php';
require_once __DIR__ . '/../../csrf.php';
require_once __DIR__ . '/can_message.php';
require_once __DIR__ . '/../Messaging/message_attachments.php';

require_once __DIR__ . '/../../../Backend/session_bootstrap.php';
app_session_start();

$isProfessor = !empty($_SESSION['professor_id']);
$isStudent   = !empty($_SESSION['student_id']);

if (!$isProfessor && !$isStudent) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Please log in.']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Invalid request method.']);
    exit;
}

csrf_verify();

$schedule_id = (int)($_POST['schedule_id'] ?? 0);
$body        = trim($_POST['body'] ?? '');

$db   = new Database();
$conn = $db->connect();

$attachment = store_message_attachment($_FILES['attachment'] ?? []);
if (isset($attachment['error'])) {
    echo json_encode(['success' => false, 'error' => $attachment['error']]);
    exit;
}

if (!$schedule_id || ($body === '' && empty($attachment))) {
    echo json_encode(['success' => false, 'error' => 'A message or an attachment is required.']);
    exit;
}

if (mb_strlen($body) > 2000) {
    echo json_encode(['success' => false, 'error' => 'Message is too long.']);
    exit;
}

if ($isProfessor) {
    $professor_id = (int)$_SESSION['professor_id'];
    $applicant_id = (int)($_POST['applicant_id'] ?? 0);
    if (!$applicant_id || !professor_can_message_thread($conn, $professor_id, $schedule_id, $applicant_id)) {
        http_response_code(403);
        echo json_encode(['success' => false, 'error' => 'You are not permitted to message this student.']);
        exit;
    }
    $sender_role = 'professor';
} else {
    $applicant_id = (int)$_SESSION['student_id'];
    if (!student_can_message_thread($conn, $applicant_id, $schedule_id)) {
        http_response_code(403);
        echo json_encode(['success' => false, 'error' => 'You are not permitted to message this class.']);
        exit;
    }
    $sender_role = 'student';
}

$attachment_path = $attachment['path'] ?? null;
$attachment_name = $attachment['name'] ?? null;
$attachment_type = $attachment['type'] ?? null;
$attachment_size = $attachment['size'] ?? null;

try {
    $stmt = $conn->prepare(
        "INSERT INTO class_message (schedule_id, applicant_id, sender_role, body, attachment_path, attachment_name, attachment_type, attachment_size)
         VALUES (?, ?, ?, ?, ?, ?, ?, ?)"
    );
    $stmt->bind_param(
        'iisssssi',
        $schedule_id,
        $applicant_id,
        $sender_role,
        $body,
        $attachment_path,
        $attachment_name,
        $attachment_type,
        $attachment_size
    );
    $stmt->execute();

    $message_id = $stmt->insert_id;
    $stmt->close();
    $db->close();

    echo json_encode([
        'success'         => true,
        'message_id'      => $message_id,
        'sent_at'         => date('Y-m-d H:i:s'),
        'attachment_name' => $attachment_name,
        'attachment_type' => $attachment_type,
        'attachment_size' => $attachment_size,
    ]);
} catch (mysqli_sql_exception $e) {
    http_response_code(500);
    error_log('ClassMessaging/send_message.php: ' . $e->getMessage());
    echo json_encode(['success' => false, 'error' => 'A database error occurred. Please try again.']);
}
