<?php
require_once __DIR__ . '/../../require_professor.php';
require_professor(true);
require_once __DIR__ . '/../../db.php';
require_once __DIR__ . '/../../csrf.php';
require_once __DIR__ . '/../../Professor/attendance_data.php';
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed.']);
    exit;
}

csrf_verify();

$scheduleId  = (int)($_POST['schedule_id'] ?? 0);
$sessionDate = $_POST['session_date'] ?? '';
$statuses    = json_decode($_POST['statuses'] ?? '', true) ?? [];

if (!$scheduleId || $sessionDate === '' || !is_array($statuses) || empty($statuses)) {
    echo json_encode(['error' => 'Missing schedule_id, session_date, or statuses.']);
    exit;
}

$db   = new Database();
$conn = $db->connect();

$error = save_attendance_batch($conn, $scheduleId, (int)$_SESSION['professor_id'], $sessionDate, $statuses);

if ($error !== null) {
    $db->close();
    echo json_encode(['error' => $error]);
    exit;
}

$roster = get_attendance_for_date($conn, $scheduleId, (int)$_SESSION['professor_id'], $sessionDate);
$db->close();

echo json_encode(['success' => true, 'roster' => $roster]);
