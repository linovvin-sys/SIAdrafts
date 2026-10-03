<?php
require_once __DIR__ . '/../../../Backend/session_bootstrap.php';
app_session_start();
require_once '../../db.php';
require_once '../../roles.php';
require_once '../../require_role.php';
require_once '../../csrf.php';
require_once '../../temp_password.php';
require_once '../../mailer.php';
require_once '../../school_branding.php';
header('Content-Type: application/json');

if (empty($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized.']);
    exit;
}

require_role([ROLE_ADMIN], true);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed.']);
    exit;
}

csrf_verify();

$db   = new Database();
$conn = $db->connect();

$raw  = file_get_contents('php://input');
$data = json_decode($raw, true) ?? $_POST;

$student_portal_account_id = (int)($data['student_portal_account_id'] ?? 0);
if (!$student_portal_account_id) {
    http_response_code(400);
    echo json_encode(['error' => 'Missing student account.']);
    exit;
}

$stmt = $conn->prepare("
    SELECT spa.student_portal_account_id, spa.student_no,
           a.first_name, a.email
    FROM student_portal_account spa
    JOIN applicants a ON a.applicant_id = spa.applicant_id
    WHERE spa.student_portal_account_id = ?
    LIMIT 1
");
$stmt->bind_param('i', $student_portal_account_id);
$stmt->execute();
$account = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$account) {
    http_response_code(404);
    echo json_encode(['error' => 'Student account not found.']);
    exit;
}

// Random temp password (see temp_password.php). Unlike this endpoint's
// previous behavior, the plaintext is never put in this response or shown
// on screen -- it's emailed once, the same way save_enrollment.php's
// first-time portal account email already does, so this fallback can't
// leave a password sitting in a browser network tab or a UI dialog.
$tempPassword = generate_temp_password();
$tempPasswordHash = password_hash($tempPassword, PASSWORD_DEFAULT);

$upd = $conn->prepare("
    UPDATE student_portal_account
    SET password_hash = ?, must_change_password = 1
    WHERE student_portal_account_id = ?
");
$upd->bind_param('si', $tempPasswordHash, $student_portal_account_id);

if (!$upd->execute()) {
    $upd->close();
    http_response_code(500);
    error_log('reset_student_password.php: ' . $conn->error);
    echo json_encode(['error' => 'A database error occurred. Please try again.']);
    exit;
}
$upd->close();

$emailSent = false;
if (!empty($account['email'])) {
    $brandName = get_school_branding()['name'];
    $credsHtml = email_password_reset($brandName, $account['first_name'], $account['student_no'], $tempPassword);
    $emailSent = send_email(
        $account['email'],
        'Your ' . $brandName . ' Student Portal Password Was Reset',
        $credsHtml
    );
}

$conn->close();

echo json_encode([
    'message'    => 'Password reset.',
    'email_sent' => $emailSent,
]);
