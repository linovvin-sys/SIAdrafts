<?php
header('Content-Type: application/json');
session_start();
require_once '../db.php';

$db   = new Database();
$conn = $db->connect();

if (empty($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'errors' => ['Unauthorized.']]);
    exit;
}

$payment_id = $_POST['payment_id'] ?? '';
$amount     = $_POST['amount'] ?? '';
$remarks    = trim($_POST['remarks'] ?? '');

$errors = [];

if (!ctype_digit((string)$payment_id)) {
    $errors[] = 'Invalid payment record.';
}
if (!is_numeric($amount) || (float)$amount <= 0) {
    $errors[] = 'Enter a valid amount greater than 0.';
}

if ($errors) {
    echo json_encode(['success' => false, 'errors' => $errors]);
    exit;
}

$payment_id = (int)$payment_id;
$amount     = round((float)$amount, 2);
$received_by = (int)$_SESSION['user_id'];

define('MIN_DOWNPAYMENT', 3000.00);

// Lock the payment row for this update so concurrent payments don't race each other.
$conn->begin_transaction();

try {
    $stmt = $conn->prepare(
        "SELECT payment_id, amount_due, downpayment, balance, payment_status
         FROM payment WHERE payment_id = ? FOR UPDATE"
    );
    if (!$stmt) {
        throw new Exception('Database error: ' . $conn->error);
    }
    $stmt->bind_param('i', $payment_id);
    $stmt->execute();
    $payment = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$payment) {
        throw new Exception('Payment record not found.');
    }

    $currentBalance = (float)$payment['balance'];
    $isFirstPayment = (float)$payment['downpayment'] <= 0;

    if ($currentBalance <= 0) {
        throw new Exception('This payment is already fully paid.');
    }
    if ($amount > $currentBalance) {
        throw new Exception('Amount exceeds the remaining balance of ₱' . number_format($currentBalance, 2) . '.');
    }
    if ($isFirstPayment && $amount < MIN_DOWNPAYMENT && $amount < $currentBalance) {
        // Allow a smaller amount only if it pays off the balance in full.
        throw new Exception('The minimum down payment is ₱' . number_format(MIN_DOWNPAYMENT, 2) . '.');
    }

    // Insert the transaction record
    $stmt = $conn->prepare(
        "INSERT INTO payment_transactions (payment_id, amount, paid_at, remarks, received_by)
         VALUES (?, ?, NOW(), ?, ?)"
    );
    if (!$stmt) {
        throw new Exception('Database error: ' . $conn->error);
    }
    $stmt->bind_param('idsi', $payment_id, $amount, $remarks, $received_by);
    $stmt->execute();
    $stmt->close();

    // Update the running downpayment total on the payment row
    $newDownpayment = (float)$payment['downpayment'] + $amount;
    $newBalance     = (float)$payment['amount_due'] - $newDownpayment;
    $newStatus      = $newBalance <= 0
        ? 'Fully Paid'
        : ($newDownpayment > 0 ? 'Down Payment Paid' : 'Unpaid');

    $stmt = $conn->prepare(
        "UPDATE payment
         SET downpayment = ?, payment_status = ?, paid_at = NOW(), received_by = ?
         WHERE payment_id = ?"
    );
    if (!$stmt) {
        throw new Exception('Database error: ' . $conn->error);
    }
    $stmt->bind_param('dsii', $newDownpayment, $newStatus, $received_by, $payment_id);
    $stmt->execute();
    $stmt->close();

    $conn->commit();

    echo json_encode([
        'success' => true,
        'payment_status' => $newStatus,
        'balance' => $newBalance,
    ]);
} catch (Exception $e) {
    $conn->rollback();
    echo json_encode(['success' => false, 'errors' => [$e->getMessage()]]);
}

$conn->close();