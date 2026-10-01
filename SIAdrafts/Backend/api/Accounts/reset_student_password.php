<?php
require_once __DIR__ . '/../../../Backend/session_bootstrap.php';
app_session_start();
require_once '../../db.php';
require_once '../../roles.php';
require_once '../../require_role.php';
require_once '../../csrf.php';
require_once '../../temp_password.php';
require_once '../../mailer.php';
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
    $e = fn($v) => htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8');
    $credsHtml = '
    <div style="margin:0;padding:24px;background:#F7F4EC;font-family:Helvetica,Arial,sans-serif;color:#1F2E28;">
      <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="max-width:560px;margin:0 auto;">
        <tr><td style="padding-bottom:18px;text-align:center;">
          <div style="font-family:Georgia,serif;font-size:22px;font-weight:600;">Edu<span style="color:#A47B3F;">School</span></div>
          <div style="font-size:12px;color:rgba(31,46,40,0.8);letter-spacing:1px;text-transform:uppercase;margin-top:4px;">Student Portal Access</div>
        </td></tr>
        <tr><td style="background:#FFFFFF;border:1px solid rgba(31,46,40,0.14);border-radius:14px;padding:28px 30px;">
          <p style="margin:0 0 16px;font-size:15px;">Hi ' . $e($account['first_name']) . ', your EduSchool student portal password was just reset:</p>
          <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="font-size:14px;border-top:1px solid rgba(31,46,40,0.14);">
            <tr><td style="padding:10px 0;color:rgba(31,46,40,0.8);border-bottom:1px solid rgba(31,46,40,0.08);">Student No.</td><td style="padding:10px 0;text-align:right;font-weight:bold;border-bottom:1px solid rgba(31,46,40,0.08);">' . $e($account['student_no']) . '</td></tr>
            <tr><td style="padding:10px 0;color:rgba(31,46,40,0.8);">Temporary Password</td><td style="padding:10px 0;text-align:right;font-weight:bold;font-family:Courier,monospace;">' . $e($tempPassword) . '</td></tr>
          </table>
          <p style="margin:22px 0 0;font-size:13px;color:rgba(31,46,40,0.8);line-height:1.6;">
            Sign in at the Student Portal with the credentials above — you\'ll be asked to set your own password right away, and this temporary one stops working once you do. If you didn\'t request this reset, contact the registrar\'s office.
          </p>
        </td></tr>
        <tr><td style="padding-top:16px;text-align:center;font-size:11px;color:rgba(31,46,40,0.6);">
          This is an automated message from the EduSchool registrar\'s office — replies are not monitored.
        </td></tr>
      </table>
    </div>';
    $emailSent = send_email(
        $account['email'],
        'Your EduSchool Student Portal Password Was Reset',
        $credsHtml
    );
}

$conn->close();

echo json_encode([
    'message'    => 'Password reset.',
    'email_sent' => $emailSent,
]);
