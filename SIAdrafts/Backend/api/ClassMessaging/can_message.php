<?php
/**
 * Authorization for class-scoped professor<->student messaging. Deliberately
 * a separate table/system from Backend/api/Messaging (staff-to-staff chat) --
 * that system's messages.sender_id/recipient_id are plain ints into
 * users.user_id, and professors/students live in entirely separate tables
 * with their own id spaces, so folding them into the same column would be
 * ambiguous. A thread here is always scoped to one (schedule_id, applicant_id)
 * pair: one class, one student, one professor (the schedule's own professor_id).
 *
 * The enrollment check mirrors get_student_announcements()
 * (Backend/Student/announcement_data.php) exactly -- a student counts as
 * "in this class" via either their section matching the schedule's section
 * (regular students) or an explicit enrollment_subject.schedule_id (irregular
 * students), with the enrollment itself still Enrolled.
 */

/** The professor_id who owns $scheduleId, or null if it doesn't exist. */
function class_message_schedule_owner(mysqli $conn, int $scheduleId): ?int
{
    $stmt = $conn->prepare("SELECT professor_id FROM schedule WHERE schedule_id = ?");
    $stmt->bind_param('i', $scheduleId);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    return $row ? (int)$row['professor_id'] : null;
}

/** True if $applicantId is a currently-enrolled student in $scheduleId's class. */
function class_message_student_in_schedule(mysqli $conn, int $applicantId, int $scheduleId): bool
{
    $stmt = $conn->prepare(
        "SELECT 1
         FROM enrollment e
         JOIN enrollment_subject es ON es.enrollment_id = e.enrollment_id AND es.status = 'Enrolled'
         JOIN schedule sch ON sch.subject_id = es.subject_id
              AND sch.school_year = e.school_year AND sch.semester = e.semester
              AND (e.section_id = sch.section_id OR es.schedule_id = sch.schedule_id)
         WHERE e.applicant_id = ? AND sch.schedule_id = ?
         LIMIT 1"
    );
    $stmt->bind_param('ii', $applicantId, $scheduleId);
    $stmt->execute();
    $exists = (bool)$stmt->get_result()->fetch_row();
    $stmt->close();
    return $exists;
}

/** True if this professor teaches $scheduleId AND $applicantId is really in that class. */
function professor_can_message_thread(mysqli $conn, int $professorId, int $scheduleId, int $applicantId): bool
{
    $owner = class_message_schedule_owner($conn, $scheduleId);
    if ($owner === null || $owner !== $professorId) {
        return false;
    }
    return class_message_student_in_schedule($conn, $applicantId, $scheduleId);
}

/** True if this student is really enrolled in $scheduleId's class (thread is always keyed on their own applicant_id). */
function student_can_message_thread(mysqli $conn, int $applicantId, int $scheduleId): bool
{
    return class_message_student_in_schedule($conn, $applicantId, $scheduleId);
}

/**
 * A professor's contact list: one row per (schedule_id, applicant_id)
 * thread across every class they teach, with the student's name, the
 * class label, and an unread count.
 */
function get_professor_class_threads(mysqli $conn, int $professorId): array
{
    $stmt = $conn->prepare(
        "SELECT DISTINCT sch.schedule_id, e.applicant_id,
                a.first_name, a.last_name,
                sub.subject_code, sub.subject_name, sec.section_name
         FROM schedule sch
         JOIN subject sub ON sub.subject_id = sch.subject_id
         JOIN section sec ON sec.section_id = sch.section_id
         JOIN enrollment e ON e.school_year = sch.school_year AND e.semester = sch.semester
         JOIN enrollment_subject es ON es.enrollment_id = e.enrollment_id AND es.status = 'Enrolled'
              AND es.subject_id = sch.subject_id
              AND (e.section_id = sch.section_id OR es.schedule_id = sch.schedule_id)
         JOIN applicants a ON a.applicant_id = e.applicant_id
         WHERE sch.professor_id = ?
         ORDER BY a.last_name, a.first_name, sub.subject_code"
    );
    $stmt->bind_param('i', $professorId);
    $stmt->execute();
    $threads = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();

    if (empty($threads)) {
        return [];
    }

    $unread = get_class_message_unread_counts($conn, 'professor', $professorId);
    foreach ($threads as &$t) {
        $key = $t['schedule_id'] . ':' . $t['applicant_id'];
        $t['unread'] = $unread[$key] ?? 0;
    }
    unset($t);
    return $threads;
}

/** A student's contact list: one row per class they're currently taking, each its own thread with that class's professor. */
function get_student_class_threads(mysqli $conn, int $applicantId): array
{
    $stmt = $conn->prepare(
        "SELECT DISTINCT sch.schedule_id,
                p.professor_id, p.first_name, p.last_name,
                sub.subject_code, sub.subject_name
         FROM enrollment e
         JOIN enrollment_subject es ON es.enrollment_id = e.enrollment_id AND es.status = 'Enrolled'
         JOIN schedule sch ON sch.subject_id = es.subject_id
              AND sch.school_year = e.school_year AND sch.semester = e.semester
              AND (e.section_id = sch.section_id OR es.schedule_id = sch.schedule_id)
         JOIN subject sub ON sub.subject_id = sch.subject_id
         JOIN professor p ON p.professor_id = sch.professor_id
         WHERE e.applicant_id = ?
         ORDER BY sub.subject_code"
    );
    $stmt->bind_param('i', $applicantId);
    $stmt->execute();
    $threads = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();

    if (empty($threads)) {
        return [];
    }

    $unread = get_class_message_unread_counts($conn, 'student', $applicantId);
    foreach ($threads as &$t) {
        $t['unread'] = $unread[$t['schedule_id']] ?? 0;
    }
    unset($t);
    return $threads;
}

/**
 * Unread counts keyed for easy lookup by the caller:
 * - viewer 'professor': key is "schedule_id:applicant_id" (a professor can share
 *   a student across more than one class, so schedule_id alone isn't unique per thread)
 * - viewer 'student': key is schedule_id (a student has exactly one professor per class)
 */
function get_class_message_unread_counts(mysqli $conn, string $viewerRole, int $viewerId): array
{
    if ($viewerRole === 'professor') {
        $stmt = $conn->prepare(
            "SELECT cm.schedule_id, cm.applicant_id, COUNT(*) AS unread
             FROM class_message cm
             JOIN schedule sch ON sch.schedule_id = cm.schedule_id
             WHERE sch.professor_id = ? AND cm.sender_role = 'student' AND cm.read_at IS NULL
             GROUP BY cm.schedule_id, cm.applicant_id"
        );
        $stmt->bind_param('i', $viewerId);
        $stmt->execute();
        $rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $stmt->close();

        $counts = [];
        foreach ($rows as $r) {
            $counts[$r['schedule_id'] . ':' . $r['applicant_id']] = (int)$r['unread'];
        }
        return $counts;
    }

    // viewer is the student -- unread messages sent by the professor's side.
    $stmt = $conn->prepare(
        "SELECT schedule_id, COUNT(*) AS unread
         FROM class_message
         WHERE applicant_id = ? AND sender_role = 'professor' AND read_at IS NULL
         GROUP BY schedule_id"
    );
    $stmt->bind_param('i', $viewerId);
    $stmt->execute();
    $rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();

    $counts = [];
    foreach ($rows as $r) {
        $counts[(int)$r['schedule_id']] = (int)$r['unread'];
    }
    return $counts;
}
