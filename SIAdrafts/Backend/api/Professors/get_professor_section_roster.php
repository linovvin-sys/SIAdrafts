<?php
require_once '../../require_professor.php';
require_professor(true);
require_once '../../db.php';
header('Content-Type: application/json');

$section_id  = (int)($_GET['section_id'] ?? 0);
$school_year = trim($_GET['school_year'] ?? '');
$semester    = (int)($_GET['semester'] ?? 0);

if (!$section_id || $school_year === '' || !$semester) {
    echo json_encode(['error' => 'Missing section_id, school_year, or semester.']);
    exit;
}

$db   = new Database();
$conn = $db->connect();

// Deduplicates students who take more than one subject with this professor
// in the same section/term. Same student.applicant_id join fix as
// get_professor_roster.php — enrollment.student_id is really applicant_id.
// The professor_id filter always comes from the session, never the
// querystring, so a professor can't page through another's section roster.
$stmt = $conn->prepare("
    SELECT DISTINCT st.student_no, st.first_name, st.middle_name, st.last_name, st.email, st.contact_number
    FROM schedule s
    JOIN enrollment e ON e.section_id = s.section_id AND e.school_year = s.school_year AND e.semester = s.semester
    JOIN enrollment_subject es ON es.enrollment_id = e.enrollment_id AND es.subject_id = s.subject_id AND es.status = 'Enrolled'
    JOIN student st ON st.applicant_id = e.student_id
    WHERE s.section_id = ? AND s.professor_id = ? AND s.school_year = ? AND s.semester = ?
      AND s.is_active = 1 AND s.status = 'Approved'
      AND e.status = 'Enrolled'
    ORDER BY st.last_name, st.first_name
");
$stmt->bind_param('iisi', $section_id, $_SESSION['professor_id'], $school_year, $semester);
$stmt->execute();
$students = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();
$db->close();

echo json_encode(['students' => $students]);
