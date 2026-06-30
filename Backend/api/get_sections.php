<?php
header('Content-Type: application/json');
session_start();
require_once '../db.php';

$db   = new Database();
$conn = $db->connect();

if (empty($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized.']);
    exit;
}

$year_level  = (int)($_GET['year_level']  ?? 0);
$semester    = (int)($_GET['semester']    ?? 0);
$school_year = trim($_GET['school_year']  ?? '');

if (!$year_level || !$semester || !$school_year) {
    echo json_encode(['error' => 'Missing parameters.']);
    exit;
}

// 1) Find sections that actually have a schedule for this year_level / semester / school_year.
$stmt = $conn->prepare(
    "SELECT DISTINCT sec.section_id, sec.section_name
     FROM section sec
     JOIN schedule sch ON sch.section_id = sec.section_id
     JOIN subject sub  ON sub.subject_id = sch.subject_id
     WHERE sub.year_level = ? AND sub.semester = ?
       AND sch.semester = ? AND sch.school_year = ?
     ORDER BY sec.section_name"
);
if (!$stmt) {
    http_response_code(500);
    echo json_encode(['error' => 'Database error: ' . $conn->error]);
    exit;
}
$stmt->bind_param('iiis', $year_level, $semester, $semester, $school_year);
$stmt->execute();
$sections = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

if (empty($sections)) {
    echo json_encode(['sections' => []]);
    exit;
}

// 2) For each section, pull its premade subject list (with schedule/professor/room).
$subStmt = $conn->prepare(
    "SELECT sub.subject_id, sub.subject_code, sub.subject_name, sub.units,
            sc.category_name,
            sch.day, sch.time_start, sch.time_end,
            CONCAT(p.first_name, ' ', p.last_name) AS professor_name,
            r.room_name
     FROM subject sub
     JOIN subject_category sc ON sub.category_id = sc.category_id
     JOIN schedule sch        ON sch.subject_id = sub.subject_id
     LEFT JOIN professor p ON sch.professor_id = p.professor_id
     LEFT JOIN room r      ON sch.room_id = r.room_id
     WHERE sub.year_level = ? AND sub.semester = ?
       AND sch.semester = ? AND sch.school_year = ? AND sch.section_id = ?
     ORDER BY sc.category_name, sub.subject_code"
);
if (!$subStmt) {
    http_response_code(500);
    echo json_encode(['error' => 'Database error: ' . $conn->error]);
    exit;
}

$out = [];
foreach ($sections as $sec) {
    $section_id = (int)$sec['section_id'];
    $subStmt->bind_param('iiisi', $year_level, $semester, $semester, $school_year, $section_id);
    $subStmt->execute();
    $subjects = $subStmt->get_result()->fetch_all(MYSQLI_ASSOC);

    $total_units = 0;
    foreach ($subjects as $s) {
        $total_units += (float)$s['units'];
    }

    $out[] = [
        'section_id'   => $section_id,
        'section_name' => $sec['section_name'],
        'subjects'     => $subjects,
        'subject_ids'  => array_map(fn($s) => (int)$s['subject_id'], $subjects),
        'total_units'  => $total_units,
    ];
}
$subStmt->close();
$conn->close();

echo json_encode(['sections' => $out]);