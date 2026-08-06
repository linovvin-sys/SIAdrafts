<?php
session_start();
require_once '../db.php';
require_once '../roles.php';
require_once '../require_role.php';
header('Content-Type: application/json');

if (empty($_SESSION['professor_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized.']);
    exit;
}

require_role([ROLE_PROFESSOR], true);

$schedule_id = (int)($_GET['schedule_id'] ?? 0);

if (!$schedule_id) {
    echo json_encode(['error' => 'Missing schedule_id.']);
    exit;
}

$db   = new Database();
$conn = $db->connect();

// enrollment_subject.schedule_id is not populated by the current enrollment
// flow, so the roster is derived from subject + section + term instead of
// a direct schedule_id join. The WHERE clause's professor_id check (from
// the requester's own session, not user input) confirms the requested
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
    SELECT st.student_no, st.first_name, st.middle_name, st.last_name, st.email, st.contact_number
    FROM schedule s
    JOIN enrollment e ON e.section_id = s.section_id AND e.school_year = s.school_year AND e.semester = s.semester
    JOIN enrollment_subject es ON es.enrollment_id = e.enrollment_id AND es.subject_id = s.subject_id AND es.status = 'Enrolled'
    JOIN student st ON st.applicant_id = e.student_id
    WHERE s.schedule_id = ? AND s.professor_id = ?
    ORDER BY st.last_name, st.first_name
");
$stmt->bind_param('ii', $schedule_id, $_SESSION['professor_id']);
$stmt->execute();
$students = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();
$db->close();

echo json_encode(['students' => $students]);
