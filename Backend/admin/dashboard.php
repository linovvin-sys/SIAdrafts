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


// Approved Admissions
$result = $conn->query("
    SELECT COUNT(*) AS total
    FROM applicants
    WHERE status = 'Approved'
");
$dashboard['approved'] = $result ? (int)$result->fetch_assoc()['total'] : 0;


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
        status,
        created_at
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
   (with total section capacity, for a fill-rate bar)
========================== */

$courseSummary = [];

$result = $conn->query("
   SELECT
    c.course_name,
    COUNT(DISTINCT s.student_id) AS enrolled,
    COUNT(DISTINCT sec.section_id) AS sections,
    (SELECT COALESCE(SUM(capacity), 0) FROM section WHERE course_id = c.course_id) AS capacity
FROM course c
LEFT JOIN section sec
    ON sec.course_id = c.course_id
LEFT JOIN student s
    ON s.section_id = sec.section_id
GROUP BY c.course_id, c.course_name
ORDER BY enrolled DESC;
");

if ($result) {
    while ($row = $result->fetch_assoc()) {
        $row['fill_rate'] = $row['capacity'] > 0
            ? min(100, round(($row['enrolled'] / $row['capacity']) * 100))
            : 0;
        $courseSummary[] = $row;
    }
}


/* ==========================
   Admission Status Breakdown
   (feeds the status donut chart)
========================== */

$admissionStatusBreakdown = [];

$result = $conn->query("
    SELECT status, COUNT(*) AS total
    FROM applicants
    GROUP BY status
    ORDER BY total DESC
");

if ($result) {
    while ($row = $result->fetch_assoc()) {
        $admissionStatusBreakdown[] = $row;
    }
}


/* ==========================
   Admissions Trend — last 6 months
   (feeds the trend line chart; zero-fills months with no applicants)
========================== */

$admissionsTrend = [];
$monthKeys = [];
for ($i = 5; $i >= 0; $i--) {
    $ym = date('Y-m', strtotime("-$i months"));
    $monthKeys[$ym] = date('M', strtotime("-$i months"));
    $admissionsTrend[$ym] = 0;
}

$result = $conn->query("
    SELECT DATE_FORMAT(created_at, '%Y-%m') AS ym, COUNT(*) AS total
    FROM applicants
    WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL 6 MONTH)
    GROUP BY ym
");

if ($result) {
    while ($row = $result->fetch_assoc()) {
        if (isset($admissionsTrend[$row['ym']])) {
            $admissionsTrend[$row['ym']] = (int)$row['total'];
        }
    }
}

$admissionsTrendLabels = array_values($monthKeys);
$admissionsTrendData   = array_values($admissionsTrend);

$db->close();
