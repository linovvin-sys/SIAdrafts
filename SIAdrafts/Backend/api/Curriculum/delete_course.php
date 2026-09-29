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

// Only the Head Registrar can remove a course.
require_registrar_tier(REGISTRAR_TIER_HEAD, true);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed.']);
    exit;
}

csrf_verify();

$db   = new Database();
$conn = $db->connect();

$raw       = file_get_contents('php://input');
$data      = json_decode($raw, true) ?? $_POST;
$course_id = (int)($data['course_id'] ?? 0);

if (!$course_id) {
    echo json_encode(['error' => 'No course specified.']);
    exit;
}

try {
    // Guard: don't allow deleting a course that still has sections or
    // subjects it directly owns -- either would be orphaned by the delete.
    // `subject_course` is different: it's a pure many-to-many "this subject
    // is ALSO offered under this course" cross-listing table (populated by
    // the curriculum CSV import, typically for shared Gen-Ed subjects like
    // UTS/NSTP/PATHFIT that legitimately belong to every program). There's
    // no UI to manage those rows directly, and the subject itself survives
    // fine under its other course(s) -- so a course's own cross-listing
    // rows are cleaned up automatically below rather than blocking the
    // whole delete on something the user has no way to resolve.
    $check = $conn->prepare("
        SELECT
            (SELECT COUNT(*) FROM section WHERE course_id = ?) AS section_cnt,
            (SELECT COUNT(*) FROM subject WHERE course_id = ?) AS subject_cnt
    ");
    $check->bind_param('ii', $course_id, $course_id);
    $check->execute();
    $counts = $check->get_result()->fetch_assoc();
    $check->close();

    if (($counts['section_cnt'] ?? 0) > 0) {
        echo json_encode(['error' => 'Cannot remove this course while it still has sections. Remove the sections first.']);
        exit;
    }
    if (($counts['subject_cnt'] ?? 0) > 0) {
        echo json_encode(['error' => 'Cannot remove this course while it still has subjects assigned to it. Reassign or remove those subjects first.']);
        exit;
    }

    $conn->begin_transaction();

    $crossListStmt = $conn->prepare("DELETE FROM subject_course WHERE course_id = ?");
    $crossListStmt->bind_param('i', $course_id);
    $crossListStmt->execute();
    $crossListStmt->close();

    $stmt = $conn->prepare("DELETE FROM course WHERE course_id = ?");
    $stmt->bind_param('i', $course_id);
    $stmt->execute();

    $deleted = $stmt->affected_rows;
    $stmt->close();
    $conn->commit();
    $db->close();

    echo json_encode(['success' => true, 'deleted' => $deleted]);
} catch (mysqli_sql_exception $e) {
    $conn->rollback();
    http_response_code(500);
    error_log('delete_course.php: ' . $e->getMessage());
    echo json_encode(['error' => 'A database error occurred. Please try again.']);
    exit;
}