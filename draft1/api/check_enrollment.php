<?php
session_start();
require_once '../includes/db.php';

header('Content-Type: application/json');

if (empty($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized.']);
    exit;
}

$student_id  = (int)trim($_GET['student_id']  ?? 0);
$school_year = trim($_GET['school_year'] ?? '');
$semester    = (int)trim($_GET['semester']    ?? 0);

if (!$student_id || !$school_year || !$semester) {
    echo json_encode(['exists' => false]);
    exit;
}

$stmt = $pdo->prepare(
    "SELECT enrollment_id FROM enrollment
     WHERE student_id = ? AND school_year = ? AND semester = ?
     LIMIT 1"
);
$stmt->execute([$student_id, $school_year, $semester]);
$row = $stmt->fetch();

echo json_encode([
    'exists'        => (bool)$row,
    'enrollment_id' => $row['enrollment_id'] ?? null,
]);
