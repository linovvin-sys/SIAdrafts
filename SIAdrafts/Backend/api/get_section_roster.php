<?php
header('Content-Type: application/json');
session_start();
require_once '../db.php';
require_once '../roles.php';
require_once '../require_role.php';

if (empty($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized.']);
    exit;
}

require_role([ROLE_REGISTRAR_STAFF, ROLE_HEAD_REGISTRAR], true);

$section_id = (int)($_GET['section_id'] ?? 0);

if (!$section_id) {
    echo json_encode(['error' => 'Missing section_id.']);
    exit;
}

$db   = new Database();
$conn = $db->connect();

$stmt = $conn->prepare(
    "SELECT student_no, first_name, middle_name, last_name, email, contact_number
     FROM student
     WHERE section_id = ?
     ORDER BY last_name, first_name"
);
$stmt->bind_param('i', $section_id);
$stmt->execute();
$students = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();
$db->close();

echo json_encode(['students' => $students]);
