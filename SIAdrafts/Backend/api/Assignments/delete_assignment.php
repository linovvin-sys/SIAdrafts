<?php
require_once __DIR__ . '/../../require_professor.php';
require_professor(true);
require_once __DIR__ . '/../../db.php';
require_once __DIR__ . '/../../csrf.php';
require_once __DIR__ . '/../../Professor/assignment_data.php';
require_once __DIR__ . '/submission_attachments.php';
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed.']);
    exit;
}

csrf_verify();

$assignmentId = (int)($_POST['assignment_id'] ?? 0);

if (!$assignmentId) {
    echo json_encode(['error' => 'Missing assignment_id.']);
    exit;
}

$db   = new Database();
$conn = $db->connect();

$filePaths = delete_assignment($conn, $assignmentId, (int)$_SESSION['professor_id']);
$db->close();

if ($filePaths === null) {
    echo json_encode(['error' => 'Assignment not found.']);
    exit;
}

foreach ($filePaths as $path) {
    delete_submission_file($path);
}

echo json_encode(['success' => true]);
