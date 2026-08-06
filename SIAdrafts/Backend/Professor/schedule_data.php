<?php
/**
 * Shared schedule lookup for the Professor portal — used by the dashboard's
 * "next class" card, the full weekly grid, and My Classes, so the
 * subject/section/room join lives in exactly one place.
 */

function get_professor_schedule(mysqli $conn, int $professorId, string $schoolYear, int $semester): array
{
    $stmt = $conn->prepare("
        SELECT s.schedule_id, s.section_id, s.day, s.time_start, s.time_end, s.school_year, s.semester,
               sub.subject_code, sub.subject_name, sec.section_name, r.room_name
        FROM schedule s
        JOIN subject sub ON sub.subject_id = s.subject_id
        JOIN section sec ON sec.section_id = s.section_id
        JOIN room r       ON r.room_id = s.room_id
        WHERE s.professor_id = ? AND s.is_active = 1 AND s.status = 'Approved'
          AND s.school_year = ? AND s.semester = ?
        ORDER BY FIELD(s.day,'Monday','Tuesday','Wednesday','Thursday','Friday','Saturday'), s.time_start
    ");
    $stmt->bind_param('isi', $professorId, $schoolYear, $semester);
    $stmt->execute();
    $rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
    return $rows;
}

function get_professor_terms(mysqli $conn, int $professorId): array
{
    $stmt = $conn->prepare("
        SELECT DISTINCT school_year, semester
        FROM schedule
        WHERE professor_id = ?
        ORDER BY school_year DESC, semester DESC
    ");
    $stmt->bind_param('i', $professorId);
    $stmt->execute();
    $rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
    return $rows;
}

function get_professor_pending(mysqli $conn, int $professorId): array
{
    $stmt = $conn->prepare("
        SELECT sub.subject_code, sub.subject_name, sec.section_name, s.day, s.time_start, s.time_end
        FROM schedule s
        JOIN subject sub ON sub.subject_id = s.subject_id
        JOIN section sec ON sec.section_id = s.section_id
        WHERE s.professor_id = ? AND s.status = 'Pending'
        ORDER BY s.day, s.time_start
    ");
    $stmt->bind_param('i', $professorId);
    $stmt->execute();
    $rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
    return $rows;
}

/**
 * Sorts a get_professor_schedule() result by how soon each meeting occurs
 * from right now, wrapping Sat -> Mon. Each row gains 'start_epoch_ms' /
 * 'end_epoch_ms' (for JS countdowns) and 'is_live' (class in progress now).
 * Mirrors Backend/Student/schedule_data.php's order_schedule_by_next_occurrence
 * — duplicated rather than cross-required, to keep each portal's Backend/<Role>/
 * folder self-contained, matching the same call made for the Js/<Role>/ split.
 */
function order_professor_schedule_by_next_occurrence(array $scheduled, ?DateTimeImmutable $now = null): array
{
    $now = $now ?? new DateTimeImmutable('now');
    $days = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'];
    $todayNum = (int)$now->format('N');
    $nowMinutes = ((int)$now->format('G')) * 60 + (int)$now->format('i');

    foreach ($scheduled as &$s) {
        $dayNum = array_search($s['day'], $days, true);
        if ($dayNum === false) { $s['sort_key'] = PHP_INT_MAX; continue; }
        $dayNum++;

        $startMin = (int)substr($s['time_start'], 0, 2) * 60 + (int)substr($s['time_start'], 3, 2);
        $endMin   = (int)substr($s['time_end'], 0, 2) * 60 + (int)substr($s['time_end'], 3, 2);

        $diffDays = ($dayNum - $todayNum + 7) % 7;
        if ($diffDays === 0 && $endMin <= $nowMinutes) $diffDays = 7;

        $s['is_live']  = $diffDays === 0 && $nowMinutes >= $startMin && $nowMinutes < $endMin;
        $s['sort_key'] = $diffDays * 1440 + $startMin;

        $classDate = $now->modify("+{$diffDays} days");
        $s['start_epoch_ms'] = (int)$classDate->setTime((int)($startMin / 60), $startMin % 60)->getTimestamp() * 1000;
        $s['end_epoch_ms']   = (int)$classDate->setTime((int)($endMin / 60), $endMin % 60)->getTimestamp() * 1000;
    }
    unset($s);

    usort($scheduled, fn($a, $b) => $a['sort_key'] <=> $b['sort_key']);
    return $scheduled;
}
