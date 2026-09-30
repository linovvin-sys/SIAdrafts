<?php
/**
 * Read-only feed for the notification bell. Merges Announcements, Quizzes,
 * Assignments, and Materials -- originally this only reused the
 * dashboard's Announcements data, so nothing else a professor posted ever
 * pinged the bell. Polled periodically by the client for the "dynamic"
 * (live-updating) badge.
 */

require_once __DIR__ . '/../../require_student.php';
require_student(true);
require_once __DIR__ . '/../../db.php';
require_once __DIR__ . '/../../Student/notification_data.php';
header('Content-Type: application/json');

$db   = new Database();
$conn = $db->connect();

$notifications = get_student_notification_feed($conn, (int)$_SESSION['student_id'], 20);
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
