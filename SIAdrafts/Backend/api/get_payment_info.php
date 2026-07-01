<?php
header('Content-Type: application/json');
session_start();
require_once '../db.php';

$db   = new Database();
$conn = $db->connect();

header('Content-Type: application/json');

if (empty($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized.']);
    exit;
}

$q = trim($_GET['q'] ?? '');
if ($q === '') {
    echo json_encode(['error' => 'Enter a student ID or name.']);
    exit;
}

// Resolve to an applicant row
$looksLikeId = preg_match('/^[\d\-]+$/', $q);

if ($looksLikeId) {
    $stmt = $conn->prepare(
        "SELECT applicant_id, student_id, first_name, last_name FROM applicants WHERE student_id = ? LIMIT 1"
    );
    $stmt->bind_param('s', $q);
} else {
    if (strpos($q, ',') !== false) {
        [$lastPart, $firstPart] = array_map('trim', explode(',', $q, 2));
    } else {
        $lastPart = $firstPart = $q;
    }

    $like      = '%' . $q . '%';
    $likeFirst = '%' . $firstPart . '%';
    $likeLast  = '%' . $lastPart . '%';

    $stmt = $conn->prepare(
        "SELECT applicant_id, student_id, first_name, last_name FROM applicants
         WHERE first_name LIKE ?
            OR last_name LIKE ?
            OR CONCAT(first_name, ' ', last_name) LIKE ?
            OR CONCAT(last_name, ', ', first_name) LIKE ?
            OR (first_name LIKE ? AND last_name LIKE ?)
         LIMIT 1"
    );
    $stmt->bind_param('ssssss', $like, $like, $like, $like, $likeFirst, $likeLast);
}
$stmt->execute();
$student = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$student) {
    echo json_encode(['error' => 'No student found.']);
    exit;
}

// Most recent enrollment + payment record for this student
$stmt = $conn->prepare(
    "SELECT p.payment_id, p.amount_due, p.downpayment, p.balance, p.due_date, p.payment_status,
            e.enrollment_id, e.school_year, e.semester
     FROM payment p
     JOIN enrollment e ON e.enrollment_id = p.enrollment_id
     WHERE e.student_id = ?
     ORDER BY e.created_at DESC
     LIMIT 1"
);
$stmt->bind_param('i', $student['applicant_id']);
$stmt->execute();
$payment = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$payment) {
    echo json_encode(['error' => 'This student has no enrollment/payment record yet.']);
    exit;
}

// Fee breakdown snapshot for this payment (tuition, laboratory, misc, etc.)
$stmt = $conn->prepare(
    "SELECT label, amount, sort_order
     FROM payment_breakdown
     WHERE payment_id = ?
     ORDER BY sort_order"
);
$stmt->bind_param('i', $payment['payment_id']);
$stmt->execute();
$breakdown = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// Payment history
$stmt = $conn->prepare(
    "SELECT t.amount, t.paid_at, t.remarks, u.first_name, u.last_name
     FROM payment_transactions t
     LEFT JOIN users u ON u.user_id = t.received_by
     WHERE t.payment_id = ?
     ORDER BY t.paid_at ASC"
);
$stmt->bind_param('i', $payment['payment_id']);
$stmt->execute();
$history = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

$conn->close();

$full_name = $student['last_name'] . ', ' . $student['first_name'];

echo json_encode([
    'student' => [
        'student_id' => $student['student_id'],
        'full_name'  => $full_name,
    ],
    'payment'   => $payment,
    'breakdown' => $breakdown,
    'history'   => $history,
]);