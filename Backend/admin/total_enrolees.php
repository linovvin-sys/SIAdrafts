<?php

require_once __DIR__ . '/../db.php';

$db   = new Database();
$conn = $db->connect();

/* ==========================
   Top Stat Cards
   Classification rule (mutually exclusive, sums to Total):
     1. type_id -> student_type 'Irregular'  => Irregular
     2. else year_level == 1                 => New Students
     3. else                                  => Continuing
   Matches dashboard.php's convention of counting every row in
   `enrollment` (all school years/semesters), not just the active term.
========================== */

$stats = ['total' => 0, 'new' => 0, 'continuing' => 0, 'irregular' => 0];

$result = $conn->query("
    SELECT e.year_level, st.type_name
    FROM enrollment e
    JOIN student_type st ON st.type_id = e.type_id
");

if ($result) {
    while ($row = $result->fetch_assoc()) {
        $stats['total']++;
        if (strcasecmp($row['type_name'], 'Irregular') === 0) {
            $stats['irregular']++;
        } elseif ((int)$row['year_level'] === 1) {
            $stats['new']++;
        } else {
            $stats['continuing']++;
        }
    }
}

/* ==========================
   Enrolees by Course & Year Level
   enrollment -> section -> course (section_id can be NULL if a
   student hasn't been assigned a section yet -> "Unassigned")
========================== */

$byCourse = []; // course_name => ['y1'=>, 'y2'=>, 'y3'=>, 'y4'=>, 'total'=>]
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

        // year_level is a tinyint with no hard cap in the schema; any
        // value outside 1-4 gets folded into year 4 so it isn't silently
        // dropped from the table.
        $col = 'y' . min(max($yl, 1), 4);

        $byCourse[$name][$col]   += $cnt;
        $byCourse[$name]['total'] += $cnt;
        $grand[$col]              += $cnt;
        $grand['total']           += $cnt;
    }
}

$db->close();