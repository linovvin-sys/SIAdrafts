<?php
require_once __DIR__ . '/../../session_security.php';
require_once __DIR__ . '/../../../Backend/session_bootstrap.php';
app_session_start();
require_once __DIR__ . '/../../db.php';

$materialId = (int)($_GET['material_id'] ?? 0);
if (!$materialId) {
    http_response_code(400);
    exit('Missing material_id.');
}

$db   = new Database();
$conn = $db->connect();

$stmt = $conn->prepare(
    "SELECT file_path, file_name, file_type, schedule_id, professor_id, visible_from
     FROM class_material WHERE material_id = ? AND type = 'file'"
);
$stmt->bind_param('i', $materialId);
$stmt->execute();
$material = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$material) {
    $conn->close();
    http_response_code(404);
    exit('Material not found.');
}

$isOwningProfessor = !empty($_SESSION['professor_id']) && (int)$_SESSION['professor_id'] === (int)$material['professor_id'];

// A student may view it once currently enrolled in that class -- same
// section-path-or-explicit-schedule_id resolution used throughout the
// Professor roster/grade/assignment queries -- and only once the
// material's own visible_from date (if any) has arrived; the professor
// bypasses that gate to preview drip content early.
$isEnrolledStudent = false;
if (!$isOwningProfessor && !empty($_SESSION['student_id'])) {
    $applicantId = (int)$_SESSION['student_id'];
    $stmt = $conn->prepare("
        SELECT 1
        FROM schedule sc
        JOIN enrollment e ON e.school_year = sc.school_year AND e.semester = sc.semester AND e.applicant_id = ?
        JOIN enrollment_subject es ON es.enrollment_id = e.enrollment_id AND es.subject_id = sc.subject_id AND es.status = 'Enrolled'
        WHERE sc.schedule_id = ? AND e.status = 'Enrolled'
          AND (e.section_id = sc.section_id OR es.schedule_id = sc.schedule_id)
        LIMIT 1
    ");
    $stmt->bind_param('ii', $applicantId, $material['schedule_id']);
    $stmt->execute();
    $isEnrolledStudent = (bool)$stmt->get_result()->fetch_row();
    $stmt->close();

    if ($isEnrolledStudent && !empty($material['visible_from']) && $material['visible_from'] > date('Y-m-d')) {
        $isEnrolledStudent = false;
    }
}

$conn->close();

if (!$isOwningProfessor && !$isEnrolledStudent) {
    http_response_code(403);
    exit('You are not permitted to view this file.');
}

$fullPath = __DIR__ . '/../../uploads/materials/' . basename($material['file_path']);
if (!is_file($fullPath)) {
    http_response_code(404);
    exit('File no longer exists.');
}

header('Content-Type: ' . $material['file_type']);
header('Content-Length: ' . filesize($fullPath));
header('Content-Disposition: inline; filename="' . rawurlencode($material['file_name']) . '"');
header('X-Content-Type-Options: nosniff');
readfile($fullPath);
