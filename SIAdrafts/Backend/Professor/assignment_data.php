<?php
/**
 * Query layer for the professor-side assignments feature. Every write is
 * ownership-checked against the professor's own session id, same pattern
 * as announcement_data.php -- a schedule_id/assignment_id must actually
 * belong to the requesting professor before anything is written.
 */

/** All assignments posted for one class, most recent first, with a submission count. */
function get_class_assignments(mysqli $conn, int $scheduleId, int $professorId): array
{
    $stmt = $conn->prepare(
        "SELECT a.assignment_id, a.title, a.instructions, a.due_date, a.max_score, a.created_at,
                a.category_id, cat.name AS category_name,
                (SELECT COUNT(*) FROM assignment_submission sub WHERE sub.assignment_id = a.assignment_id) AS submission_count
         FROM assignment a
         LEFT JOIN assignment_category cat ON cat.category_id = a.category_id
         WHERE a.schedule_id = ? AND a.professor_id = ?
         ORDER BY a.created_at DESC"
    );
    $stmt->bind_param('ii', $scheduleId, $professorId);
    $stmt->execute();
    $rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
    return $rows;
}

/**
 * Returns null on success, or an error string if the schedule doesn't
 * belong to this professor or the input is invalid.
 */
function post_assignment(mysqli $conn, int $scheduleId, int $professorId, string $title, string $instructions, ?string $dueDate, ?string $maxScore, ?int $categoryId = null): ?string
{
    $title        = trim($title);
    $instructions = trim($instructions);

    if ($title === '' || $instructions === '') {
        return 'Title and instructions are both required.';
    }
    if (mb_strlen($title) > 150) {
        return 'Title is too long.';
    }

    $dueDateParam = null;
    if ($dueDate !== null && trim($dueDate) !== '') {
        $d = DateTime::createFromFormat('Y-m-d', $dueDate);
        if (!$d || $d->format('Y-m-d') !== $dueDate) {
            return 'Invalid due date.';
        }
        $dueDateParam = $dueDate;
    }

    $maxScoreParam = null;
    if ($maxScore !== null && trim($maxScore) !== '') {
        if (!is_numeric($maxScore) || (float)$maxScore <= 0 || (float)$maxScore > 9999) {
            return 'Max score must be a positive number.';
        }
        $maxScoreParam = (float)$maxScore;
    }

    $stmt = $conn->prepare("SELECT 1 FROM schedule WHERE schedule_id = ? AND professor_id = ?");
    $stmt->bind_param('ii', $scheduleId, $professorId);
    $stmt->execute();
    $owns = (bool)$stmt->get_result()->fetch_row();
    $stmt->close();
    if (!$owns) {
        return 'That class does not belong to you.';
    }

    // A category_id must actually belong to this same class -- otherwise a
    // stale/tampered id from another class (even another one of this
    // professor's classes) could get an assignment miscategorized there.
    if ($categoryId !== null) {
        $catStmt = $conn->prepare("SELECT 1 FROM assignment_category WHERE category_id = ? AND schedule_id = ?");
        $catStmt->bind_param('ii', $categoryId, $scheduleId);
        $catStmt->execute();
        $validCategory = (bool)$catStmt->get_result()->fetch_row();
        $catStmt->close();
        if (!$validCategory) {
            $categoryId = null;
        }
    }

    try {
        $stmt = $conn->prepare(
            "INSERT INTO assignment (schedule_id, professor_id, title, instructions, due_date, max_score, category_id)
             VALUES (?, ?, ?, ?, ?, ?, ?)"
        );
        $stmt->bind_param('iisssdi', $scheduleId, $professorId, $title, $instructions, $dueDateParam, $maxScoreParam, $categoryId);
        $stmt->execute();
        $stmt->close();
        return null;
    } catch (mysqli_sql_exception $e) {
        error_log('post_assignment: ' . $e->getMessage());
        return 'A database error occurred. Please try again.';
    }
}

/**
 * Returns null on success, or an error string if the assignment doesn't
 * belong to this professor or the input is invalid. Unlike delete+repost,
 * this leaves existing submissions untouched.
 */
function update_assignment(mysqli $conn, int $assignmentId, int $professorId, string $title, string $instructions, ?string $dueDate, ?string $maxScore, ?int $categoryId = null): ?string
{
    $title        = trim($title);
    $instructions = trim($instructions);

    if ($title === '' || $instructions === '') {
        return 'Title and instructions are both required.';
    }
    if (mb_strlen($title) > 150) {
        return 'Title is too long.';
    }

    $dueDateParam = null;
    if ($dueDate !== null && trim($dueDate) !== '') {
        $d = DateTime::createFromFormat('Y-m-d', $dueDate);
        if (!$d || $d->format('Y-m-d') !== $dueDate) {
            return 'Invalid due date.';
        }
        $dueDateParam = $dueDate;
    }

    $maxScoreParam = null;
    if ($maxScore !== null && trim($maxScore) !== '') {
        if (!is_numeric($maxScore) || (float)$maxScore <= 0 || (float)$maxScore > 9999) {
            return 'Max score must be a positive number.';
        }
        $maxScoreParam = (float)$maxScore;
    }

    $stmt = $conn->prepare("SELECT schedule_id FROM assignment WHERE assignment_id = ? AND professor_id = ?");
    $stmt->bind_param('ii', $assignmentId, $professorId);
    $stmt->execute();
    $owned = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    if (!$owned) {
        return 'Assignment not found.';
    }

    if ($categoryId !== null) {
        $catStmt = $conn->prepare("SELECT 1 FROM assignment_category WHERE category_id = ? AND schedule_id = ?");
        $catStmt->bind_param('ii', $categoryId, $owned['schedule_id']);
        $catStmt->execute();
        $validCategory = (bool)$catStmt->get_result()->fetch_row();
        $catStmt->close();
        if (!$validCategory) {
            $categoryId = null;
        }
    }

    try {
        $stmt = $conn->prepare(
            "UPDATE assignment SET title = ?, instructions = ?, due_date = ?, max_score = ?, category_id = ?
             WHERE assignment_id = ? AND professor_id = ?"
        );
        $stmt->bind_param('sssdiii', $title, $instructions, $dueDateParam, $maxScoreParam, $categoryId, $assignmentId, $professorId);
        $stmt->execute();
        $stmt->close();
        return null;
    } catch (mysqli_sql_exception $e) {
        error_log('update_assignment: ' . $e->getMessage());
        return 'A database error occurred. Please try again.';
    }
}

/**
 * Deletes an assignment (and, via FK cascade, its submissions). Returns
 * the deleted submissions' stored file paths so the caller can remove
 * them from disk, or null if the assignment wasn't found/owned.
 */
function delete_assignment(mysqli $conn, int $assignmentId, int $professorId): ?array
{
    try {
        $stmt = $conn->prepare(
            "SELECT sub.file_path FROM assignment a
             JOIN assignment_submission sub ON sub.assignment_id = a.assignment_id
             WHERE a.assignment_id = ? AND a.professor_id = ?"
        );
        $stmt->bind_param('ii', $assignmentId, $professorId);
        $stmt->execute();
        $filePaths = array_column($stmt->get_result()->fetch_all(MYSQLI_ASSOC), 'file_path');
        $stmt->close();

        $stmt = $conn->prepare("DELETE FROM assignment WHERE assignment_id = ? AND professor_id = ?");
        $stmt->bind_param('ii', $assignmentId, $professorId);
        $stmt->execute();
        $deleted = $stmt->affected_rows > 0;
        $stmt->close();

        return $deleted ? $filePaths : null;
    } catch (mysqli_sql_exception $e) {
        error_log('delete_assignment: ' . $e->getMessage());
        return null;
    }
}

/**
 * Roster for one assignment's class with each student's submission, if
 * any. Same enrollment resolution as grade_data.php's get_class_grades --
 * matches either the regular section path or the irregular per-subject
 * schedule_id path.
 */
function get_assignment_submissions(mysqli $conn, int $assignmentId, int $professorId): ?array
{
    $stmt = $conn->prepare(
        "SELECT a.schedule_id, a.due_date FROM assignment a WHERE a.assignment_id = ? AND a.professor_id = ?"
    );
    $stmt->bind_param('ii', $assignmentId, $professorId);
    $stmt->execute();
    $assignment = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    if (!$assignment) {
        return null;
    }

    $stmt = $conn->prepare("
        SELECT es.enrollment_subject_id, st.student_no, st.first_name, st.middle_name, st.last_name,
               sub.submission_id, sub.file_name, sub.submitted_at, sub.score, sub.feedback
        FROM schedule s
        JOIN enrollment_subject es ON es.subject_id = s.subject_id AND es.status = 'Enrolled'
        JOIN enrollment e ON e.enrollment_id = es.enrollment_id AND e.school_year = s.school_year AND e.semester = s.semester
        JOIN student st ON st.applicant_id = e.applicant_id
        LEFT JOIN assignment_submission sub ON sub.enrollment_subject_id = es.enrollment_subject_id AND sub.assignment_id = ?
        WHERE s.schedule_id = ?
          AND (e.section_id = s.section_id OR es.schedule_id = s.schedule_id)
          AND e.status = 'Enrolled'
        ORDER BY st.last_name, st.first_name
    ");
    $stmt->bind_param('ii', $assignmentId, $assignment['schedule_id']);
    $stmt->execute();
    $rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();

    $dueDate = $assignment['due_date'];
    foreach ($rows as &$row) {
        $row['is_late'] = $dueDate !== null && $row['submitted_at'] !== null
            && substr($row['submitted_at'], 0, 10) > $dueDate;
    }
    unset($row);

    return $rows;
}

/**
 * Saves score/feedback for a whole submissions roster in one transaction.
 * Previously this ran once per row via a since-removed single-submission
 * version: one ownership SELECT + one UPDATE per student, no transaction --
 * a 30-student roster meant ~60 sequential queries per "Save" click, and a
 * failure partway through left a half-graded roster. Same fix as
 * save_grades_batch: one ownership+max_score query for the whole
 * assignment, then one transaction covering every row's UPDATE. Also caps
 * each score at the assignment's own max_score, which the old version
 * never checked.
 *
 * $rows is the raw decoded JSON from the client: each entry may have
 * submission_id / score / feedback. Returns an array of error strings
 * (empty on full success).
 */
function save_submission_scores_batch(mysqli $conn, int $assignmentId, int $professorId, array $rows): array
{
    $stmt = $conn->prepare("SELECT max_score FROM assignment WHERE assignment_id = ? AND professor_id = ?");
    $stmt->bind_param('ii', $assignmentId, $professorId);
    $stmt->execute();
    $assignment = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    if (!$assignment) {
        return ['That assignment does not belong to you.'];
    }
    $maxScore = $assignment['max_score'] !== null ? (float)$assignment['max_score'] : null;

    $ownedStmt = $conn->prepare("SELECT submission_id FROM assignment_submission WHERE assignment_id = ?");
    $ownedStmt->bind_param('i', $assignmentId);
    $ownedStmt->execute();
    $ownedIds = array_flip(array_column($ownedStmt->get_result()->fetch_all(MYSQLI_ASSOC), 'submission_id'));
    $ownedStmt->close();

    $errors = [];
    $toSave = [];
    foreach ($rows as $row) {
        if (!is_array($row)) {
            continue;
        }
        $submissionId = (int)($row['submission_id'] ?? 0);
        $score        = $row['score'] ?? null;
        $feedback     = trim((string)($row['feedback'] ?? ''));

        if (!$submissionId) {
            continue;
        }
        // A row with neither a score nor feedback typed in is left alone --
        // no need to touch a submission the professor hasn't graded yet.
        if (($score === null || trim((string)$score) === '') && $feedback === '') {
            continue;
        }
        if (!isset($ownedIds[$submissionId])) {
            $errors[] = 'That submission does not belong to one of your assignments.';
            continue;
        }
        if (mb_strlen($feedback) > 500) {
            $errors[] = 'Feedback is too long.';
            continue;
        }

        $scoreParam = null;
        if ($score !== null && trim((string)$score) !== '') {
            if (!is_numeric($score) || (float)$score < 0) {
                $errors[] = 'Score must be a non-negative number.';
                continue;
            }
            $scoreParam = (float)$score;
            if ($maxScore !== null && $scoreParam > $maxScore) {
                $errors[] = 'Score cannot exceed the maximum of ' . rtrim(rtrim(number_format($maxScore, 2), '0'), '.') . '.';
                continue;
            }
        }

        $toSave[] = [$submissionId, $scoreParam, $feedback === '' ? null : $feedback];
    }

    if (empty($toSave)) {
        return array_unique($errors);
    }

    $conn->begin_transaction();
    try {
        $stmt = $conn->prepare("UPDATE assignment_submission SET score = ?, feedback = ? WHERE submission_id = ?");
        foreach ($toSave as [$submissionId, $scoreParam, $feedbackParam]) {
            $stmt->bind_param('dsi', $scoreParam, $feedbackParam, $submissionId);
            $stmt->execute();
        }
        $stmt->close();
        $conn->commit();
    } catch (mysqli_sql_exception $e) {
        $conn->rollback();
        error_log('save_submission_scores_batch: ' . $e->getMessage());
        $errors[] = 'A database error occurred while saving. Please try again.';
    }

    return array_unique($errors);
}
