<?php
session_start();
require_once '../db.php';
require_once '../require_role.php';
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

$db   = new Database();
$conn = $db->connect();

$raw  = file_get_contents('php://input');
$data = json_decode($raw, true) ?? $_POST;

$enrollment_id = (int)($data['enrollment_id'] ?? 0);
$subject_id    = (int)($data['subject_id'] ?? 0);
$schedule_id   = !empty($data['schedule_id']) ? (int)$data['schedule_id'] : null;

if (!$enrollment_id || !$subject_id) {
    echo json_encode(['error' => 'Missing required fields.']);
    exit;
}

// Make sure the enrollment actually exists.
$check = $conn->prepare("SELECT enrollment_id FROM enrollment WHERE enrollment_id = ? LIMIT 1");
$check->bind_param('i', $enrollment_id);
$check->execute();
if (!$check->get_result()->fetch_assoc()) {
    echo json_encode(['error' => 'Enrollment not found.']);
    exit;
}
$check->close();

// Guard against re-adding a subject the student is already active in.
$dupe = $conn->prepare(
    "SELECT enrollment_subject_id FROM enrollment_subject
     WHERE enrollment_id = ? AND subject_id = ? AND status != 'Dropped'
     LIMIT 1"
);
$dupe->bind_param('ii', $enrollment_id, $subject_id);
$dupe->execute();
if ($dupe->get_result()->fetch_assoc()) {
    echo json_encode(['error' => 'Student is already enrolled in this subject.']);
    exit;
}
$dupe->close();

$stmt = $conn->prepare(
    "INSERT INTO enrollment_subject (enrollment_id, subject_id, schedule_id, status)
     VALUES (?, ?, ?, 'Enrolled')"
);
if (!$stmt) {
    http_response_code(500);
    echo json_encode(['error' => 'Database error: ' . $conn->error]);
    exit;
}
$stmt->bind_param('iii', $enrollment_id, $subject_id, $schedule_id);

try {
    $stmt->execute();
    echo json_encode([
        'success'               => true,
        'enrollment_subject_id' => $conn->insert_id,
        'message'               => 'Subject added.',
    ]);
} catch (mysqli_sql_exception $e) {
    echo json_encode(['error' => 'Could not add subject. Please try again.']);
}

$stmt->close();
$db->close();