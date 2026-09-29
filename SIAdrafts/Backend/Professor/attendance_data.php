<?php
/**
 * Query layer for attendance. Same enrollment-join pattern used by
 * announcements/messaging/grouping for "who's really in this class right
 * now" -- kept as its own copy here (not a shared import) to match this
 * codebase's existing convention of each *_data.php owning its own
 * roster query rather than a cross-cutting shared helper.
 */

function get_class_roster_for_attendance(mysqli $conn, int $scheduleId, int $professorId): array
{
    $stmt = $conn->prepare(
        "SELECT DISTINCT e.applicant_id, a.first_name, a.last_name
         FROM schedule sch
         JOIN subject sub ON sub.subject_id = sch.subject_id
         JOIN enrollment e ON e.school_year = sch.school_year AND e.semester = sch.semester
         JOIN enrollment_subject es ON es.enrollment_id = e.enrollment_id AND es.status = 'Enrolled'
              AND es.subject_id = sch.subject_id
              AND (e.section_id = sch.section_id OR es.schedule_id = sch.schedule_id)
         JOIN applicants a ON a.applicant_id = e.applicant_id
         WHERE sch.schedule_id = ? AND sch.professor_id = ?
         ORDER BY a.last_name, a.first_name"
    );
    $stmt->bind_param('ii', $scheduleId, $professorId);
    $stmt->execute();
    $roster = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
    return $roster;
}

/** Roster + each student's existing status for one session date (null if not yet recorded). */
function get_attendance_for_date(mysqli $conn, int $scheduleId, int $professorId, string $sessionDate): array
{
    $roster = get_class_roster_for_attendance($conn, $scheduleId, $professorId);
    if (empty($roster)) {
        return [];
    }

    $stmt = $conn->prepare(
        "SELECT applicant_id, status FROM attendance WHERE schedule_id = ? AND session_date = ?"
    );
    $stmt->bind_param('is', $scheduleId, $sessionDate);
    $stmt->execute();
    $existing = [];
    foreach ($stmt->get_result()->fetch_all(MYSQLI_ASSOC) as $row) {
        $existing[(int)$row['applicant_id']] = $row['status'];
    }
    $stmt->close();

    foreach ($roster as &$r) {
        $r['status'] = $existing[(int)$r['applicant_id']] ?? null;
    }
    unset($r);
    return $roster;
}

/**
 * Bulk save/update one session date's attendance for a whole class in one
 * transaction -- same batching pattern as save_grades_batch() /
 * save_submission_scores_batch(), for the same reason (a 40-student
 * roster shouldn't mean 40 sequential round trips).
 * $statuses is [applicant_id => status]. Returns null on success or an error string.
 */
function save_attendance_batch(mysqli $conn, int $scheduleId, int $professorId, string $sessionDate, array $statuses): ?string
{
    $d = DateTime::createFromFormat('Y-m-d', $sessionDate);
    if (!$d || $d->format('Y-m-d') !== $sessionDate) {
        return 'Invalid session date.';
    }

    $validStatuses = ['Present', 'Absent', 'Late', 'Excused'];
    $roster = get_class_roster_for_attendance($conn, $scheduleId, $professorId);
    if (empty($roster)) {
        return 'That class does not belong to you, or has no enrolled students.';
    }
    $rosterIds = array_column($roster, 'applicant_id');

    try {
        $conn->begin_transaction();

        $stmt = $conn->prepare(
            "INSERT INTO attendance (schedule_id, applicant_id, session_date, status, professor_id)
             VALUES (?, ?, ?, ?, ?)
             ON DUPLICATE KEY UPDATE status = VALUES(status)"
        );
        foreach ($statuses as $applicantId => $status) {
            $applicantId = (int)$applicantId;
            // Only ever write a status for a student actually on this
            // class's current roster -- a stale/tampered applicant_id in
            // the request body can't plant an attendance row for someone
            // who was never in this class.
            if (!in_array($applicantId, $rosterIds, true)) {
                continue;
            }
            if (!in_array($status, $validStatuses, true)) {
                continue;
            }
            $stmt->bind_param('iissi', $scheduleId, $applicantId, $sessionDate, $status, $professorId);
            $stmt->execute();
        }
        $stmt->close();

        $conn->commit();
        return null;
    } catch (mysqli_sql_exception $e) {
        $conn->rollback();
        error_log('save_attendance_batch: ' . $e->getMessage());
        return 'A database error occurred. Please try again.';
    }
}

/** Every session date recorded for a class, with each student's status -- a simple history view. */
function get_attendance_history(mysqli $conn, int $scheduleId, int $professorId): array
{
    $ownerStmt = $conn->prepare("SELECT 1 FROM schedule WHERE schedule_id = ? AND professor_id = ?");
    $ownerStmt->bind_param('ii', $scheduleId, $professorId);
    $ownerStmt->execute();
    $owns = (bool)$ownerStmt->get_result()->fetch_row();
    $ownerStmt->close();
    if (!$owns) {
        return [];
    }

    $stmt = $conn->prepare(
        "SELECT DISTINCT session_date FROM attendance WHERE schedule_id = ? ORDER BY session_date DESC"
    );
    $stmt->bind_param('i', $scheduleId);
    $stmt->execute();
    $dates = array_column($stmt->get_result()->fetch_all(MYSQLI_ASSOC), 'session_date');
    $stmt->close();
    return $dates;
}
