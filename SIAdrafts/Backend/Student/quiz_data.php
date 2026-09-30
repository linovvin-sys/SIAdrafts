<?php
/**
 * Query layer for the student-side Quiz feature. The server is the sole
 * timer/strike authority throughout: every function below that touches an
 * in-progress attempt calls enforce_attempt_caps() first, so a deadline or
 * violation cap that's already been exceeded gets finalized on whichever
 * request reaches the server first -- never left to the client to decide.
 */

/**
 * Resolves the enrollment_subject_id for this applicant in the class that
 * owns $quizId, or null if not enrolled. Same dual-path join
 * get_my_assignments() uses: Regular/Transferee enrollments carry their
 * schedule via enrollment.section_id (enrollment_subject.schedule_id is
 * NULL for them); Irregular enrollments instead carry an explicit
 * per-subject enrollment_subject.schedule_id.
 */
function resolve_enrollment_subject_id(mysqli $conn, int $quizId, int $applicantId): ?int
{
    $stmt = $conn->prepare("
        SELECT es.enrollment_subject_id
        FROM quiz q
        JOIN schedule sc ON sc.schedule_id = q.schedule_id
        JOIN enrollment e ON e.applicant_id = ? AND e.status = 'Enrolled'
             AND e.school_year = sc.school_year AND e.semester = sc.semester
        JOIN enrollment_subject es ON es.enrollment_id = e.enrollment_id AND es.subject_id = sc.subject_id AND es.status = 'Enrolled'
             AND (e.section_id = sc.section_id OR es.schedule_id = sc.schedule_id)
        WHERE q.quiz_id = ?
    ");
    $stmt->bind_param('ii', $applicantId, $quizId);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_row();
    $stmt->close();
    return $row ? (int)$row[0] : null;
}

/** Every published quiz with >=1 question posted for a subject the student is currently enrolled in, with their own attempt if any. */
function get_my_quizzes(mysqli $conn, int $applicantId): array
{
    $stmt = $conn->prepare("
        SELECT q.quiz_id, q.title, q.instructions, q.time_limit_minutes,
               q.available_from, q.available_until,
               sub.subject_code, sub.subject_name,
               es.enrollment_subject_id,
               qa.attempt_id, qa.status, qa.score, qa.max_score
        FROM enrollment e
        JOIN enrollment_subject es ON es.enrollment_id = e.enrollment_id AND es.status = 'Enrolled'
        JOIN subject sub ON sub.subject_id = es.subject_id
        JOIN schedule sc ON sc.subject_id = es.subject_id
             AND sc.school_year = e.school_year AND sc.semester = e.semester
             AND (e.section_id = sc.section_id OR es.schedule_id = sc.schedule_id)
        JOIN quiz q ON q.schedule_id = sc.schedule_id AND q.status = 'published'
             AND EXISTS (SELECT 1 FROM quiz_question qq WHERE qq.quiz_id = q.quiz_id)
        LEFT JOIN quiz_attempt qa ON qa.quiz_id = q.quiz_id AND qa.enrollment_subject_id = es.enrollment_subject_id
        WHERE e.applicant_id = ? AND e.status = 'Enrolled'
        ORDER BY q.created_at DESC
    ");
    $stmt->bind_param('i', $applicantId);
    $stmt->execute();
    $rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
    return $rows;
}

/**
 * Starts (or resumes) an attempt. Returns ['error' => string] or
 * ['attempt' => array]. deadline_at is computed by the DB itself
 * (DATE_ADD(NOW(), INTERVAL ? MINUTE)), capped at the quiz's
 * available_until if that's earlier -- an attempt can never outlive the
 * professor's closing window even if the per-attempt time limit would
 * otherwise allow it.
 */
function start_attempt(mysqli $conn, int $quizId, int $applicantId): array
{
    $stmt = $conn->prepare("SELECT quiz_id, status, time_limit_minutes, questions_per_attempt, available_from, available_until FROM quiz WHERE quiz_id = ?");
    $stmt->bind_param('i', $quizId);
    $stmt->execute();
    $quiz = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    if (!$quiz) {
        return ['error' => 'Quiz not found.'];
    }
    if ($quiz['status'] !== 'published') {
        return ['error' => "This quiz isn't ready yet."];
    }

    $now = new DateTime();
    if ($quiz['available_from'] !== null && $now < new DateTime($quiz['available_from'])) {
        return ['error' => 'This quiz is not open yet.'];
    }
    if ($quiz['available_until'] !== null && $now > new DateTime($quiz['available_until'])) {
        return ['error' => "This quiz's window has closed."];
    }

    $enrollmentSubjectId = resolve_enrollment_subject_id($conn, $quizId, $applicantId);
    if ($enrollmentSubjectId === null) {
        return ['error' => 'You are not enrolled in this class.'];
    }

    $stmt = $conn->prepare("SELECT 1 FROM quiz_question WHERE quiz_id = ? LIMIT 1");
    $stmt->bind_param('i', $quizId);
    $stmt->execute();
    $hasQuestions = (bool)$stmt->get_result()->fetch_row();
    $stmt->close();
    if (!$hasQuestions) {
        return ['error' => "This quiz isn't ready yet."];
    }

    $stmt = $conn->prepare("SELECT * FROM quiz_attempt WHERE quiz_id = ? AND enrollment_subject_id = ?");
    $stmt->bind_param('ii', $quizId, $enrollmentSubjectId);
    $stmt->execute();
    $existing = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if ($existing) {
        if ($existing['status'] === 'in_progress') {
            $existing = enforce_attempt_caps($conn, $existing);
            if ($existing['status'] !== 'in_progress') {
                return ['error' => 'Your time already ran out.', 'attempt' => $existing];
            }
            return ['attempt' => $existing];
        }
        return ['error' => "You've already completed this quiz."];
    }

    try {
        $conn->begin_transaction();

        $stmt = $conn->prepare(
            "INSERT INTO quiz_attempt (quiz_id, enrollment_subject_id, deadline_at)
             VALUES (?, ?, LEAST(DATE_ADD(NOW(), INTERVAL ? MINUTE), COALESCE(?, DATE_ADD(NOW(), INTERVAL ? MINUTE))))"
        );
        // LEAST() needs a real upper bound even when available_until is
        // NULL -- passing the same time-limit-derived value twice makes
        // the COALESCE branch a no-op instead of a special case.
        $timeLimit = (int)$quiz['time_limit_minutes'];
        $stmt->bind_param('iiisi', $quizId, $enrollmentSubjectId, $timeLimit, $quiz['available_until'], $timeLimit);
        $stmt->execute();
        $attemptId = $stmt->insert_id;
        $stmt->close();

        // Deal this attempt its own fixed question set now, so it's
        // locked in for the life of the attempt regardless of any later
        // edits to the quiz's question pool. When questions_per_attempt
        // is set and the pool is bigger than it, a random subset is
        // drawn -- otherwise every question in the pool is used (the
        // original, pre-generation behavior).
        $stmt = $conn->prepare("SELECT question_id FROM quiz_question WHERE quiz_id = ? ORDER BY sort_order, question_id");
        $stmt->bind_param('i', $quizId);
        $stmt->execute();
        $questionIds = array_column($stmt->get_result()->fetch_all(MYSQLI_ASSOC), 'question_id');
        $stmt->close();

        $perAttempt = $quiz['questions_per_attempt'] !== null ? (int)$quiz['questions_per_attempt'] : null;
        if ($perAttempt !== null && count($questionIds) > $perAttempt) {
            shuffle($questionIds);
            $questionIds = array_slice($questionIds, 0, $perAttempt);
        }

        $stmt = $conn->prepare("INSERT INTO quiz_attempt_question (attempt_id, question_id, sort_order) VALUES (?, ?, ?)");
        foreach ($questionIds as $i => $questionId) {
            $stmt->bind_param('iii', $attemptId, $questionId, $i);
            $stmt->execute();
        }
        $stmt->close();

        $conn->commit();
    } catch (mysqli_sql_exception $e) {
        $conn->rollback();
        error_log('start_attempt: ' . $e->getMessage());
        return ['error' => 'A database error occurred. Please try again.'];
    }

    $stmt = $conn->prepare("SELECT * FROM quiz_attempt WHERE attempt_id = ?");
    $stmt->bind_param('i', $attemptId);
    $stmt->execute();
    $attempt = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    return ['attempt' => $attempt];
}

/** Ownership check shared by every attempt-scoped function below. Null if not this applicant's attempt. */
function load_owned_attempt(mysqli $conn, int $attemptId, int $applicantId): ?array
{
    $stmt = $conn->prepare(
        "SELECT qa.* FROM quiz_attempt qa
         JOIN enrollment_subject es ON es.enrollment_subject_id = qa.enrollment_subject_id
         JOIN enrollment e ON e.enrollment_id = es.enrollment_id AND e.applicant_id = ?
         WHERE qa.attempt_id = ?"
    );
    $stmt->bind_param('ii', $applicantId, $attemptId);
    $stmt->execute();
    $attempt = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    return $attempt ?: null;
}

/**
 * Re-checks an in_progress attempt's deadline (and its quiz's
 * available_until) and violation count on every request that touches it.
 * This is what makes the server, not the client, authoritative: even if
 * the client never calls submit_attempt itself (closed tab, crashed
 * browser, disabled JS timer), the very next request that DOES reach the
 * server for this attempt -- an autosave, a violation log, a
 * get_attempt_state poll -- discovers the deadline has passed or the
 * strike cap was hit and finalizes it right there.
 */
function enforce_attempt_caps(mysqli $conn, array $attempt): array
{
    if ($attempt['status'] !== 'in_progress') {
        return $attempt;
    }
    if (strtotime($attempt['deadline_at']) <= time()) {
        return finalize_attempt($conn, (int)$attempt['attempt_id'], 'auto_submitted_time');
    }
    if ((int)$attempt['violation_count'] >= 3) {
        return finalize_attempt($conn, (int)$attempt['attempt_id'], 'auto_submitted_violations');
    }
    return $attempt;
}

/**
 * Grades every quiz_attempt_answer row for this attempt against
 * quiz_choice.is_correct, sums quiz_question.points for correct answers,
 * snapshots max_score as the sum of all the quiz's question points at
 * this moment, and marks the attempt with the given terminal status.
 * Idempotent -- the UPDATE's WHERE status = 'in_progress' guard means a
 * second call is a no-op that just re-reads the already-finalized row.
 */
function finalize_attempt(mysqli $conn, int $attemptId, string $status): array
{
    $conn->begin_transaction();
    try {
        $stmt = $conn->prepare(
            "UPDATE quiz_attempt_answer aa
             JOIN quiz_choice c ON c.choice_id = aa.choice_id
             SET aa.is_correct = c.is_correct
             WHERE aa.attempt_id = ?"
        );
        $stmt->bind_param('i', $attemptId);
        $stmt->execute();
        $stmt->close();

        $stmt = $conn->prepare(
            "SELECT
                (SELECT COALESCE(SUM(qq.points), 0) FROM quiz_attempt_answer aa
                    JOIN quiz_question qq ON qq.question_id = aa.question_id
                    WHERE aa.attempt_id = ? AND aa.is_correct = 1) AS score,
                (SELECT COALESCE(SUM(qq.points), 0) FROM quiz_attempt_question qaq
                    JOIN quiz_question qq ON qq.question_id = qaq.question_id
                    WHERE qaq.attempt_id = ?) AS max_score"
        );
        $stmt->bind_param('ii', $attemptId, $attemptId);
        $stmt->execute();
        $totals = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        $stmt = $conn->prepare(
            "UPDATE quiz_attempt SET score = ?, max_score = ?, status = ?, submitted_at = NOW()
             WHERE attempt_id = ? AND status = 'in_progress'"
        );
        $stmt->bind_param('ddsi', $totals['score'], $totals['max_score'], $status, $attemptId);
        $stmt->execute();
        $stmt->close();

        $conn->commit();
    } catch (mysqli_sql_exception $e) {
        $conn->rollback();
        error_log('finalize_attempt: ' . $e->getMessage());
    }

    $stmt = $conn->prepare("SELECT * FROM quiz_attempt WHERE attempt_id = ?");
    $stmt->bind_param('i', $attemptId);
    $stmt->execute();
    $attempt = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    return $attempt;
}

/**
 * Full state for the attempt screen: the attempt row plus every question
 * and its choices (without is_correct -- never revealed mid-quiz) and the
 * student's current answers. Null if not owned.
 */
function get_attempt_state(mysqli $conn, int $attemptId, int $applicantId): ?array
{
    $attempt = load_owned_attempt($conn, $attemptId, $applicantId);
    if (!$attempt) {
        return null;
    }
    $attempt = enforce_attempt_caps($conn, $attempt);

    $stmt = $conn->prepare(
        "SELECT qq.question_id, qq.question_text, qq.points, qaq.sort_order
         FROM quiz_attempt_question qaq
         JOIN quiz_question qq ON qq.question_id = qaq.question_id
         WHERE qaq.attempt_id = ? ORDER BY qaq.sort_order, qq.question_id"
    );
    $stmt->bind_param('i', $attemptId);
    $stmt->execute();
    $questions = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();

    if (!empty($questions)) {
        $ids = array_column($questions, 'question_id');
        $placeholders = implode(',', array_fill(0, count($ids), '?'));
        $types = str_repeat('i', count($ids));
        $stmt = $conn->prepare(
            "SELECT choice_id, question_id, choice_text, sort_order
             FROM quiz_choice WHERE question_id IN ($placeholders) ORDER BY sort_order, choice_id"
        );
        $stmt->bind_param($types, ...$ids);
        $stmt->execute();
        $choices = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $stmt->close();

        $byQuestion = [];
        foreach ($choices as $c) {
            $byQuestion[$c['question_id']][] = $c;
        }
        foreach ($questions as &$q) {
            $q['choices'] = $byQuestion[$q['question_id']] ?? [];
        }
        unset($q);
    }

    $stmt = $conn->prepare("SELECT question_id, choice_id FROM quiz_attempt_answer WHERE attempt_id = ?");
    $stmt->bind_param('i', $attemptId);
    $stmt->execute();
    $answerRows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
    $answers = [];
    foreach ($answerRows as $r) {
        $answers[$r['question_id']] = $r['choice_id'];
    }

    $attempt['questions'] = $questions;
    $attempt['answers'] = $answers;
    return $attempt;
}

/** Returns ['error'=>..., 'status'=>...] if the attempt has already ended, or ['success'=>true]. */
function autosave_answer(mysqli $conn, int $attemptId, int $applicantId, int $questionId, int $choiceId): array
{
    $attempt = load_owned_attempt($conn, $attemptId, $applicantId);
    if (!$attempt) {
        return ['error' => 'Attempt not found.'];
    }
    $attempt = enforce_attempt_caps($conn, $attempt);
    if ($attempt['status'] !== 'in_progress') {
        return ['error' => 'This attempt has already ended.', 'status' => $attempt['status']];
    }

    $stmt = $conn->prepare(
        "SELECT 1 FROM quiz_choice c JOIN quiz_question qq ON qq.question_id = c.question_id
         WHERE c.choice_id = ? AND c.question_id = ? AND qq.quiz_id = ?"
    );
    $stmt->bind_param('iii', $choiceId, $questionId, $attempt['quiz_id']);
    $stmt->execute();
    $valid = (bool)$stmt->get_result()->fetch_row();
    $stmt->close();
    if (!$valid) {
        return ['error' => 'Invalid choice.'];
    }

    try {
        $stmt = $conn->prepare(
            "INSERT INTO quiz_attempt_answer (attempt_id, question_id, choice_id)
             VALUES (?, ?, ?)
             ON DUPLICATE KEY UPDATE choice_id = VALUES(choice_id), answered_at = NOW()"
        );
        $stmt->bind_param('iii', $attemptId, $questionId, $choiceId);
        $stmt->execute();
        $stmt->close();
    } catch (mysqli_sql_exception $e) {
        error_log('autosave_answer: ' . $e->getMessage());
        return ['error' => 'Could not save your answer. Please try again.'];
    }

    return ['success' => true, 'deadline_at' => $attempt['deadline_at']];
}

/** One of 'tab_switch' | 'window_blur' | 'fullscreen_exit'. Returns the new violation_count/status. */
function log_violation(mysqli $conn, int $attemptId, int $applicantId, string $violationType, string $ip, ?string $userAgent): array
{
    if (!in_array($violationType, ['tab_switch', 'window_blur', 'fullscreen_exit'], true)) {
        return ['error' => 'Invalid violation type.'];
    }

    $attempt = load_owned_attempt($conn, $attemptId, $applicantId);
    if (!$attempt) {
        return ['error' => 'Attempt not found.'];
    }
    $attempt = enforce_attempt_caps($conn, $attempt);
    if ($attempt['status'] !== 'in_progress') {
        // Already ended -- don't log a violation against a dead attempt,
        // just tell the client so it can show the result screen.
        return ['violation_count' => (int)$attempt['violation_count'], 'status' => $attempt['status']];
    }

    try {
        $stmt = $conn->prepare(
            "INSERT INTO quiz_violation_log (violation_type, attempt_id, ip_address, user_agent) VALUES (?, ?, ?, ?)"
        );
        $stmt->bind_param('siss', $violationType, $attemptId, $ip, $userAgent);
        $stmt->execute();
        $stmt->close();

        $stmt = $conn->prepare("SELECT COUNT(*) FROM quiz_violation_log WHERE attempt_id = ?");
        $stmt->bind_param('i', $attemptId);
        $stmt->execute();
        $count = (int)$stmt->get_result()->fetch_row()[0];
        $stmt->close();

        $stmt = $conn->prepare("UPDATE quiz_attempt SET violation_count = ? WHERE attempt_id = ?");
        $stmt->bind_param('ii', $count, $attemptId);
        $stmt->execute();
        $stmt->close();
    } catch (mysqli_sql_exception $e) {
        error_log('log_violation: ' . $e->getMessage());
        return ['error' => 'Could not log this event.'];
    }

    if ($count >= 3) {
        $attempt = finalize_attempt($conn, $attemptId, 'auto_submitted_violations');
        return ['violation_count' => $count, 'status' => $attempt['status']];
    }

    return ['violation_count' => $count, 'status' => 'in_progress'];
}

/** Idempotent: if already terminal, just returns the existing result. */
function submit_attempt(mysqli $conn, int $attemptId, int $applicantId): array
{
    $attempt = load_owned_attempt($conn, $attemptId, $applicantId);
    if (!$attempt) {
        return ['error' => 'Attempt not found.'];
    }
    if ($attempt['status'] !== 'in_progress') {
        return ['score' => $attempt['score'], 'max_score' => $attempt['max_score'], 'status' => $attempt['status']];
    }

    $attempt = finalize_attempt($conn, $attemptId, 'submitted');
    return ['score' => $attempt['score'], 'max_score' => $attempt['max_score'], 'status' => $attempt['status']];
}
