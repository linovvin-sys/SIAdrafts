<?php
/**
 * Starts a PayMongo Checkout Session for one tuition payment and returns
 * the hosted checkout_url for the browser to redirect to.
 *
 * Flow: staff (or the student, on a shared terminal) clicks "Pay online".
 * We validate the amount against the live balance, create the session,
 * store a tracking row, and hand back the URL. Nothing is posted to the
 * ledger here — that happens in paymongo_return.php once PayMongo confirms
 * the payment.
 */

header('Content-Type: application/json');
session_start();
require_once '../../db.php';
require_once '../../roles.php';
require_once '../../require_role.php';
require_once '../../csrf.php';
require_once '../../paymongo.php';

$db   = new Database();
$conn = $db->connect();

if (empty($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Unauthorized.']);
    exit;
}
require_role([ROLE_TREASURY], true);
csrf_verify();

$payment_id = $_POST['payment_id'] ?? '';
$amount     = $_POST['amount'] ?? '';

if (!ctype_digit((string)$payment_id)) {
    echo json_encode(['success' => false, 'error' => 'Invalid payment record.']);
    exit;
}
if (!is_numeric($amount) || (float)$amount <= 0) {
    echo json_encode(['success' => false, 'error' => 'Enter a valid amount greater than 0.']);
    exit;
}

$payment_id = (int)$payment_id;
$amount     = round((float)$amount, 2);

// PayMongo's GCash minimum is PHP 100.00.
if ($amount < 100.00) {
    echo json_encode(['success' => false, 'error' => 'Online payments must be at least ' . "\u{20B1}" . '100.00.']);
    exit;
}

// Validate against the live balance + down-payment rule before sending the
// student off to pay (the authoritative re-check still runs under a row
// lock in record_treasury_payment when the payment comes back).
$stmt = $conn->prepare("
    SELECT p.amount_due, p.downpayment, p.balance, p.payment_status,
           a.first_name, a.last_name, COALESCE(s.student_no, a.reference_id) AS display_id
    FROM payment p
    JOIN enrollment e  ON e.enrollment_id = p.enrollment_id
    JOIN applicants a  ON a.applicant_id = e.student_id
    LEFT JOIN student s ON s.applicant_id = a.applicant_id
    WHERE p.payment_id = ?
");
$stmt->bind_param('i', $payment_id);
$stmt->execute();
$row = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$row) {
    echo json_encode(['success' => false, 'error' => 'Payment record not found.']);
    exit;
}

$balance = (float)$row['balance'];
if ($balance <= 0) {
    echo json_encode(['success' => false, 'error' => 'This payment is already fully paid.']);
    exit;
}
if ($amount > $balance) {
    echo json_encode(['success' => false, 'error' => 'Amount exceeds the remaining balance of ' . "\u{20B1}" . number_format($balance, 2) . '.']);
    exit;
}
$isFirstPayment = ((float)$row['downpayment'] <= 0);
if ($isFirstPayment && $amount < 3000.00 && $amount < $balance) {
    echo json_encode(['success' => false, 'error' => 'The minimum down payment is ' . "\u{20B1}" . '3,000.00.']);
    exit;
}

$localRef  = bin2hex(random_bytes(16));
$studentNm = trim($row['first_name'] . ' ' . $row['last_name']);

// Absolute URLs PayMongo will send the browser back to. Built from the host
// the staff browser is actually on, so it works on localhost:8888 or a LAN IP.
$scheme  = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
$base    = $scheme . '://' . $_SERVER['HTTP_HOST'];
$successUrl = $base . '/SIAdrafts/Backend/api/Treasury/paymongo_return.php?ref=' . $localRef;
$cancelUrl  = $base . '/SIAdrafts/Frontend/View/Admission/treasury.php?pay=cancelled';

try {
    $resp = paymongo_api('POST', '/checkout_sessions', [
        'data' => [
            'attributes' => [
                'line_items' => [[
                    'name'     => 'Tuition payment — ' . $studentNm,
                    'amount'   => (int) round($amount * 100), // centavos
                    'currency' => 'PHP',
                    'quantity' => 1,
                ]],
                'payment_method_types' => ['gcash', 'paymaya', 'card'],
                'description'  => 'EduSchool tuition — payment #' . $payment_id . ' (' . $row['display_id'] . ')',
                'success_url'  => $successUrl,
                'cancel_url'   => $cancelUrl,
                'reference_number' => 'PMT-' . $payment_id,
            ],
        ],
    ]);
} catch (PayMongoError $e) {
    error_log('paymongo_create_checkout: ' . $e->getMessage());
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    exit;
}

$sessionId   = $resp['data']['id'] ?? '';
$checkoutUrl = $resp['data']['attributes']['checkout_url'] ?? '';
if ($sessionId === '' || $checkoutUrl === '') {
    echo json_encode(['success' => false, 'error' => 'PayMongo did not return a checkout URL.']);
    exit;
}

$ins = $conn->prepare("
    INSERT INTO paymongo_checkout (local_ref, checkout_session_id, payment_id, amount, status)
    VALUES (?, ?, ?, ?, 'pending')
");
$ins->bind_param('ssid', $localRef, $sessionId, $payment_id, $amount);
$ins->execute();
$ins->close();

$conn->close();

echo json_encode(['success' => true, 'checkout_url' => $checkoutUrl]);
