<?php
session_start();
require_once '../db.php';
require_once '../roles.php';
require_once '../require_role.php';
require_once '../csrf.php';

header('Content-Type: application/json');

if (empty($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Unauthorized.']);
    exit;
}

require_role([ROLE_ADMIN], true);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Method not allowed.']);
    exit;
}

csrf_verify();

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

$conn->begin_transaction();

try {
    // Lock the enrollment row so two concurrent setup requests for the same
    // enrollment can't both pass the duplicate-check below before either
    // has inserted — matches the FOR UPDATE pattern used in record_payment.php.
    $check = $conn->prepare("SELECT enrollment_id FROM enrollment WHERE enrollment_id = ? LIMIT 1 FOR UPDATE");
    $check->bind_param('i', $enrollment_id);
    $check->execute();
    if (!$check->get_result()->fetch_assoc()) {
        $check->close();
        throw new RuntimeException('Enrollment not found.');
    }
    $check->close();

    $dup = $conn->prepare("SELECT payment_id FROM payment WHERE enrollment_id = ? LIMIT 1");
    $dup->bind_param('i', $enrollment_id);
    $dup->execute();
    if ($dup->get_result()->fetch_assoc()) {
        $dup->close();
        throw new RuntimeException('Payment is already set up for this enrollment.');
    }
    $dup->close();

    $stmt = $conn->prepare(
        "INSERT INTO payment (enrollment_id, amount_due, downpayment, due_date, payment_status)
         VALUES (?, ?, 0, ?, 'Unpaid')"
    );
    $stmt->bind_param('ids', $enrollment_id, $amount_due, $due_date);

    if (!$stmt->execute()) {
        $stmt->close();
        throw new RuntimeException('Database error: ' . $conn->error);
    }

    $payment_id = (int)$conn->insert_id;
    $stmt->close();
    $conn->commit();

    echo json_encode(['success' => true, 'payment_id' => $payment_id]);
} catch (RuntimeException $e) {
    $conn->rollback();
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
$conn->close();