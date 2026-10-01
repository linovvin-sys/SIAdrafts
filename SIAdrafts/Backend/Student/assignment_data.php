<?php
/**
 * Query layer for the student-side assignments feature. Scoped to the
 * student's currently-enrolled subjects only (enrollment_subject.status =
 * 'Enrolled'), same scoping the Professor portal's own class list uses --
 * assignments are a live, per-current-class feature, not a historical
 * record like grades.
 */

/**
 * Every assignment posted for a subject the student is currently enrolled
 * in, with the student's own submission (if any), soonest due date first.
 * Resolves each enrollment_subject's actual schedule the same way
 * Professor/grade_data.php's get_class_grades does: Regular/Transferee
 * enrollments carry their schedule implicitly via enrollment.section_id
 * (enrollment_subject.schedule_id is NULL for them); Irregular enrollments
 * instead carry an explicit per-subject enrollment_subject.schedule_id.
 * Joining only on the section path (or only on the explicit schedule_id)
 * would silently drop whichever group doesn't use that path.
 */
function get_my_assignments(mysqli $conn, int $applicantId): array
{
    $stmt = $conn->prepare("
        SELECT a.assignment_id, a.title, a.instructions, a.due_date, a.max_score,
               sub.subject_code, sub.subject_name,
               es.enrollment_subject_id,
               subm.submission_id, subm.file_name, subm.submitted_at, subm.score, subm.feedback
        FROM enrollment e
        JOIN enrollment_subject es ON es.enrollment_id = e.enrollment_id AND es.status = 'Enrolled'
        JOIN subject sub ON sub.subject_id = es.subject_id
        JOIN schedule sc ON sc.subject_id = es.subject_id
             AND sc.school_year = e.school_year AND sc.semester = e.semester
             AND (e.section_id = sc.section_id OR es.schedule_id = sc.schedule_id)
        JOIN assignment a ON a.schedule_id = sc.schedule_id
        LEFT JOIN assignment_submission subm ON subm.assignment_id = a.assignment_id AND subm.enrollment_subject_id = es.enrollment_subject_id
        WHERE e.applicant_id = ? AND e.status = 'Enrolled'
        ORDER BY (a.due_date IS NULL), a.due_date ASC, a.created_at DESC
    ");
    $stmt->bind_param('i', $applicantId);
    $stmt->execute();
    $rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();

    foreach ($rows as &$row) {
        $row['is_late'] = $row['due_date'] !== null && $row['submitted_at'] !== null
            && substr($row['submitted_at'], 0, 10) > $row['due_date'];
    }
    unset($row);

    return $rows;
}

/**
 * Same as get_my_assignments(), scoped to one SUBJECT (course_detail.php's
 * Assignments tab) instead of every enrolled class. Joins on subject_id
 * rather than one fixed schedule_id, so a subject with more than one
 * schedule row (e.g. a lecture block and a separate lab block) merges
 * every matching schedule's assignments into one feed, instead of only
 * showing whichever single block's content the old schedule_id-pinned
 * version would have. A student not actually enrolled in that subject
 * gets an empty array (the join simply finds no matching
 * enrollment_subject row), not an error -- course_detail.php does the
 * real ownership check before this is called. subject_code/subject_name
 * are dropped from the SELECT since the whole page is already scoped to
 * that one subject.
 */
function get_my_assignments_for_subject(mysqli $conn, int $applicantId, int $subjectId): array
{
    $stmt = $conn->prepare("
        SELECT a.assignment_id, a.title, a.instructions, a.due_date, a.max_score,
               subm.submission_id, subm.file_name, subm.submitted_at, subm.score, subm.feedback
        FROM enrollment e
        JOIN enrollment_subject es ON es.enrollment_id = e.enrollment_id AND es.status = 'Enrolled'
        JOIN schedule sc ON sc.subject_id = ? AND sc.subject_id = es.subject_id
             AND sc.school_year = e.school_year AND sc.semester = e.semester
             AND (e.section_id = sc.section_id OR es.schedule_id = sc.schedule_id)
        JOIN assignment a ON a.schedule_id = sc.schedule_id
        LEFT JOIN assignment_submission subm ON subm.assignment_id = a.assignment_id AND subm.enrollment_subject_id = es.enrollment_subject_id
        WHERE e.applicant_id = ? AND e.status = 'Enrolled'
        ORDER BY (a.due_date IS NULL), a.due_date ASC, a.created_at DESC
    ");
    $stmt->bind_param('ii', $subjectId, $applicantId);
    $stmt->execute();
    $rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();

    foreach ($rows as &$row) {
        $row['is_late'] = $row['due_date'] !== null && $row['submitted_at'] !== null
            && substr($row['submitted_at'], 0, 10) > $row['due_date'];
    }
    unset($row);

    return $rows;
}
