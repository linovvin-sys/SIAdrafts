<?php
session_start();
require_once '../db.php';
header('Content-Type: application/json');

if (empty($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized.']);
    exit;
}

$db   = new Database();
$conn = $db->connect();

$sections = $conn->query("
    SELECT sec.section_id, sec.section_name, sec.course_id, c.course_code
    FROM section sec
    JOIN course c ON c.course_id = sec.course_id
    ORDER BY c.course_code, sec.section_name
")->fetch_all(MYSQLI_ASSOC);

$subjects = $conn->query("
    SELECT subject_id, subject_code, subject_name, units, year_level, semester
    FROM subject
    ORDER BY year_level, semester, subject_code
")->fetch_all(MYSQLI_ASSOC);

$rooms = $conn->query("
    SELECT room_id, room_name, room_type, capacity
    FROM room
    ORDER BY room_name
")->fetch_all(MYSQLI_ASSOC);

$professors = $conn->query("
    SELECT professor_id, CONCAT(first_name, ' ', last_name) AS professor_name
    FROM professor
    WHERE status_id = 1
    ORDER BY last_name
")->fetch_all(MYSQLI_ASSOC);

$db->close();

echo json_encode([
    'sections'   => $sections,
    'subjects'   => $subjects,
    'rooms'      => $rooms,
    'professors' => $professors,
]);