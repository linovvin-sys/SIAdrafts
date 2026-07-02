<?php

require_once __DIR__ . '/../db.php';

$db = new Database();
$conn = $db->connect();


// Add Course
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'add_course') {
    $code  = trim($_POST['course_code'] ?? '');
    $name  = trim($_POST['course_name'] ?? '');
    $units = (int)($_POST['total_units'] ?? 0);

    if ($code === '' || $name === '') {
        $error = "Course code and name are required.";
    } else {
        $stmt = $conn->prepare("INSERT INTO course (course_code, course_name, total_units) VALUES (?, ?, ?)");
        $stmt->bind_param("ssi", $code, $name, $units);
        $stmt->execute();

        header("Location: course_section.php");
        exit;
    }
}

// Add Section
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'add_section') {
    $sectionName = trim($_POST['section_name'] ?? '');
    $capacity    = (int)($_POST['capacity'] ?? 40);
    $courseId    = (int)($_POST['course_id'] ?? 0);

    if ($sectionName === '' || $courseId <= 0) {
        $error = "Section name and course are required.";
    } else {
        $stmt = $conn->prepare("INSERT INTO section (section_name, capacity, course_id) VALUES (?, ?, ?)");
        $stmt->bind_param("sii", $sectionName, $capacity, $courseId);
        $stmt->execute();

        header("Location: course_section.php");
        exit;
    }
}

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