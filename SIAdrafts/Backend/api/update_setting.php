<?php
session_start();
header('Content-Type: application/json');

require_once '../db.php';
require_once '../roles.php';
require_once '../require_role.php';
require_once '../csrf.php';
require_once '../settings.php';

if (empty($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized.']);
    exit;
}

require_role([ROLE_ADMIN, ROLE_HEAD_REGISTRAR], true);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed.']);
    exit;
}

csrf_verify();

$key   = trim($_POST['setting_key'] ?? '');
$value = trim($_POST['setting_value'] ?? '');

$allowedKeys = ['current_school_year', 'current_semester'];
if (!in_array($key, $allowedKeys, true) || $value === '') {
    http_response_code(422);
    echo json_encode(['error' => 'Invalid setting key or value.']);
    exit;
}

if ($key === 'current_school_year' && !preg_match('/^\d{4}-\d{4}$/', $value)) {
    http_response_code(422);
    echo json_encode(['error' => 'current_school_year must be in YYYY-YYYY format.']);
    exit;
}

if ($key === 'current_semester' && !in_array($value, ['1', '2', '3'], true)) {
    http_response_code(422);
    echo json_encode(['error' => 'current_semester must be 1, 2, or 3.']);
    exit;
}

set_setting($key, $value, (int)$_SESSION['user_id']);

echo json_encode(['success' => true]);
