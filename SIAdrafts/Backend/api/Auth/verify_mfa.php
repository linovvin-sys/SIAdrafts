<?php
session_start();
require_once '../../db.php';
require_once '../../rate_limit.php';
require_once '../../roles.php';
require_once '../../login_attempt.php';
require_once '../../totp.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed.']);
    exit;
}

// Keyed by session (rate_limit_check is IP-keyed by default) so this
// can't be turned into an unlimited TOTP-guessing oracle once someone
// already has a correct password.
if (!rate_limit_check('verify_mfa', 10, 300)) {
    http_response_code(429);
    echo json_encode(['error' => 'Too many attempts. Please wait a few minutes and try again.']);
    exit;
}

$pending = $_SESSION['mfa_pending'] ?? null;

if (!$pending || $pending['expires_at'] < time()) {
    unset($_SESSION['mfa_pending']);
    http_response_code(401);
    echo json_encode(['error' => 'Your login has expired. Please sign in again.']);
    exit;
}

$code = trim($_POST['code'] ?? '');

$db   = new Database();
$conn = $db->connect();

$stmt = $conn->prepare(
    "SELECT secret, recovery_codes FROM staff_mfa
     WHERE login_type = ? AND account_id = ? AND enabled = 1"
);
$stmt->bind_param('si', $pending['login_type'], $pending['account_id']);
$stmt->execute();
$mfa = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$mfa) {
    // MFA was disabled in the gap between the password check and this
    // step (another tab, an admin action) -- treat it as no longer
    // required rather than locking the user out of their own account
    // over a race they didn't cause.
    unset($_SESSION['mfa_pending']);
    finish_pending_login($conn, $db, $pending);
    exit;
}

$verified = totp_verify_code($mfa['secret'], $code);
$recoveryCodes = json_decode($mfa['recovery_codes'] ?? '[]', true) ?: [];
$usedRecoveryIndex = null;

if (!$verified && $code !== '') {
    foreach ($recoveryCodes as $index => $hash) {
        if (password_verify($code, $hash)) {
            $verified = true;
            $usedRecoveryIndex = $index;
            break;
        }
    }
}

if (!$verified) {
    log_login_attempt($conn, $pending['login_type'], $pending['username'], false);
    echo json_encode(['error' => 'Incorrect code.']);
    exit;
}

if ($usedRecoveryIndex !== null) {
    // Single-use: drop the consumed code so it can't be replayed.
    unset($recoveryCodes[$usedRecoveryIndex]);
    $updated = json_encode(array_values($recoveryCodes));
    $upd = $conn->prepare("UPDATE staff_mfa SET recovery_codes = ? WHERE login_type = ? AND account_id = ?");
    $upd->bind_param('ssi', $updated, $pending['login_type'], $pending['account_id']);
    $upd->execute();
    $upd->close();
}

unset($_SESSION['mfa_pending']);
finish_pending_login($conn, $db, $pending);

// Mirrors the tail end of login.php's success path -- this is the same
// "grant the real session" step, just reached one hop later once MFA has
// also passed instead of right after the password.
function finish_pending_login(mysqli $conn, Database $db, array $pending): void
{
    session_regenerate_id(true);

    if ($pending['login_type'] === 'staff') {
        $_SESSION['user_id']   = $pending['account_id'];
        $_SESSION['username']  = $pending['username'];
        $_SESSION['role_id']   = $pending['role_id'];
        $_SESSION['role_name'] = $pending['role_name'];
        $_SESSION['full_name'] = $pending['full_name'];

        $upd = $conn->prepare("UPDATE users SET last_login = NOW() WHERE user_id = ?");
        $upd->bind_param('i', $pending['account_id']);
        $upd->execute();
        $upd->close();

        $role = strtolower(trim($pending['role_name']));
    } else {
        $_SESSION['professor_id']         = $pending['account_id'];
        $_SESSION['username']             = $pending['username'];
        $_SESSION['role_name']            = 'Professor';
        $_SESSION['full_name']            = $pending['full_name'];
        $_SESSION['professor_department'] = $pending['professor_department'];

        $upd = $conn->prepare("UPDATE professor SET last_login = NOW() WHERE professor_id = ?");
        $upd->bind_param('i', $pending['account_id']);
        $upd->execute();
        $upd->close();

        $role = 'professor';
    }

    $_SESSION['tab_token'] = bin2hex(random_bytes(16));

    log_login_attempt($conn, $pending['login_type'], $pending['username'], true, $pending['account_id']);

    $db->close();

    echo json_encode([
        'success'   => true,
        'redirect'  => staff_dashboard_url($role),
        'tab_token' => $_SESSION['tab_token'],
    ]);
}
