<?php
session_start();
require_once '../db.php';
require_once 'can_message.php';

if (empty($_SESSION['user_id'])) {
    http_response_code(401);
    exit;
}

$my_id   = (int)$_SESSION['user_id'];
$with_id = (int)($_GET['with'] ?? 0);
$last_id = (int)($_GET['last_id'] ?? 0);

$db   = new Database();
$conn = $db->connect();

if (!$with_id || !users_can_message($conn, $my_id, $with_id)) {
    http_response_code(403);
    exit;
}

// Release the session lock — otherwise it stays held for the entire
// life of this long-running connection and blocks every other request
// (like sending a message) from the same browser session.
session_write_close();

header('Content-Type: text/event-stream');
header('Cache-Control: no-cache');
header('Connection: keep-alive');
header('X-Accel-Buffering: no'); // prevents proxy buffering if behind nginx

// Give up after ~5 minutes so a stray open tab doesn't hold a PHP-FPM
// worker forever — the client-side JS reconnects automatically.
$max_runtime = 300;
$started_at  = time();

while (true) {
    if (connection_aborted() || (time() - $started_at) > $max_runtime) {
        break;
    }

    $stmt = $conn->prepare(
        "SELECT message_id, sender_id, recipient_id, body,
                attachment_name, attachment_type, attachment_size,
                sent_at
         FROM messages
         WHERE ((sender_id = ? AND recipient_id = ?) OR (sender_id = ? AND recipient_id = ?))
           AND message_id > ?
         ORDER BY sent_at ASC"
    );
    $stmt->bind_param('iiiii', $my_id, $with_id, $with_id, $my_id, $last_id);
    $stmt->execute();
    $newMessages = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();

    if (!empty($newMessages)) {
        // Mark any newly-arrived incoming messages as read immediately,
        // since the recipient's tab is actively open and receiving them.
        $mark = $conn->prepare(
            "UPDATE messages SET read_at = NOW()
             WHERE sender_id = ? AND recipient_id = ? AND read_at IS NULL AND message_id > ?"
        );
        $mark->bind_param('iii', $with_id, $my_id, $last_id);
        $mark->execute();
        $mark->close();

        foreach ($newMessages as $m) {
            $last_id = max($last_id, (int)$m['message_id']);
        }

        echo "data: " . json_encode($newMessages) . "\n\n";
        if (ob_get_level() > 0) ob_flush();
        flush();
    } else {
        // heartbeat comment keeps the connection alive through some proxies
        echo ": heartbeat\n\n";
        if (ob_get_level() > 0) ob_flush();
        flush();
    }

    sleep(1);
}

$conn->close();