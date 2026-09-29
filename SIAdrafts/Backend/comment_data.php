<?php
/**
 * Shared submission-comment thread, usable from both the Professor and
 * Student portals (unlike most *_data.php files here, which are portal-
 * specific) since a comment thread inherently has two sides talking to
 * each other on the same submission.
 */

function get_submission_comments(mysqli $conn, int $submissionId): array
{
    $stmt = $conn->prepare(
        "SELECT comment_id, author_role, body, created_at FROM submission_comment WHERE submission_id = ? ORDER BY created_at ASC"
    );
    $stmt->bind_param('i', $submissionId);
    $stmt->execute();
    $rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
    return $rows;
}

/** True if this professor owns the assignment this submission belongs to. */
function professor_owns_submission(mysqli $conn, int $submissionId, int $professorId): bool
{
    $stmt = $conn->prepare(
        "SELECT 1 FROM assignment_submission sub
         JOIN assignment a ON a.assignment_id = sub.assignment_id
         WHERE sub.submission_id = ? AND a.professor_id = ?"
    );
    $stmt->bind_param('ii', $submissionId, $professorId);
    $stmt->execute();
    $owns = (bool)$stmt->get_result()->fetch_row();
    $stmt->close();
    return $owns;
}

/** True if this submission actually belongs to this student. */
function student_owns_submission(mysqli $conn, int $submissionId, int $applicantId): bool
{
    $stmt = $conn->prepare(
        "SELECT 1 FROM assignment_submission sub
         JOIN enrollment_subject es ON es.enrollment_subject_id = sub.enrollment_subject_id
         JOIN enrollment e ON e.enrollment_id = es.enrollment_id
         WHERE sub.submission_id = ? AND e.applicant_id = ?"
    );
    $stmt->bind_param('ii', $submissionId, $applicantId);
    $stmt->execute();
    $owns = (bool)$stmt->get_result()->fetch_row();
    $stmt->close();
    return $owns;
}

/** Returns null on success, or an error string. */
function post_submission_comment(mysqli $conn, int $submissionId, string $authorRole, string $body): ?string
{
    $body = trim($body);
    if ($body === '') {
        return 'Comment cannot be empty.';
    }
    if (mb_strlen($body) > 1000) {
        return 'Comment is too long (1000 characters max).';
    }

    try {
        $stmt = $conn->prepare("INSERT INTO submission_comment (submission_id, author_role, body) VALUES (?, ?, ?)");
        $stmt->bind_param('iss', $submissionId, $authorRole, $body);
        $stmt->execute();
        $stmt->close();
        return null;
    } catch (mysqli_sql_exception $e) {
        error_log('post_submission_comment: ' . $e->getMessage());
        return 'A database error occurred. Please try again.';
    }
}
