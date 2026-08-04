<?php

// A subject's prereq_id existed in the schema but nothing ever read it —
// there's no grading system in this app, so "completed" is approximated
// as: the student has an enrollment_subject record for that prerequisite
// that was never Dropped (Enrolled or Credited), in any past enrollment.
function subject_prereq_met(mysqli $conn, int $applicant_id, int $subject_id): bool
{
    $stmt = $conn->prepare("SELECT prereq_id FROM subject WHERE subject_id = ? LIMIT 1");
    $stmt->bind_param('i', $subject_id);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    $prereq_id = $row['prereq_id'] ?? null;
    if (!$prereq_id) {
        return true;
    }

    $stmt = $conn->prepare(
        "SELECT 1
         FROM enrollment_subject es
         JOIN enrollment e ON e.enrollment_id = es.enrollment_id
         WHERE e.student_id = ? AND es.subject_id = ? AND es.status IN ('Enrolled', 'Credited')
         LIMIT 1"
    );
    $stmt->bind_param('ii', $applicant_id, $prereq_id);
    $stmt->execute();
    $met = (bool)$stmt->get_result()->fetch_assoc();
    $stmt->close();

    return $met;
}

// Human-readable label of a subject's prerequisite, for error messages.
function subject_prereq_label(mysqli $conn, int $subject_id): ?string
{
    $stmt = $conn->prepare(
        "SELECT p.subject_code, p.subject_name
         FROM subject sub JOIN subject p ON p.subject_id = sub.prereq_id
         WHERE sub.subject_id = ? LIMIT 1"
    );
    $stmt->bind_param('i', $subject_id);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    return $row ? $row['subject_code'] . ' — ' . $row['subject_name'] : null;
}
