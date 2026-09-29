<?php
require_once __DIR__ . '/../../require_professor.php';
require_professor(true);
require_once __DIR__ . '/../../db.php';
require_once __DIR__ . '/../../csrf.php';
require_once __DIR__ . '/../../grade_periods.php';
require_once __DIR__ . '/../../Professor/grade_data.php';
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed.']);
    exit;
}

csrf_verify();

$scheduleId = (int)($_POST['schedule_id'] ?? 0);
$period     = (string)($_POST['period'] ?? '');
$gradesJson = (string)($_POST['grades'] ?? '');

if (!$scheduleId) {
    echo json_encode(['error' => 'Missing schedule_id.']);
    exit;
}
if (!is_valid_grade_period($period)) {
    echo json_encode(['error' => 'Invalid grading period.']);
    exit;
}

$grades = json_decode($gradesJson, true);
if (!is_array($grades) || empty($grades)) {
    echo json_encode(['error' => 'No grades submitted.']);
    exit;
}

$db   = new Database();
$conn = $db->connect();

$professorId = (int)$_SESSION['professor_id'];
$errors = save_grades_batch($conn, $scheduleId, $professorId, $period, $grades);

$students = get_class_grades($conn, $scheduleId, $professorId, $period);
$db->close();

if (!empty($errors)) {
    echo json_encode(['error' => implode(' ', $errors), 'students' => $students]);
    exit;
}

echo json_encode(['success' => true, 'students' => $students]);
