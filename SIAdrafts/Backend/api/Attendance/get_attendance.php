<?php
require_once __DIR__ . '/../../require_professor.php';
require_professor(true);
require_once __DIR__ . '/../../db.php';
require_once __DIR__ . '/../../Professor/attendance_data.php';
header('Content-Type: application/json');

$scheduleId  = (int)($_GET['schedule_id'] ?? 0);
$sessionDate = $_GET['session_date'] ?? '';

if (!$scheduleId || $sessionDate === '') {
    echo json_encode(['error' => 'Missing schedule_id or session_date.']);
    exit;
}

$db   = new Database();
$conn = $db->connect();

$roster = get_attendance_for_date($conn, $scheduleId, (int)$_SESSION['professor_id'], $sessionDate);
$history = get_attendance_history($conn, $scheduleId, (int)$_SESSION['professor_id']);
$db->close();

echo json_encode(['roster' => $roster, 'history' => $history]);
