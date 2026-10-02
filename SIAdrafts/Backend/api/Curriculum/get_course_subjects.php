<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../../../Backend/session_bootstrap.php';
app_session_start();
require_once '../../db.php';
require_once '../../roles.php';
require_once '../../require_role.php';

if (empty($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized.']);
    exit;
}

// Admin is read-only monitoring across every Registrar/Admission/Treasury
// screen (see git history) -- this endpoint was the one gap: the page
// that calls it (Registrar/courses.php) already allows Admin, but this
// is where the actual "no permission" error was coming from.
require_role([ROLE_REGISTRAR_STAFF, ROLE_HEAD_REGISTRAR, ROLE_ADMIN], true);

$course_id = (int)($_GET['course_id'] ?? 0);

if (!$course_id) {
    echo json_encode(['error' => 'Missing course_id.']);
    exit;
}

$db   = new Database();
$conn = $db->connect();

$stmt = $conn->prepare(
    "SELECT sub.subject_id, sub.subject_code, sub.subject_name, sub.units,
            sub.year_level, sub.semester, sc.category_name
     FROM subject sub
     LEFT JOIN subject_category sc ON sc.category_id = sub.category_id
     WHERE sub.course_id = ? AND sub.status = 'Approved'
     ORDER BY sub.year_level, sub.semester, sub.subject_code"
);
$stmt->bind_param('i', $course_id);
$stmt->execute();
$subjects = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

$totalUnits = 0;
foreach ($subjects as $s) {
    $totalUnits += (float)$s['units'];
}

$db->close();

echo json_encode([
    'subjects'    => $subjects,
    'total_units' => $totalUnits,
]);
