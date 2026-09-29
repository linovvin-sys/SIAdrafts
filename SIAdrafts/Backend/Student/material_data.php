<?php
/**
 * Query layer for the student-side course materials feature. Scoped to
 * the student's currently-enrolled subjects only, same as
 * Student/assignment_data.php, and resolves each enrollment_subject's
 * actual schedule the same section-path-or-explicit-schedule_id way (see
 * that file's docblock for why both paths are needed). Materials whose
 * visible_from date hasn't arrived yet are withheld -- drip content.
 */

function get_my_materials(mysqli $conn, int $applicantId): array
{
    $stmt = $conn->prepare("
        SELECT m.material_id, m.title, m.type, m.file_name, m.url, m.body, m.created_at,
               sub.subject_code, sub.subject_name
        FROM enrollment e
        JOIN enrollment_subject es ON es.enrollment_id = e.enrollment_id AND es.status = 'Enrolled'
        JOIN subject sub ON sub.subject_id = es.subject_id
        JOIN schedule sc ON sc.subject_id = es.subject_id
             AND sc.school_year = e.school_year AND sc.semester = e.semester
             AND (e.section_id = sc.section_id OR es.schedule_id = sc.schedule_id)
        JOIN class_material m ON m.schedule_id = sc.schedule_id
             AND (m.visible_from IS NULL OR m.visible_from <= CURDATE())
        WHERE e.applicant_id = ? AND e.status = 'Enrolled'
        ORDER BY m.created_at DESC
    ");
    $stmt->bind_param('i', $applicantId);
    $stmt->execute();
    $rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
    return $rows;
}
