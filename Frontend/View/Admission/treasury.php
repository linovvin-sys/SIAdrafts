<?php 
$page_scripts = ['/SIAdrafts/Frontend/Js/Admission/treasury.js'];
$pageTitle = "Treasury";
$activePage = "treasury";
require_once '../../../Backend/auth.php';
require_once '../../../Backend/db.php';

$db = new Database();
$conn = $db->connect();
include '../Admission/Include/header.php';


// ---- Queue: all payment rows not yet fully paid ----
$queueStmt = $conn->prepare(
    // Show student.student_no (the official ID), falling back to
    // applicants.reference_id only if the student row is somehow missing
    // (shouldn't happen for this queue, but matches get_student.php's pattern).
    "SELECT p.payment_id, p.amount_due, p.downpayment, p.balance, p.due_date, p.payment_status,
            e.enrollment_id, e.school_year, e.semester,
            COALESCE(s.student_no, a.reference_id) AS display_id, a.first_name, a.last_name
     FROM payment p
     JOIN enrollment e ON e.enrollment_id = p.enrollment_id
     JOIN applicants a ON a.applicant_id = e.student_id
     LEFT JOIN student s ON s.applicant_id = a.applicant_id
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
    // Same fix as the queue above — prefer the official student_no.
    "SELECT e.enrollment_id, e.school_year, e.semester, e.created_at,
            COALESCE(s.student_no, a.reference_id) AS display_id, a.first_name, a.last_name
     FROM enrollment e
     JOIN applicants a ON a.applicant_id = e.student_id
     LEFT JOIN student s ON s.applicant_id = a.applicant_id
     LEFT JOIN payment p ON p.enrollment_id = e.enrollment_id
     WHERE p.payment_id IS NULL
     ORDER BY e.created_at ASC"
);
$setupStmt->execute();
$setupQueue = $setupStmt->get_result()->fetch_all(MYSQLI_ASSOC);
$setupStmt->close();

// Revenue tab data — this opens/closes its own DB connection, so it must
// run after the queue/setup queries above are done with $conn.
require_once __DIR__ . '/../../../Backend/admin/revenue.php';

function fmt_id_t($id): string {
    // Now receives student.student_no ("2026-00001") when the student is
    // enrolled, falling back to applicants.reference_id if no student row
    // exists yet (see the COALESCE in the queries above).
    return (string)$id;
}
function student_fullname_t(array $s): string {
    return $s['last_name'] . ', ' . $s['first_name'];
}
?>

<div class="app-layout">
  <?php include '../Admission/Include/sidebar.php'; ?>
  <main class="page-content">

<div class="treasury-page">
 
  <div class="treasury-head">
    <h1>Treasury</h1>
    <p>Track who still owes a balance, and record payments as they come in.</p>
  </div>
 
  <div class="treasury-tabs">
    <button class="t-tab active" id="tabRevenue">Revenue</button>
    <button class="t-tab" id="tabQueue">Pending payments</button>
    <button class="t-tab" id="tabSetup">Needs payment setup<?= count($setupQueue) ? ' (' . count($setupQueue) . ')' : '' ?></button>
    <button class="t-tab" id="tabSearch">Search a student</button>
  </div>
 
  <div class="treasury-card">

    <!-- ===== REVENUE VIEW ===== -->
    <div id="revenuePanel">
      <div class="stats-grid" style="grid-template-columns: repeat(3,1fr); margin-bottom:24px;">
        <div class="stat-card">
          <div class="stat-icon green">💰</div>
          <div><div class="stat-value">₱<?= number_format($revenue['total'], 2) ?></div><div class="stat-label">Total Revenue</div></div>
        </div>
        <div class="stat-card">
          <div class="stat-icon gold">📈</div>
          <div><div class="stat-value">₱<?= number_format($revenue['collected'], 2) ?></div><div class="stat-label">Collected</div></div>
        </div>
        <div class="stat-card">
          <div class="stat-icon blue">⏳</div>
          <div><div class="stat-value">₱<?= number_format($revenue['outstanding'], 2) ?></div><div class="stat-label">Outstanding</div></div>
        </div>
      </div>

      <div class="panel">
        <div class="panel-header">
          <span class="panel-title">Revenue by Course</span>
          <a class="btn btn-outline" href="/SIAdrafts/Backend/api/export_revenue_csv.php">Export CSV</a>
        </div>
        <div class="panel-body" style="padding:0">
          <table class="data-table">
            <thead>
              <tr>
                <th>Course</th>
                <th>Enrolled</th>
                <th>Fee / Student</th>
                <th>Total Expected</th>
                <th>Collected</th>
                <th>Balance</th>
              </tr>
            </thead>
            <tbody>
              <?php if (empty($revenueByCourse)): ?>
              <tr>
                <td colspan="6" style="text-align:center; padding:32px; color:#888;">No payment records yet.</td>
              </tr>
              <?php else: foreach ($revenueByCourse as $row): ?>
              <tr>
                <td><?= htmlspecialchars($row['course_name']) ?></td>
                <td><?= (int)$row['enrolled'] ?></td>
                <td>₱<?= number_format($row['fee_per_student'], 2) ?></td>
                <td>₱<?= number_format($row['total_expected'], 2) ?></td>
                <td>₱<?= number_format($row['collected'], 2) ?></td>
                <td>₱<?= number_format($row['balance'], 2) ?></td>
              </tr>
              <?php endforeach; endif; ?>
            </tbody>
          </table>
        </div>
      </div>
    </div>

    <!-- ===== QUEUE VIEW ===== -->
    <div id="queuePanel" style="display:none;">
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
                <td><?= htmlspecialchars(fmt_id_t($row['display_id'])) ?></td>
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
                <td><?= htmlspecialchars(fmt_id_t($row['display_id'])) ?></td>
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

  </main>
</div>

<?php include '../Admission/Include/footer.php' ?>