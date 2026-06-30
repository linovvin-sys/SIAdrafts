<?php
session_start();
require_once '../db.php';

header('Content-Type: application/json');

if (empty($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Unauthorized.']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Method not allowed.']);
    exit;
}

$db   = new Database();
$conn = $db->connect();

$raw  = file_get_contents('php://input');
$data = json_decode($raw, true);

if (!is_array($data)) {
    echo json_encode(['success' => false, 'error' => 'Invalid request body.']);
    exit;
}

$enrollment_id = (int)($data['enrollment_id'] ?? 0);
$amount_due    = round((float)($data['amount_due'] ?? 0), 2);
$due_date      = trim($data['due_date'] ?? '');

if (!$enrollment_id) {
    echo json_encode(['success' => false, 'error' => 'Missing enrollment.']);
    exit;
}
if ($amount_due <= 0) {
    echo json_encode(['success' => false, 'error' => 'Amount due must be greater than zero.']);
    exit;
}
if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $due_date)) {
    echo json_encode(['success' => false, 'error' => 'Invalid due date.']);
    exit;
}

// Confirm the enrollment exists and doesn't already have a payment row.
$check = $conn->prepare("SELECT enrollment_id FROM enrollment WHERE enrollment_id = ? LIMIT 1");
$check->bind_param('i', $enrollment_id);
$check->execute();
if (!$check->get_result()->fetch_assoc()) {
    $check->close();
    echo json_encode(['success' => false, 'error' => 'Enrollment not found.']);
    exit;
}
$check->close();

$dup = $conn->prepare("SELECT payment_id FROM payment WHERE enrollment_id = ? LIMIT 1");
$dup->bind_param('i', $enrollment_id);
$dup->execute();
if ($dup->get_result()->fetch_assoc()) {
    $dup->close();
    echo json_encode(['success' => false, 'error' => 'Payment is already set up for this enrollment.']);
    exit;
}
$dup->close();

$stmt = $conn->prepare(
    "INSERT INTO payment (enrollment_id, amount_due, downpayment, due_date, payment_status)
     VALUES (?, ?, 0, ?, 'Unpaid')"
);
$stmt->bind_param('ids', $enrollment_id, $amount_due, $due_date);

if (!$stmt->execute()) {
    echo json_encode(['success' => false, 'error' => 'Database error: ' . $stmt->error]);
    exit;
}

$payment_id = (int)$conn->insert_id;
$stmt->close();
$conn->close();

echo json_encode(['success' => true, 'payment_id' => $payment_id]);