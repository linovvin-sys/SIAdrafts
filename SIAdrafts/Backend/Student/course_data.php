<?php
/**
 * Query layer for the Student "My Courses" hub. One row per SUBJECT, not
 * per schedule_id -- a subject commonly has more than one schedule row
 * (e.g. a lecture block and a separate lab block, sometimes with
 * different professors), and the student doesn't experience that as two
 * different courses, so grouping by schedule_id here used to produce
 * duplicate-looking cards for the same subject. Every per-feature query
 * below (assignments/materials/quizzes/announcements) aggregates across
 * all of a subject's schedule rows for the same reason -- a lab
 * professor's posted material should show up next to the lecture
 * professor's, not be hidden behind a second, identical-looking card.
 *
 * Same dual-path enrollment resolution used everywhere else in the
 * Student backend (Regular/Transferee enrollments carry their schedule
 * via enrollment.section_id; Irregular enrollments via an explicit
 * enrollment_subject.schedule_id).
 */

/** Every subject the student is currently enrolled in, for the My Courses list. */
function get_my_courses(mysqli $conn, int $applicantId): array
{
    $stmt = $conn->prepare("
        SELECT DISTINCT sub.subject_id, sub.subject_code, sub.subject_name,
               CONCAT(p.first_name, ' ', p.last_name) AS professor_name
        FROM enrollment e
        JOIN enrollment_subject es ON es.enrollment_id = e.enrollment_id AND es.status = 'Enrolled'
        JOIN subject sub ON sub.subject_id = es.subject_id
        JOIN schedule sc ON sc.subject_id = es.subject_id
             AND sc.school_year = e.school_year AND sc.semester = e.semester
             AND (e.section_id = sc.section_id OR es.schedule_id = sc.schedule_id)
        JOIN professor p ON p.professor_id = sc.professor_id
        WHERE e.applicant_id = ? AND e.status = 'Enrolled'
        ORDER BY sub.subject_code, professor_name
    ");
    $stmt->bind_param('i', $applicantId);
    $stmt->execute();
    $rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();

    $courses = [];
    foreach ($rows as $r) {
        $sid = (int)$r['subject_id'];
        if (!isset($courses[$sid])) {
            $courses[$sid] = [
                'subject_id'   => $sid,
                'subject_code' => $r['subject_code'],
                'subject_name' => $r['subject_name'],
                'professors'   => [],
            ];
        }
        if (!in_array($r['professor_name'], $courses[$sid]['professors'], true)) {
            $courses[$sid]['professors'][] = $r['professor_name'];
        }
    }
    return array_values($courses);
}

/** Single-subject lookup for the course-detail page header + ownership check. Null if not enrolled. */
function get_my_course_by_subject(mysqli $conn, int $subjectId, int $applicantId): ?array
{
    $stmt = $conn->prepare("
        SELECT DISTINCT sub.subject_code, sub.subject_name,
               CONCAT(p.first_name, ' ', p.last_name) AS professor_name
        FROM enrollment e
        JOIN enrollment_subject es ON es.enrollment_id = e.enrollment_id AND es.status = 'Enrolled'
        JOIN subject sub ON sub.subject_id = ? AND sub.subject_id = es.subject_id
        JOIN schedule sc ON sc.subject_id = es.subject_id
             AND sc.school_year = e.school_year AND sc.semester = e.semester
             AND (e.section_id = sc.section_id OR es.schedule_id = sc.schedule_id)
        JOIN professor p ON p.professor_id = sc.professor_id
        WHERE e.applicant_id = ? AND e.status = 'Enrolled'
    ");
    $stmt->bind_param('ii', $subjectId, $applicantId);
    $stmt->execute();
    $rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
    if (!$rows) {
        return null;
    }

    $professors = [];
    foreach ($rows as $r) {
        if (!in_array($r['professor_name'], $professors, true)) {
            $professors[] = $r['professor_name'];
        }
    }
    return [
        'subject_id'   => $subjectId,
        'subject_code' => $rows[0]['subject_code'],
        'subject_name' => $rows[0]['subject_name'],
        'professors'   => $professors,
    ];
}

/**
 * Every schedule_id belonging to one subject for this student. Only
 * needed by features whose table is keyed by schedule_id directly with
 * no subject_id column of its own (attendance, groups) -- everything
 * else scopes by subject_id in one query instead.
 */
function get_my_schedule_ids_for_subject(mysqli $conn, int $subjectId, int $applicantId): array
{
    $stmt = $conn->prepare("
        SELECT DISTINCT sc.schedule_id
        FROM enrollment e
        JOIN enrollment_subject es ON es.enrollment_id = e.enrollment_id AND es.status = 'Enrolled'
        JOIN schedule sc ON sc.subject_id = ? AND sc.subject_id = es.subject_id
             AND sc.school_year = e.school_year AND sc.semester = e.semester
             AND (e.section_id = sc.section_id OR es.schedule_id = sc.schedule_id)
        WHERE e.applicant_id = ? AND e.status = 'Enrolled'
    ");
    $stmt->bind_param('ii', $subjectId, $applicantId);
    $stmt->execute();
    $ids = array_column($stmt->get_result()->fetch_all(MYSQLI_ASSOC), 'schedule_id');
    $stmt->close();
    return array_map('intval', $ids);
}
