<?php
session_start();
header('Content-Type: application/json');
require_once '../db.php';
require_once '../roles.php';
require_once '../require_role.php';

if (empty($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized.']);
    exit;
}

// Only Registrar staff/head may look up students for add/drop.
require_role([ROLE_REGISTRAR_STAFF, ROLE_HEAD_REGISTRAR], true);

$db   = new Database();
$conn = $db->connect();

$q = trim($_GET['student_no'] ?? '');

if ($q === '') {
    echo json_encode(['error' => 'Please enter a student ID.']);
    exit;
}

// Exact match first (e.g. "2026-00005"); fall back to a partial match
// so a staff member can type a shorter fragment like "2026-0005".
$stmt = $conn->prepare(
    "SELECT student_id, student_no, student_name, first_name, last_name, middle_name, section_id, applicant_id
     FROM student
     WHERE student_no = ?
     LIMIT 1"
);
$stmt->bind_param('s', $q);
$stmt->execute();
$student = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$student) {
    $like = '%' . $q . '%';
    $stmt = $conn->prepare(
        "SELECT student_id, student_no, student_name, first_name, last_name, middle_name, section_id, applicant_id
         FROM student
         WHERE student_no LIKE ?
         ORDER BY student_no
         LIMIT 1"
    );
    $stmt->bind_param('s', $like);
    $stmt->execute();
    $student = $stmt->get_result()->fetch_assoc();
    $stmt->close();
}

if (!$student) {
    echo json_encode(['error' => "No student found with ID \"$q\"."]);
    exit;
}

// Most recent enrollment record for this student (current school year / semester).
$stmt = $conn->prepare(
    "SELECT e.enrollment_id, e.school_year, e.semester, e.year_level, e.status AS enrollment_status,
            e.section_id, sec.section_name, c.course_id, c.course_code, c.course_name
     FROM enrollment e
     LEFT JOIN section sec ON sec.section_id = e.section_id
     LEFT JOIN course  c   ON c.course_id = sec.course_id
     WHERE e.student_id = ?
     ORDER BY e.school_year DESC, e.semester DESC, e.enrollment_id DESC
     LIMIT 1"
);
$stmt->bind_param('i', $student['applicant_id']);
$stmt->execute();
$enrollment = $stmt->get_result()->fetch_assoc();
$stmt->close();

$subjects = [];
if ($enrollment) {
    // 1) Fetch subjects with their DIRECT schedule link (only ever set for Irregular students)
    $stmt = $conn->prepare(
        "SELECT es.enrollment_subject_id, es.subject_id, es.schedule_id, es.status,
                sub.subject_code, sub.subject_name, sub.units,
                sch.day, sch.time_start, sch.time_end,
                CONCAT(p.first_name, ' ', p.last_name) AS professor_name
         FROM enrollment_subject es
         JOIN subject sub        ON sub.subject_id = es.subject_id
         LEFT JOIN schedule sch  ON sch.schedule_id = es.schedule_id
         LEFT JOIN professor p   ON p.professor_id = sch.professor_id
         WHERE es.enrollment_id = ? AND es.status != 'Dropped'
         ORDER BY sub.subject_code"
    );
    $stmt->bind_param('i', $enrollment['enrollment_id']);
    $stmt->execute();
    $subjects = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();

    // 2) For any subject with no direct schedule (the normal Regular/Transferee case),
    //    fall back to the section's approved schedule for that subject/term.
    $needsFallback = array_values(array_filter($subjects, fn($s) => empty($s['day'])));
    if ($needsFallback && $enrollment['section_id']) {
        $stmt = $conn->prepare(
            "SELECT sch.subject_id, sch.day, sch.time_start, sch.time_end,
                    CONCAT(p.first_name, ' ', p.last_name) AS professor_name
             FROM schedule sch
             LEFT JOIN professor p ON p.professor_id = sch.professor_id
             WHERE sch.section_id  = ?
               AND sch.school_year = ?
               AND sch.semester    = ?
               AND sch.status      = 'Approved'
             ORDER BY sch.schedule_id"
        );
        $stmt->bind_param('isi', $enrollment['section_id'], $enrollment['school_year'], $enrollment['semester']);
        $stmt->execute();
        $sectionSchedules = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $stmt->close();

        $bySubject = [];
        foreach ($sectionSchedules as $row) {
            if (!isset($bySubject[$row['subject_id']])) $bySubject[$row['subject_id']] = $row;
        }

        foreach ($subjects as &$sub) {
            if (empty($sub['day']) && isset($bySubject[$sub['subject_id']])) {
                $fallback = $bySubject[$sub['subject_id']];
                $sub['day']            = $fallback['day'];
                $sub['time_start']     = $fallback['time_start'];
                $sub['time_end']       = $fallback['time_end'];
                $sub['professor_name'] = $fallback['professor_name'];
            }
        }
        unset($sub);
    }
}

$db->close();

echo json_encode([
    'student'    => $student,
    'enrollment' => $enrollment,
    'subjects'   => $subjects,
]);