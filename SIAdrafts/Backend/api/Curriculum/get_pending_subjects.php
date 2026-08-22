<?php
session_start();
require_once '../../db.php';
require_once '../../require_role.php';
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
        sub.subject_id,
        sub.subject_code,
        sub.subject_name,
        sub.units,
        sub.year_level,
        sub.semester,
        c.course_code,
        c.course_name,
        CONCAT(u.first_name, ' ', u.last_name) AS requested_by_name
    FROM subject sub
    LEFT JOIN course c ON c.course_id = sub.course_id
    LEFT JOIN users u  ON u.user_id = sub.requested_by
    WHERE sub.status = 'Pending'
    ORDER BY sub.subject_id ASC
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
