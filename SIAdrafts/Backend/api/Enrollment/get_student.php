<?php
header('Content-Type: application/json');
session_start();
require_once '../../db.php';
require_once '../../roles.php';
require_once '../../require_role.php';

$db   = new Database();
$conn = $db->connect();

if (empty($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized.']);
    exit;
}

require_role([ROLE_STAFF, ROLE_ADMIN], true);

$q    = trim($_GET['q'] ?? '');
$mode = $_GET['mode'] ?? 'id';

if (strlen($q) < 1) {
    echo json_encode([]);
    exit;
}

// Enrollment only ever operates against applicants who have already cleared
// walk-in verification. A reference ID that's still pending_verification
// won't show up here — that applicant needs to go through admission.php first.
// display_code is whichever ID the person currently has: their student number
// if they've enrolled before (student.student_no), otherwise their reference ID.
if ($mode === 'id') {
    $stmt = $conn->prepare(
        "SELECT a.applicant_id, a.first_name, a.last_name, a.middle_name,
                st.type_name,
                COALESCE(sec.section_name, '—') AS section_name,
                COALESCE(s.student_no, a.reference_id) AS display_code
         FROM applicants a
         JOIN student_type st ON a.applicant_type_id = st.type_id
         LEFT JOIN student s ON s.applicant_id = a.applicant_id
         LEFT JOIN section sec ON sec.section_id = s.section_id
         WHERE a.admission_status = 'verified'
           AND (a.reference_id = ? OR s.student_no = ?)
         LIMIT 1"
    );
    $stmt->bind_param('ss', $q, $q);
    $stmt->execute();
} else {
    $like = '%' . $q . '%';
    $stmt = $conn->prepare(
        "SELECT a.applicant_id, a.first_name, a.last_name, a.middle_name,
                st.type_name,
                COALESCE(sec.section_name, '—') AS section_name,
                COALESCE(s.student_no, a.reference_id) AS display_code
         FROM applicants a
         JOIN student_type st ON a.applicant_type_id = st.type_id
         LEFT JOIN student s ON s.applicant_id = a.applicant_id
         LEFT JOIN section sec ON sec.section_id = s.section_id
         WHERE a.admission_status = 'verified'
           AND (a.first_name LIKE ? OR a.last_name LIKE ?)
         ORDER BY a.last_name, a.first_name
         LIMIT 10"
    );
    $stmt->bind_param('ss', $like, $like);
    $stmt->execute();
}

$rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

foreach ($rows as &$r) {
    // Field kept as "student_id" for frontend/URL compatibility — it's the
    // display code (student number if already enrolled once, otherwise the
    // reference ID), not a raw DB column.
    $r['student_id'] = $r['display_code'];
    $ln = $r['last_name']  ?? '';
    $fn = $r['first_name'] ?? '';
    $mn = $r['middle_name'] ?? '';
    $r['full_name'] = ($ln && $fn)
        ? $ln . ', ' . $fn . ($mn ? ' ' . $mn : '')
        : '';
}
unset($r);

$conn->close();

echo json_encode($rows);