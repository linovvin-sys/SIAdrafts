<?php
session_start();
require_once '../db.php';
require_once '../require_role.php';
header('Content-Type: application/json');

if (empty($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized.']);
    exit;
}

// Only the Head Registrar reviews the pending queue.
require_role(['Head Registrar'], true);

$db   = new Database();
$conn = $db->connect();

$sql = "
    SELECT
        c.course_id,
        c.course_code,
        c.course_name,
        c.total_units,
        CONCAT(u.first_name, ' ', u.last_name) AS requested_by_name
    FROM course c
    LEFT JOIN users u ON u.user_id = c.requested_by
    WHERE c.status = 'Pending'
    ORDER BY c.course_id ASC
";

$result = $conn->query($sql);
$rows = [];
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $rows[] = $row;
    }
}

$db->close();

echo json_encode(['pending' => $rows]);