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

require_registrar_tier(REGISTRAR_TIER_STAFF, true);

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

$enrollment_subject_id = (int)($data['enrollment_subject_id'] ?? 0);

if (!$enrollment_subject_id) {
    echo json_encode(['error' => 'Missing subject reference.']);
    exit;
}

$conn->begin_transaction();

try {
    // Re-fetch under FOR UPDATE inside the transaction instead of trusting an
    // earlier unlocked read -- record_subject_fee_payment.php locks the same
    // row before marking it Paid, so without this lock a Pay and a Cancel
    // racing on the same fee_id could both pass their own "is it Pending?"
    // check and the cancel would silently overwrite a just-paid fee back to
    // Cancelled (then delete/revert the enrollment_subject row underneath a
    // payment that was already collected).
    $feeStmt = $conn->prepare(
        "SELECT fee_id, action FROM subject_change_fee
         WHERE enrollment_subject_id = ? AND status = 'Pending'
         ORDER BY fee_id DESC LIMIT 1 FOR UPDATE"
    );
    $feeStmt->bind_param('i', $enrollment_subject_id);
    $feeStmt->execute();
    $fee = $feeStmt->get_result()->fetch_assoc();
    $feeStmt->close();

    if (!$fee) {
        $conn->rollback();
        echo json_encode(['error' => 'No pending fee found for this subject.']);
        exit;
    }

    $cancelStmt = $conn->prepare("UPDATE subject_change_fee SET status = 'Cancelled' WHERE fee_id = ? AND status = 'Pending'");
    $cancelStmt->bind_param('i', $fee['fee_id']);
    $cancelStmt->execute();
    $cancelledRow = $cancelStmt->affected_rows > 0;
    $cancelStmt->close();

    if (!$cancelledRow) {
        // Lost the race anyway (paid between the SELECT...FOR UPDATE above and
        // this UPDATE) -- bail out rather than touching enrollment_subject.
        $conn->rollback();
        echo json_encode(['error' => 'This request was already processed. Please refresh.']);
        exit;
    }

    if ($fee['action'] === 'Add') {
        // Never actually enrolled — remove the placeholder row entirely.
        // Guarded first: `grade` and `assignment_submission` both carry a
        // real FK back to enrollment_subject_id. In the normal timeline an
        // unpaid "Add" is cancelled before either exists, but if a professor
        // already graded or a student already submitted on this row (e.g. a
        // stale pending fee cleaned up late), the bare DELETE would hit that
        // FK and throw -- caught below, but with no indication of the real
        // cause. Check first and say so plainly instead.
        $depStmt = $conn->prepare(
            "SELECT
                (SELECT COUNT(*) FROM grade WHERE enrollment_subject_id = ?) AS grade_cnt,
                (SELECT COUNT(*) FROM assignment_submission WHERE enrollment_subject_id = ?) AS submission_cnt"
        );
        $depStmt->bind_param('ii', $enrollment_subject_id, $enrollment_subject_id);
        $depStmt->execute();
        $deps = $depStmt->get_result()->fetch_assoc();
        $depStmt->close();

        if (($deps['grade_cnt'] ?? 0) > 0 || ($deps['submission_cnt'] ?? 0) > 0) {
            $conn->rollback();
            echo json_encode(['error' => 'This subject already has grade or submission activity and can\'t be cancelled.']);
            exit;
        }

        $delStmt = $conn->prepare("DELETE FROM enrollment_subject WHERE enrollment_subject_id = ?");
        $delStmt->bind_param('i', $enrollment_subject_id);
        $delStmt->execute();
        $delStmt->close();
    } else {
        // Drop request cancelled — student keeps the subject.
        $revertStmt = $conn->prepare("UPDATE enrollment_subject SET status = 'Enrolled' WHERE enrollment_subject_id = ?");
        $revertStmt->bind_param('i', $enrollment_subject_id);
        $revertStmt->execute();
        $revertStmt->close();
    }

    $conn->commit();
    echo json_encode(['success' => true, 'message' => 'Request cancelled.']);
} catch (mysqli_sql_exception $e) {
    $conn->rollback();
    error_log('cancel_subject_fee.php: ' . $e->getMessage());
    echo json_encode(['error' => 'Could not cancel request. Please try again.']);
}

$db->close();
