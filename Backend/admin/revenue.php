<?php

require_once __DIR__ . '/../db.php';

$db   = new Database();
$conn = $db->connect();

/* ==========================
   Top Stat Cards
   (amount_due = expected total, downpayment = collected,
    balance = amount_due - downpayment, generated column)
========================== */

$revenue = ['total' => 0, 'collected' => 0, 'outstanding' => 0];

$result = $conn->query("
    SELECT
        COALESCE(SUM(amount_due), 0)   AS total,
        COALESCE(SUM(downpayment), 0)  AS collected,
        COALESCE(SUM(balance), 0)      AS outstanding
    FROM payment
");

if ($result && ($row = $result->fetch_assoc())) {
    $revenue['total']       = (float)$row['total'];
    $revenue['collected']   = (float)$row['collected'];
    $revenue['outstanding'] = (float)$row['outstanding'];
}

/* ==========================
   Revenue by Course
   payment -> enrollment -> section -> course
   (section_id can be NULL on an enrollment that hasn't been
   assigned a section yet, so these are grouped as "Unassigned")
========================== */

$revenueByCourse = [];

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

if ($result) {
    while ($row = $result->fetch_assoc()) {
        $enrolled = (int)$row['enrolled'];
        $row['enrolled']       = $enrolled;
        $row['total_expected'] = (float)$row['total_expected'];
        $row['collected']      = (float)$row['collected'];
        $row['balance']        = (float)$row['balance'];
        $row['fee_per_student'] = $enrolled > 0
            ? $row['total_expected'] / $enrolled
            : 0;
        $revenueByCourse[] = $row;
    }
}

$db->close();