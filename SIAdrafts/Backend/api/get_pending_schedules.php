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
        sch.schedule_id,
        sch.day,
        sch.time_start,
        sch.time_end,
        sch.school_year,
        sch.semester,
        sub.subject_code,
        sub.subject_name,
        sec.section_name,
        c.course_code,
        r.room_name,
        CONCAT(p.first_name, ' ', p.last_name) AS professor_name,
        CONCAT(u.first_name, ' ', u.last_name) AS requested_by_name,
        sch.created_at
    FROM schedule sch
    JOIN subject sub  ON sub.subject_id = sch.subject_id
    JOIN section sec  ON sec.section_id = sch.section_id
    LEFT JOIN course c     ON c.course_id = sec.course_id
    LEFT JOIN room r       ON r.room_id = sch.room_id
    LEFT JOIN professor p  ON p.professor_id = sch.professor_id
    LEFT JOIN users u      ON u.user_id = sch.requested_by
    WHERE sch.status = 'Pending'
    ORDER BY sch.created_at ASC
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