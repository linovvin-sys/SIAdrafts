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

// Only the Head Registrar can remove a section.
require_role(['Head Registrar'], true);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed.']);
    exit;
}

csrf_verify();

$db   = new Database();
$conn = $db->connect();

$raw        = file_get_contents('php://input');
$data       = json_decode($raw, true) ?? $_POST;
$section_id = (int)($data['section_id'] ?? 0);

if (!$section_id) {
    echo json_encode(['error' => 'No section specified.']);
    exit;
}

// Guard: don't allow deleting a section that still has enrolled students or schedules.
$check = $conn->prepare(
    "SELECT
        (SELECT COUNT(*) FROM student  WHERE section_id = ?) AS student_cnt,
        (SELECT COUNT(*) FROM schedule WHERE section_id = ?) AS schedule_cnt"
);
$check->bind_param('ii', $section_id, $section_id);
$check->execute();
$row = $check->get_result()->fetch_assoc();
$check->close();

if (($row['student_cnt'] ?? 0) > 0) {
    echo json_encode(['error' => 'Cannot remove this section — students are still enrolled in it.']);
    exit;
}
if (($row['schedule_cnt'] ?? 0) > 0) {
    echo json_encode(['error' => 'Cannot remove this section — it still has class schedules. Remove those first.']);
    exit;
}

$stmt = $conn->prepare("DELETE FROM section WHERE section_id = ?");
if (!$stmt) {
    http_response_code(500);
    echo json_encode(['error' => 'Database error: ' . $conn->error]);
    exit;
}
$stmt->bind_param('i', $section_id);

if (!$stmt->execute()) {
    $stmt->close();
    echo json_encode(['error' => 'Database error: ' . $conn->error]);
    exit;
}

$deleted = $stmt->affected_rows;
$stmt->close();
$db->close();

echo json_encode(['success' => true, 'deleted' => $deleted]);