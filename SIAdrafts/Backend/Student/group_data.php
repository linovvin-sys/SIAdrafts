<?php
require_once __DIR__ . '/../Professor/group_data.php';

/** The student's own group (with groupmates) for one class, or null if ungrouped/not enrolled. */
function get_my_group(mysqli $conn, int $scheduleId, int $applicantId): ?array
{
    $stmt = $conn->prepare(
        "SELECT g.group_id
         FROM class_group g
         JOIN class_group_member gm ON gm.group_id = g.group_id
         WHERE g.schedule_id = ? AND gm.applicant_id = ?"
    );
    $stmt->bind_param('ii', $scheduleId, $applicantId);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    if (!$row) {
        return null;
    }

    $groups = fetch_groups_with_members($conn, $scheduleId);
    foreach ($groups as $g) {
        if ((int)$g['group_id'] === (int)$row['group_id']) {
            return $g;
        }
    }
    return null;
}

/** Every class the student is in, each with its group (or null if that class has no groups yet). */
function get_my_groups_all_classes(mysqli $conn, int $applicantId): array
{
    $stmt = $conn->prepare(
        "SELECT DISTINCT sch.schedule_id, sub.subject_code, sub.subject_name
         FROM enrollment e
         JOIN enrollment_subject es ON es.enrollment_id = e.enrollment_id AND es.status = 'Enrolled'
         JOIN schedule sch ON sch.subject_id = es.subject_id
              AND sch.school_year = e.school_year AND sch.semester = e.semester
              AND (e.section_id = sch.section_id OR es.schedule_id = sch.schedule_id)
         JOIN subject sub ON sub.subject_id = sch.subject_id
         WHERE e.applicant_id = ?
         ORDER BY sub.subject_code"
    );
    $stmt->bind_param('i', $applicantId);
    $stmt->execute();
    $classes = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();

    foreach ($classes as &$c) {
        $c['group'] = get_my_group($conn, (int)$c['schedule_id'], $applicantId);
    }
    unset($c);
    return $classes;
}
