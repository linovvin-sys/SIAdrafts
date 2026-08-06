<?php
session_start();
require_once '../db.php';
require_once '../require_role.php';
require_once '../csrf.php';
header('Content-Type: application/json');

if (empty($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized.']);
    exit;
}

// Both roles may add a course.
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

$code  = trim($data['course_code'] ?? '');
$name  = trim($data['course_name'] ?? '');
$units = (int)($data['total_units'] ?? 0);

if ($code === '' || $name === '') {
    echo json_encode(['error' => 'Course code and name are required.']);
    exit;
}

if ($units <= 0) {
    echo json_encode(['error' => 'Total units must be greater than zero.']);
    exit;
}

// Head Registrar submissions go live immediately.
// Registrar Staff submissions need Head Registrar approval first.
$isHeadRegistrar = current_user_is(['Head Registrar']);
$status           = $isHeadRegistrar ? 'Approved' : 'Pending';
$requested_by     = (int)$_SESSION['user_id'];

$stmt = $conn->prepare("INSERT INTO course (course_code, course_name, total_units, status, requested_by) VALUES (?, ?, ?, ?, ?)");
if (!$stmt) {
    http_response_code(500);
    echo json_encode(['error' => 'Database error: ' . $conn->error]);
    exit;
}
$stmt->bind_param('ssisi', $code, $name, $units, $status, $requested_by);

try {
    $stmt->execute();
    echo json_encode([
        'success'   => true,
        'course_id' => $conn->insert_id,
        'status'    => $status,
        'message'   => $status === 'Pending'
            ? 'Course submitted for Head Registrar approval.'
            : 'Course added.',
    ]);
} catch (mysqli_sql_exception $e) {
    if ($e->getCode() === 1062) {
        echo json_encode(['error' => "Course code \"$code\" already exists."]);
    } else {
        echo json_encode(['error' => 'Could not save course. Please try again.']);
    }
}

$stmt->close();
$db->close();