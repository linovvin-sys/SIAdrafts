<?php
session_start();
require_once '../db.php';

if (empty($_SESSION['user_id'])) {
    http_response_code(401);
    echo 'Unauthorized.';
    exit;
}

$db   = new Database();
$conn = $db->connect();

$byCourse = [];
$grand    = ['y1' => 0, 'y2' => 0, 'y3' => 0, 'y4' => 0, 'total' => 0];

$result = $conn->query("
    SELECT
        COALESCE(c.course_name, 'Unassigned') AS course_name,
        e.year_level,
        COUNT(*) AS cnt
    FROM enrollment e
    LEFT JOIN section sec ON sec.section_id = e.section_id
    LEFT JOIN course c    ON c.course_id    = sec.course_id
    GROUP BY c.course_id, c.course_name, e.year_level
    ORDER BY c.course_name
");

if ($result) {
    while ($row = $result->fetch_assoc()) {
        $name = $row['course_name'];
        $yl   = (int)$row['year_level'];
        $cnt  = (int)$row['cnt'];
        if (!isset($byCourse[$name])) {
            $byCourse[$name] = ['y1' => 0, 'y2' => 0, 'y3' => 0, 'y4' => 0, 'total' => 0];
        }
        $col = 'y' . min(max($yl, 1), 4);
        $byCourse[$name][$col]    += $cnt;
        $byCourse[$name]['total'] += $cnt;
        $grand[$col]               += $cnt;
        $grand['total']            += $cnt;
    }
}

header('Content-Type: text/csv');
header('Content-Disposition: attachment; filename="enrolees_by_course_' . date('Y-m-d') . '.csv"');

$out = fopen('php://output', 'w');
fputcsv($out, ['Course', '1st Year', '2nd Year', '3rd Year', '4th Year', 'Total']);
foreach ($byCourse as $name => $c) {
    fputcsv($out, [$name, $c['y1'], $c['y2'], $c['y3'], $c['y4'], $c['total']]);
}
fputcsv($out, ['Grand Total', $grand['y1'], $grand['y2'], $grand['y3'], $grand['y4'], $grand['total']]);

fclose($out);
$db->close();