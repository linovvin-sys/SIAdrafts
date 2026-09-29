<?php
require_once '../../require_professor.php';
require_professor(true);
require_once '../../db.php';
require_once '../../grade_periods.php';
require_once '../../Professor/grade_data.php';
header('Content-Type: application/json');

$schedule_id = (int)($_GET['schedule_id'] ?? 0);
$period      = (string)($_GET['period'] ?? '');

if (!$schedule_id) {
    echo json_encode(['error' => 'Missing schedule_id.']);
    exit;
}
if (!is_valid_grade_period($period)) {
    echo json_encode(['error' => 'Invalid grading period.']);
    exit;
}

$db   = new Database();
$conn = $db->connect();

$students = get_class_grades($conn, $schedule_id, (int)$_SESSION['professor_id'], $period);
$db->close();

echo json_encode(['students' => $students]);
