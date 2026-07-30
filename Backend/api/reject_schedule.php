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

require_role(['Head Registrar'], true);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed.']);
    exit;
}

$db   = new Database();
$conn = $db->connect();

$raw         = file_get_contents('php://input');
$data        = json_decode($raw, true) ?? $_POST;
$schedule_id = (int)($data['schedule_id'] ?? 0);
$reason      = trim($data['reason'] ?? '');
$reviewer_id = (int)$_SESSION['user_id'];

if (!$schedule_id) {
    echo json_encode(['error' => 'No schedule specified.']);
    exit;
}

$stmt = $conn->prepare(
    "UPDATE schedule
     SET status = 'Rejected', is_active = 0, reviewed_by = ?, review_note = ?
     WHERE schedule_id = ? AND status = 'Pending'"
);
$stmt->bind_param('isi', $reviewer_id, $reason, $schedule_id);

if (!$stmt->execute()) {
    $stmt->close();
    echo json_encode(['error' => 'Database error: ' . $conn->error]);
    exit;
}

$affected = $stmt->affected_rows;
$stmt->close();
$db->close();

if ($affected === 0) {
    echo json_encode(['error' => 'This schedule is no longer pending.']);
    exit;
}

echo json_encode(['success' => true, 'message' => 'Schedule rejected.']);
