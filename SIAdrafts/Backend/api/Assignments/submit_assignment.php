<?php
require_once __DIR__ . '/../../require_student.php';
require_student(true);
require_once __DIR__ . '/../../db.php';
require_once __DIR__ . '/../../csrf.php';
require_once __DIR__ . '/submission_attachments.php';
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed.']);
    exit;
}

csrf_verify();

$assignmentId = (int)($_POST['assignment_id'] ?? 0);
if (!$assignmentId) {
    echo json_encode(['error' => 'Missing assignment_id.']);
    exit;
}

$db   = new Database();
$conn = $db->connect();

$applicantId = (int)$_SESSION['student_id'];

// Ownership check mirrors get_my_assignments' own join: the assignment
// must belong to a subject/schedule this student is currently enrolled
// in, resolved the same section-path-or-explicit-schedule-id way.
$stmt = $conn->prepare("
    SELECT es.enrollment_subject_id
    FROM assignment a
    JOIN schedule sc ON sc.schedule_id = a.schedule_id
    JOIN enrollment e ON e.school_year = sc.school_year AND e.semester = sc.semester AND e.applicant_id = ?
    JOIN enrollment_subject es ON es.enrollment_id = e.enrollment_id AND es.subject_id = sc.subject_id AND es.status = 'Enrolled'
    WHERE a.assignment_id = ? AND e.status = 'Enrolled'
      AND (e.section_id = sc.section_id OR es.schedule_id = sc.schedule_id)
    LIMIT 1
");
$stmt->bind_param('ii', $applicantId, $assignmentId);
$stmt->execute();
$match = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$match) {
    $db->close();
    echo json_encode(['error' => 'That assignment is not for one of your current classes.']);
    exit;
}

$enrollmentSubjectId = (int)$match['enrollment_subject_id'];

$stored = store_submission_file($_FILES['submission_file'] ?? []);
if (isset($stored['error'])) {
    $db->close();
    echo json_encode(['error' => $stored['error']]);
    exit;
}

// Re-submission replaces the file on disk, not just the DB row -- fetch
// the previous stored path first so it can be removed after the new one
// is safely written and committed. SELECT ... FOR UPDATE inside a
// transaction closes a double-click/retry race: without the row lock, two
// near-simultaneous submits could both read the same "previous" path
// before either INSERT committed, then each independently decide what to
// delete against a now-stale view -- the loser's cleanup could end up
// deleting the winner's just-written file. Locking the row (if it exists)
// forces the second request to wait for the first to commit, so it always
// sees the true up-to-date previous path before deciding what to delete.
$conn->begin_transaction();

$stmt = $conn->prepare(
    "SELECT file_path FROM assignment_submission WHERE assignment_id = ? AND enrollment_subject_id = ? FOR UPDATE"
);
$stmt->bind_param('ii', $assignmentId, $enrollmentSubjectId);
$stmt->execute();
$previous = $stmt->get_result()->fetch_assoc();
$stmt->close();

$stmt = $conn->prepare("
    INSERT INTO assignment_submission (assignment_id, enrollment_subject_id, file_path, file_name, file_type)
    VALUES (?, ?, ?, ?, ?)
    ON DUPLICATE KEY UPDATE file_path = VALUES(file_path), file_name = VALUES(file_name),
        file_type = VALUES(file_type), submitted_at = CURRENT_TIMESTAMP, score = NULL, feedback = NULL
");
$stmt->bind_param('iisss', $assignmentId, $enrollmentSubjectId, $stored['path'], $stored['name'], $stored['type']);
$stmt->execute();
$stmt->close();

$conn->commit();
$db->close();

if ($previous && $previous['file_path'] !== $stored['path']) {
    delete_submission_file($previous['file_path']);
}

echo json_encode(['success' => true]);
