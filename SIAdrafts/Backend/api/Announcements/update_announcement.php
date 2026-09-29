<?php
require_once __DIR__ . '/../../require_professor.php';
require_professor(true);
require_once __DIR__ . '/../../db.php';
require_once __DIR__ . '/../../csrf.php';
require_once __DIR__ . '/../../Professor/announcement_data.php';
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed.']);
    exit;
}

csrf_verify();

$announcementId = (int)($_POST['announcement_id'] ?? 0);
$title          = (string)($_POST['title'] ?? '');
$body           = (string)($_POST['body'] ?? '');

if (!$announcementId) {
    echo json_encode(['error' => 'Missing announcement_id.']);
    exit;
}

$db   = new Database();
$conn = $db->connect();

$error = update_announcement($conn, $announcementId, (int)$_SESSION['professor_id'], $title, $body);

if ($error !== null) {
    $db->close();
    echo json_encode(['error' => $error]);
    exit;
}

$stmt = $conn->prepare("SELECT schedule_id FROM announcement WHERE announcement_id = ?");
$stmt->bind_param('i', $announcementId);
$stmt->execute();
$row = $stmt->get_result()->fetch_assoc();
$stmt->close();

$announcements = get_class_announcements($conn, (int)$row['schedule_id'], (int)$_SESSION['professor_id']);
$db->close();

echo json_encode(['success' => true, 'announcements' => $announcements]);
