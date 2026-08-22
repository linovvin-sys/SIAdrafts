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
require_registrar_tier(REGISTRAR_TIER_HEAD, true);

$db   = new Database();
$conn = $db->connect();

$sql = "
    SELECT
        s.section_id,
        s.section_name,
        s.capacity,
        c.course_code,
        c.course_name,
        CONCAT(u.first_name, ' ', u.last_name) AS requested_by_name
    FROM section s
    LEFT JOIN course c ON c.course_id = s.course_id
    LEFT JOIN users u  ON u.user_id = s.requested_by
    WHERE s.status = 'Pending'
    ORDER BY s.section_id ASC
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