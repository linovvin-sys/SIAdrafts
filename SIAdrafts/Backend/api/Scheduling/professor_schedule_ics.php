<?php
/**
 * Downloads the logged-in professor's weekly teaching schedule as a .ics
 * file, mirroring Backend/api/Scheduling/student_schedule_ics.php. Each class becomes
 * a weekly recurring event (RRULE) starting from its next real occurrence.
 *
 * The schema has no semester end date, so RRULE is capped at COUNT=16 (a
 * typical semester length) rather than an exact cutoff.
 */

require_once __DIR__ . '/../../require_professor.php';
require_professor();
require_once __DIR__ . '/../../db.php';
require_once __DIR__ . '/../../settings.php';
require_once __DIR__ . '/../../Professor/schedule_data.php';

$db   = new Database();
$conn = $db->connect();

$professorId = (int)$_SESSION['professor_id'];
$schoolYear  = $_GET['school_year'] ?? (get_setting('current_school_year') ?? '');
$semester    = (int)($_GET['semester'] ?? (get_setting('current_semester') ?? 0));

$scheduled = order_professor_schedule_by_next_occurrence(get_professor_schedule($conn, $professorId, $schoolYear, $semester));
$db->close();

function pp_ics_escape(string $text): string
{
    return str_replace(['\\', ',', ';', "\n"], ['\\\\', '\\,', '\\;', '\\n'], $text);
}

$byDayMap = ['Monday' => 'MO', 'Tuesday' => 'TU', 'Wednesday' => 'WE', 'Thursday' => 'TH', 'Friday' => 'FR', 'Saturday' => 'SA'];

header('Content-Type: text/calendar; charset=utf-8');
header('Content-Disposition: attachment; filename="my-teaching-schedule.ics"');

$lines = ['BEGIN:VCALENDAR', 'VERSION:2.0', 'PRODID:-//EduSchool Professor Portal//Schedule Export//EN', 'CALSCALE:GREGORIAN'];

foreach ($scheduled as $s) {
    if (!isset($byDayMap[$s['day']])) continue;

    $start = date('Ymd\THis', (int)($s['start_epoch_ms'] / 1000));
    $end   = date('Ymd\THis', (int)($s['end_epoch_ms'] / 1000));

    $lines[] = 'BEGIN:VEVENT';
    $lines[] = 'UID:' . md5($professorId . $s['subject_code'] . $s['day'] . $s['time_start']) . '@professor-portal';
    $lines[] = 'DTSTAMP:' . gmdate('Ymd\THis\Z');
    $lines[] = 'DTSTART:' . $start;
    $lines[] = 'DTEND:' . $end;
    $lines[] = 'RRULE:FREQ=WEEKLY;COUNT=16;BYDAY=' . $byDayMap[$s['day']];
    $lines[] = 'SUMMARY:' . pp_ics_escape($s['subject_code'] . ' - ' . $s['subject_name']);
    $lines[] = 'DESCRIPTION:' . pp_ics_escape('Section: ' . $s['section_name']);
    if (!empty($s['room_name'])) $lines[] = 'LOCATION:' . pp_ics_escape($s['room_name']);

    $lines[] = 'END:VEVENT';
}

$lines[] = 'END:VCALENDAR';

echo implode("\r\n", $lines) . "\r\n";
