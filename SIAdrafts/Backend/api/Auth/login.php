<?php
session_start();
require_once '../../db.php';
require_once '../../rate_limit.php';
require_once '../../roles.php';
require_once '../../login_attempt.php';
require_once '../../account_lockout.php';
require_once '../../totp.php';
require_once '../../staff_mfa.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed.']);
    exit;
}

// Cap credential-guessing attempts per IP — before even touching the DB.
if (!rate_limit_check('login', 10, 300)) {
    http_response_code(429);
    echo json_encode(['error' => 'Too many login attempts. Please wait a few minutes and try again.']);
    exit;
}

$db   = new Database();
$conn = $db->connect();

$username = trim($_POST['username'] ?? '');
$password = $_POST['password'] ?? '';

if ($username === '' || $password === '') {
    echo json_encode(['error' => 'Username and password are required.']);
    exit;
}

// Per-account lockout, on top of rate_limit_check()'s per-IP throttle
// above — that one alone does nothing against credential stuffing spread
// across many source IPs at the SAME account. Checked before querying
// the real tables so a locked-out account gets the same generic response
// either way.
if (account_locked_out($conn, 'staff', $username)) {
    http_response_code(429);
    echo json_encode(['error' => 'Too many failed attempts on this account. Please wait 15 minutes and try again.']);
    exit;
}

$stmt = $conn->prepare(
    "SELECT u.user_id, u.first_name, u.last_name, u.username, u.password,
            u.role_id, r.role_name
     FROM users u
     JOIN roles r ON u.role_id = r.role_id
     WHERE (u.username = ? OR u.email = ?) AND u.status_id = 1
     LIMIT 1"
);

if (!$stmt) {
    http_response_code(500);
    echo json_encode(['error' => 'Database error: ' . $conn->error]);
    exit;
}

$stmt->bind_param('ss', $username, $username);
$stmt->execute();

$result = $stmt->get_result();
$user   = $result->fetch_assoc();
$stmt->close();

if ($user && password_verify($password, $user['password'])) {
    // MFA gate: if this account has enrolled, the password alone is only
    // half the credential. Stash what's needed to finish the login after
    // a correct code (verify_mfa.php) instead of granting the session's
    // real privileges yet — no user_id/role_name is set below, so
    // require_role() treats this request the same as an anonymous one
    // until MFA actually passes.
    if (staff_mfa_enabled($conn, 'staff', $user['user_id'])) {
        session_regenerate_id(true);
        $_SESSION['mfa_pending'] = [
            'login_type' => 'staff',
            'account_id' => $user['user_id'],
            'username'   => $user['username'],
            'role_id'    => $user['role_id'],
            'role_name'  => $user['role_name'],
            'full_name'  => trim($user['first_name'] . ' ' . $user['last_name']),
            'expires_at' => time() + 300,
        ];
        $db->close();
        echo json_encode(['mfa_required' => true]);
        exit;
    }

    session_regenerate_id(true);

    $_SESSION['user_id']    = $user['user_id'];
    $_SESSION['username']   = $user['username'];
    $_SESSION['role_id']    = $user['role_id'];
    $_SESSION['role_name']  = $user['role_name'];
    $_SESSION['full_name']  = trim($user['first_name'] . ' ' . $user['last_name']);
    $_SESSION['tab_token']  = bin2hex(random_bytes(16));

    $loginStmt = $conn->prepare("UPDATE users SET last_login = NOW() WHERE user_id = ?");
    $loginStmt->bind_param('i', $user['user_id']);
    $loginStmt->execute();
    $loginStmt->close();

    log_login_attempt($conn, 'staff', $user['username'], true, $user['user_id']);

    $role = strtolower(trim($user['role_name']));
} else {
    // Not a staff account — professors have their own credentials on the
    // `professor` table rather than a `users` row, so check there before
    // giving up.
    $profStmt = $conn->prepare(
        "SELECT p.professor_id, p.first_name, p.last_name, p.username, p.password, d.department_code
         FROM professor p
         JOIN department d ON d.department_id = p.department_id
         WHERE p.username = ? AND p.status_id = 1
         LIMIT 1"
    );
    $profStmt->bind_param('s', $username);
    $profStmt->execute();
    $professor = $profStmt->get_result()->fetch_assoc();
    $profStmt->close();

    if (!$professor || $professor['password'] === null || !password_verify($password, $professor['password'])) {
        log_login_attempt($conn, 'staff', $username, false);
        echo json_encode(['error' => 'Invalid username or password.']);
        exit;
    }

    if (staff_mfa_enabled($conn, 'professor', $professor['professor_id'])) {
        session_regenerate_id(true);
        $_SESSION['mfa_pending'] = [
            'login_type'           => 'professor',
            'account_id'           => $professor['professor_id'],
            'username'             => $professor['username'],
            'full_name'            => trim($professor['first_name'] . ' ' . $professor['last_name']),
            'professor_department' => $professor['department_code'],
            'expires_at'           => time() + 300,
        ];
        $db->close();
        echo json_encode(['mfa_required' => true]);
        exit;
    }

    session_regenerate_id(true);

    $_SESSION['professor_id']         = $professor['professor_id'];
    $_SESSION['username']             = $professor['username'];
    $_SESSION['role_name']            = 'Professor';
    $_SESSION['full_name']            = trim($professor['first_name'] . ' ' . $professor['last_name']);
    $_SESSION['professor_department'] = $professor['department_code'];
    $_SESSION['tab_token']            = bin2hex(random_bytes(16));

    $loginStmt = $conn->prepare("UPDATE professor SET last_login = NOW() WHERE professor_id = ?");
    $loginStmt->bind_param('i', $professor['professor_id']);
    $loginStmt->execute();
    $loginStmt->close();

    log_login_attempt($conn, 'professor', $professor['username'], true, $professor['professor_id']);

    $role = 'professor';
}

$db->close();

// Same mapping login.php's "already signed in" check uses (Backend/
// roles.php) — one place, so they can't drift out of sync.
$redirect = staff_dashboard_url($role);

echo json_encode([
    'success'   => true,
    'redirect'  => $redirect,
    'tab_token' => $_SESSION['tab_token'],
]);