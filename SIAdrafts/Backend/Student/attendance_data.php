<?php
require_once __DIR__ . '/course_data.php';

/** The student's own attendance record + % present for one class. */
function get_my_attendance(mysqli $conn, int $applicantId, int $scheduleId): array
{
    $stmt = $conn->prepare(
        "SELECT session_date, status FROM attendance
         WHERE schedule_id = ? AND applicant_id = ?
         ORDER BY session_date DESC"
    );
    $stmt->bind_param('ii', $scheduleId, $applicantId);
    $stmt->execute();
    $records = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();

    $total = count($records);
    $present = count(array_filter($records, fn($r) => $r['status'] === 'Present' || $r['status'] === 'Late'));
    $percent = $total > 0 ? round($present / $total * 100) : null;

    return ['records' => $records, 'percent' => $percent, 'total_sessions' => $total];
}

/**
 * Attendance across a SUBJECT's schedule blocks, for course_detail.php's
 * Attendance tab. The attendance table is keyed by schedule_id directly
 * (no subject_id column), so this merges each schedule_id's own session
 * log into one combined list/percent instead of showing only whichever
 * single block's sessions the old schedule_id-pinned version would have
 * -- a subject with a lecture block and a lab block has two independent
 * sets of session dates, and the student experiences both as "this
 * subject's attendance," not two separate records.
 */
function get_my_attendance_for_subject(mysqli $conn, int $applicantId, int $subjectId): array
{
    $scheduleIds = get_my_schedule_ids_for_subject($conn, $subjectId, $applicantId);
    if (!$scheduleIds) {
        return ['records' => [], 'percent' => null, 'total_sessions' => 0];
    }

    $records = [];
    foreach ($scheduleIds as $scheduleId) {
        $records = array_merge($records, get_my_attendance($conn, $applicantId, $scheduleId)['records']);
    }
    usort($records, fn($a, $b) => strcmp($b['session_date'], $a['session_date']));

    $total = count($records);
    $present = count(array_filter($records, fn($r) => $r['status'] === 'Present' || $r['status'] === 'Late'));
    $percent = $total > 0 ? round($present / $total * 100) : null;

    return ['records' => $records, 'percent' => $percent, 'total_sessions' => $total];
}

/** Every class the student is in, each with its attendance summary. */
function get_my_attendance_all_classes(mysqli $conn, int $applicantId): array
{
    $stmt = $conn->prepare(
        "SELECT DISTINCT sch.schedule_id, sub.subject_code, sub.subject_name
         FROM enrollment e
         JOIN enrollment_subject es ON es.enrollment_id = e.enrollment_id AND es.status = 'Enrolled'
         JOIN schedule sch ON sch.subject_id = es.subject_id
              AND sch.school_year = e.school_year AND sch.semester = e.semester
              AND (e.section_id = sch.section_id OR es.schedule_id = sch.schedule_id)
         JOIN subject sub ON sub.subject_id = sch.subject_id
         WHERE e.applicant_id = ?
         ORDER BY sub.subject_code"
    );
    $stmt->bind_param('i', $applicantId);
    $stmt->execute();
    $classes = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();

    foreach ($classes as &$c) {
        $summary = get_my_attendance($conn, $applicantId, (int)$c['schedule_id']);
        $c['percent'] = $summary['percent'];
        $c['total_sessions'] = $summary['total_sessions'];
    }
    unset($c);
    return $classes;
}
