<?php
/**
 * Query layer for the professor-side Quiz feature. Every write is
 * ownership-checked against the professor's own session id, same pattern
 * as assignment_data.php -- a schedule_id/quiz_id/question_id must
 * actually belong to the requesting professor before anything is written.
 */

/** All quizzes posted for one class, most recent first, with attempt stats. */
function get_class_quizzes(mysqli $conn, int $scheduleId, int $professorId): array
{
    $stmt = $conn->prepare(
        "SELECT q.quiz_id, q.title, q.instructions, q.status, q.time_limit_minutes, q.questions_per_attempt,
                q.available_from, q.available_until, q.created_at,
                (SELECT COUNT(*) FROM quiz_question qq WHERE qq.quiz_id = q.quiz_id) AS question_count,
                (SELECT COUNT(*) FROM quiz_attempt qa WHERE qa.quiz_id = q.quiz_id) AS attempt_count,
                (SELECT AVG(qa.score / qa.max_score) FROM quiz_attempt qa
                    WHERE qa.quiz_id = q.quiz_id AND qa.max_score > 0 AND qa.status != 'in_progress') AS avg_score_ratio
         FROM quiz q
         WHERE q.schedule_id = ? AND q.professor_id = ?
         ORDER BY q.created_at DESC"
    );
    $stmt->bind_param('ii', $scheduleId, $professorId);
    $stmt->execute();
    $rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
    return $rows;
}

/** Full quiz + questions + choices (incl. is_correct) for the builder's edit form. Null if not owned. */
function get_quiz_detail(mysqli $conn, int $quizId, int $professorId): ?array
{
    $stmt = $conn->prepare(
        "SELECT quiz_id, schedule_id, title, instructions, status, time_limit_minutes, questions_per_attempt, available_from, available_until
         FROM quiz WHERE quiz_id = ? AND professor_id = ?"
    );
    $stmt->bind_param('ii', $quizId, $professorId);
    $stmt->execute();
    $quiz = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    if (!$quiz) {
        return null;
    }

    $stmt = $conn->prepare(
        "SELECT question_id, question_text, points, sort_order
         FROM quiz_question WHERE quiz_id = ? ORDER BY sort_order, question_id"
    );
    $stmt->bind_param('i', $quizId);
    $stmt->execute();
    $questions = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();

    if (!empty($questions)) {
        $ids = array_column($questions, 'question_id');
        $placeholders = implode(',', array_fill(0, count($ids), '?'));
        $types = str_repeat('i', count($ids));
        $stmt = $conn->prepare(
            "SELECT choice_id, question_id, choice_text, is_correct, sort_order
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

    $quiz['questions'] = $questions;
    return $quiz;
}

/**
 * Returns ['error' => string] or ['quiz_id' => int]. $availableFrom/
 * $availableUntil are optional 'Y-m-d H:i:s'-ish datetime strings (from a
 * <input type="datetime-local">, which gives 'Y-m-dTH:i') or null.
 */
function create_quiz(mysqli $conn, int $scheduleId, int $professorId, string $title, ?string $instructions, int $timeLimitMinutes, ?string $availableFrom, ?string $availableUntil, ?int $questionsPerAttempt = null): array
{
    $validated = validate_quiz_input($title, $instructions, $timeLimitMinutes, $availableFrom, $availableUntil, $questionsPerAttempt);
    if (isset($validated['error'])) {
        return $validated;
    }
    [$title, $instructions, $availableFrom, $availableUntil, $questionsPerAttempt] = [
        $validated['title'], $validated['instructions'], $validated['available_from'], $validated['available_until'], $validated['questions_per_attempt'],
    ];

    $stmt = $conn->prepare("SELECT 1 FROM schedule WHERE schedule_id = ? AND professor_id = ?");
    $stmt->bind_param('ii', $scheduleId, $professorId);
    $stmt->execute();
    $owns = (bool)$stmt->get_result()->fetch_row();
    $stmt->close();
    if (!$owns) {
        return ['error' => 'That class does not belong to you.'];
    }

    try {
        $stmt = $conn->prepare(
            "INSERT INTO quiz (schedule_id, professor_id, title, instructions, time_limit_minutes, questions_per_attempt, available_from, available_until)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?)"
        );
        $stmt->bind_param('iissiiss', $scheduleId, $professorId, $title, $instructions, $timeLimitMinutes, $questionsPerAttempt, $availableFrom, $availableUntil);
        $stmt->execute();
        $quizId = $stmt->insert_id;
        $stmt->close();
        return ['quiz_id' => $quizId];
    } catch (mysqli_sql_exception $e) {
        error_log('create_quiz: ' . $e->getMessage());
        return ['error' => 'A database error occurred. Please try again.'];
    }
}

/** Returns null on success, or an error string. */
function update_quiz(mysqli $conn, int $quizId, int $professorId, string $title, ?string $instructions, int $timeLimitMinutes, ?string $availableFrom, ?string $availableUntil, ?int $questionsPerAttempt = null): ?string
{
    $validated = validate_quiz_input($title, $instructions, $timeLimitMinutes, $availableFrom, $availableUntil, $questionsPerAttempt);
    if (isset($validated['error'])) {
        return $validated['error'];
    }
    [$title, $instructions, $availableFrom, $availableUntil, $questionsPerAttempt] = [
        $validated['title'], $validated['instructions'], $validated['available_from'], $validated['available_until'], $validated['questions_per_attempt'],
    ];

    $stmt = $conn->prepare("SELECT 1 FROM quiz WHERE quiz_id = ? AND professor_id = ?");
    $stmt->bind_param('ii', $quizId, $professorId);
    $stmt->execute();
    $owned = (bool)$stmt->get_result()->fetch_row();
    $stmt->close();
    if (!$owned) {
        return 'Quiz not found.';
    }

    try {
        $stmt = $conn->prepare(
            "UPDATE quiz SET title = ?, instructions = ?, time_limit_minutes = ?, questions_per_attempt = ?, available_from = ?, available_until = ?
             WHERE quiz_id = ? AND professor_id = ?"
        );
        $stmt->bind_param('ssiissii', $title, $instructions, $timeLimitMinutes, $questionsPerAttempt, $availableFrom, $availableUntil, $quizId, $professorId);
        $stmt->execute();
        $stmt->close();
        return null;
    } catch (mysqli_sql_exception $e) {
        error_log('update_quiz: ' . $e->getMessage());
        return 'A database error occurred. Please try again.';
    }
}

/** Ownership check + requires >=1 question. Returns null on success, or an error string. */
function publish_quiz(mysqli $conn, int $quizId, int $professorId): ?string
{
    $stmt = $conn->prepare(
        "SELECT (SELECT COUNT(*) FROM quiz_question WHERE quiz_id = quiz.quiz_id) AS question_count
         FROM quiz WHERE quiz_id = ? AND professor_id = ?"
    );
    $stmt->bind_param('ii', $quizId, $professorId);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    if (!$row) {
        return 'Quiz not found.';
    }
    if ((int)$row['question_count'] < 1) {
        return 'Add at least one question before publishing.';
    }

    $stmt = $conn->prepare("UPDATE quiz SET status = 'published' WHERE quiz_id = ? AND professor_id = ?");
    $stmt->bind_param('ii', $quizId, $professorId);
    $stmt->execute();
    $stmt->close();
    return null;
}

/** Pulls a quiz back to draft (e.g. to fix a generated question before students see it again). */
function unpublish_quiz(mysqli $conn, int $quizId, int $professorId): ?string
{
    $stmt = $conn->prepare("UPDATE quiz SET status = 'draft' WHERE quiz_id = ? AND professor_id = ?");
    $stmt->bind_param('ii', $quizId, $professorId);
    $stmt->execute();
    $updated = $stmt->affected_rows > 0;
    $stmt->close();
    return $updated ? null : 'Quiz not found.';
}

/** Shared validation for create_quiz/update_quiz. Returns ['error'=>...] or the normalized fields. */
function validate_quiz_input(string $title, ?string $instructions, int $timeLimitMinutes, ?string $availableFrom, ?string $availableUntil, ?int $questionsPerAttempt = null): array
{
    $title = trim($title);
    $instructions = $instructions !== null ? trim($instructions) : null;

    if ($title === '') {
        return ['error' => 'Title is required.'];
    }
    if (mb_strlen($title) > 150) {
        return ['error' => 'Title is too long.'];
    }
    if ($timeLimitMinutes < 1 || $timeLimitMinutes > 180) {
        return ['error' => 'Time limit must be between 1 and 180 minutes.'];
    }
    if ($questionsPerAttempt !== null && ($questionsPerAttempt < 1 || $questionsPerAttempt > 999)) {
        return ['error' => 'Questions per attempt must be between 1 and 999.'];
    }

    $availableFrom = normalize_datetime_local($availableFrom);
    $availableUntil = normalize_datetime_local($availableUntil);
    if ($availableFrom !== null && $availableUntil !== null && $availableFrom >= $availableUntil) {
        return ['error' => 'The opening time must be before the closing time.'];
    }

    return [
        'title' => $title,
        'instructions' => ($instructions === '' ? null : $instructions),
        'available_from' => $availableFrom,
        'available_until' => $availableUntil,
        'questions_per_attempt' => $questionsPerAttempt,
    ];
}

/** Converts a <input type="datetime-local"> value ('Y-m-dTH:i') to 'Y-m-d H:i:s', or null if empty/invalid. */
function normalize_datetime_local(?string $value): ?string
{
    if ($value === null || trim($value) === '') {
        return null;
    }
    $d = DateTime::createFromFormat('Y-m-d\TH:i', trim($value));
    return $d ? $d->format('Y-m-d H:i:s') : null;
}

/** Deletes a quiz (and, via FK cascade, its questions/choices/attempts/answers). Returns null if not owned. */
function delete_quiz(mysqli $conn, int $quizId, int $professorId): ?string
{
    try {
        $stmt = $conn->prepare("DELETE FROM quiz WHERE quiz_id = ? AND professor_id = ?");
        $stmt->bind_param('ii', $quizId, $professorId);
        $stmt->execute();
        $deleted = $stmt->affected_rows > 0;
        $stmt->close();
        return $deleted ? null : 'Quiz not found.';
    } catch (mysqli_sql_exception $e) {
        error_log('delete_quiz: ' . $e->getMessage());
        return 'A database error occurred. Please try again.';
    }
}

/**
 * $choices is [['text' => string, 'is_correct' => bool], ...], decoded
 * client-side JSON. Requires >=2 choices and exactly one is_correct.
 * Returns ['error'=>...] or ['question_id'=>int].
 */
function post_question(mysqli $conn, int $quizId, int $professorId, string $questionText, float $points, array $choices): array
{
    $validated = validate_question_input($questionText, $points, $choices);
    if (isset($validated['error'])) {
        return $validated;
    }
    [$questionText, $points, $choices] = [$validated['question_text'], $validated['points'], $validated['choices']];

    $stmt = $conn->prepare("SELECT 1 FROM quiz WHERE quiz_id = ? AND professor_id = ?");
    $stmt->bind_param('ii', $quizId, $professorId);
    $stmt->execute();
    $owns = (bool)$stmt->get_result()->fetch_row();
    $stmt->close();
    if (!$owns) {
        return ['error' => 'That quiz does not belong to you.'];
    }

    $stmt = $conn->prepare("SELECT COALESCE(MAX(sort_order), -1) + 1 FROM quiz_question WHERE quiz_id = ?");
    $stmt->bind_param('i', $quizId);
    $stmt->execute();
    $nextOrder = (int)$stmt->get_result()->fetch_row()[0];
    $stmt->close();

    $conn->begin_transaction();
    try {
        $stmt = $conn->prepare("INSERT INTO quiz_question (quiz_id, question_text, points, sort_order) VALUES (?, ?, ?, ?)");
        $stmt->bind_param('isdi', $quizId, $questionText, $points, $nextOrder);
        $stmt->execute();
        $questionId = $stmt->insert_id;
        $stmt->close();

        $stmt = $conn->prepare("INSERT INTO quiz_choice (question_id, choice_text, is_correct, sort_order) VALUES (?, ?, ?, ?)");
        foreach ($choices as $i => $c) {
            $isCorrect = $c['is_correct'] ? 1 : 0;
            $stmt->bind_param('isii', $questionId, $c['text'], $isCorrect, $i);
            $stmt->execute();
        }
        $stmt->close();

        $conn->commit();
        return ['question_id' => $questionId];
    } catch (mysqli_sql_exception $e) {
        $conn->rollback();
        error_log('post_question: ' . $e->getMessage());
        return ['error' => 'A database error occurred. Please try again.'];
    }
}

/**
 * Runs generate_cloze_questions() (Backend/Quizzes/cloze_generator.php)
 * over $text and inserts each result via the SAME post_question() manual
 * entry uses -- full reuse of its ownership check, validation, and
 * transaction, rather than a separate insert path for generated content.
 * Also persists $questionsPerAttempt on the quiz row if given. Returns
 * ['generated_count'=>int, 'requested_count'=>?int] or ['error'=>string].
 */
function generate_questions_from_text(mysqli $conn, int $quizId, int $professorId, ?int $questionsPerAttempt, string $text): array
{
    $stmt = $conn->prepare("SELECT 1 FROM quiz WHERE quiz_id = ? AND professor_id = ?");
    $stmt->bind_param('ii', $quizId, $professorId);
    $stmt->execute();
    $owns = (bool)$stmt->get_result()->fetch_row();
    $stmt->close();
    if (!$owns) {
        return ['error' => 'That quiz does not belong to you.'];
    }

    if ($questionsPerAttempt !== null && ($questionsPerAttempt < 1 || $questionsPerAttempt > 999)) {
        return ['error' => 'Questions per attempt must be between 1 and 999.'];
    }

    require_once __DIR__ . '/../Quizzes/cloze_generator.php';
    $generated = generate_cloze_questions($text, 100);
    if (empty($generated)) {
        return ['error' => "Couldn't generate any usable questions from this file — try a file with more full sentences."];
    }

    $generatedCount = 0;
    foreach ($generated as $item) {
        $result = post_question($conn, $quizId, $professorId, $item['question_text'], $item['points'], $item['choices']);
        if (isset($result['question_id'])) {
            $generatedCount++;
        }
    }

    if ($questionsPerAttempt !== null) {
        $stmt = $conn->prepare("UPDATE quiz SET questions_per_attempt = ? WHERE quiz_id = ? AND professor_id = ?");
        $stmt->bind_param('iii', $questionsPerAttempt, $quizId, $professorId);
        $stmt->execute();
        $stmt->close();
    }

    return ['generated_count' => $generatedCount, 'requested_count' => $questionsPerAttempt];
}

/** Replaces the question text/points and all its choices. Returns null on success, or an error string. */
function update_question(mysqli $conn, int $questionId, int $professorId, string $questionText, float $points, array $choices): ?string
{
    $validated = validate_question_input($questionText, $points, $choices);
    if (isset($validated['error'])) {
        return $validated['error'];
    }
    [$questionText, $points, $choices] = [$validated['question_text'], $validated['points'], $validated['choices']];

    $stmt = $conn->prepare(
        "SELECT 1 FROM quiz_question qq JOIN quiz q ON q.quiz_id = qq.quiz_id
         WHERE qq.question_id = ? AND q.professor_id = ?"
    );
    $stmt->bind_param('ii', $questionId, $professorId);
    $stmt->execute();
    $owns = (bool)$stmt->get_result()->fetch_row();
    $stmt->close();
    if (!$owns) {
        return 'Question not found.';
    }

    $conn->begin_transaction();
    try {
        $stmt = $conn->prepare("UPDATE quiz_question SET question_text = ?, points = ? WHERE question_id = ?");
        $stmt->bind_param('sdi', $questionText, $points, $questionId);
        $stmt->execute();
        $stmt->close();

        // Choice ids aren't preserved across an edit -- MVP has no need to
        // keep an individual choice_id stable, and quiz_attempt_answer's
        // choice_id FK is ON DELETE SET NULL so an in-progress attempt's
        // answer for this question just reverts to "unanswered" rather
        // than erroring or vanishing.
        $stmt = $conn->prepare("DELETE FROM quiz_choice WHERE question_id = ?");
        $stmt->bind_param('i', $questionId);
        $stmt->execute();
        $stmt->close();

        $stmt = $conn->prepare("INSERT INTO quiz_choice (question_id, choice_text, is_correct, sort_order) VALUES (?, ?, ?, ?)");
        foreach ($choices as $i => $c) {
            $isCorrect = $c['is_correct'] ? 1 : 0;
            $stmt->bind_param('isii', $questionId, $c['text'], $isCorrect, $i);
            $stmt->execute();
        }
        $stmt->close();

        $conn->commit();
        return null;
    } catch (mysqli_sql_exception $e) {
        $conn->rollback();
        error_log('update_question: ' . $e->getMessage());
        return 'A database error occurred. Please try again.';
    }
}

function delete_question(mysqli $conn, int $questionId, int $professorId): ?string
{
    try {
        $stmt = $conn->prepare(
            "DELETE qq FROM quiz_question qq JOIN quiz q ON q.quiz_id = qq.quiz_id
             WHERE qq.question_id = ? AND q.professor_id = ?"
        );
        $stmt->bind_param('ii', $questionId, $professorId);
        $stmt->execute();
        $deleted = $stmt->affected_rows > 0;
        $stmt->close();
        return $deleted ? null : 'Question not found.';
    } catch (mysqli_sql_exception $e) {
        error_log('delete_question: ' . $e->getMessage());
        return 'A database error occurred. Please try again.';
    }
}

/** Shared validation for post_question/update_question. */
function validate_question_input(string $questionText, float $points, array $choices): array
{
    $questionText = trim($questionText);
    if ($questionText === '') {
        return ['error' => 'Question text is required.'];
    }
    if ($points <= 0 || $points > 999) {
        return ['error' => 'Points must be a positive number.'];
    }

    $clean = [];
    $correctCount = 0;
    foreach ($choices as $c) {
        if (!is_array($c)) {
            continue;
        }
        $text = trim((string)($c['text'] ?? ''));
        if ($text === '') {
            continue;
        }
        $isCorrect = !empty($c['is_correct']);
        if ($isCorrect) {
            $correctCount++;
        }
        $clean[] = ['text' => $text, 'is_correct' => $isCorrect];
    }
    if (count($clean) < 2) {
        return ['error' => 'A question needs at least two choices.'];
    }
    if ($correctCount !== 1) {
        return ['error' => 'Exactly one choice must be marked correct.'];
    }

    return ['question_text' => $questionText, 'points' => $points, 'choices' => $clean];
}

/**
 * Roster for one quiz's class with each student's attempt, if any. Same
 * dual-path enrollment resolution as get_assignment_submissions.
 */
function get_quiz_roster(mysqli $conn, int $quizId, int $professorId): ?array
{
    $stmt = $conn->prepare("SELECT schedule_id FROM quiz WHERE quiz_id = ? AND professor_id = ?");
    $stmt->bind_param('ii', $quizId, $professorId);
    $stmt->execute();
    $quiz = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    if (!$quiz) {
        return null;
    }

    $stmt = $conn->prepare("
        SELECT es.enrollment_subject_id, st.student_no, st.first_name, st.middle_name, st.last_name,
               qa.attempt_id, qa.status, qa.score, qa.max_score, qa.violation_count, qa.submitted_at
        FROM schedule s
        JOIN enrollment_subject es ON es.subject_id = s.subject_id AND es.status = 'Enrolled'
        JOIN enrollment e ON e.enrollment_id = es.enrollment_id AND e.school_year = s.school_year AND e.semester = s.semester
        JOIN student st ON st.applicant_id = e.applicant_id
        LEFT JOIN quiz_attempt qa ON qa.enrollment_subject_id = es.enrollment_subject_id AND qa.quiz_id = ?
        WHERE s.schedule_id = ?
          AND (e.section_id = s.section_id OR es.schedule_id = s.schedule_id)
          AND e.status = 'Enrolled'
        ORDER BY st.last_name, st.first_name
    ");
    $stmt->bind_param('ii', $quizId, $quiz['schedule_id']);
    $stmt->execute();
    $rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
    return $rows;
}

/**
 * Ownership-checked full per-question breakdown of one attempt, plus its
 * violation timeline. Null if the attempt isn't for one of this
 * professor's own quizzes.
 */
function get_attempt_review(mysqli $conn, int $attemptId, int $professorId): ?array
{
    $stmt = $conn->prepare(
        "SELECT qa.attempt_id, qa.status, qa.score, qa.max_score, qa.violation_count,
                qa.started_at, qa.submitted_at, q.title AS quiz_title,
                st.first_name, st.last_name
         FROM quiz_attempt qa
         JOIN quiz q ON q.quiz_id = qa.quiz_id AND q.professor_id = ?
         JOIN enrollment_subject es ON es.enrollment_subject_id = qa.enrollment_subject_id
         JOIN enrollment en ON en.enrollment_id = es.enrollment_id
         JOIN student st ON st.applicant_id = en.applicant_id
         WHERE qa.attempt_id = ?"
    );
    $stmt->bind_param('ii', $professorId, $attemptId);
    $stmt->execute();
    $attempt = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    if (!$attempt) {
        return null;
    }

    $stmt = $conn->prepare(
        "SELECT qq.question_id, qq.question_text, qq.points,
                aa.choice_id AS chosen_choice_id, aa.is_correct,
                correct.choice_id AS correct_choice_id, correct.choice_text AS correct_choice_text,
                chosen.choice_text AS chosen_choice_text
         FROM quiz_attempt_question qaq
         JOIN quiz_question qq ON qq.question_id = qaq.question_id
         LEFT JOIN quiz_attempt_answer aa ON aa.question_id = qq.question_id AND aa.attempt_id = qaq.attempt_id
         LEFT JOIN quiz_choice correct ON correct.question_id = qq.question_id AND correct.is_correct = 1
         LEFT JOIN quiz_choice chosen ON chosen.choice_id = aa.choice_id
         WHERE qaq.attempt_id = ?
         ORDER BY qaq.sort_order, qq.question_id"
    );
    $stmt->bind_param('i', $attemptId);
    $stmt->execute();
    $attempt['questions'] = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();

    $stmt = $conn->prepare(
        "SELECT violation_type, created_at FROM quiz_violation_log WHERE attempt_id = ? ORDER BY created_at"
    );
    $stmt->bind_param('i', $attemptId);
    $stmt->execute();
    $attempt['violations'] = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();

    return $attempt;
}
