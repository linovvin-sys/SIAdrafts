<?php
header('Content-Type: application/json');
session_start();
require_once '../../db.php';
require_once '../../roles.php';
require_once '../../require_role.php';
require_once '../../csrf.php';
require_once '../../Treasury/record_payment_core.php';

$db   = new Database();
$conn = $db->connect();

if (empty($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'errors' => ['Unauthorized.']]);
    exit;
}

// Admin is deliberately excluded — read-only monitoring only (see treasury.php).
require_role([ROLE_TREASURY], true);

csrf_verify();

$payment_id = $_POST['payment_id'] ?? '';
$amount     = $_POST['amount'] ?? '';

$errors = [];
if (!ctype_digit((string)$payment_id)) {
    $errors[] = 'Invalid payment record.';
}
if (!is_numeric($amount) || (float)$amount <= 0) {
    $errors[] = 'Enter a valid amount greater than 0.';
}
if (!empty($errors)) {
    echo json_encode(['success' => false, 'errors' => $errors]);
    exit;
}

// All the balance maths, status transitions, OR numbering and unpaid-flag
// resolution live in the shared core so the online (PayMongo) path posts
// payments exactly the same way.
$result = record_treasury_payment(
    $conn,
    (int)$payment_id,
    round((float)$amount, 2),
    (int)$_SESSION['user_id'],
    'Cash (counter)'
);

if ($result['success']) {
    echo json_encode([
        'success'          => true,
        'payment_status'   => $result['payment_status'],
        'applicant_status' => $result['applicant_status'],
        'balance'          => $result['balance'],
        'or_number'        => $result['or_number'],
    ]);
} else {
    echo json_encode(['success' => false, 'errors' => [$result['error']]]);
}

$conn->close();
