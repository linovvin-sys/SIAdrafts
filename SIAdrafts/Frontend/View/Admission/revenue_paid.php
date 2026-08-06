<?php
$extraCss = ['/SIAdrafts/Frontend/Css/Admission/treasury.css'];
$pageTitle = "Revenue — Paid";
$activePage = "revenue_paid";

require_once '../../../Backend/auth.php';
require_once '../../../Backend/roles.php';
require_once '../../../Backend/require_role.php';
require_role([ROLE_TREASURY, ROLE_ADMIN]);
require_once '../../../Backend/db.php';
require_once '../../../Backend/unpaid_transfer.php';

$db = new Database();
$conn = $db->connect();
transfer_overdue_unpaid($conn);
include '../Include/header.php';

// ---- Fully paid: balance = 0 ----
$paidStmt = $conn->prepare(
    "SELECT p.payment_id, p.amount_due, p.downpayment, p.paid_at,
            e.enrollment_id, e.school_year, e.semester,
            COALESCE(s.student_no, a.reference_id) AS display_id, a.first_name, a.last_name
     FROM payment p
     JOIN enrollment e ON e.enrollment_id = p.enrollment_id
     JOIN applicants a ON a.applicant_id = e.student_id
     LEFT JOIN student s ON s.applicant_id = a.applicant_id
     WHERE p.balance = 0
     ORDER BY p.paid_at DESC"
);
$paidStmt->execute();
$paid = $paidStmt->get_result()->fetch_all(MYSQLI_ASSOC);
$paidStmt->close();

$totalCollected = array_sum(array_column($paid, 'amount_due'));

function student_fullname_rp(array $s): string {
    return $s['last_name'] . ', ' . $s['first_name'];
}
?>

<div class="app-layout">
  <?php include '../Include/sidebar.php'; ?>
  <main class="page-content">
    <?php include '../Include/readonly_banner.php'; ?>

    <div class="treasury-page">

      <div class="treasury-head">
        <h1>Revenue — Paid</h1>
        <p>Students who are fully paid, with no remaining balance.</p>
      </div>

      <div class="treasury-card">
        <?php if (empty($paid)): ?>
          <div class="empty-queue">
            <p>No fully paid records yet.</p>
          </div>
        <?php else: ?>
          <table class="queue-table">
            <thead>
              <tr>
                <th>Student</th>
                <th>ID</th>
                <th>SY / Sem</th>
                <th>Amount Paid</th>
                <th>Paid On</th>
                <th>Status</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($paid as $row): ?>
                <tr>
                  <td><?= htmlspecialchars(student_fullname_rp($row)) ?></td>
                  <td><?= htmlspecialchars((string)$row['display_id']) ?></td>
                  <td><?= htmlspecialchars($row['school_year']) ?> &middot; Sem <?= (int)$row['semester'] ?></td>
                  <td>₱<?= number_format((float)$row['amount_due'], 2) ?></td>
                  <td><?= $row['paid_at'] ? htmlspecialchars($row['paid_at']) : '—' ?></td>
                  <td><span class="status-pill full">Fully Paid</span></td>
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