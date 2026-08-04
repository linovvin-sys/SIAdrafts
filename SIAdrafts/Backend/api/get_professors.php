<?php
session_start();
require_once '../db.php';
require_once '../require_role.php';
header('Content-Type: application/json');

if (empty($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized.']);
    exit;
}

require_role(['Registrar Staff', 'Head Registrar'], true);

$db   = new Database();
$conn = $db->connect();

$professors = $conn->query("
    SELECT p.professor_id, p.first_name, p.middle_name, p.last_name,
           p.department_id, d.department_code, d.department_name,
           p.status_id, st.status_name
    FROM professor p
    JOIN department d ON d.department_id = p.department_id
    JOIN statuses st  ON st.status_id = p.status_id
    ORDER BY p.last_name, p.first_name
")->fetch_all(MYSQLI_ASSOC);

$departments = $conn->query("
    SELECT department_id, department_code, department_name
    FROM department
    ORDER BY department_name
")->fetch_all(MYSQLI_ASSOC);

$db->close();

echo json_encode([
    'professors'  => $professors,
    'departments' => $departments,
]);
