<?php
header('Content-Type: application/json');
session_start();
require_once '../db.php';

$db   = new Database();
$conn = $db->connect();

if (empty($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode([
        'success' => false,
        'errors' => ['Unauthorized.']
    ]);
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

if (!empty($errors)) {
    echo json_encode([
        'success' => false,
        'errors' => $errors
    ]);
    exit;
}

$payment_id  = (int)$payment_id;
$amount      = round((float)$amount, 2);
$received_by = (int)$_SESSION['user_id'];

define('MIN_DOWNPAYMENT', 3000.00);

$conn->begin_transaction();

try {

    // Lock payment row
    $stmt = $conn->prepare("
        SELECT payment_id, enrollment_id, amount_due, downpayment, balance
        FROM payment
        WHERE payment_id = ?
        FOR UPDATE
    ");

    if (!$stmt) {
        throw new Exception($conn->error);
    }

    $stmt->bind_param("i", $payment_id);
    $stmt->execute();
    $payment = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$payment) {
        throw new Exception("Payment record not found.");
    }

    $currentBalance = (float)$payment['balance'];
    $isFirstPayment = ((float)$payment['downpayment'] <= 0);

    if ($currentBalance <= 0) {
        throw new Exception("This payment is already fully paid.");
    }

    if ($amount > $currentBalance) {
        throw new Exception(
            "Amount exceeds the remaining balance of ₱" .
            number_format($currentBalance, 2)
        );
    }

    if (
        $isFirstPayment &&
        $amount < MIN_DOWNPAYMENT &&
        $amount < $currentBalance
    ) {
        throw new Exception(
            "The minimum down payment is ₱" .
            number_format(MIN_DOWNPAYMENT, 2)
        );
    }

    // Save payment transaction
    $stmt = $conn->prepare("
        INSERT INTO payment_transactions
        (payment_id, amount, paid_at, remarks, received_by)
        VALUES (?, ?, NOW(), ?, ?)
    ");

    if (!$stmt) {
        throw new Exception($conn->error);
    }

    $stmt->bind_param(
        "idsi",
        $payment_id,
        $amount,
        $remarks,
        $received_by
    );

    $stmt->execute();
    $stmt->close();

    // Compute totals
    $newDownpayment = (float)$payment['downpayment'] + $amount;
    $newBalance     = (float)$payment['amount_due'] - $newDownpayment;

    // Payment status
    $paymentStatus = ($newBalance <= 0)
        ? "Fully Paid"
        : "Down Payment Paid";

    // Applicant status
    $applicantStatus = ($newBalance <= 0)
        ? "Fully Paid"
        : "Downpayment Paid";

    // Update payment table
    $stmt = $conn->prepare("
        UPDATE payment
        SET
            downpayment = ?,
            payment_status = ?,
            paid_at = NOW(),
            received_by = ?
        WHERE payment_id = ?
    ");

    if (!$stmt) {
        throw new Exception($conn->error);
    }

    $stmt->bind_param(
        "dsii",
        $newDownpayment,
        $paymentStatus,
        $received_by,
        $payment_id
    );

    $stmt->execute();
    $stmt->close();

    // Update applicant status
    $stmt = $conn->prepare("
        UPDATE applicants a
        INNER JOIN enrollment e
            ON e.student_id = a.applicant_id
        INNER JOIN payment p
            ON e.enrollment_id = p.enrollment_id
        SET a.status = ?
        WHERE p.payment_id = ?
    ");

    if (!$stmt) {
        throw new Exception($conn->error);
    }

    $stmt->bind_param(
        "si",
        $applicantStatus,
        $payment_id
    );

    $stmt->execute();
    $stmt->close();

    // Update enrollment status
    $stmt = $conn->prepare("
        UPDATE enrollment e
        INNER JOIN payment p
            ON p.enrollment_id = e.enrollment_id
        SET e.status = 'Enrolled'
        WHERE p.payment_id = ?
    ");

    if (!$stmt) {
        throw new Exception($conn->error);
    }

    $stmt->bind_param("i", $payment_id);
    $stmt->execute();
    $stmt->close();

    $conn->commit();

    echo json_encode([
        "success" => true,
        "payment_status" => $paymentStatus,
        "applicant_status" => $applicantStatus,
        "balance" => $newBalance
    ]);

} catch (Exception $e) {

    $conn->rollback();

    echo json_encode([
        "success" => false,
        "errors" => [$e->getMessage()]
    ]);
}

$conn->close();