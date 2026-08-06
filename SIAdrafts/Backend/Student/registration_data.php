<?php
/**
 * Query layer for Frontend/View/Student/registration.php.
 */

function get_registration_data(mysqli $conn, int $applicantId): array
{
    $stmt = $conn->prepare(
        "SELECT a.first_name, a.middle_name, a.last_name, a.program, s.student_no
         FROM applicants a
         LEFT JOIN student s ON s.applicant_id = a.applicant_id
         WHERE a.applicant_id = ?
         LIMIT 1"
    );
    $stmt->bind_param('i', $applicantId);
    $stmt->execute();
    $applicant = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    $stmt = $conn->prepare(
        "SELECT e.enrollment_id, e.school_year, e.semester, e.status, e.year_level,
                sec.section_name, c.course_code, c.course_name
         FROM enrollment e
         LEFT JOIN section sec ON sec.section_id = e.section_id
         LEFT JOIN course c    ON c.course_id    = sec.course_id
         WHERE e.student_id = ?
         ORDER BY e.created_at DESC
         LIMIT 1"
    );
    $stmt->bind_param('i', $applicantId);
    $stmt->execute();
    $enrollment = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    $subjects = [];
    if ($enrollment) {
        $stmt = $conn->prepare(
            "SELECT sub.subject_code, sub.subject_name, sub.units
             FROM enrollment_subject es
             JOIN subject sub ON sub.subject_id = es.subject_id
             WHERE es.enrollment_id = ? AND es.status != 'Dropped'
             ORDER BY sub.subject_code"
        );
        $stmt->bind_param('i', $enrollment['enrollment_id']);
        $stmt->execute();
        $subjects = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $stmt->close();
    }

    return [
        'applicant'  => $applicant,
        'enrollment' => $enrollment,
        'subjects'   => $subjects,
    ];
}
