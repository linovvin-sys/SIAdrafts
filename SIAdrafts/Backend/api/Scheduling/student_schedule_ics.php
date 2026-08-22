<?php
/**
 * Downloads the logged-in student's weekly schedule as a .ics file so it can
 * be imported into a phone/desktop calendar. Each class becomes a weekly
 * recurring event (RRULE) starting from its next real occurrence.
 *
 * The schema has no semester end date, so RRULE is capped at COUNT=16 (a
 * typical semester length) rather than an exact cutoff -- students can
 * delete the series once the term ends.
 */

require_once __DIR__ . '/../../require_student.php';
require_student();
require_once __DIR__ . '/../../db.php';
require_once __DIR__ . '/../../Student/schedule_data.php';

$db   = new Database();
$conn = $db->connect();

$applicantId = (int)$_SESSION['student_id'];
$scheduled = order_schedule_by_next_occurrence(get_student_schedule($conn, $applicantId)['scheduled']);
$db->close();

function sp_ics_escape(string $text): string
{
    return str_replace(['\\', ',', ';', "\n"], ['\\\\', '\\,', '\\;', '\\n'], $text);
}

$byDayMap = ['Monday' => 'MO', 'Tuesday' => 'TU', 'Wednesday' => 'WE', 'Thursday' => 'TH', 'Friday' => 'FR', 'Saturday' => 'SA'];

header('Content-Type: text/calendar; charset=utf-8');
header('Content-Disposition: attachment; filename="my-schedule.ics"');

$lines = ['BEGIN:VCALENDAR', 'VERSION:2.0', 'PRODID:-//EduSchool Student Portal//Schedule Export//EN', 'CALSCALE:GREGORIAN'];

foreach ($scheduled as $s) {
    if (!isset($byDayMap[$s['day']])) continue;

    // Round-trip through the server's local timezone (not gmdate) so the
    // wall-clock time in the export matches the wall-clock time in the DB.
    $start = date('Ymd\THis', (int)($s['start_epoch_ms'] / 1000));
    $end   = date('Ymd\THis', (int)($s['end_epoch_ms'] / 1000));

    $lines[] = 'BEGIN:VEVENT';
    $lines[] = 'UID:' . md5($applicantId . $s['subject_code'] . $s['day'] . $s['time_start']) . '@student-portal';
    $lines[] = 'DTSTAMP:' . gmdate('Ymd\THis\Z');
    $lines[] = 'DTSTART:' . $start;
    $lines[] = 'DTEND:' . $end;
    $lines[] = 'RRULE:FREQ=WEEKLY;COUNT=16;BYDAY=' . $byDayMap[$s['day']];
    $lines[] = 'SUMMARY:' . sp_ics_escape($s['subject_code'] . ' - ' . $s['subject_name']);

    $professor = trim($s['professor_name'] ?? '');
    if ($professor !== '') $lines[] = 'DESCRIPTION:' . sp_ics_escape('Professor: ' . $professor);
    if (!empty($s['room_name'])) $lines[] = 'LOCATION:' . sp_ics_escape($s['room_name']);

    $lines[] = 'END:VEVENT';
}

$lines[] = 'END:VCALENDAR';

echo implode("\r\n", $lines) . "\r\n";
