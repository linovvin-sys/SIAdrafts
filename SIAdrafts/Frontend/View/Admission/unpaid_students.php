<?php
$extraCss = ['/SIAdrafts/Frontend/Css/Admission/treasury.css'];
$pageTitle = "Unpaid Students";
$activePage = "unpaid_students";

require_once '../../../Backend/auth.php';
require_once '../../../Backend/roles.php';
require_once '../../../Backend/require_role.php';
require_role([ROLE_TREASURY]);
require_once '../../../Backend/db.php';
require_once '../../../Backend/unpaid_transfer.php';

$db = new Database();
$conn = $db->connect();
transfer_overdue_unpaid($conn);
include '../Include/header.php';

// Balance/amount_due are joined live from `payment` rather than read off
// unpaid_students' own snapshot columns, so a partial payment made after
// being flagged still shows the true current balance instead of a stale
// figure from the moment it was transferred.
$unpaidStmt = $conn->prepare(
    "SELECT u.unpaid_id, u.due_date, u.transferred_at,
            p.payment_id, p.amount_due, p.downpayment, p.balance,
            e.school_year, e.semester,
            COALESCE(s.student_no, a.reference_id) AS display_id, a.first_name, a.last_name
     FROM unpaid_students u
     JOIN payment p     ON p.payment_id = u.payment_id
     JOIN enrollment e  ON e.enrollment_id = u.enrollment_id
     JOIN applicants a  ON a.applicant_id = e.student_id
     LEFT JOIN student s ON s.applicant_id = a.applicant_id
     WHERE u.status = 'Pending'
     ORDER BY u.due_date ASC"
);
$unpaidStmt->execute();
$unpaid = $unpaidStmt->get_result()->fetch_all(MYSQLI_ASSOC);
$unpaidStmt->close();

$totalOutstanding = array_sum(array_column($unpaid, 'balance'));

function student_fullname_us(array $s): string {
    return $s['last_name'] . ', ' . $s['first_name'];
}
?>

<div class="app-layout">
  <?php include '../Include/sidebar.php'; ?>
  <main class="page-content">

    <div class="treasury-page">

      <div class="treasury-head">
        <h1>Unpaid Students</h1>
        <p>Enrollments that missed their 3-day payment window with no downpayment made — <?= count($unpaid) ?> student<?= count($unpaid) === 1 ? '' : 's' ?>, ₱<?= number_format($totalOutstanding, 2) ?> outstanding.</p>
      </div>

      <div class="treasury-card">
        <?php if (empty($unpaid)): ?>
          <div class="empty-queue">
            <p>No unpaid students right now.</p>
          </div>
        <?php else: ?>
          <table class="queue-table">
            <thead>
              <tr>
                <th>Student</th>
                <th>ID</th>
                <th>SY / Sem</th>
                <th>Amount Due</th>
                <th>Balance</th>
                <th>Due Date</th>
                <th>Flagged On</th>
                <th></th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($unpaid as $row): ?>
                <tr>
                  <td><?= htmlspecialchars(student_fullname_us($row)) ?></td>
                  <td><?= htmlspecialchars((string)$row['display_id']) ?></td>
                  <td><?= htmlspecialchars($row['school_year']) ?> &middot; Sem <?= (int)$row['semester'] ?></td>
                  <td>₱<?= number_format((float)$row['amount_due'], 2) ?></td>
                  <td><span class="status-pill unpaid">₱<?= number_format((float)$row['balance'], 2) ?></span></td>
                  <td><?= htmlspecialchars($row['due_date']) ?></td>
                  <td><?= date('M d, Y', strtotime($row['transferred_at'])) ?></td>
                  <td>
                    <a class="btn-pay-row" href="/SIAdrafts/Frontend/View/Admission/treasury.php">Collect</a>
                  </td>
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
