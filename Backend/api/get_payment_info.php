<?php
header('Content-Type: application/json');
session_start();
require_once '../db.php';
require_once '../roles.php';
require_once '../require_role.php';

$db   = new Database();
$conn = $db->connect();

header('Content-Type: application/json');

if (empty($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized.']);
    exit;
}

require_role([ROLE_TREASURY, ROLE_STAFF, ROLE_ADMIN], true);

$q = trim($_GET['q'] ?? '');
if ($q === '') {
    echo json_encode(['error' => 'Enter a student ID or name.']);
    exit;
}

// Resolve to an applicant row
$looksLikeId = preg_match('/^[\d\-]+$/', $q);

if ($looksLikeId) {
    $stmt = $conn->prepare(
        "SELECT a.applicant_id, COALESCE(s.student_no, a.reference_id) AS display_id,
                a.first_name, a.last_name
         FROM applicants a
         LEFT JOIN student s ON s.applicant_id = a.applicant_id
         WHERE a.reference_id = ? OR s.student_no = ?
         LIMIT 1"
    );
    $stmt->bind_param('ss', $q, $q);
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
        "SELECT a.applicant_id, COALESCE(s.student_no, a.reference_id) AS display_id,
                a.first_name, a.last_name
         FROM applicants a
         LEFT JOIN student s ON s.applicant_id = a.applicant_id
         WHERE a.first_name LIKE ?
            OR a.last_name LIKE ?
            OR CONCAT(a.first_name, ' ', a.last_name) LIKE ?
            OR CONCAT(a.last_name, ', ', a.first_name) LIKE ?
            OR (a.first_name LIKE ? AND a.last_name LIKE ?)
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
    "SELECT t.transaction_id, t.amount, t.paid_at, u.first_name, u.last_name
     FROM payment_transactions t
     LEFT JOIN users u ON u.user_id = t.received_by
     WHERE t.payment_id = ?
     ORDER BY t.paid_at ASC"
);
$stmt->bind_param('i', $payment['payment_id']);
$stmt->execute();
$history = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// Pending add/drop subject fees for this enrollment.
$stmt = $conn->prepare(
    "SELECT scf.fee_id, scf.action, scf.units, scf.amount,
            sub.subject_code, sub.subject_name
     FROM subject_change_fee scf
     JOIN enrollment_subject es ON es.enrollment_subject_id = scf.enrollment_subject_id
     JOIN subject sub           ON sub.subject_id = es.subject_id
     WHERE scf.enrollment_id = ? AND scf.status = 'Pending'
     ORDER BY scf.created_at ASC"
);
$stmt->bind_param('i', $payment['enrollment_id']);
$stmt->execute();
$pendingFees = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

$conn->close();

$full_name = $student['last_name'] . ', ' . $student['first_name'];

echo json_encode([
    'student' => [
        'student_id' => $student['display_id'],
        'full_name'  => $full_name,
    ],
    'payment'      => $payment,
    'breakdown'    => $breakdown,
    'history'      => $history,
    'pending_fees' => $pendingFees,
]);