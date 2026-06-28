<?php
session_start();
require_once '../includes/db.php';

header('Content-Type: application/json');

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
    $sid = (int)str_replace('-', '', $q);
    if ($sid <= 0) {
        echo json_encode([]);
        exit;
    }
    $stmt = $pdo->prepare(
        "SELECT s.student_id, s.student_name, s.first_name, s.last_name, s.middle_name,
                st.type_name, sec.section_name
         FROM student s
         JOIN student_type st ON s.type_id = st.type_id
         JOIN section sec ON s.section_id = sec.section_id
         WHERE s.student_id = ?
         LIMIT 1"
    );
    $stmt->execute([$sid]);
} else {
    $like = '%' . $q . '%';
    $stmt = $pdo->prepare(
        "SELECT s.student_id, s.student_name, s.first_name, s.last_name, s.middle_name,
                st.type_name, sec.section_name
         FROM student s
         JOIN student_type st ON s.type_id = st.type_id
         JOIN section sec ON s.section_id = sec.section_id
         WHERE s.first_name LIKE ? OR s.last_name LIKE ? OR s.student_name LIKE ?
         ORDER BY s.last_name, s.first_name
         LIMIT 10"
    );
    $stmt->execute([$like, $like, $like]);
}

$rows = $stmt->fetchAll();

foreach ($rows as &$r) {
    $id = (string)$r['student_id'];
    $r['display_id'] = strlen($id) >= 5
        ? substr($id, 0, 4) . '-' . substr($id, 4)
        : $id;
    $ln = $r['last_name'] ?? '';
    $fn = $r['first_name'] ?? '';
    $mn = $r['middle_name'] ?? '';
    $r['full_name'] = ($ln && $fn)
        ? $ln . ', ' . $fn . ($mn ? ' ' . $mn : '')
        : ($r['student_name'] ?? '');
}
unset($r);

echo json_encode($rows);
