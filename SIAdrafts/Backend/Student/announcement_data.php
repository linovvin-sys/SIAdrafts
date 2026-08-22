<?php
/**
 * Query layer for Frontend/View/Student/dashboard.php's announcements card.
 */

/**
 * Recent announcements across every class the student is currently
 * enrolled in. Joins the same section_id-OR-schedule_id path the
 * professor roster queries use, so irregular students (no section_id,
 * carried via enrollment_subject.schedule_id instead) aren't silently
 * dropped from seeing their own classes' announcements.
 */
function get_student_announcements(mysqli $conn, int $applicantId, int $limit = 5): array
{
    $stmt = $conn->prepare(
        "SELECT DISTINCT a.announcement_id, a.title, a.body, a.created_at,
                sub.subject_code,
                CONCAT(p.first_name, ' ', p.last_name) AS professor_name
         FROM enrollment e
         JOIN enrollment_subject es ON es.enrollment_id = e.enrollment_id AND es.status = 'Enrolled'
         JOIN schedule sch ON sch.subject_id = es.subject_id
              AND sch.school_year = e.school_year AND sch.semester = e.semester
              AND (e.section_id = sch.section_id OR es.schedule_id = sch.schedule_id)
         JOIN announcement a ON a.schedule_id = sch.schedule_id
         JOIN subject sub    ON sub.subject_id = sch.subject_id
         JOIN professor p    ON p.professor_id = a.professor_id
         WHERE e.student_id = ?
         ORDER BY a.created_at DESC, a.announcement_id DESC
         LIMIT ?"
    );
    $stmt->bind_param('ii', $applicantId, $limit);
    $stmt->execute();
    $rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
    return $rows;
}
