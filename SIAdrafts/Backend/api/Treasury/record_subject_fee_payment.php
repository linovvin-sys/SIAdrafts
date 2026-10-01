<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../../../Backend/session_bootstrap.php';
app_session_start();
require_once '../../db.php';
require_once '../../roles.php';
require_once '../../require_role.php';
require_once '../../csrf.php';

$db   = new Database();
$conn = $db->connect();

if (empty($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'errors' => ['Unauthorized.']]);
    exit;
}

// Admin is deliberately excluded — read-only monitoring only (see treasury.php).
require_role([ROLE_TREASURY], true);

csrf_verify();

$fee_id = $_POST['fee_id'] ?? '';

if (!ctype_digit((string)$fee_id)) {
    echo json_encode(['success' => false, 'errors' => ['Invalid fee record.']]);
    exit;
}

$fee_id      = (int)$fee_id;
$received_by = (int)$_SESSION['user_id'];

$conn->begin_transaction();

try {
    $stmt = $conn->prepare(
        "SELECT fee_id, enrollment_subject_id, action, amount, status
         FROM subject_change_fee
         WHERE fee_id = ?
         FOR UPDATE"
    );
    $stmt->bind_param('i', $fee_id);
    $stmt->execute();
    $fee = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$fee) {
        throw new Exception('Fee record not found.');
    }
    if ($fee['status'] !== 'Pending') {
        throw new Exception('This fee has already been ' . strtolower($fee['status']) . '.');
    }

    $newSubjectStatus = ($fee['action'] === 'Add') ? 'Enrolled' : 'Dropped';

    $updFee = $conn->prepare(
        "UPDATE subject_change_fee
         SET status = 'Paid', paid_at = NOW(), received_by = ?
         WHERE fee_id = ?"
    );
    $updFee->bind_param('ii', $received_by, $fee_id);
    $updFee->execute();
    $updFee->close();

    $updSubject = $conn->prepare(
        "UPDATE enrollment_subject SET status = ? WHERE enrollment_subject_id = ?"
    );
    $updSubject->bind_param('si', $newSubjectStatus, $fee['enrollment_subject_id']);
    $updSubject->execute();
    $updSubject->close();

    $conn->commit();

    // OR/receipt number for this add/drop fee — its own auto-increment ID,
    // zero-padded, prefixed to keep it distinct from tuition OR numbers.
    $orNumber = sprintf('ADF-%06d', $fee_id);

    echo json_encode([
        'success'         => true,
        'subject_status'  => $newSubjectStatus,
        'or_number'       => $orNumber,
    ]);
} catch (mysqli_sql_exception $e) {
    $conn->rollback();
    error_log('record_subject_fee_payment.php: ' . $e->getMessage());
    echo json_encode(['success' => false, 'errors' => ['A database error occurred. Please try again.']]);
} catch (Exception $e) {
    $conn->rollback();
    echo json_encode(['success' => false, 'errors' => [$e->getMessage()]]);
}

$conn->close();
