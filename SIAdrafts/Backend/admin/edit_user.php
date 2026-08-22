<?php
session_start();

require_once "../db.php";   // Change this if your db.php is in another folder
require_once __DIR__ . '/../roles.php';
require_once __DIR__ . '/../require_role.php';
require_once __DIR__ . '/../csrf.php';
require_role([ROLE_ADMIN, ROLE_HEAD_REGISTRAR], true);

// Optional: Allow only admins
/*
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'Administrator') {
    header("Location: ../../index.php");
    exit();
}
*/
$db = new Database();
$conn = $db->connect();

if ($_SERVER["REQUEST_METHOD"] != "POST") {
    header("Location: ../../Frontend/View/Admin/manage_user.php");
    exit();
}

csrf_verify();

// Get form data
$user_id     = trim($_POST['user_id']);
$first_name  = trim($_POST['first_name']);
$middle_name = trim($_POST['middle_name']);
$last_name   = trim($_POST['last_name']);
$email       = trim($_POST['email']);
$phone       = trim($_POST['phone_number']);
$username    = trim($_POST['username']);
$password    = trim($_POST['password']); // optional on edit

$role_name   = trim($_POST['role']);
$status_name = isset($_POST['status']) ? "Active" : "Inactive";

// Basic validation
if (
    empty($user_id) ||
    empty($first_name) ||
    empty($last_name) ||
    empty($email) ||
    empty($username) ||
    empty($role_name)
) {
    die("Please complete all required fields.");
}

// Make sure the user actually exists, and grab their current role/status
// while we're at it — both the last-admin guard below and the Head
// Registrar scope check need it.
$stmt = $conn->prepare("
    SELECT r.role_name, st.status_name FROM users u
    JOIN roles r ON r.role_id = u.role_id
    JOIN statuses st ON st.status_id = u.status_id
    WHERE u.user_id = ?
");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$targetCurrent = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$targetCurrent) {
    die("User not found.");
}

// A Head Registrar may only edit accounts within the staff branches below
// them, and may only move an account to another role within that same
// scope — never into Admin/Head Registrar, and never an Admin/Head
// Registrar account out of it (those accounts are simply invisible to
// them per Backend/admin/manage_user.php's list filter, but the backend
// enforces it independently rather than trusting that the row never
// reached the edit form).
if (current_user_is([ROLE_HEAD_REGISTRAR])) {
    if (!in_array($targetCurrent['role_name'], ROLES_HEAD_REGISTRAR_MANAGEABLE, true)) {
        die("You do not have permission to edit this account.");
    }
    if (!in_array($role_name, ROLES_HEAD_REGISTRAR_MANAGEABLE, true)) {
        die("You can only assign Admission, Registrar Staff, Treasury, or Staff roles.");
    }
}

// Check duplicate email (excluding this user)
$stmt = $conn->prepare("SELECT user_id FROM users WHERE email = ? AND user_id != ?");
$stmt->bind_param("si", $email, $user_id);
$stmt->execute();
$stmt->store_result();

if ($stmt->num_rows > 0) {
    die("Email already exists.");
}
$stmt->close();

// Check duplicate username (excluding this user)
$stmt = $conn->prepare("SELECT user_id FROM users WHERE username = ? AND user_id != ?");
$stmt->bind_param("si", $username, $user_id);
$stmt->execute();
$stmt->store_result();

if ($stmt->num_rows > 0) {
    die("Username already exists.");
}
$stmt->close();

// Get role_id
$stmt = $conn->prepare("SELECT role_id FROM roles WHERE role_name = ?");
$stmt->bind_param("s", $role_name);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows == 0) {
    die("Invalid role.");
}

$role = $result->fetch_assoc();
$role_id = $role['role_id'];
$stmt->close();

// Get status_id
$stmt = $conn->prepare("SELECT status_id FROM statuses WHERE status_name = ?");
$stmt->bind_param("s", $status_name);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows == 0) {
    die("Invalid status.");
}

$status = $result->fetch_assoc();
$status_id = $status['status_id'];
$stmt->close();

// An Admin editing their own account can't demote or deactivate themselves —
// that's how an Admin locks themselves out with no one left to undo it.
$isSelf = ((int)$user_id === (int)($_SESSION['user_id'] ?? 0));
if ($isSelf && $role_name !== 'Admin') {
    die("You cannot change your own role away from Admin.");
}
if ($isSelf && $status_name !== 'Active') {
    die("You cannot deactivate your own account.");
}

// Also guard the case where this isn't self-edit, but the target is the
// last remaining active Admin — demoting/deactivating them the same way
// would leave the system with no one who can manage users at all.
if ($role_name !== 'Admin' || $status_name !== 'Active') {
    if ($targetCurrent['role_name'] === 'Admin' && $targetCurrent['status_name'] === 'Active') {
        $stmt = $conn->prepare("
            SELECT COUNT(*) AS cnt FROM users u
            JOIN roles r ON r.role_id = u.role_id
            JOIN statuses st ON st.status_id = u.status_id
            WHERE r.role_name = 'Admin' AND st.status_name = 'Active' AND u.user_id != ?
        ");
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $cnt = (int)$stmt->get_result()->fetch_assoc()['cnt'];
        $stmt->close();

        if ($cnt === 0) {
            die("At least one active Admin must remain in the system.");
        }
    }
}

// Update user — with or without a new password
if (!empty($password)) {

    if (strlen($password) < 8) {
        die("Password must be at least 8 characters.");
    }

    $hashed_password = password_hash($password, PASSWORD_DEFAULT);

    $stmt = $conn->prepare("
        UPDATE users
        SET
            first_name    = ?,
            middle_name   = ?,
            last_name     = ?,
            email         = ?,
            phone_number  = ?,
            username      = ?,
            password      = ?,
            role_id       = ?,
            status_id     = ?
        WHERE user_id = ?
    ");

    $stmt->bind_param(
        "sssssssiii",
        $first_name,
        $middle_name,
        $last_name,
        $email,
        $phone,
        $username,
        $hashed_password,
        $role_id,
        $status_id,
        $user_id
    );

} else {

    $stmt = $conn->prepare("
        UPDATE users
        SET
            first_name    = ?,
            middle_name   = ?,
            last_name     = ?,
            email         = ?,
            phone_number  = ?,
            username      = ?,
            role_id       = ?,
            status_id     = ?
        WHERE user_id = ?
    ");

    $stmt->bind_param(
        "ssssssiii",
        $first_name,
        $middle_name,
        $last_name,
        $email,
        $phone,
        $username,
        $role_id,
        $status_id,
        $user_id
    );
}

if ($stmt->execute()) {
    header("Location: ../../Frontend/View/Admin/manage_user.php?success=User updated successfully");
    exit();
} else {
    die("Error: " . $stmt->error);
}

$stmt->close();
$conn->close();