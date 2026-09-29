<?php
require_once __DIR__ . '/../../require_professor.php';
require_professor(true);
require_once __DIR__ . '/../../db.php';
require_once __DIR__ . '/../../csrf.php';
require_once __DIR__ . '/../../Professor/group_data.php';
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed.']);
    exit;
}

csrf_verify();

$scheduleId = (int)($_POST['schedule_id'] ?? 0);
$groupCount = (int)($_POST['group_count'] ?? 0);

if (!$scheduleId) {
    echo json_encode(['error' => 'Missing schedule_id.']);
    exit;
}

$db   = new Database();
$conn = $db->connect();

$error = generate_random_groups($conn, $scheduleId, (int)$_SESSION['professor_id'], $groupCount);

if ($error !== null) {
    $db->close();
    echo json_encode(['error' => $error]);
    exit;
}

$groups = get_class_groups($conn, $scheduleId, (int)$_SESSION['professor_id']);
$db->close();

echo json_encode(['success' => true, 'groups' => $groups]);
