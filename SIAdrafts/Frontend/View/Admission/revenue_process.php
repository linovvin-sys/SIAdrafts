<?php
$extraCss = ['/SIAdrafts/Frontend/Css/Admission/treasury.css'];
$pageTitle = "Revenue — Process";
$activePage = "revenue_process";

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

// ---- Still has a balance ----
// Excludes anything already transferred to the Unpaid Students list — once
// an enrollment misses its payment window with no downpayment at all, it
// belongs there instead of lingering in this queue too.
$procStmt = $conn->prepare(
    "SELECT p.payment_id, p.amount_due, p.downpayment, p.balance, p.due_date, p.payment_status,
            e.enrollment_id, e.school_year, e.semester,
            COALESCE(s.student_no, a.reference_id) AS display_id, a.first_name, a.last_name
     FROM payment p
     JOIN enrollment e ON e.enrollment_id = p.enrollment_id
     JOIN applicants a ON a.applicant_id = e.student_id
     LEFT JOIN student s ON s.applicant_id = a.applicant_id
     WHERE p.balance > 0
       AND NOT EXISTS (SELECT 1 FROM unpaid_students u WHERE u.payment_id = p.payment_id)
     ORDER BY p.due_date ASC"
);
$procStmt->execute();
$processing = $procStmt->get_result()->fetch_all(MYSQLI_ASSOC);
$procStmt->close();

$totalOutstanding = array_sum(array_column($processing, 'balance'));

function student_fullname_rpr(array $s): string {
    return $s['last_name'] . ', ' . $s['first_name'];
}
?>

<div class="app-layout">
  <?php include '../Include/sidebar.php'; ?>
  <main class="page-content">
    <?php include '../Include/readonly_banner.php'; ?>

    <div class="treasury-page">

      <div class="treasury-head">
        <h1>Revenue — Process</h1>
        <p>Students who still have a remaining balance to settle.</p>
      </div>

      <div class="treasury-card">
        <?php if (empty($processing)): ?>
          <div class="empty-queue">
            <p>No pending balances right now.</p>
          </div>
        <?php else: ?>
          <table class="queue-table">
            <thead>
              <tr>
                <th>Student</th>
                <th>ID</th>
                <th>SY / Sem</th>
                <th>Amount Due</th>
                <th>Downpayment</th>
                <th>Balance</th>
                <th>Due</th>
                <th>Status</th>
                <?php if (!$isAdminViewer): ?><th></th><?php endif; ?>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($processing as $row): ?>
                <tr>
                  <td><?= htmlspecialchars(student_fullname_rpr($row)) ?></td>
                  <td><?= htmlspecialchars((string)$row['display_id']) ?></td>
                  <td><?= htmlspecialchars($row['school_year']) ?> &middot; Sem <?= (int)$row['semester'] ?></td>
                  <td>₱<?= number_format((float)$row['amount_due'], 2) ?></td>
                  <td>₱<?= number_format((float)$row['downpayment'], 2) ?></td>
                  <td>₱<?= number_format((float)$row['balance'], 2) ?></td>
                  <td><?= htmlspecialchars($row['due_date']) ?></td>
                  <td>
                    <?php
                      $st = $row['payment_status'];
                      $cls = $st === 'Down Payment Paid' ? 'down' : ($st === 'Fully Paid' ? 'full' : 'unpaid');
                    ?>
                    <span class="status-pill <?= $cls ?>"><?= htmlspecialchars($st) ?></span>
                  </td>
                  <?php if (!$isAdminViewer): ?>
                  <td>
                    <a class="btn-pay-row" href="/SIAdrafts/Frontend/View/Admission/treasury.php">Pay</a>
                  </td>
                  <?php endif; ?>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        <?php endif; ?>
      </div>

    </div>

  </main>
</div>

<?php include '../Include/footer.php'; ?>