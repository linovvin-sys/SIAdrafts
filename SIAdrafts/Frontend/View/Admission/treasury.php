<?php
$extraScripts = [
    'https://code.jquery.com/jquery-3.7.1.min.js',
    'https://cdn.datatables.net/1.13.8/js/jquery.dataTables.min.js',
    'https://cdn.datatables.net/1.13.8/js/dataTables.bootstrap5.min.js',
    '/SIAdrafts/Frontend/Js/datatable-init.js',
    '/SIAdrafts/Frontend/Js/Admission/treasury.js',
];
$extraCss = ['/SIAdrafts/Frontend/Css/Admission/treasury.css'];
$pageTitle = "Treasury";
$activePage = "treasury";
require_once '../../../Backend/auth.php';
require_once '../../../Backend/roles.php';
require_once '../../../Backend/require_role.php';
require_role([ROLE_TREASURY, ROLE_ADMIN]);
$isAdminViewer = current_user_is(['Admin']);
require_once '../../../Backend/db.php';
require_once '../../../Backend/unpaid_transfer.php';

$db = new Database();
$conn = $db->connect();
transfer_overdue_unpaid($conn);
include '../Include/header.php';


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

// ---- Queue: pending add/drop subject-change fees ----
$feeQueueStmt = $conn->prepare(
    "SELECT scf.fee_id, scf.action, scf.units, scf.amount,
            sub.subject_code, sub.subject_name,
            COALESCE(s.student_no, a.reference_id) AS display_id, a.first_name, a.last_name
     FROM subject_change_fee scf
     JOIN enrollment_subject es ON es.enrollment_subject_id = scf.enrollment_subject_id
     JOIN subject sub           ON sub.subject_id = es.subject_id
     JOIN enrollment e          ON e.enrollment_id = scf.enrollment_id
     JOIN applicants a          ON a.applicant_id = e.student_id
     LEFT JOIN student s        ON s.applicant_id = a.applicant_id
     WHERE scf.status = 'Pending'
     ORDER BY scf.created_at ASC"
);
$feeQueueStmt->execute();
$feeQueue = $feeQueueStmt->get_result()->fetch_all(MYSQLI_ASSOC);
$feeQueueStmt->close();

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
  <?php include '../Include/sidebar.php'; ?>
  <main class="page-content">
    <?php include '../Include/readonly_banner.php'; ?>

<div class="treasury-page">
 
  <div class="treasury-head">
    <h1>Treasury</h1>
    <p>Track who still owes a balance, and record payments as they come in.</p>
  </div>

  <?php if (($_GET['pay'] ?? '') === 'cancelled'): ?>
  <div class="t-banner error" style="max-width:1100px;margin:0 auto 16px;">
    Online payment was cancelled. Nothing was charged.
  </div>
  <?php endif; ?>

  <div class="treasury-tabs">
    <button class="t-tab active" id="tabRevenue">Revenue</button>
    <button class="t-tab" id="tabQueue">Pending payments</button>
    <button class="t-tab" id="tabSetup">Needs payment setup<?= count($setupQueue) ? ' (' . count($setupQueue) . ')' : '' ?></button>
    <button class="t-tab" id="tabSearch">Search a student</button>
  </div>
 
  <div class="treasury-card">

    <!-- ===== REVENUE VIEW ===== -->
    <div id="revenuePanel">
      <div class="stat-grid" style="grid-template-columns: 1fr 1fr 1.3fr;">
        <div class="surface-1 rd-stat-card">
          <div class="rd-stat-icon" style="background:var(--sky-100); color:var(--sky-600);">₱</div>
          <div class="rd-stat-figure mono">₱<?= number_format($revenue['total'], 2) ?></div>
          <div class="rd-stat-label">Total Revenue Expected</div>
        </div>
        <div class="surface-1 rd-stat-card">
          <div class="rd-stat-icon" style="background:var(--teal-100); color:var(--teal-600);">✓</div>
          <div class="rd-stat-figure mono">₱<?= number_format($revenue['collected'], 2) ?></div>
          <div class="rd-stat-label">Collected</div>
        </div>
        <div class="rd-stat-card" style="background:var(--seal-100); border:1px solid #E3B9AF; border-radius:var(--radius-lg);">
          <div class="rd-stat-icon" style="background:var(--seal-600); color:#fff;">!</div>
          <div class="rd-stat-figure mono" style="font-size:29px; color:var(--seal-600);">₱<?= number_format($revenue['outstanding'], 2) ?></div>
          <div class="rd-stat-label" style="color:#8A3A2E; font-weight:500;">Outstanding — needs follow-up</div>
        </div>
      </div>

      <div class="panel">
        <div class="panel-header">
          <span class="panel-title">Revenue by Course</span>
          <a class="btn btn-outline" href="/SIAdrafts/Backend/api/Treasury/export_revenue_csv.php">Export CSV</a>
        </div>
        <div class="panel-body" style="padding:0">
          <div class="table-responsive">
          <table class="data-table" id="revenueByCourseTable">
            <thead>
              <tr>
                <th>Course</th>
                <th>Enrolled</th>
                <th>Fee / Student</th>
                <th>Total Expected</th>
                <th>Collected</th>
                <th style="width:150px;">Collected %</th>
                <th>Balance</th>
              </tr>
            </thead>
            <tbody>
              <?php if (empty($revenueByCourse)): ?>
              <tr>
                <td colspan="7" style="text-align:center; padding:32px; color:#888;">No payment records yet.</td>
              </tr>
              <?php else:
                $sumEnrolled = 0; $sumExpected = 0; $sumCollected = 0; $sumBalance = 0;
                foreach ($revenueByCourse as $row):
                  $sumEnrolled  += (int)$row['enrolled'];
                  $sumExpected  += (float)$row['total_expected'];
                  $sumCollected += (float)$row['collected'];
                  $sumBalance   += (float)$row['balance'];
                  $pct = $row['total_expected'] > 0 ? min(100, round(($row['collected'] / $row['total_expected']) * 100)) : 0;
                  $fillClass = $pct >= 80 ? 'fill-bar-fill--high' : ($pct >= 40 ? 'fill-bar-fill--mid' : 'fill-bar-fill--low');
              ?>
              <tr>
                <td><?= htmlspecialchars($row['course_name']) ?></td>
                <td class="mono"><?= (int)$row['enrolled'] ?></td>
                <td class="mono">₱<?= number_format($row['fee_per_student'], 2) ?></td>
                <td class="mono">₱<?= number_format($row['total_expected'], 2) ?></td>
                <td class="mono" style="color:var(--teal-600);">₱<?= number_format($row['collected'], 2) ?></td>
                <td>
                  <div class="rd-progress-track" style="width:130px;" title="₱<?= number_format($row['collected'], 2) ?> of ₱<?= number_format($row['total_expected'], 2) ?>">
                    <div class="rd-progress-fill" style="width: <?= $pct ?>%;"></div>
                  </div>
                  <div class="row-secondary mono" style="margin-top:4px;"><?= $pct ?>% collected</div>
                </td>
                <td class="mono" style="color:<?= $row['balance'] > 0 ? 'var(--seal-600)' : 'inherit' ?>;">₱<?= number_format($row['balance'], 2) ?></td>
              </tr>
              <?php endforeach; ?>
              <?php endif; ?>
            </tbody>
            <?php if (!empty($revenueByCourse)): ?>
            <tfoot>
              <tr class="total-row">
                <td style="font-weight:600;">Total</td>
                <td class="mono"><?= $sumEnrolled ?></td>
                <td class="mono">—</td>
                <td class="mono">₱<?= number_format($sumExpected, 2) ?></td>
                <td class="mono" style="color:var(--teal-600);">₱<?= number_format($sumCollected, 2) ?></td>
                <td class="mono"><?= $sumExpected > 0 ? round(($sumCollected / $sumExpected) * 100) : 0 ?>%</td>
                <td class="mono" style="color:var(--seal-600);">₱<?= number_format($sumBalance, 2) ?></td>
              </tr>
            </tfoot>
            <?php endif; ?>
          </table>
          </div>
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
        <table class="queue-table" id="paymentQueueTable">
          <thead>
            <tr>
              <th>Student</th>
              <th>ID</th>
              <th>SY / Sem</th>
              <th>Due</th>
              <th>Balance</th>
              <th>Status</th>
              <?php if (!$isAdminViewer): ?><th></th><?php endif; ?>
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
                <?php if (!$isAdminViewer): ?>
                <td>
                  <button class="btn-pay-row" data-payment-id="<?= (int)$row['payment_id'] ?>" data-student="<?= htmlspecialchars(student_fullname_t($row), ENT_QUOTES) ?>">
                    Pay
                  </button>
                </td>
                <?php endif; ?>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      <?php endif; ?>

      <?php if (!empty($feeQueue)): ?>
        <h3 style="margin:24px 0 12px;">Pending Subject-Change Fees</h3>
        <table class="queue-table" id="feeQueueTable">
          <thead>
            <tr>
              <th>Student</th>
              <th>ID</th>
              <th>Subject</th>
              <th>Action</th>
              <th>Amount</th>
              <?php if (!$isAdminViewer): ?><th></th><?php endif; ?>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($feeQueue as $row): ?>
              <tr>
                <td><?= htmlspecialchars(student_fullname_t($row)) ?></td>
                <td><?= htmlspecialchars(fmt_id_t($row['display_id'])) ?></td>
                <td><?= htmlspecialchars($row['subject_code'] . ' — ' . $row['subject_name']) ?> (<?= htmlspecialchars($row['units']) ?> units)</td>
                <td><span class="status-pill <?= $row['action'] === 'Add' ? 'down' : 'unpaid' ?>"><?= htmlspecialchars($row['action']) ?></span></td>
                <td>₱<?= number_format((float)$row['amount'], 2) ?></td>
                <?php if (!$isAdminViewer): ?>
                <td>
                  <button class="btn-pay-row" data-fee-id="<?= (int)$row['fee_id'] ?>" data-student="<?= htmlspecialchars(student_fullname_t($row), ENT_QUOTES) ?>">
                    Pay
                  </button>
                </td>
                <?php endif; ?>
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
        <table class="queue-table" id="setupQueueTable">
          <thead>
            <tr>
              <th>Student</th>
              <th>ID</th>
              <th>SY / Sem</th>
              <?php if ($isAdminViewer): ?>
              <th>Status</th>
              <?php else: ?>
              <th>Amount Due</th>
              <th>Due Date</th>
              <th></th>
              <?php endif; ?>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($setupQueue as $row): ?>
              <tr data-enrollment-id="<?= (int)$row['enrollment_id'] ?>">
                <td><?= htmlspecialchars(student_fullname_t($row)) ?></td>
                <td><?= htmlspecialchars(fmt_id_t($row['display_id'])) ?></td>
                <td><?= htmlspecialchars($row['school_year']) ?> &middot; Sem <?= (int)$row['semester'] ?></td>
                <?php if ($isAdminViewer): ?>
                <td><span class="status-pill unpaid">Needs setup</span></td>
                <?php else: ?>
                <td><input type="number" class="setup-amount" min="0" step="0.01" placeholder="0.00"></td>
                <td><input type="date" class="setup-due-date"></td>
                <td>
                  <button class="btn-setup-row" data-enrollment-id="<?= (int)$row['enrollment_id'] ?>">
                    Set Up
                  </button>
                </td>
                <?php endif; ?>
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

<!-- ===== PAYMENT TERMINAL — processing overlay shown while a payment or
     fee is being recorded. Purely a front-end sequencing/perception layer;
     record_payment.php / record_subject_fee_payment.php still do the real
     work. See runPaymentTerminal() in treasury.js. ===== -->
<div class="pay-terminal" id="payTerminal" aria-hidden="true">
  <div class="pay-terminal-backdrop" data-terminal-dismiss></div>
  <div class="pay-terminal-card" role="dialog" aria-modal="true" aria-labelledby="payTerminalStep">
    <div class="pay-terminal-stage">
      <div class="pay-terminal-ring">
        <svg class="pay-terminal-glyph pay-terminal-check" viewBox="0 0 52 52" aria-hidden="true">
          <circle class="pay-terminal-glyph-ring" cx="26" cy="26" r="23"/>
          <path class="pay-terminal-glyph-mark" d="M15 27l7.2 7.2L37.5 19"/>
        </svg>
        <svg class="pay-terminal-glyph pay-terminal-x" viewBox="0 0 52 52" aria-hidden="true">
          <circle class="pay-terminal-glyph-ring" cx="26" cy="26" r="23"/>
          <path class="pay-terminal-glyph-mark" d="M18 18l16 16M34 18L18 34"/>
        </svg>
      </div>
      <p class="pay-terminal-step" id="payTerminalStep" aria-live="polite">Verifying amount&hellip;</p>
      <div class="pay-terminal-track"><div class="pay-terminal-fill" id="payTerminalFill"></div></div>
    </div>
    <div class="pay-terminal-result" id="payTerminalResult" aria-live="polite"></div>
  </div>
</div>

  </main>
  </div><!-- /.main-content, opened by Include/sidebar.php -->
</div>

<?php include '../Include/footer.php'; ?>