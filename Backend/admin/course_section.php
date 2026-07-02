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

        header("Location: courses.php");
        exit;
    }
}

// Add Subject
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'add_subject') {
    $subCode    = trim($_POST['subject_code'] ?? '');
    $subName    = trim($_POST['subject_name'] ?? '');
    $units      = (float)($_POST['units'] ?? 0);
    $categoryId = (int)($_POST['category_id'] ?? 0);
    $yearLevel  = (int)($_POST['year_level'] ?? 0);
    $semester   = (int)($_POST['semester'] ?? 0);
    $prereqId   = trim($_POST['prereq_id'] ?? '');
    $prereqId   = ($prereqId === '') ? null : (int)$prereqId;

    if ($subCode === '' || $subName === '' || $categoryId <= 0 || $yearLevel <= 0 || $semester <= 0) {
        $subjectError = "Subject code, name, category, year level, and semester are required.";
    } else {
        $stmt = $conn->prepare(
            "INSERT INTO subject (subject_code, subject_name, units, category_id, year_level, semester, prereq_id)
             VALUES (?, ?, ?, ?, ?, ?, ?)"
        );
        $stmt->bind_param("ssdiiii", $subCode, $subName, $units, $categoryId, $yearLevel, $semester, $prereqId);

        try {
            $stmt->execute();
            header("Location: courses.php");
            exit;
        } catch (mysqli_sql_exception $e) {
            if ($e->getCode() === 1062) {
                $subjectError = "Subject code \"$subCode\" already exists. Please use a different code.";
            } else {
                $subjectError = "Could not save subject. Please try again.";
            }
        }
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

        header("Location: courses.php");
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


/* ==========================
   Subjects
========================== */

$subjects = [];

$sql = "
SELECT
    sub.subject_id,
    sub.subject_code,
    sub.subject_name,
    sub.units,
    sc.category_name,
    sub.year_level,
    sub.semester,
    p.subject_code AS prereq_code

FROM subject sub

LEFT JOIN subject_category sc
    ON sc.category_id = sub.category_id

LEFT JOIN subject p
    ON p.subject_id = sub.prereq_id

ORDER BY sub.year_level, sub.semester, sub.subject_code
";

$result = $conn->query($sql);

if ($result) {
    while ($row = $result->fetch_assoc()) {
        $subjects[] = $row;
    }
}


/* ==========================
   Subject Categories (for Add Subject dropdown)
========================== */

$categories = [];

$sql = "SELECT category_id, category_name FROM subject_category ORDER BY category_name";

$result = $conn->query($sql);

if ($result) {
    while ($row = $result->fetch_assoc()) {
        $categories[] = $row;
    }
}