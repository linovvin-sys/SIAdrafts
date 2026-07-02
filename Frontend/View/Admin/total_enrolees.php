<?php
$pageTitle = "TOTAL ENROLEES";
$activePage = "total_enrolees";

require_once '../../../Backend/auth.php';
require_once __DIR__ . '/../../../Backend/admin/total_enrolees.php';


include 'Include/header.php';
?>

<div class="app-layout">

  <?php include 'Include/sidebar.php'; ?>

    <main class="page-content">

      <div class="stats-grid" style="grid-template-columns:repeat(4,1fr); margin-bottom:24px;">
        <div class="stat-card">
          <div class="stat-icon gold">👥</div>
          <div><div class="stat-value"><?= number_format($stats['total']) ?></div><div class="stat-label">Total Enrolees</div></div>
        </div>
        <div class="stat-card">
          <div class="stat-icon blue">🆕</div>
          <div><div class="stat-value"><?= number_format($stats['new']) ?></div><div class="stat-label">New Students</div></div>
        </div>
        <div class="stat-card">
          <div class="stat-icon green">🔄</div>
          <div><div class="stat-value"><?= number_format($stats['continuing']) ?></div><div class="stat-label">Continuing</div></div>
        </div>
        <div class="stat-card">
          <div class="stat-icon purple">👨‍🎓</div>
          <div><div class="stat-value"><?= number_format($stats['irregular']) ?></div><div class="stat-label">Irregular</div></div>
        </div>
      </div>

      <div class="panel">
        <div class="panel-header">
          <span class="panel-title">Enrolees by Course & Year Level</span>
          <a class="btn btn-outline" href="/SIAdrafts/Backend/api/export_enrolees_csv.php">Export Report</a>
        </div>
        <div class="panel-body" style="padding:0">
          <table class="data-table">
            <thead>
              <tr>
                <th>Course</th>
                <th>1st Year</th>
                <th>2nd Year</th>
                <th>3rd Year</th>
                <th>4th Year</th>
                <th>Total</th>
              </tr>
            </thead>
            <tbody>
              <?php if (empty($byCourse)): ?>
              <tr>
                <td colspan="6" style="text-align:center; padding:32px; color:#888;">No enrollment records yet.</td>
              </tr>
              <?php else: ?>
                <?php foreach ($byCourse as $courseName => $c): ?>
                <tr>
                  <td><?= htmlspecialchars($courseName) ?></td>
                  <td><?= $c['y1'] ?></td>
                  <td><?= $c['y2'] ?></td>
                  <td><?= $c['y3'] ?></td>
                  <td><?= $c['y4'] ?></td>
                  <td><strong><?= $c['total'] ?></strong></td>
                </tr>
                <?php endforeach; ?>
                <tr style="background:#faf7f2">
                  <td><strong>Grand Total</strong></td>
                  <td><strong><?= $grand['y1'] ?></strong></td>
                  <td><strong><?= $grand['y2'] ?></strong></td>
                  <td><strong><?= $grand['y3'] ?></strong></td>
                  <td><strong><?= $grand['y4'] ?></strong></td>
                  <td><strong><?= $grand['total'] ?></strong></td>
                </tr>
              <?php endif; ?>
            </tbody>
          </table>
        </div>
      </div>
    </main>
  </div>
</div>
<?php include 'Include/footer.php'?>