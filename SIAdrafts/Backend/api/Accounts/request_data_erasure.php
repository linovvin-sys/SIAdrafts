<?php
require_once __DIR__ . '/../../require_student.php';
require_student(true);
require_once __DIR__ . '/../../db.php';
require_once __DIR__ . '/../../csrf.php';
require_once __DIR__ . '/../../rate_limit.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed.']);
    exit;
}

csrf_verify();

if (!rate_limit_check('data_erasure_request', 3, 3600)) {
    http_response_code(429);
    echo json_encode(['error' => 'Too many requests. Please wait before submitting another.']);
    exit;
}

$studentId = (int)$_SESSION['student_id'];

$db   = new Database();
$conn = $db->connect();

// Not an automatic delete: academic/financial records have their own
// recordkeeping requirements that a raw erasure request can't just
// override. This logs the request for a human (Admin / whoever ends up
// holding the DPO role) to review and act on deliberately.
$existing = $conn->prepare(
    "SELECT id FROM data_privacy_request WHERE applicant_id = ? AND request_type = 'erasure' AND status = 'pending'"
);
$existing->bind_param('i', $studentId);
$existing->execute();
$hasPending = (bool)$existing->get_result()->fetch_assoc();
$existing->close();

if ($hasPending) {
    $db->close();
    echo json_encode(['error' => 'You already have a pending data deletion request.']);
    exit;
}

$stmt = $conn->prepare(
    "INSERT INTO data_privacy_request (applicant_id, request_type, status) VALUES (?, 'erasure', 'pending')"
);
$stmt->bind_param('i', $studentId);
$stmt->execute();
$stmt->close();
$db->close();

echo json_encode([
    'success' => true,
    'message' => 'Your request has been submitted. The Registrar\'s Office will review it and contact you.',
]);
