<?php
$pageTitle = "REVENUE";
$activePage = "revenue";

require_once '../../../Backend/auth.php';
require_once __DIR__ . '/../../../Backend/admin/revenue.php';


include 'Include/header.php';
?>

<div class="app-layout">

  <?php include 'Include/sidebar.php'; ?>

    <main class="page-content">

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
    </main>
  </div>
</div>
<?php include 'Include/footer.php'?>