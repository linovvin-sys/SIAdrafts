<?php
require_once __DIR__ . '/../../require_student.php';
require_student(true);
require_once __DIR__ . '/../../db.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed.']);
    exit;
}

// RA 10173 data-access right: a self-service export of everything this
// system holds about the signed-in student, scoped entirely by their own
// session id — never a client-supplied id, so this can only ever return
// the requester's own data.
$studentId = (int)$_SESSION['student_id'];

$db   = new Database();
$conn = $db->connect();

$stmt = $conn->prepare(
    "SELECT applicant_id, reference_id, last_name, first_name, middle_name,
            birth_date, sex, nationality, civil_status, contact_number, email,
            home_address, guardian_name, guardian_relationship, guardian_contact
     FROM applicants WHERE applicant_id = ?"
);
$stmt->bind_param('i', $studentId);
$stmt->execute();
$profile = $stmt->get_result()->fetch_assoc();
$stmt->close();

$stmt = $conn->prepare(
    "SELECT enrollment_id, school_year, semester, year_level, status, created_at
     FROM enrollment WHERE student_id = ? ORDER BY created_at DESC"
);
$stmt->bind_param('i', $studentId);
$stmt->execute();
$enrollments = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

$stmt = $conn->prepare(
    "SELECT p.payment_id, p.amount_due, p.downpayment, p.balance, p.payment_status, p.due_date, p.paid_at
     FROM payment p
     JOIN enrollment e ON e.enrollment_id = p.enrollment_id
     WHERE e.student_id = ?
     ORDER BY p.created_at DESC"
);
$stmt->bind_param('i', $studentId);
$stmt->execute();
$payments = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

$stmt = $conn->prepare(
    "SELECT login_type, success, ip_address, created_at
     FROM login_attempt
     WHERE login_type = 'student' AND account_id = ?
     ORDER BY created_at DESC LIMIT 100"
);
$portalAccountId = (int)$_SESSION['student_portal_account_id'];
$stmt->bind_param('i', $portalAccountId);
$stmt->execute();
$loginHistory = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

$db->close();

echo json_encode([
    'success'     => true,
    'exported_at' => date('c'),
    'data'        => [
        'profile'        => $profile,
        'enrollments'    => $enrollments,
        'payments'       => $payments,
        'login_history'  => $loginHistory,
    ],
], JSON_PRETTY_PRINT);
