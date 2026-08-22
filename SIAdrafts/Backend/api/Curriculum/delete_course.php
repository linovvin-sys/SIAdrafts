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

// Only the Head Registrar can remove a course.
require_role(['Head Registrar'], true);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed.']);
    exit;
}

csrf_verify();

$db   = new Database();
$conn = $db->connect();

$raw       = file_get_contents('php://input');
$data      = json_decode($raw, true) ?? $_POST;
$course_id = (int)($data['course_id'] ?? 0);

if (!$course_id) {
    echo json_encode(['error' => 'No course specified.']);
    exit;
}

// Guard: don't allow deleting a course that still has sections tied to it.
$check = $conn->prepare("SELECT COUNT(*) AS cnt FROM section WHERE course_id = ?");
$check->bind_param('i', $course_id);
$check->execute();
$cnt = $check->get_result()->fetch_assoc()['cnt'] ?? 0;
$check->close();

if ($cnt > 0) {
    echo json_encode(['error' => 'Cannot remove this course while it still has sections. Remove the sections first.']);
    exit;
}

$stmt = $conn->prepare("DELETE FROM course WHERE course_id = ?");
if (!$stmt) {
    http_response_code(500);
    echo json_encode(['error' => 'Database error: ' . $conn->error]);
    exit;
}
$stmt->bind_param('i', $course_id);

if (!$stmt->execute()) {
    $stmt->close();
    echo json_encode(['error' => 'Database error: ' . $conn->error]);
    exit;
}

$deleted = $stmt->affected_rows;
$stmt->close();
$db->close();

echo json_encode(['success' => true, 'deleted' => $deleted]);