<?php
require_once __DIR__ . '/../../require_professor.php';
require_professor(true);
require_once __DIR__ . '/../../db.php';
require_once __DIR__ . '/../../csrf.php';
require_once __DIR__ . '/../../Professor/announcement_data.php';
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed.']);
    exit;
}

csrf_verify();

$announcementId = (int)($_POST['announcement_id'] ?? 0);

if (!$announcementId) {
    echo json_encode(['error' => 'Missing announcement_id.']);
    exit;
}

$db   = new Database();
$conn = $db->connect();

$error = delete_announcement($conn, $announcementId, (int)$_SESSION['professor_id']);
$db->close();

if ($error !== null) {
    echo json_encode(['error' => $error]);
    exit;
}

echo json_encode(['success' => true]);
