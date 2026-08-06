<?php
/**
 * Query layer for Frontend/View/Student/dashboard.php -- keeps the SQL out
 * of the view, matching the same 1:1 file-per-page convention as
 * Frontend/Js/Student/*.js.
 */

function get_dashboard_data(mysqli $conn, int $applicantId): array
{
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

    $payment = null;
    if ($enrollment) {
        $stmt = $conn->prepare("SELECT amount_due, balance, payment_status FROM payment WHERE enrollment_id = ? LIMIT 1");
        $stmt->bind_param('i', $enrollment['enrollment_id']);
        $stmt->execute();
        $payment = $stmt->get_result()->fetch_assoc();
        $stmt->close();
    }

    $subjectCount = 0;
    $totalUnits = 0.0;
    if ($enrollment) {
        $stmt = $conn->prepare(
            "SELECT COUNT(*) AS subject_count, COALESCE(SUM(sub.units), 0) AS total_units
             FROM enrollment_subject es
             JOIN subject sub ON sub.subject_id = es.subject_id
             WHERE es.enrollment_id = ? AND es.status != 'Dropped'"
        );
        $stmt->bind_param('i', $enrollment['enrollment_id']);
        $stmt->execute();
        $unitsRow = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        $subjectCount = (int)$unitsRow['subject_count'];
        $totalUnits = (float)$unitsRow['total_units'];
    }

    $stmt = $conn->prepare("SELECT document_name FROM applicant_documents WHERE applicant_id = ? AND status = 'submitted'");
    $stmt->bind_param('i', $applicantId);
    $stmt->execute();
    $submittedLabels = array_column($stmt->get_result()->fetch_all(MYSQLI_ASSOC), 'document_name');
    $stmt->close();

    // Recent activity: every enrollment + every payment for this student, merged
    // chronologically from real timestamps -- no synthetic/placeholder events.
    $activity = [];
    $stmt = $conn->prepare(
        "SELECT CONCAT('Enrolled — A.Y. ', e.school_year, ', Semester ', e.semester) AS label, 'mdi:school-outline' AS icon, 'enrollment' AS type, e.created_at AS ts
         FROM enrollment e WHERE e.student_id = ?"
    );
    $stmt->bind_param('i', $applicantId);
    $stmt->execute();
    foreach ($stmt->get_result()->fetch_all(MYSQLI_ASSOC) as $row) $activity[] = $row;
    $stmt->close();

    $stmt = $conn->prepare(
        "SELECT CONCAT('Payment received — \u{20B1}', FORMAT(p.downpayment, 2)) AS label, 'mdi:cash-check' AS icon, 'payment' AS type, p.paid_at AS ts
         FROM payment p JOIN enrollment e ON e.enrollment_id = p.enrollment_id
         WHERE e.student_id = ? AND p.paid_at IS NOT NULL"
    );
    $stmt->bind_param('i', $applicantId);
    $stmt->execute();
    foreach ($stmt->get_result()->fetch_all(MYSQLI_ASSOC) as $row) $activity[] = $row;
    $stmt->close();

    usort($activity, fn($a, $b) => strtotime($b['ts']) <=> strtotime($a['ts']));
    $activity = array_slice($activity, 0, 4);

    return [
        'enrollment'      => $enrollment,
        'payment'         => $payment,
        'subjectCount'    => $subjectCount,
        'totalUnits'      => $totalUnits,
        'submittedLabels' => $submittedLabels,
        'activity'        => $activity,
    ];
}
