<?php
session_start();
require_once '../../db.php';
require_once '../../require_role.php';
require_once '../../csrf.php';
header('Content-Type: application/json');

if (empty($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized.']);
    exit;
}

require_role(['Head Registrar', 'Registrar Staff'], true);

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

$course_id   = (int)($data['course_id'] ?? 0);
$code        = trim($data['subject_code'] ?? '');
$name        = trim($data['subject_name'] ?? '');
$units       = (float)($data['units'] ?? 0);
$category_id = (int)($data['category_id'] ?? 0);
$year_level  = (int)($data['year_level'] ?? 0);
$semester    = (int)($data['semester'] ?? 0);

if (!$course_id || $code === '' || $name === '' || !$category_id || !$year_level || !$semester) {
    echo json_encode(['error' => 'Course, subject code, subject name, category, year level, and semester are required.']);
    exit;
}

if ($units <= 0) {
    echo json_encode(['error' => 'Units must be greater than zero.']);
    exit;
}

// Head Registrar submissions go live immediately.
// Registrar Staff submissions need Head Registrar approval first.
$isHeadRegistrar = current_user_is(['Head Registrar']);
$status          = $isHeadRegistrar ? 'Approved' : 'Pending';
$requested_by    = (int)$_SESSION['user_id'];

$stmt = $conn->prepare("INSERT INTO subject (subject_code, subject_name, units, course_id, category_id, year_level, semester, status, requested_by) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
if (!$stmt) {
    http_response_code(500);
    echo json_encode(['error' => 'Database error: ' . $conn->error]);
    exit;
}
$stmt->bind_param('ssdiiiisi', $code, $name, $units, $course_id, $category_id, $year_level, $semester, $status, $requested_by);

try {
    $stmt->execute();
    echo json_encode([
        'success'    => true,
        'subject_id' => $conn->insert_id,
        'status'     => $status,
        'message'    => $status === 'Pending'
            ? 'Subject submitted for Head Registrar approval.'
            : 'Subject added.',
    ]);
} catch (mysqli_sql_exception $e) {
    if ($e->getCode() === 1062) {
        echo json_encode(['error' => "Subject code \"$code\" already exists."]);
    } else {
        echo json_encode(['error' => 'Could not save subject. Please try again.']);
    }
}

$stmt->close();
$db->close();
