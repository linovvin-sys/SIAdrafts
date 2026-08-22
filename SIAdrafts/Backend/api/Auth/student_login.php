<?php
session_start();
require_once '../../db.php';
require_once '../../rate_limit.php';

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
    echo json_encode(['error' => 'Invalid student number or password.']);
    exit;
}

session_regenerate_id(true);

$_SESSION['student_id']            = (int)$account['applicant_id'];
$_SESSION['student_portal_account_id'] = (int)$account['student_portal_account_id'];
$_SESSION['student_no']            = $student_no;
$_SESSION['student_full_name']     = trim($account['first_name'] . ' ' . $account['last_name']);
$_SESSION['must_change_password']  = (bool)$account['must_change_password'];

$loginStmt = $conn->prepare("UPDATE student_portal_account SET last_login = NOW() WHERE student_portal_account_id = ?");
$loginStmt->bind_param('i', $account['student_portal_account_id']);
$loginStmt->execute();
$loginStmt->close();

$db->close();

echo json_encode([
    'success'  => true,
    'redirect' => $account['must_change_password']
        ? '/SIAdrafts/Frontend/View/Student/change_password.php'
        : '/SIAdrafts/Frontend/View/Student/dashboard.php',
]);
