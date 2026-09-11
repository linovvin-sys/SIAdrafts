<?php

require_once __DIR__ . '/../db.php';
require_once __DIR__ . '/../roles.php';
require_once __DIR__ . '/../require_role.php';
require_role([ROLE_REGISTRAR_STAFF, ROLE_HEAD_REGISTRAR, ROLE_ADMISSION, ROLE_ADMIN], true);

$db = new Database();
$conn = $db->connect();

$admissions = [];

$sql = "
SELECT
    a.reference_id,
    CONCAT(a.first_name, ' ', a.last_name) AS applicant_name,
    a.program,
    c.course_code,
    a.created_at,
    a.status
FROM applicants a
LEFT JOIN course c ON LOWER(c.course_name) = LOWER(a.program)
ORDER BY a.created_at ASC
";

$result = $conn->query($sql);

if ($result) {
    while ($row = $result->fetch_assoc()) {
        $admissions[] = $row;
    }
}