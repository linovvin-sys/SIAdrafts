<?php
require_once '../../require_professor.php';
require_professor(true);
require_once '../../db.php';
header('Content-Type: application/json');

$schedule_id = (int)($_GET['schedule_id'] ?? 0);

if (!$schedule_id) {
    echo json_encode(['error' => 'Missing schedule_id.']);
    exit;
}

$db   = new Database();
$conn = $db->connect();

// Regular/Transferee enrollments carry their schedule implicitly via
// enrollment.section_id (enrollment_subject.schedule_id is NULL for them).
// Irregular enrollments have enrollment.section_id = NULL and instead carry
// an explicit per-subject enrollment_subject.schedule_id. Matching only
// e.section_id = s.section_id therefore silently drops every irregular
// student from a professor's roster, since NULL never equals a NOT NULL
// section_id. The WHERE clause matches either path. The professor_id check
// (from the requester's own session, not user input) confirms the requested
// schedule_id actually belongs to them, so one professor can't page
// through another's rosters by guessing schedule_id values.
//
// enrollment.student_id is a misnomer — it's actually a FK to
// applicants.applicant_id (see fk_enroll_applicant), and `student` rows
// are linked back to that same applicant via student.applicant_id, not
// student.student_id. Joining on student_id = student_id would silently
// match the wrong person whenever both tables happen to share that
// numeric id for unrelated people.
$stmt = $conn->prepare("
    SELECT DISTINCT st.student_no, st.first_name, st.middle_name, st.last_name, st.email, st.contact_number
    FROM schedule s
    JOIN enrollment_subject es ON es.subject_id = s.subject_id AND es.status = 'Enrolled'
    JOIN enrollment e ON e.enrollment_id = es.enrollment_id AND e.school_year = s.school_year AND e.semester = s.semester
    JOIN student st ON st.applicant_id = e.student_id
    WHERE s.schedule_id = ? AND s.professor_id = ?
      AND (e.section_id = s.section_id OR es.schedule_id = s.schedule_id)
      AND e.status = 'Enrolled'
    ORDER BY st.last_name, st.first_name
");
$stmt->bind_param('ii', $schedule_id, $_SESSION['professor_id']);
$stmt->execute();
$students = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();
$db->close();

echo json_encode(['students' => $students]);
