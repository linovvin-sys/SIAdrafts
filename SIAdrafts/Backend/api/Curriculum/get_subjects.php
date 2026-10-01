<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../../../Backend/session_bootstrap.php';
app_session_start();
require_once '../../db.php';
require_once '../../roles.php';
require_once '../../require_role.php';

$db   = new Database();
$conn = $db->connect();


if (empty($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized.']);
    exit;
}

require_role([ROLE_STAFF, ROLE_ADMIN], true);

$year_level  = (int)($_GET['year_level']  ?? 0);
$semester    = (int)($_GET['semester']    ?? 0);
$school_year = trim($_GET['school_year']  ?? '');
$section_id  = (int)($_GET['section_id'] ?? 0);

if (!$year_level || !$semester || !$school_year) {
    echo json_encode(['error' => 'Missing parameters.']);
    exit;
}

try {
    $stmt = $conn->prepare(
        "SELECT sub.subject_id, sub.subject_code, sub.subject_name, sub.units,
                sub.prereq_id, sc.category_name,
                sch.day, sch.time_start, sch.time_end,
                CONCAT(p.first_name, ' ', p.last_name) AS professor_name,
                r.room_name
         FROM subject sub
         JOIN subject_category sc ON sub.category_id = sc.category_id
         LEFT JOIN schedule sch ON sch.subject_id = sub.subject_id
             AND sch.school_year = ?
             AND sch.semester    = ?
             AND sch.section_id  = ?
         LEFT JOIN professor p ON sch.professor_id = p.professor_id
         LEFT JOIN room r       ON sch.room_id = r.room_id
         WHERE sub.year_level = ? AND sub.semester = ? AND sub.status = 'Approved'
         ORDER BY sc.category_name, sub.subject_code"
    );

    // Placeholder order matches the query exactly:
    // school_year(s), semester(i), section_id(i), year_level(i), semester(i) again
    $stmt->bind_param('siiii', $school_year, $semester, $section_id, $year_level, $semester);
    $stmt->execute();

    $result = $stmt->get_result();
    $subjects = $result->fetch_all(MYSQLI_ASSOC);
    $stmt->close();

    $categories = [];
    foreach ($subjects as $sub) {
        $cat = $sub['category_name'];
        if (!isset($categories[$cat])) {
            $categories[$cat] = ['category_name' => $cat, 'subjects' => []];
        }
        $categories[$cat]['subjects'][] = $sub;
    }

    $conn->close();

    echo json_encode(['categories' => array_values($categories)]);
} catch (mysqli_sql_exception $e) {
    http_response_code(500);
    error_log('get_subjects.php: ' . $e->getMessage());
    echo json_encode(['error' => 'A database error occurred. Please try again.']);
    exit;
}