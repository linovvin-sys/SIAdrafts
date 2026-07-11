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

// Optional filters — default to nothing (return everything) if not passed.
$school_year = trim($_GET['school_year'] ?? '');
$semester    = (int)($_GET['semester'] ?? 0);

$sql = "
    SELECT
        sch.schedule_id, sch.subject_id, sch.professor_id, sch.section_id, sch.room_id,
        sch.day, sch.time_start, sch.time_end, sch.school_year, sch.semester,
        sub.subject_code, sub.subject_name, sub.year_level,
        sec.section_name, sec.course_id,
        c.course_code,
        r.room_name,
        CONCAT(p.first_name, ' ', p.last_name) AS professor_name
    FROM schedule sch
    JOIN subject sub       ON sub.subject_id = sch.subject_id
    JOIN section sec       ON sec.section_id = sch.section_id
    JOIN course c          ON c.course_id    = sec.course_id
    JOIN room r            ON r.room_id      = sch.room_id
    LEFT JOIN professor p  ON p.professor_id = sch.professor_id
";

$conds  = [];
$params = [];
$types  = '';

if ($school_year !== '') {
    $conds[]  = 'sch.school_year = ?';
    $params[] = $school_year;
    $types   .= 's';
}
if ($semester) {
    $conds[]  = 'sch.semester = ?';
    $params[] = $semester;
    $types   .= 'i';
}
if ($conds) {
    $sql .= ' WHERE ' . implode(' AND ', $conds);
}

$sql .= "
    ORDER BY c.course_code, sub.year_level, sec.section_name, sch.subject_id,
             FIELD(sch.day, 'Monday','Tuesday','Wednesday','Thursday','Friday','Saturday','Sunday')
";

$stmt = $conn->prepare($sql);
if (!$stmt) {
    http_response_code(500);
    echo json_encode(['error' => 'Database error: ' . $conn->error]);
    exit;
}
if ($types) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();
$db->close();

echo json_encode($rows);