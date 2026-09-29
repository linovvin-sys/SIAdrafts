<?php
require_once __DIR__ . '/../../db.php';
require_once __DIR__ . '/can_message.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$isProfessor = !empty($_SESSION['professor_id']);
$isStudent   = !empty($_SESSION['student_id']);

if (!$isProfessor && !$isStudent) {
    http_response_code(401);
    exit;
}

$schedule_id = (int)($_GET['schedule_id'] ?? 0);
$last_id     = (int)($_GET['last_id'] ?? 0);

$db   = new Database();
$conn = $db->connect();

if ($isProfessor) {
    $professor_id = (int)$_SESSION['professor_id'];
    $applicant_id = (int)($_GET['applicant_id'] ?? 0);
    if (!$schedule_id || !$applicant_id || !professor_can_message_thread($conn, $professor_id, $schedule_id, $applicant_id)) {
        http_response_code(403);
        exit;
    }
    $unreadSenderRole = 'student';
} else {
    $applicant_id = (int)$_SESSION['student_id'];
    if (!$schedule_id || !student_can_message_thread($conn, $applicant_id, $schedule_id)) {
        http_response_code(403);
        exit;
    }
    $unreadSenderRole = 'professor';
}

// Release the session lock -- otherwise it stays held for the entire life
// of this long-running connection and blocks every other request (like
// sending a message) from the same browser session.
session_write_close();

header('Content-Type: text/event-stream');
header('Cache-Control: no-cache');
header('Connection: keep-alive');
header('X-Accel-Buffering: no');

$max_runtime = 300;
$started_at  = time();

while (true) {
    if (connection_aborted() || (time() - $started_at) > $max_runtime) {
        break;
    }

    $stmt = $conn->prepare(
        "SELECT class_message_id, schedule_id, applicant_id, sender_role, body,
                attachment_name, attachment_type, attachment_size, sent_at
         FROM class_message
         WHERE schedule_id = ? AND applicant_id = ? AND class_message_id > ?
         ORDER BY sent_at ASC"
    );
    $stmt->bind_param('iii', $schedule_id, $applicant_id, $last_id);
    $stmt->execute();
    $newMessages = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();

    if (!empty($newMessages)) {
        $mark = $conn->prepare(
            "UPDATE class_message SET read_at = NOW()
             WHERE schedule_id = ? AND applicant_id = ? AND sender_role = ? AND read_at IS NULL AND class_message_id > ?"
        );
        $mark->bind_param('iisi', $schedule_id, $applicant_id, $unreadSenderRole, $last_id);
        $mark->execute();
        $mark->close();

        foreach ($newMessages as $m) {
            $last_id = max($last_id, (int)$m['class_message_id']);
        }

        echo "data: " . json_encode($newMessages) . "\n\n";
        if (ob_get_level() > 0) ob_flush();
        flush();
    } else {
        echo ": heartbeat\n\n";
        if (ob_get_level() > 0) ob_flush();
        flush();
    }

    sleep(1);
}

$conn->close();
