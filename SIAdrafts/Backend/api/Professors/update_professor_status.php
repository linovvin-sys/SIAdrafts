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

require_min_role(role_level(ROLE_REGISTRAR_STAFF), true);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed.']);
    exit;
}

csrf_verify();

$db   = new Database();
$conn = $db->connect();

$raw  = file_get_contents('php://input');
$data = json_decode($raw, true) ?? $_POST;

$professor_id = (int)($data['professor_id'] ?? 0);
$action       = $data['action'] ?? '';

if (!$professor_id || !in_array($action, ['activate', 'deactivate'], true)) {
    echo json_encode(['error' => 'Invalid request.']);
    exit;
}

$status_name = $action === 'activate' ? 'Active' : 'Inactive';

try {
    // Looked up by name rather than a hardcoded id -- add_user.php/edit_user.php
    // already do this for the same `statuses` table; this endpoint assumed
    // id 1 = Active / 2 = Inactive, which silently sets the wrong status if
    // those ids are ever reordered or reseeded, with no error to notice it by.
    $statusStmt = $conn->prepare("SELECT status_id FROM statuses WHERE status_name = ? LIMIT 1");
    $statusStmt->bind_param('s', $status_name);
    $statusStmt->execute();
    $statusRow = $statusStmt->get_result()->fetch_assoc();
    $statusStmt->close();

    if (!$statusRow) {
        http_response_code(500);
        error_log('update_professor_status.php: missing statuses row for "' . $status_name . '"');
        echo json_encode(['error' => 'A database error occurred. Please try again.']);
        exit;
    }
    $status_id = $statusRow['status_id'];

    $stmt = $conn->prepare("UPDATE professor SET status_id = ? WHERE professor_id = ?");
    $stmt->bind_param('ii', $status_id, $professor_id);
    $stmt->execute();

    $affected = $stmt->affected_rows;
    $stmt->close();
    $db->close();

    if ($affected === 0) {
        echo json_encode(['error' => 'Professor not found.']);
        exit;
    }

    echo json_encode(['success' => true, 'message' => 'Status updated.']);
} catch (mysqli_sql_exception $e) {
    http_response_code(500);
    error_log('update_professor_status.php: ' . $e->getMessage());
    echo json_encode(['error' => 'A database error occurred. Please try again.']);
    exit;
}
