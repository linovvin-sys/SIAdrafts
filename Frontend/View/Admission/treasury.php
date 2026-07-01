<?php 
$page_scripts = ['/SIAdrafts/Frontend/Js/Admission/treasury.js'];
require_once '../../../Backend/auth.php';
require_once '../../../Backend/db.php';

$db = new Database();
$conn = $db->connect();
include '../Admission/Include/header.php';

 
// ---- Queue: all payment rows not yet fully paid ----
$queueStmt = $conn->prepare(
    "SELECT p.payment_id, p.amount_due, p.downpayment, p.balance, p.due_date, p.payment_status,
            e.enrollment_id, e.school_year, e.semester,
            a.student_id, a.first_name, a.last_name
     FROM payment p
     JOIN enrollment e ON e.enrollment_id = p.enrollment_id
     JOIN applicants a ON a.applicant_id = e.student_id
     WHERE p.payment_status != 'Fully Paid'
     ORDER BY p.due_date ASC"
);
$queueStmt->execute();
$queue = $queueStmt->get_result()->fetch_all(MYSQLI_ASSOC);
$queueStmt->close();
 
// ---- Queue: enrollments awaiting payment setup (no payment row yet) ----
// As of the fee-schedule automation, save_enrollment.php auto-creates the
// payment row (with amount + due date pulled from fee_schedule) at the
// moment enrollment is finalized. So new enrollments should never land
// here. This queue/panel — and setup_payment.php — are kept only as a
// manual fallback for legacy enrollments created before this change, or
// for rare cases treasury needs to override.
$setupStmt = $conn->prepare(
    "SELECT e.enrollment_id, e.school_year, e.semester, e.created_at,
            a.student_id, a.first_name, a.last_name
     FROM enrollment e
     JOIN applicants a ON a.applicant_id = e.student_id
     LEFT JOIN payment p ON p.enrollment_id = e.enrollment_id
     WHERE p.payment_id IS NULL
     ORDER BY e.created_at ASC"
);
$setupStmt->execute();
$setupQueue = $setupStmt->get_result()->fetch_all(MYSQLI_ASSOC);
$setupStmt->close();

function fmt_id_t($id): string {
    // applicants.student_id is already formatted, e.g. "2026-00001"
    return (string)$id;
}
function student_fullname_t(array $s): string {
    return $s['last_name'] . ', ' . $s['first_name'];
}
?>

<div class="treasury-page">
 
  <div class="treasury-head">
    <h1>Treasury</h1>
    <p>Track who still owes a balance, and record payments as they come in.</p>
  </div>
 
  <div class="treasury-tabs">
    <button class="t-tab active" id="tabQueue">Pending payments</button>
    <button class="t-tab" id="tabSetup">Needs payment setup<?= count($setupQueue) ? ' (' . count($setupQueue) . ')' : '' ?></button>
    <button class="t-tab" id="tabSearch">Search a student</button>
  </div>
 
  <div class="treasury-card">
 
    <!-- ===== QUEUE VIEW ===== -->
    <div id="queuePanel">
      <?php if (empty($queue)): ?>
        <div class="empty-queue">
          <p>No pending payments right now.</p>
        </div>
      <?php else: ?>
        <table class="queue-table">
          <thead>
            <tr>
              <th>Student</th>
              <th>ID</th>
              <th>SY / Sem</th>
              <th>Due</th>
              <th>Balance</th>
              <th>Status</th>
              <th></th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($queue as $row): ?>
              <tr>
                <td><?= htmlspecialchars(student_fullname_t($row)) ?></td>
                <td><?= htmlspecialchars(fmt_id_t($row['student_id'])) ?></td>
                <td><?= htmlspecialchars($row['school_year']) ?> &middot; Sem <?= (int)$row['semester'] ?></td>
                <td><?= htmlspecialchars($row['due_date']) ?></td>
                <td>₱<?= number_format((float)$row['balance'], 2) ?></td>
                <td>
                  <?php
                    $st = $row['payment_status'];
                    $cls = $st === 'Down Payment Paid' ? 'down' : ($st === 'Fully Paid' ? 'full' : 'unpaid');
                  ?>
                  <span class="status-pill <?= $cls ?>"><?= htmlspecialchars($st) ?></span>
                </td>
                <td>
                  <button class="btn-pay-row" data-payment-id="<?= (int)$row['payment_id'] ?>" data-student="<?= htmlspecialchars(student_fullname_t($row), ENT_QUOTES) ?>">
                    Pay
                  </button>
                </td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      <?php endif; ?>
    </div>
 
    <!-- ===== SETUP QUEUE VIEW (legacy fallback — enrollments with no
         payment row, e.g. created before fee-schedule automation) ===== -->
    <div id="setupPanel" style="display:none;">
      <?php if (empty($setupQueue)): ?>
        <div class="empty-queue">
          <p>No enrollments waiting on payment setup.</p>
        </div>
      <?php else: ?>
        <div class="alert-box alert-info mb-3">
          <iconify-icon icon="mdi:information-outline"></iconify-icon>
          New enrollments get their payment set up automatically from the fee schedule at finalize time.
          The rows below are older enrollments (or edge cases) that still need it done manually.
        </div>
        <table class="queue-table">
          <thead>
            <tr>
              <th>Student</th>
              <th>ID</th>
              <th>SY / Sem</th>
              <th>Amount Due</th>
              <th>Due Date</th>
              <th></th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($setupQueue as $row): ?>
              <tr data-enrollment-id="<?= (int)$row['enrollment_id'] ?>">
                <td><?= htmlspecialchars(student_fullname_t($row)) ?></td>
                <td><?= htmlspecialchars(fmt_id_t($row['student_id'])) ?></td>
                <td><?= htmlspecialchars($row['school_year']) ?> &middot; Sem <?= (int)$row['semester'] ?></td>
                <td><input type="number" class="setup-amount" min="0" step="0.01" placeholder="0.00"></td>
                <td><input type="date" class="setup-due-date"></td>
                <td>
                  <button class="btn-setup-row" data-enrollment-id="<?= (int)$row['enrollment_id'] ?>">
                    Set Up
                  </button>
                </td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      <?php endif; ?>
    </div>

    <!-- ===== SEARCH + PAY VIEW ===== -->
    <div id="searchPanel" class="search-panel">
      <div class="search-box">
        <input type="text" id="studentSearchInput" placeholder="Student ID or name">
        <button id="studentSearchBtn">Search</button>
      </div>
      <div id="payResult" class="pay-result"></div>
    </div>
 
  </div>
</div>

<?php include '../Admission/Include/footer.php' ?>