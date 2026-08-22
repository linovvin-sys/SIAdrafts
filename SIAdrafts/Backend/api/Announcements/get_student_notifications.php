<?php
/**
 * Read-only feed for the notification bell. Reuses the same announcement
 * data the dashboard's Announcements card shows -- this is deliberately
 * not a separate notification system with its own table; "new
 * announcement from a professor" is the only kind of push-worthy event
 * this app currently has. Polled periodically by the client for the
 * "dynamic" (live-updating) badge.
 */

require_once __DIR__ . '/../../require_student.php';
require_student(true);
require_once __DIR__ . '/../../db.php';
require_once __DIR__ . '/../../Student/announcement_data.php';
header('Content-Type: application/json');

$db   = new Database();
$conn = $db->connect();

$notifications = get_student_announcements($conn, (int)$_SESSION['student_id'], 20);
$db->close();

// Same tint-per-subject cycling the dashboard uses, so a subject's color
// in the notification bell matches its color everywhere else -- computed
// here (not stored) since it's just a display cycle over whatever
// subjects appear, in first-seen order.
$tintClasses  = ['tint-1', 'tint-2', 'tint-3', 'tint-4'];
$subjectTints = [];
foreach ($notifications as &$n) {
    if (!isset($subjectTints[$n['subject_code']])) {
        $subjectTints[$n['subject_code']] = $tintClasses[count($subjectTints) % count($tintClasses)];
    }
    $n['tint'] = $subjectTints[$n['subject_code']];
}
unset($n);

echo json_encode(['notifications' => $notifications]);
