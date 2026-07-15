<?php

require_once __DIR__ . '/../db.php';

$db = new Database();
$conn = $db->connect();

$dashboard = [];

/* ==========================
   Dashboard Cards
========================== */

// Total Students
$result = $conn->query("
    SELECT COUNT(*) AS total
    FROM student
");
$dashboard['students'] = $result ? (int)$result->fetch_assoc()['total'] : 0;


// Pending Admissions
$result = $conn->query("
    SELECT COUNT(*) AS total
    FROM applicants
    WHERE status = 'Pending'
");
$dashboard['pending'] = $result ? (int)$result->fetch_assoc()['total'] : 0;


// Revenue Collected
$result = $conn->query("
    SELECT COALESCE(SUM(amount),0) AS total
    FROM payment_transactions
");
$dashboard['revenue'] = $result ? (float)$result->fetch_assoc()['total'] : 0;


// Active Courses
$result = $conn->query("
    SELECT COUNT(*) AS total
    FROM course
");
$dashboard['courses'] = $result ? (int)$result->fetch_assoc()['total'] : 0;


/* ==========================
   Recent Admissions
========================== */

$recentAdmissions = [];

$result = $conn->query("
    SELECT
        CONCAT(first_name,' ',last_name) AS student_name,
        program,
        status
    FROM applicants
    ORDER BY created_at DESC
    LIMIT 5
");

if ($result) {
    while ($row = $result->fetch_assoc()) {
        $recentAdmissions[] = $row;
    }
}


/* ==========================
   Enrollment Summary
========================== */

$courseSummary = [];

$result = $conn->query("
   SELECT
    c.course_name,
    COUNT(s.student_id) AS enrolled,
    COUNT(DISTINCT sec.section_id) AS sections
FROM course c
LEFT JOIN section sec
    ON sec.course_id = c.course_id
LEFT JOIN student s
    ON s.section_id = sec.section_id
GROUP BY c.course_id, c.course_name
ORDER BY c.course_name;
");

if ($result) {
    while ($row = $result->fetch_assoc()) {
        $courseSummary[] = $row;
    }
}