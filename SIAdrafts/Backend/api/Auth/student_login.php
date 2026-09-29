<?php
session_start();
require_once '../../db.php';
require_once '../../rate_limit.php';
require_once '../../login_attempt.php';
require_once '../../login_session.php';
require_once '../../account_lockout.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed.']);
    exit;
}

if (!rate_limit_check('student_login', 10, 300)) {
    http_response_code(429);
    echo json_encode(['error' => 'Too many login attempts. Please wait a few minutes and try again.']);
    exit;
}

$db   = new Database();
$conn = $db->connect();

$student_no = trim($_POST['student_no'] ?? '');
$password   = $_POST['password'] ?? '';

if ($student_no === '' || $password === '') {
    echo json_encode(['error' => 'Student number and password are required.']);
    exit;
}

// Same per-account lockout as the staff login (see Backend/account_lockout.php)
// — catches credential stuffing spread across many source IPs at one
// student account, which the per-IP throttle above can't.
if (account_locked_out($conn, 'student', $student_no)) {
    http_response_code(429);
    echo json_encode(['error' => 'Too many failed attempts on this account. Please wait 15 minutes and try again.']);
    exit;
}

$stmt = $conn->prepare(
    "SELECT spa.student_portal_account_id, spa.applicant_id, spa.password_hash, spa.must_change_password,
            a.first_name, a.last_name
     FROM student_portal_account spa
     JOIN applicants a ON a.applicant_id = spa.applicant_id
     WHERE spa.student_no = ?
     LIMIT 1"
);
$stmt->bind_param('s', $student_no);
$stmt->execute();
$account = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$account || !password_verify($password, $account['password_hash'])) {
    log_login_attempt($conn, 'student', $student_no, false);
    echo json_encode(['error' => 'Invalid student number or password.']);
    exit;
}

issue_login_session($conn, [
    'student_id'                => (int)$account['applicant_id'],
    'student_portal_account_id' => (int)$account['student_portal_account_id'],
    'student_no'                => $student_no,
    'student_full_name'         => trim($account['first_name'] . ' ' . $account['last_name']),
    'must_change_password'      => (bool)$account['must_change_password'],
], 'student_portal_account', 'student_portal_account_id', (int)$account['student_portal_account_id'], 'student', $student_no);

$db->close();

echo json_encode([
    'success'   => true,
    'redirect'  => $account['must_change_password']
        ? '/SIAdrafts/Frontend/View/Student/change_password.php'
        : '/SIAdrafts/Frontend/View/Student/dashboard.php',
    'tab_token' => $_SESSION['tab_token'],
]);
