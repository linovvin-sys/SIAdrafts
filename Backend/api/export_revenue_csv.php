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

$result = $conn->query("
    SELECT
        COALESCE(c.course_name, 'Unassigned') AS course_name,
        COUNT(DISTINCT e.enrollment_id)       AS enrolled,
        COALESCE(SUM(p.amount_due), 0)        AS total_expected,
        COALESCE(SUM(p.downpayment), 0)       AS collected,
        COALESCE(SUM(p.balance), 0)           AS balance
    FROM payment p
    JOIN enrollment e   ON e.enrollment_id = p.enrollment_id
    LEFT JOIN section sec ON sec.section_id = e.section_id
    LEFT JOIN course c    ON c.course_id    = sec.course_id
    GROUP BY c.course_id, c.course_name
    ORDER BY total_expected DESC
");

header('Content-Type: text/csv');
header('Content-Disposition: attachment; filename="revenue_by_course_' . date('Y-m-d') . '.csv"');

$out = fopen('php://output', 'w');
fputcsv($out, ['Course', 'Enrolled', 'Fee / Student', 'Total Expected', 'Collected', 'Balance']);

if ($result) {
    while ($row = $result->fetch_assoc()) {
        $enrolled = (int)$row['enrolled'];
        $feePerStudent = $enrolled > 0 ? $row['total_expected'] / $enrolled : 0;
        fputcsv($out, [
            $row['course_name'],
            $enrolled,
            number_format($feePerStudent, 2),
            number_format($row['total_expected'], 2),
            number_format($row['collected'], 2),
            number_format($row['balance'], 2),
        ]);
    }
}

fclose($out);
$db->close();