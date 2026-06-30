<?php

require_once __DIR__ . '/../db.php';

$db = new Database();
$conn = $db->connect();

/* ==========================
   Courses
========================== */

$courses = [];

$sql = "
SELECT
    course_id,
    course_code,
    course_name,
    total_units
FROM course
ORDER BY course_name
";

$result = $conn->query($sql);

if ($result) {
    while ($row = $result->fetch_assoc()) {
        $courses[] = $row;
    }
}


/* ==========================
   Sections
========================== */

$sections = [];

$sql = "
SELECT
    s.section_id,
    s.section_name,
    c.course_code,
    s.capacity,
    COUNT(st.student_id) AS enrolled

FROM section s

LEFT JOIN course c
    ON c.course_id = s.course_id

LEFT JOIN student st
    ON st.section_id = s.section_id

GROUP BY
    s.section_id,
    s.section_name,
    c.course_code,
    s.capacity

ORDER BY s.section_name
";

$result = $conn->query($sql);

if ($result) {
    while ($row = $result->fetch_assoc()) {
        $sections[] = $row;
    }
}