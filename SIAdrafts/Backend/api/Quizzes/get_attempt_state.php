<?php
require_once __DIR__ . '/../../require_student.php';
require_student(true);
require_once __DIR__ . '/../../db.php';
require_once __DIR__ . '/../../Student/quiz_data.php';
header('Content-Type: application/json');

$attemptId = (int)($_GET['attempt_id'] ?? 0);
if (!$attemptId) {
    echo json_encode(['error' => 'Missing attempt_id.']);
    exit;
}

$db = new Database();
$conn = $db->connect();
$state = get_attempt_state($conn, $attemptId, (int)$_SESSION['student_id']);
$db->close();

if ($state === null) {
    echo json_encode(['error' => 'Attempt not found.']);
    exit;
}

// deadline_at comes straight from the DB as a naive "Y-m-d H:i:s" string
// in MySQL's own UTC clock -- converted to an explicit UTC ISO string here
// so the client's `new Date(...)` can't misread it as local time (see
// parse_db_utc_datetime()'s doc comment for why that was silently
// expiring every quiz attempt within seconds of starting).
if (!empty($state['deadline_at'])) {
    $state['deadline_at'] = utc_datetime_to_iso($state['deadline_at']);
}

echo json_encode(['success' => true, 'attempt' => $state]);
