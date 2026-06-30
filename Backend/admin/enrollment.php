<?php

require_once __DIR__ . '/../db.php';

$db = new Database();
$conn = $db->connect();

$dashboard = [];

/* ==========================
   Dashboard Cards
========================== */

// Total Enrolled Students
$result = $conn->query("
    SELECT COUNT(*) AS total
    FROM enrollment
");

$dashboard['enrolled'] = $result
    ? (int)$result->fetch_assoc()['total']
    : 0;


// Awaiting Payment
$result = $conn->query("
    SELECT COUNT(*) AS total
    FROM payment
    WHERE payment_status = 'Unpaid'
");

$dashboard['pending_payment'] = $result
    ? (int)$result->fetch_assoc()['total']
    : 0;


// Fully Enrolled
$result = $conn->query("
    SELECT COUNT(*) AS total
    FROM payment
    WHERE payment_status = 'Paid'
");

$dashboard['fully_enrolled'] = $result
    ? (int)$result->fetch_assoc()['total']
    : 0;


/* ==========================
   Enrollment List
========================== */

$enrollmentList = [];

$sql = "
SELECT
    s.student_id,
    CONCAT(s.first_name, ' ', s.last_name) AS student_name,
    c.course_name,
    sec.section_name,
    COALESCE(p.payment_status, 'No Payment') AS payment_status,
    e.status

FROM enrollment e

INNER JOIN student s
    ON s.student_id = e.student_id

LEFT JOIN section sec
    ON sec.section_id = s.section_id

LEFT JOIN course c
    ON c.course_id = sec.course_id

LEFT JOIN payment p
    ON p.enrollment_id = e.enrollment_id

ORDER BY s.student_id ASC
";

$result = $conn->query($sql);

if ($result) {
    while ($row = $result->fetch_assoc()) {
        $enrollmentList[] = $row;
    }
}