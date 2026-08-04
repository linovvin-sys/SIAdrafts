<?php
session_start();
require_once '../db.php';
require_once '../roles.php';
require_once '../require_role.php';

if (empty($_SESSION['user_id'])) {
    http_response_code(401);
    exit('Unauthorized.');
}

require_role([ROLE_ADMISSION, ROLE_ADMIN], true);

$document_id = (int)($_GET['document_id'] ?? 0);

if (!$document_id) {
    http_response_code(400);
    exit('Missing document_id.');
}

$db   = new Database();
$conn = $db->connect();

$stmt = $conn->prepare(
    "SELECT document_name, file_path FROM applicant_documents WHERE document_id = ? AND source = 'applicant'"
);
$stmt->bind_param('i', $document_id);
$stmt->execute();
$doc = $stmt->get_result()->fetch_assoc();
$stmt->close();
$conn->close();

if (!$doc || empty($doc['file_path'])) {
    http_response_code(404);
    exit('No soft copy on file for this document.');
}

// file_path is stored as 'requirements/<generated-name>.<ext>' — basename()
// strips any directory traversal a corrupted row might otherwise carry.
$uploadDir = realpath(__DIR__ . '/../uploads');
$fullPath  = $uploadDir . '/requirements/' . basename($doc['file_path']);

if (!is_file($fullPath)) {
    http_response_code(404);
    exit('File no longer exists.');
}

$ext = strtolower(pathinfo($fullPath, PATHINFO_EXTENSION));
$mimeByExt = [
    'pdf'  => 'application/pdf',
    'jpg'  => 'image/jpeg',
    'jpeg' => 'image/jpeg',
    'png'  => 'image/png',
];
$contentType = $mimeByExt[$ext] ?? 'application/octet-stream';

header('Content-Type: ' . $contentType);
header('Content-Length: ' . filesize($fullPath));
header('Content-Disposition: inline; filename="' . rawurlencode($doc['document_name'] . '.' . $ext) . '"');
header('X-Content-Type-Options: nosniff');
readfile($fullPath);
