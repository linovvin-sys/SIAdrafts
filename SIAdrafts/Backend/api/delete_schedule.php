<?php
session_start();
require_once '../db.php';
require_once '../roles.php';
require_once '../require_role.php';
require_once '../csrf.php';
header('Content-Type: application/json');

if (empty($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized.']);
    exit;
}

require_role([ROLE_REGISTRAR_STAFF, ROLE_HEAD_REGISTRAR], true);

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

$ids    = array_values(array_unique(array_filter(array_map('intval', $data['ids'] ?? []))));
$reason = trim($data['reason'] ?? '');

if (empty($ids)) {
    echo json_encode(['error' => 'No schedule rows specified.']);
    exit;
}

if ($reason === '') {
    echo json_encode(['error' => 'Please provide a reason for deleting this schedule.']);
    exit;
}

$placeholders = implode(',', array_fill(0, count($ids), '?'));

// Snapshot the rows being deleted — once the schedule row is gone this is
// the only record of what it was and why it was removed.
$snapStmt = $conn->prepare(
    "SELECT sch.schedule_id, sub.subject_code, sub.subject_name, sec.section_name,
            sch.day, sch.time_start, sch.time_end, sch.school_year, sch.semester
     FROM schedule sch
     JOIN subject sub ON sub.subject_id = sch.subject_id
     JOIN section sec ON sec.section_id = sch.section_id
     WHERE sch.schedule_id IN ($placeholders)"
);
$snapStmt->bind_param(str_repeat('i', count($ids)), ...$ids);
$snapStmt->execute();
$snapshots = $snapStmt->get_result()->fetch_all(MYSQLI_ASSOC);
$snapStmt->close();

$deleted_by = (int)$_SESSION['user_id'];

$conn->begin_transaction();

// mysqli defaults to throwing on error (PHP 8.1+), so both the log insert
// and the delete are wrapped in one try/catch rather than checking return
// values — a false return from execute() would never actually happen here.
try {
    $logStmt = $conn->prepare(
        "INSERT INTO schedule_deletion_log
            (schedule_id, subject_code, subject_name, section_name, day, time_start, time_end, school_year, semester, reason, deleted_by)
         VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)"
    );
    foreach ($snapshots as $s) {
        $logStmt->bind_param(
            'isssssssisi',
            $s['schedule_id'], $s['subject_code'], $s['subject_name'], $s['section_name'],
            $s['day'], $s['time_start'], $s['time_end'], $s['school_year'], $s['semester'],
            $reason, $deleted_by
        );
        $logStmt->execute();
    }
    $logStmt->close();

    $delStmt = $conn->prepare("DELETE FROM schedule WHERE schedule_id IN ($placeholders)");
    $delStmt->bind_param(str_repeat('i', count($ids)), ...$ids);
    $delStmt->execute();
    $deleted = $delStmt->affected_rows;
    $delStmt->close();

    $conn->commit();
    $db->close();
    echo json_encode(['success' => true, 'deleted' => $deleted]);
} catch (mysqli_sql_exception $e) {
    $conn->rollback();
    // FK constraint (1451) — students are already enrolled under this schedule.
    if ($e->getCode() === 1451) {
        echo json_encode(['error' => 'Cannot delete — students are already enrolled under this schedule. Drop or transfer them first.']);
    } else {
        http_response_code(500);
        echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
    }
}