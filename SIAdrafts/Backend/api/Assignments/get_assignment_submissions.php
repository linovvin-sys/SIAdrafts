<?php
require_once '../../require_professor.php';
require_professor(true);
require_once '../../db.php';
require_once '../../Professor/assignment_data.php';
header('Content-Type: application/json');

$assignment_id = (int)($_GET['assignment_id'] ?? 0);

if (!$assignment_id) {
    echo json_encode(['error' => 'Missing assignment_id.']);
    exit;
}

$db   = new Database();
$conn = $db->connect();

$students = get_assignment_submissions($conn, $assignment_id, (int)$_SESSION['professor_id']);
$db->close();

if ($students === null) {
    echo json_encode(['error' => 'Assignment not found.']);
    exit;
}

echo json_encode(['students' => $students]);
