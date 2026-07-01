<?php
session_start();

require_once "../db.php";   // Change this if your db.php is in another folder

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
// remove this line if your db.php already provides $conn

// Get form data
$first_name  = trim($_POST['first_name']);
$middle_name = trim($_POST['middle_name']);
$last_name   = trim($_POST['last_name']);
$email       = trim($_POST['email']);
$phone       = trim($_POST['phone_number']);
$username    = trim($_POST['username']);
$password    = $_POST['password'];

$role_name = trim($_POST['role']);
$status_name = isset($_POST['status']) ? "Active" : "Inactive";

// Basic validation
if (
    empty($first_name) ||
    empty($last_name) ||
    empty($email) ||
    empty($username) ||
    empty($password)
) {
    die("Please complete all required fields.");
}

// Check duplicate email
$stmt = $conn->prepare("SELECT user_id FROM users WHERE email = ?");
$stmt->bind_param("s", $email);
$stmt->execute();
$stmt->store_result();

if ($stmt->num_rows > 0) {
    die("Email already exists.");
}
$stmt->close();

// Check duplicate username
$stmt = $conn->prepare("SELECT user_id FROM users WHERE username = ?");
$stmt->bind_param("s", $username);
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

// Hash password
$hashed_password = password_hash($password, PASSWORD_DEFAULT);

// Insert user
$stmt = $conn->prepare("
INSERT INTO users
(
    first_name,
    middle_name,
    last_name,
    email,
    phone_number,
    username,
    password,
    role_id,
    status_id
)
VALUES
(
    ?,?,?,?,?,?,?,?,?
)
");

$stmt->bind_param(
    "sssssssii",
    $first_name,
    $middle_name,
    $last_name,
    $email,
    $phone,
    $username,
    $hashed_password,
    $role_id,
    $status_id
);

if ($stmt->execute()) {
    header("Location: ../../Frontend/View/Admin/manage_user.php?success=User added successfully");
    exit();
} else {
    die("Error: " . $stmt->error);
}

$stmt->close();
$conn->close();