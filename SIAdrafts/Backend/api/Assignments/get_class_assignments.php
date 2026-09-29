<?php
require_once '../../require_professor.php';
require_professor(true);
require_once '../../db.php';
require_once '../../Professor/assignment_data.php';
header('Content-Type: application/json');

$schedule_id = (int)($_GET['schedule_id'] ?? 0);

if (!$schedule_id) {
    echo json_encode(['error' => 'Missing schedule_id.']);
    exit;
}

$db   = new Database();
$conn = $db->connect();

$assignments = get_class_assignments($conn, $schedule_id, (int)$_SESSION['professor_id']);
$db->close();

echo json_encode(['assignments' => $assignments]);
