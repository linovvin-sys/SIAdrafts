<?php
/**
 * Query layer for the Student Grades views. Grades are filtered by
 * year_level + semester (1st Year Sem 1 through 4th Year Sem 2) rather
 * than by enrollment.school_year, since the filter is meant to read like a
 * transcript's term list, not a literal school-year picker -- see
 * grade_term_options() for the fixed 8-term list this drives.
 */

require_once __DIR__ . '/../grade_periods.php';

/** The 8 fixed year/semester terms a 4-year program has, in order. */
function grade_term_options(): array
{
    $terms = [];
    for ($year = 1; $year <= 4; $year++) {
        for ($sem = 1; $sem <= 2; $sem++) {
            $terms[] = ['year_level' => $year, 'semester' => $sem];
        }
    }
    return $terms;
}

/**
 * The student's most recent enrollment (by created_at), used to default
 * the term filter to wherever the student actually is right now. Falls
 * back to Year 1 / Sem 1 if the student has no enrollment on file yet.
 */
function get_student_current_term(mysqli $conn, int $applicantId): array
{
    $stmt = $conn->prepare(
        "SELECT year_level, semester FROM enrollment WHERE applicant_id = ? ORDER BY created_at DESC LIMIT 1"
    );
    $stmt->bind_param('i', $applicantId);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    return $row ?: ['year_level' => 1, 'semester' => 1];
}

/**
 * A student's subjects and per-period grades for one year_level/semester
 * term. One row per subject, with a column per grading period -- absent
 * grades (professor hasn't encoded that period yet) come back null.
 */
function get_student_grades_for_term(mysqli $conn, int $applicantId, int $yearLevel, int $semester): array
{
    $stmt = $conn->prepare("
        SELECT sub.subject_code, sub.subject_name,
               MAX(CASE WHEN g.period = 'Prelim'   THEN g.grade_value END) AS prelim,
               MAX(CASE WHEN g.period = 'Midterm'  THEN g.grade_value END) AS midterm,
               MAX(CASE WHEN g.period = 'Prefinal' THEN g.grade_value END) AS prefinal,
               MAX(CASE WHEN g.period = 'Final'    THEN g.grade_value END) AS final,
               MAX(CASE WHEN g.period = 'Final'    THEN g.remarks END) AS final_remarks
        FROM enrollment e
        JOIN enrollment_subject es ON es.enrollment_id = e.enrollment_id AND es.status = 'Enrolled'
        JOIN subject sub ON sub.subject_id = es.subject_id
        LEFT JOIN grade g ON g.enrollment_subject_id = es.enrollment_subject_id
        WHERE e.applicant_id = ? AND e.year_level = ? AND e.semester = ?
        GROUP BY sub.subject_id, sub.subject_code, sub.subject_name
        ORDER BY sub.subject_code
    ");
    $stmt->bind_param('iii', $applicantId, $yearLevel, $semester);
    $stmt->execute();
    $rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
    return $rows;
}

/**
 * A single course's own grade row, for course_detail.php's Grades tab.
 * Grades are already naturally subject-scoped (one enrollment_subject row
 * per subject per term, independent of how many schedule/meeting blocks
 * that subject has), so this filters directly on es.subject_id -- no
 * schedule join needed at all, unlike every other per-course Student
 * function. Null if not enrolled in this subject.
 */
function get_my_grades_for_subject(mysqli $conn, int $applicantId, int $subjectId): ?array
{
    $stmt = $conn->prepare("
        SELECT sub.subject_code, sub.subject_name,
               MAX(CASE WHEN g.period = 'Prelim'   THEN g.grade_value END) AS prelim,
               MAX(CASE WHEN g.period = 'Midterm'  THEN g.grade_value END) AS midterm,
               MAX(CASE WHEN g.period = 'Prefinal' THEN g.grade_value END) AS prefinal,
               MAX(CASE WHEN g.period = 'Final'    THEN g.grade_value END) AS final,
               MAX(CASE WHEN g.period = 'Final'    THEN g.remarks END) AS final_remarks
        FROM enrollment e
        JOIN enrollment_subject es ON es.enrollment_id = e.enrollment_id AND es.status = 'Enrolled' AND es.subject_id = ?
        JOIN subject sub ON sub.subject_id = es.subject_id
        LEFT JOIN grade g ON g.enrollment_subject_id = es.enrollment_subject_id
        WHERE e.applicant_id = ? AND e.status = 'Enrolled'
        GROUP BY es.enrollment_subject_id, sub.subject_code, sub.subject_name
    ");
    $stmt->bind_param('ii', $subjectId, $applicantId);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    return $row ?: null;
}
