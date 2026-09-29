<?php
/**
 * Query layer for the professor-side gradebook. Every write is
 * ownership-checked against the professor's own session id, same pattern
 * as announcement_data.php -- a schedule_id/enrollment_subject_id pair
 * must actually belong to the requesting professor before anything is
 * written. Grades are recorded per grading period (see grade_periods.php)
 * -- one class can hold up to 4 grade rows per student, one per period.
 */

require_once __DIR__ . '/../grade_periods.php';

/**
 * Roster for one class (schedule_id) with each student's grade for one
 * grading period, if any. Same enrollment resolution as
 * get_professor_roster.php -- matches either the regular section path or
 * the irregular per-subject schedule_id path, and joins through
 * applicant_id, the shared identity between enrollment and student.
 */
function get_class_grades(mysqli $conn, int $scheduleId, int $professorId, string $period): array
{
    $stmt = $conn->prepare("
        SELECT es.enrollment_subject_id, st.student_no, st.first_name, st.middle_name, st.last_name,
               g.grade_value, g.remarks, g.encoded_at
        FROM schedule s
        JOIN enrollment_subject es ON es.subject_id = s.subject_id AND es.status = 'Enrolled'
        JOIN enrollment e ON e.enrollment_id = es.enrollment_id AND e.school_year = s.school_year AND e.semester = s.semester
        JOIN student st ON st.applicant_id = e.applicant_id
        LEFT JOIN grade g ON g.enrollment_subject_id = es.enrollment_subject_id AND g.period = ?
        WHERE s.schedule_id = ? AND s.professor_id = ?
          AND (e.section_id = s.section_id OR es.schedule_id = s.schedule_id)
          AND e.status = 'Enrolled'
        ORDER BY st.last_name, st.first_name
    ");
    $stmt->bind_param('sii', $period, $scheduleId, $professorId);
    $stmt->execute();
    $rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
    return $rows;
}

/**
 * Saves (inserts or overwrites) a whole gradesheet submission in one
 * transaction. Previously each row ran its own ownership-check query plus
 * its own INSERT with an implicit autocommit -- a 40-student roster meant
 * ~80 sequential queries per "Save" click, and a failure partway through
 * left a half-saved sheet with no way back. Now the ownership check runs
 * once for the whole roster, and every row's INSERT shares one
 * transaction: either the whole batch lands or none of it does.
 *
 * $rows is the raw decoded JSON from the client: each entry may have
 * enrollment_subject_id / grade_value / remarks. Returns an array of
 * error strings (empty on full success).
 */
function save_grades_batch(mysqli $conn, int $scheduleId, int $professorId, string $period, array $rows): array
{
    if (!is_valid_grade_period($period)) {
        return ['Invalid grading period.'];
    }

    $ownedStmt = $conn->prepare("
        SELECT es.enrollment_subject_id
        FROM schedule s
        JOIN enrollment_subject es ON es.subject_id = s.subject_id AND es.status = 'Enrolled'
        JOIN enrollment e ON e.enrollment_id = es.enrollment_id AND e.school_year = s.school_year AND e.semester = s.semester
        WHERE s.schedule_id = ? AND s.professor_id = ?
          AND (e.section_id = s.section_id OR es.schedule_id = s.schedule_id)
          AND e.status = 'Enrolled'
    ");
    $ownedStmt->bind_param('ii', $scheduleId, $professorId);
    $ownedStmt->execute();
    $ownedIds = array_flip(array_column($ownedStmt->get_result()->fetch_all(MYSQLI_ASSOC), 'enrollment_subject_id'));
    $ownedStmt->close();

    $errors = [];
    $toSave = [];
    foreach ($rows as $row) {
        if (!is_array($row)) {
            continue;
        }
        $enrollmentSubjectId = (int)($row['enrollment_subject_id'] ?? 0);
        $gradeValue = trim((string)($row['grade_value'] ?? ''));
        $remarks    = trim((string)($row['remarks'] ?? ''));

        // Rows left blank in the sheet are skipped, not errored -- a
        // professor grading a partial roster shouldn't be blocked by
        // students they haven't reached yet.
        if ($gradeValue === '' || !$enrollmentSubjectId) {
            continue;
        }
        if (mb_strlen($gradeValue) > 10) {
            $errors[] = 'Grade value is too long.';
            continue;
        }
        if (mb_strlen($remarks) > 255) {
            $errors[] = 'Remarks are too long.';
            continue;
        }
        if (!isset($ownedIds[$enrollmentSubjectId])) {
            $errors[] = 'That student is not enrolled in this class.';
            continue;
        }
        $toSave[] = [$enrollmentSubjectId, $gradeValue, $remarks === '' ? null : $remarks];
    }

    if (empty($toSave)) {
        return array_unique($errors);
    }

    $conn->begin_transaction();
    try {
        $stmt = $conn->prepare("
            INSERT INTO grade (enrollment_subject_id, professor_id, period, grade_value, remarks)
            VALUES (?, ?, ?, ?, ?)
            ON DUPLICATE KEY UPDATE grade_value = VALUES(grade_value), remarks = VALUES(remarks), professor_id = VALUES(professor_id)
        ");
        foreach ($toSave as [$enrollmentSubjectId, $gradeValue, $remarksParam]) {
            $stmt->bind_param('iisss', $enrollmentSubjectId, $professorId, $period, $gradeValue, $remarksParam);
            $stmt->execute();
        }
        $stmt->close();
        $conn->commit();
    } catch (mysqli_sql_exception $e) {
        $conn->rollback();
        error_log('save_grades_batch: ' . $e->getMessage());
        $errors[] = 'A database error occurred while saving. Please try again.';
    }

    return array_unique($errors);
}
