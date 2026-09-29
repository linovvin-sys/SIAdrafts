<?php
require_once '../../require_student.php';
require_student(true);
require_once '../../db.php';
require_once '../../Student/assignment_data.php';
header('Content-Type: application/json');

$db   = new Database();
$conn = $db->connect();

$assignments = get_my_assignments($conn, (int)$_SESSION['student_id']);
$db->close();

echo json_encode(['assignments' => $assignments]);
