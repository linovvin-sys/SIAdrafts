<?php
require_once __DIR__ . '/../../require_professor.php';
require_professor(true);
require_once __DIR__ . '/../../db.php';
require_once __DIR__ . '/../../Professor/announcement_data.php';
header('Content-Type: application/json');

$scheduleId = (int)($_GET['schedule_id'] ?? 0);

if (!$scheduleId) {
    echo json_encode(['error' => 'Missing schedule_id.']);
    exit;
}

$db   = new Database();
$conn = $db->connect();

$announcements = get_class_announcements($conn, $scheduleId, (int)$_SESSION['professor_id']);
$db->close();

echo json_encode(['announcements' => $announcements]);
