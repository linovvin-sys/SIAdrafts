<?php
require_once __DIR__ . '/../../../Backend/session_bootstrap.php';
app_session_start();
require_once '../../db.php';
require_once '../../require_role.php';
require_once '../../csrf.php';
header('Content-Type: application/json');

if (empty($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized.']);
    exit;
}

require_registrar_tier(REGISTRAR_TIER_STAFF, true);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed.']);
    exit;
}

csrf_verify();

$db   = new Database();
$conn = $db->connect();

$raw  = file_get_contents('php://input');
$data = json_decode($raw, true) ?? $_POST;

$capacity     = (int)($data['capacity']  ?? 40);
$course_id    = (int)($data['course_id'] ?? 0);
$year_level   = (int)($data['year_level'] ?? 0);
$shift        = strtoupper(trim($data['shift'] ?? ''));
$section_no   = (int)($data['section_no'] ?? 0);

// Section names are generated, never free text: <COURSE CODE> <year><shift><no>,
// e.g. "BSIT 1M3". Shift is M(orning)/A(fternoon)/E(vening), 5 sections each,
// 4 year levels -- so a typo or an out-of-scheme name can't be created
// even by calling this endpoint directly.
if ($course_id <= 0 || $year_level < 1 || $year_level > 4
    || !in_array($shift, ['M', 'A', 'E'], true)
    || $section_no < 1 || $section_no > 5) {
    echo json_encode(['error' => 'Choose a course, year level (1-4), shift (M/A/E) and section number (1-5).']);
    exit;
}

$codeStmt = $conn->prepare("SELECT course_code FROM course WHERE course_id = ? LIMIT 1");
$codeStmt->bind_param('i', $course_id);
$codeStmt->execute();
$courseRow = $codeStmt->get_result()->fetch_assoc();
$codeStmt->close();
if (!$courseRow) {
    echo json_encode(['error' => 'Selected course does not exist.']);
    exit;
}
$section_name = $courseRow['course_code'] . ' ' . $year_level . $shift . $section_no;

if ($capacity <= 0) {
    echo json_encode(['error' => 'Capacity must be a positive number.']);
    exit;
}

// Head Registrar submissions go live immediately.
// Registrar Staff submissions need Head Registrar approval first.
$isHeadRegistrar = current_user_is(['Head Registrar']);
$status           = $isHeadRegistrar ? 'Approved' : 'Pending';
$requested_by     = (int)$_SESSION['user_id'];

try {
    $stmt = $conn->prepare("INSERT INTO section (section_name, capacity, course_id, status, requested_by) VALUES (?, ?, ?, ?, ?)");
    $stmt->bind_param('siisi', $section_name, $capacity, $course_id, $status, $requested_by);
    $stmt->execute();
    $stmt->close();
    echo json_encode([
        'success'    => true,
        'section_id' => $conn->insert_id,
        'status'     => $status,
        'message'    => $status === 'Pending'
            ? 'Section submitted for Head Registrar approval.'
            : 'Section added.',
    ]);
} catch (mysqli_sql_exception $e) {
    if ($e->getCode() === 1062) {
        echo json_encode(['error' => "Section \"$section_name\" already exists for this course."]);
    } else {
        error_log('save_section.php: ' . $e->getMessage());
        echo json_encode(['error' => 'Could not save section. Please try again.']);
    }
}

$db->close();