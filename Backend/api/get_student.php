<?php
header('Content-Type: application/json');
session_start();
require_once '../db.php';

$db   = new Database();
$conn = $db->connect();



if (empty($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized.']);
    exit;
}

$q    = trim($_GET['q'] ?? '');
$mode = $_GET['mode'] ?? 'id';

if (strlen($q) < 1) {
    echo json_encode([]);
    exit;
}

if ($mode === 'id') {
    $stmt = $conn->prepare(
        "SELECT a.applicant_id, a.student_id, a.first_name, a.last_name, a.middle_name,
                st.type_name, '—' AS section_name
         FROM applicants a
         JOIN student_type st ON a.applicant_type_id = st.type_id
         WHERE a.student_id = ?
         LIMIT 1"
    );
    $stmt->bind_param('s', $q);
    $stmt->execute();
} else {
    $like = '%' . $q . '%';
    $stmt = $conn->prepare(
        "SELECT a.applicant_id, a.student_id, a.first_name, a.last_name, a.middle_name,
                st.type_name, '—' AS section_name
         FROM applicants a
         JOIN student_type st ON a.applicant_type_id = st.type_id
         WHERE a.first_name LIKE ? OR a.last_name LIKE ?
         ORDER BY a.last_name, a.first_name
         LIMIT 10"
    );
    $stmt->bind_param('ss', $like, $like);
    $stmt->execute();
}

$rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

foreach ($rows as &$r) {
    $r['display_id'] = $r['student_id']; // already in 2026-XXXXX format
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