<?php
session_start();
require_once '../../db.php';
require_once '../../roles.php';
require_once '../../require_role.php';
require_once '../../csrf.php';
require_once '../../subject_course.php';
require_once '../../curriculum_import.php';
header('Content-Type: application/json');

if (empty($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized.']);
    exit;
}

require_role([ROLE_REGISTRAR_STAFF, ROLE_HEAD_REGISTRAR], true);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed.']);
    exit;
}

csrf_verify();

if (empty($_FILES['curriculum_csv']['tmp_name']) || $_FILES['curriculum_csv']['error'] !== UPLOAD_ERR_OK) {
    echo json_encode(['error' => 'Please choose a CSV file to upload.']);
    exit;
}

$db   = new Database();
$conn = $db->connect();

$result = parse_and_validate_curriculum_csv($conn, $_FILES['curriculum_csv']['tmp_name']);
$db->close();

if (!empty($result['file_error'])) {
    echo json_encode(['error' => $result['file_error']]);
    exit;
}

$counts = ['insert' => 0, 'cross_list' => 0, 'no_action' => 0, 'error' => 0];
foreach ($result['rows'] as $r) $counts[$r['action']]++;

echo json_encode([
    'success' => true,
    'rows' => $result['rows'],
    'has_errors' => $result['has_errors'],
    'counts' => $counts,
]);
