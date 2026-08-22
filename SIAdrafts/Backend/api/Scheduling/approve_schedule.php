<?php
session_start();
require_once '../../db.php';
require_once '../../require_role.php';
require_once '../../csrf.php';
header('Content-Type: application/json');

if (empty($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized.']);
    exit;
}

require_registrar_tier(REGISTRAR_TIER_HEAD, true);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed.']);
    exit;
}

csrf_verify();

$db   = new Database();
$conn = $db->connect();

$raw         = file_get_contents('php://input');
$data        = json_decode($raw, true) ?? $_POST;
$schedule_id = (int)($data['schedule_id'] ?? 0);
$reviewer_id = (int)$_SESSION['user_id'];

if (!$schedule_id) {
    echo json_encode(['error' => 'No schedule specified.']);
    exit;
}

$conn->begin_transaction();

try {
    // Fetch the row first so we can re-run the conflict check against
    // whatever is Approved right now (things may have changed since submission).
    $stmt = $conn->prepare("SELECT * FROM schedule WHERE schedule_id = ? AND status = 'Pending' FOR UPDATE");
    $stmt->bind_param('i', $schedule_id);
    $stmt->execute();
    $sched = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$sched) {
        throw new RuntimeException('This schedule is no longer pending (it may have already been reviewed).');
    }

    $conflictStmt = $conn->prepare(
        "SELECT schedule_id FROM schedule
         WHERE school_year = ? AND semester = ? AND day = ?
           AND time_start < ? AND time_end > ?
           AND status = 'Approved'
           AND (room_id = ? OR section_id = ?)
           AND schedule_id != ?"
    );
    $conflictStmt->bind_param(
        'sisssiii',
        $sched['school_year'], $sched['semester'], $sched['day'],
        $sched['time_end'], $sched['time_start'],
        $sched['room_id'], $sched['section_id'], $schedule_id
    );
    $conflictStmt->execute();
    $conflict = $conflictStmt->get_result()->fetch_assoc();
    $conflictStmt->close();

    if ($conflict) {
        throw new RuntimeException('Cannot approve — this room or section is now booked on ' . $sched['day'] . ' at an overlapping time.');
    }

    $update = $conn->prepare(
        "UPDATE schedule
         SET status = 'Approved', is_active = 1, reviewed_by = ?, review_note = NULL
         WHERE schedule_id = ?"
    );
    $update->bind_param('ii', $reviewer_id, $schedule_id);
    $update->execute();
    $update->close();

    $conn->commit();
    echo json_encode(['success' => true, 'message' => 'Schedule approved.']);

} catch (RuntimeException $e) {
    $conn->rollback();
    echo json_encode(['error' => $e->getMessage()]);
} catch (mysqli_sql_exception $e) {
    $conn->rollback();
    echo json_encode(['error' => 'A database error occurred. Please try again.']);
}

$db->close();