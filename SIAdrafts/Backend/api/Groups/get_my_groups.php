<?php
require_once __DIR__ . '/../../require_student.php';
require_student(true);
require_once __DIR__ . '/../../db.php';
require_once __DIR__ . '/../../Student/group_data.php';
header('Content-Type: application/json');

$db   = new Database();
$conn = $db->connect();

$classes = get_my_groups_all_classes($conn, (int)$_SESSION['student_id']);
$db->close();

echo json_encode(['classes' => $classes]);
