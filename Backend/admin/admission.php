<?php

require_once __DIR__ . '/../db.php';

$db = new Database();
$conn = $db->connect();

$admissions = [];

$sql = "
SELECT
    student_id,
    CONCAT(first_name, ' ', last_name) AS applicant_name,
    program,
    created_at,
    status
FROM applicants
ORDER BY created_at DESC
";

$result = $conn->query($sql);

if ($result) {
    while ($row = $result->fetch_assoc()) {
        $admissions[] = $row;
    }
}