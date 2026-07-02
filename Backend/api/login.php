<?php
session_start();
require_once '../db.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed.']);
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

if (!$user || !password_verify($password, $user['password'])) {
    echo json_encode(['error' => 'Invalid username or password.']);
    exit;
}

session_regenerate_id(true);

$_SESSION['user_id']   = $user['user_id'];
$_SESSION['username']  = $user['username'];
$_SESSION['role_id']   = $user['role_id'];
$_SESSION['role_name'] = $user['role_name'];
$_SESSION['full_name'] = trim($user['first_name'] . ' ' . $user['last_name']);

$db->close();

// Determine redirect based on role
$role = strtolower(trim($user['role_name']));

switch ($role) {
    case 'admin':
        $redirect = '/SIAdrafts/Frontend/View/Admin/admin_dashboard.php';
        break;

    case 'staff':
        $redirect = '/SIAdrafts/Frontend/View/Admission/enrollment.php';
        break;
    
    case 'treasury':
        $redirect = '/SIAdrafts/Frontend/View/Admission/treasury.php';
        break;

    case 'admission':
        $redirect = '/SIAdrafts/Frontend/View/Admission/admission.php';
        break;
    default:
        // Students and all other roles
        $redirect = '/SIAdrafts/Frontend/View/index.php';
        break;
}

echo json_encode([
    'success'  => true,
    'redirect' => $redirect
]);