<?php
/**
 * Shared schedule lookup for the Student portal — used by both the full
 * weekly grid (schedule.php) and the dashboard's "next class" / preview
 * modules, so the enrollment_subject → section-schedule fallback logic
 * (irregular vs. regular students) lives in exactly one place.
 */

function get_student_schedule(mysqli $conn, int $studentId): array
{
    $stmt = $conn->prepare(
        "SELECT enrollment_id, section_id, school_year, semester
         FROM enrollment
         WHERE student_id = ?
         ORDER BY created_at DESC
         LIMIT 1"
    );
    $stmt->bind_param('i', $studentId);
    $stmt->execute();
    $enrollment = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    $subjects = [];
    if ($enrollment) {
        // Direct per-subject schedule first (Irregular students).
        $stmt = $conn->prepare(
            "SELECT es.subject_id, sub.subject_code, sub.subject_name,
                    sch.day, sch.time_start, sch.time_end,
                    CONCAT(p.first_name, ' ', p.last_name) AS professor_name,
                    r.room_name
             FROM enrollment_subject es
             JOIN subject sub        ON sub.subject_id = es.subject_id
             LEFT JOIN schedule sch  ON sch.schedule_id = es.schedule_id
             LEFT JOIN professor p   ON p.professor_id = sch.professor_id
             LEFT JOIN room r        ON r.room_id = sch.room_id
             WHERE es.enrollment_id = ? AND es.status != 'Dropped'
             ORDER BY sub.subject_code"
        );
        $stmt->bind_param('i', $enrollment['enrollment_id']);
        $stmt->execute();
        $subjects = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $stmt->close();

        // Fallback to the section's approved schedule for anything still missing a day (Regular students).
        $needsFallback = array_values(array_filter($subjects, fn($s) => empty($s['day'])));
        if ($needsFallback && $enrollment['section_id']) {
            $stmt = $conn->prepare(
                "SELECT sch.subject_id, sch.day, sch.time_start, sch.time_end,
                        CONCAT(p.first_name, ' ', p.last_name) AS professor_name,
                        r.room_name
                 FROM schedule sch
                 LEFT JOIN professor p ON p.professor_id = sch.professor_id
                 LEFT JOIN room r      ON r.room_id = sch.room_id
                 WHERE sch.section_id  = ?
                   AND sch.school_year = ?
                   AND sch.semester    = ?
                   AND sch.status      = 'Approved'
                 ORDER BY sch.schedule_id"
            );
            $stmt->bind_param('isi', $enrollment['section_id'], $enrollment['school_year'], $enrollment['semester']);
            $stmt->execute();
            // A subject can meet more than once a week (lecture + lab, or two
            // separate lecture blocks) -- collapsing to one row per subject_id
            // silently dropped every session but the first (lowest schedule_id).
            $bySubject = [];
            foreach ($stmt->get_result()->fetch_all(MYSQLI_ASSOC) as $row) {
                $bySubject[$row['subject_id']][] = $row;
            }
            $stmt->close();

            $expanded = [];
            foreach ($subjects as $sub) {
                if (empty($sub['day']) && isset($bySubject[$sub['subject_id']])) {
                    foreach ($bySubject[$sub['subject_id']] as $occurrence) {
                        $expanded[] = array_merge($sub, array_intersect_key($occurrence, array_flip(['day', 'time_start', 'time_end', 'professor_name', 'room_name'])));
                    }
                } else {
                    $expanded[] = $sub;
                }
            }
            $subjects = $expanded;
        }
    }

    $scheduled = array_values(array_filter($subjects, fn($s) => !empty($s['day']) && !empty($s['time_start'])));

    return ['enrollment' => $enrollment, 'scheduled' => $scheduled];
}

/**
 * Sorts $scheduled (from get_student_schedule) by how soon each meeting
 * occurs from right now, wrapping Sat -> Mon. Each row gains 'start_epoch_ms'
 * / 'end_epoch_ms' (for JS countdowns) and 'is_live' (class in progress now).
 */
function order_schedule_by_next_occurrence(array $scheduled, ?DateTimeImmutable $now = null): array
{
    $now = $now ?? new DateTimeImmutable('now');
    $days = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'];
    $todayNum = (int)$now->format('N'); // 1 (Mon) .. 7 (Sun)
    $nowMinutes = ((int)$now->format('G')) * 60 + (int)$now->format('i');

    foreach ($scheduled as &$s) {
        $dayNum = array_search($s['day'], $days, true);
        if ($dayNum === false) { $s['sort_key'] = PHP_INT_MAX; continue; }
        $dayNum++; // 1-based to match date('N')

        $startMin = (int)substr($s['time_start'], 0, 2) * 60 + (int)substr($s['time_start'], 3, 2);
        $endMin   = (int)substr($s['time_end'], 0, 2) * 60 + (int)substr($s['time_end'], 3, 2);

        $diffDays = ($dayNum - $todayNum + 7) % 7;
        if ($diffDays === 0 && $endMin <= $nowMinutes) $diffDays = 7; // already finished today, roll to next week

        $s['is_live']    = $diffDays === 0 && $nowMinutes >= $startMin && $nowMinutes < $endMin;
        $s['sort_key']   = $diffDays * 1440 + $startMin;

        $classDate = $now->modify("+{$diffDays} days");
        $s['start_epoch_ms'] = (int)$classDate->setTime((int)($startMin / 60), $startMin % 60)->getTimestamp() * 1000;
        $s['end_epoch_ms']   = (int)$classDate->setTime((int)($endMin / 60), $endMin % 60)->getTimestamp() * 1000;
    }
    unset($s);

    usort($scheduled, fn($a, $b) => $a['sort_key'] <=> $b['sort_key']);
    return $scheduled;
}
