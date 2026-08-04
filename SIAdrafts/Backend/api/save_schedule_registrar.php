<?php
session_start();
require_once '../db.php';
require_once '../require_role.php';
require_once '../csrf.php';
require_once '../subject_course.php';
header('Content-Type: application/json');

if (empty($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized.']);
    exit;
}

require_role(['Head Registrar', 'Registrar Staff'], true);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed.']);
    exit;
}

csrf_verify();

$db   = new Database();
$conn = $db->connect();

$raw  = file_get_contents('php://input');
$data = json_decode($raw, true);

if (!is_array($data)) {
    echo json_encode(['error' => 'Invalid request body.']);
    exit;
}

$section_id   = (int)($data['section_id']   ?? 0);
$subject_id   = (int)($data['subject_id']   ?? 0);
$professor_id = (int)($data['professor_id'] ?? 0);
$room_id      = (int)($data['room_id']      ?? 0);
$day          = trim($data['day']           ?? '');
$time_start   = trim($data['time_start']    ?? '');
$time_end     = trim($data['time_end']      ?? '');
$school_year  = trim($data['school_year']   ?? '');
$semester     = (int)($data['semester']     ?? 0);

$valid_days = ['Monday','Tuesday','Wednesday','Thursday','Friday','Saturday','Sunday'];

if (!$section_id || !$subject_id || !$room_id || !$day || !$time_start || !$time_end
    || !$school_year || !$semester || !in_array($day, $valid_days, true)) {
    echo json_encode(['error' => 'Missing or invalid required fields.']);
    exit;
}

if ($time_start >= $time_end) {
    echo json_encode(['error' => 'End time must be after start time.']);
    exit;
}

if (!preg_match('/^\d{4}-\d{4}$/', $school_year)) {
    echo json_encode(['error' => 'Invalid school year format. Use YYYY-YYYY.']);
    exit;
}

// The subject picker only shows subjects valid for the selected section's
// course, but that's a client-side convenience, not a trust boundary —
// re-verify here so a stale form or a direct API call can't schedule a
// subject under the wrong course's section.
$secStmt = $conn->prepare("SELECT course_id FROM section WHERE section_id = ? LIMIT 1");
$secStmt->bind_param('i', $section_id);
$secStmt->execute();
$sectionRow = $secStmt->get_result()->fetch_assoc();
$secStmt->close();

if (!$sectionRow) {
    echo json_encode(['error' => 'Section not found.']);
    exit;
}

if (!subject_belongs_to_course($conn, $subject_id, (int)$sectionRow['course_id'])) {
    echo json_encode(['error' => 'This subject is not offered under the selected section\'s course.']);
    exit;
}

// The subject picker only shows subjects matching the selected year level
// and semester, but same as above, re-verify server-side — a schedule row
// whose semester disagrees with its subject's own catalog semester would
// silently disappear from enrollment (that exact mismatch happened once
// with a hand-entered row and had to be fixed by hand).
$subStmt = $conn->prepare("SELECT semester FROM subject WHERE subject_id = ? LIMIT 1");
$subStmt->bind_param('i', $subject_id);
$subStmt->execute();
$subjectRow = $subStmt->get_result()->fetch_assoc();
$subStmt->close();

if (!$subjectRow) {
    echo json_encode(['error' => 'Subject not found.']);
    exit;
}

if ((int)$subjectRow['semester'] !== $semester) {
    echo json_encode(['error' => 'This subject belongs to a different semester than the one selected.']);
    exit;
}

// Registrar Staff submissions need Head Registrar approval first.
// The Head Registrar's own submissions go live immediately.
$isHeadRegistrar = current_user_is(['Head Registrar']);
$status          = $isHeadRegistrar ? 'Approved' : 'Pending';
$is_active        = $isHeadRegistrar ? 1 : 0;
$requested_by     = (int)$_SESSION['user_id'];

$conn->begin_transaction();

try {
    // Conflict check only against already-approved schedules — a still-pending
    // request from someone else shouldn't block a new submission.
    $conflictStmt = $conn->prepare(
        "SELECT schedule_id FROM schedule
         WHERE school_year = ? AND semester = ? AND day = ?
           AND time_start < ? AND time_end > ?
           AND status = 'Approved'
           AND (room_id = ? OR section_id = ?)"
    );
    if (!$conflictStmt) {
        throw new RuntimeException('Database error: ' . $conn->error);
    }
    $conflictStmt->bind_param(
        'sisssii',
        $school_year, $semester, $day, $time_end, $time_start, $room_id, $section_id
    );
    $conflictStmt->execute();
    $conflict = $conflictStmt->get_result()->fetch_assoc();
    $conflictStmt->close();

    if ($conflict) {
        throw new RuntimeException('This room or section is already booked on ' . $day . ' at an overlapping time.');
    }

    $insStmt = $conn->prepare(
        "INSERT INTO schedule
            (subject_id, professor_id, section_id, room_id, day, time_start, time_end,
             school_year, semester, is_active, status, requested_by)
         VALUES (?,?,?,?,?,?,?,?,?,?,?,?)"
    );
    if (!$insStmt) {
        throw new RuntimeException('Database error: ' . $conn->error);
    }
    $insStmt->bind_param(
        'iiiissssiisi',
        $subject_id, $professor_id, $section_id, $room_id, $day, $time_start, $time_end,
        $school_year, $semester, $is_active, $status, $requested_by
    );
    if (!$insStmt->execute()) {
        $insStmt->close();
        throw new RuntimeException('Database error: ' . $conn->error);
    }
    $newId = (int)$conn->insert_id;
    $insStmt->close();

    $conn->commit();

    echo json_encode([
        'success' => true,
        'schedule_id' => $newId,
        'status' => $status,
        'message' => $status === 'Pending'
            ? 'Schedule submitted for Head Registrar approval.'
            : 'Schedule created and approved.',
    ]);

} catch (RuntimeException $e) {
    $conn->rollback();
    echo json_encode(['error' => $e->getMessage()]);
} catch (mysqli_sql_exception $e) {
    $conn->rollback();
    echo json_encode(['error' => 'A database error occurred. Please try again.']);
}

$db->close();