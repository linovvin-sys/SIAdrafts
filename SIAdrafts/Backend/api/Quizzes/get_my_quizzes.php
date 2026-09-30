<?php
require_once __DIR__ . '/../../require_student.php';
require_student(true);
require_once __DIR__ . '/../../db.php';
require_once __DIR__ . '/../../Student/quiz_data.php';
header('Content-Type: application/json');

$db = new Database();
$conn = $db->connect();
$quizzes = get_my_quizzes($conn, (int)$_SESSION['student_id']);
$db->close();

echo json_encode(['success' => true, 'quizzes' => $quizzes]);
