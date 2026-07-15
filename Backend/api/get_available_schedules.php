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
$course_id   = (int)($_GET['course_id']   ?? 0);

if (!$year_level || !$semester || !$school_year || !$course_id) {
    echo json_encode(['error' => 'Missing parameters.']);
    exit;
}

// Every schedule slot for subjects offered in this year_level/semester,
// across ALL sections of the applicant's course — not locked to one section.
$stmt = $conn->prepare(
    "SELECT sub.subject_id, sub.subject_code, sub.subject_name, sub.units, sub.prereq_id,
            sc.category_name,
            sch.schedule_id, sch.day, sch.time_start, sch.time_end,
            sec.section_id, sec.section_name,
            CONCAT(p.first_name, ' ', p.last_name) AS professor_name,
            r.room_name
     FROM subject sub
     JOIN subject_category sc ON sub.category_id = sc.category_id
     JOIN schedule sch        ON sch.subject_id = sub.subject_id
     JOIN section sec         ON sec.section_id = sch.section_id
     LEFT JOIN professor p ON sch.professor_id = p.professor_id
     LEFT JOIN room r      ON sch.room_id = r.room_id
     WHERE sec.course_id = ?
       AND sub.year_level = ? AND sub.semester = ?
       AND sch.semester = ? AND sch.school_year = ?
       AND sch.is_active = 1
     ORDER BY sc.category_name, sub.subject_code, sch.day, sch.time_start"
);
if (!$stmt) {
    http_response_code(500);
    echo json_encode(['error' => 'Database error: ' . $conn->error]);
    exit;
}
$stmt->bind_param('iiiis', $course_id, $year_level, $semester, $semester, $school_year);
$stmt->execute();
$rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();
$conn->close();

// Group: one entry per subject, each with a list of schedule "options"
$subjects = [];
foreach ($rows as $r) {
    $sid = $r['subject_id'];
    if (!isset($subjects[$sid])) {
        $subjects[$sid] = [
            'subject_id'    => (int)$r['subject_id'],
            'subject_code'  => $r['subject_code'],
            'subject_name'  => $r['subject_name'],
            'units'         => $r['units'],
            'category_name' => $r['category_name'],
            'options'       => [],
        ];
    }
    $subjects[$sid]['options'][] = [
        'schedule_id'     => (int)$r['schedule_id'],
        'section_id'      => (int)$r['section_id'],
        'section_name'    => $r['section_name'],
        'day'             => $r['day'],
        'time_start'      => $r['time_start'],
        'time_end'        => $r['time_end'],
        'room_name'       => $r['room_name'],
        'professor_name'  => $r['professor_name'],
    ];
}

// Group by category for display, same shape subject-picker.js already expects
$categories = [];
foreach ($subjects as $sub) {
    $cat = $sub['category_name'];
    if (!isset($categories[$cat])) {
        $categories[$cat] = ['category_name' => $cat, 'subjects' => []];
    }
    $categories[$cat]['subjects'][] = $sub;
}

echo json_encode(['categories' => array_values($categories)]);