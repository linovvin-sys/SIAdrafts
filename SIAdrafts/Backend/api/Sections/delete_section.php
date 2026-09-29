<?php
session_start();
require_once '../../db.php';
require_once '../../require_role.php';
require_once '../../csrf.php';
header('Content-Type: application/json');

if (empty($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized.']);
    exit;
}

// Only the Head Registrar can remove a section.
require_registrar_tier(REGISTRAR_TIER_HEAD, true);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed.']);
    exit;
}

csrf_verify();

$db   = new Database();
$conn = $db->connect();

$raw        = file_get_contents('php://input');
$data       = json_decode($raw, true) ?? $_POST;
$section_id = (int)($data['section_id'] ?? 0);

if (!$section_id) {
    echo json_encode(['error' => 'No section specified.']);
    exit;
}

try {
    // Guard: don't allow deleting a section that still has enrolled students,
    // schedules, or enrollment records tied to it. `enrollment.section_id`
    // has its own FK to `section` alongside `student`/`schedule` -- missing
    // it here previously let a section with enrollment rows (but no rows
    // in student/schedule) reach the DELETE below and fail with a raw
    // foreign-key error instead of a clear reason.
    $check = $conn->prepare(
        "SELECT
            (SELECT COUNT(*) FROM student    WHERE section_id = ?) AS student_cnt,
            (SELECT COUNT(*) FROM schedule   WHERE section_id = ?) AS schedule_cnt,
            (SELECT COUNT(*) FROM enrollment WHERE section_id = ?) AS enrollment_cnt"
    );
    $check->bind_param('iii', $section_id, $section_id, $section_id);
    $check->execute();
    $row = $check->get_result()->fetch_assoc();
    $check->close();

    if (($row['student_cnt'] ?? 0) > 0) {
        echo json_encode(['error' => 'Cannot remove this section — students are still enrolled in it.']);
        exit;
    }
    if (($row['schedule_cnt'] ?? 0) > 0) {
        echo json_encode(['error' => 'Cannot remove this section — it still has class schedules. Remove those first.']);
        exit;
    }
    if (($row['enrollment_cnt'] ?? 0) > 0) {
        echo json_encode(['error' => 'Cannot remove this section — it still has enrollment records tied to it.']);
        exit;
    }

    $stmt = $conn->prepare("DELETE FROM section WHERE section_id = ?");
    $stmt->bind_param('i', $section_id);
    $stmt->execute();

    $deleted = $stmt->affected_rows;
    $stmt->close();
    $db->close();

    echo json_encode(['success' => true, 'deleted' => $deleted]);
} catch (mysqli_sql_exception $e) {
    http_response_code(500);
    error_log('delete_section.php: ' . $e->getMessage());
    echo json_encode(['error' => 'A database error occurred. Please try again.']);
    exit;
}