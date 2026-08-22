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

$section_name = trim($data['section_name'] ?? '');
$capacity     = (int)($data['capacity']  ?? 40);
$course_id    = (int)($data['course_id'] ?? 0);

if ($section_name === '' || $course_id <= 0) {
    echo json_encode(['error' => 'Section name and course are required.']);
    exit;
}

if ($capacity <= 0) {
    echo json_encode(['error' => 'Capacity must be a positive number.']);
    exit;
}

// Head Registrar submissions go live immediately.
// Registrar Staff submissions need Head Registrar approval first.
$isHeadRegistrar = current_user_is(['Head Registrar']);
$status           = $isHeadRegistrar ? 'Approved' : 'Pending';
$requested_by     = (int)$_SESSION['user_id'];

$stmt = $conn->prepare("INSERT INTO section (section_name, capacity, course_id, status, requested_by) VALUES (?, ?, ?, ?, ?)");
if (!$stmt) {
    http_response_code(500);
    echo json_encode(['error' => 'Database error: ' . $conn->error]);
    exit;
}
$stmt->bind_param('siisi', $section_name, $capacity, $course_id, $status, $requested_by);

try {
    $stmt->execute();
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
        echo json_encode(['error' => 'Could not save section. Please try again.']);
    }
}

$stmt->close();
$db->close();