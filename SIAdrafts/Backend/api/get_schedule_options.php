<?php
session_start();
require_once '../db.php';
require_once '../roles.php';
require_once '../require_role.php';
header('Content-Type: application/json');

if (empty($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized.']);
    exit;
}

require_role([ROLE_REGISTRAR_STAFF, ROLE_HEAD_REGISTRAR], true);

$db   = new Database();
$conn = $db->connect();

$sections = $conn->query("
    SELECT sec.section_id, sec.section_name, sec.course_id, c.course_code
    FROM section sec
    JOIN course c ON c.course_id = sec.course_id
    WHERE sec.status = 'Approved' AND c.status = 'Approved'
    ORDER BY c.course_code, sec.section_name
")->fetch_all(MYSQLI_ASSOC);

$subjects = $conn->query("
    SELECT sub.subject_id, sub.subject_code, sub.subject_name, sub.units, sub.year_level, sub.semester, sub.course_id,
           sc.category_name, sc.department_id
    FROM subject sub
    LEFT JOIN subject_category sc ON sc.category_id = sub.category_id
    WHERE sub.status = 'Approved'
    ORDER BY sub.year_level, sub.semester, sub.subject_code
")->fetch_all(MYSQLI_ASSOC);

// subject_course cross-lists a subject to multiple courses (e.g. Gen-Ed
// subjects shared by BSPSYCH and BSCRIM) — it's the source of truth
// whenever it has rows for a subject. subject.course_id defaults to 1
// (BSIT) for nearly everything, so it's only used as a fallback for
// subjects with no subject_course rows at all (see subject_course.php).
$subjectCourseMap = [];
$scRows = $conn->query("SELECT subject_id, course_id FROM subject_course")->fetch_all(MYSQLI_ASSOC);
foreach ($scRows as $row) {
    $subjectCourseMap[(int)$row['subject_id']][] = (int)$row['course_id'];
}
foreach ($subjects as &$subject) {
    $sid = (int)$subject['subject_id'];
    $subject['course_ids'] = $subjectCourseMap[$sid] ?? [(int)$subject['course_id']];
}
unset($subject);

$rooms = $conn->query("
    SELECT room_id, room_name, room_type, capacity
    FROM room
    ORDER BY room_name
")->fetch_all(MYSQLI_ASSOC);

$professors = $conn->query("
    SELECT professor_id, CONCAT(first_name, ' ', last_name) AS professor_name, department_id
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