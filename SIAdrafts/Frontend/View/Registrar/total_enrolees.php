<?php
$pageTitle = "TOTAL ENROLEES";
$activePage = "total_enrolees";
$pageScript = "total_enrolees";

require_once '../../../Backend/auth.php';
require_once '../../../Backend/require_role.php';
require_role(['Registrar Staff']);
require_once __DIR__ . '/../../../Backend/admin/total_enrolees.php';


include '../Include/header.php';
?>

<div class="app-layout">

  <?php include '../Include/sidebar.php'; ?>

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

      <div class="grid-2">

        <div class="panel">
          <div class="panel-header">
            <span class="panel-title">Enrolees by Year Level</span>
          </div>
          <div class="panel-body">
            <div class="chart-container">
              <canvas id="yearLevelChart"></canvas>
            </div>
          </div>
        </div>

        <div class="panel">
          <div class="panel-header">
            <span class="panel-title">Student Type Breakdown</span>
          </div>
          <div class="panel-body">
            <div class="chart-container">
              <canvas id="studentTypeChart"></canvas>
            </div>
          </div>
        </div>

      </div>

      <div class="panel">
        <div class="panel-header">
          <span class="panel-title">Enrolees by Course &amp; Year Level</span>
          <a class="btn btn-outline" href="/SIAdrafts/Backend/api/export_enrolees_csv.php">Export Report</a>
        </div>

        <div class="panel-body" style="padding:16px 24px 0;">
          <div class="filter-bar">
            <input type="text" class="form-input" id="courseSearch" placeholder="Search course…">
          </div>
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
                <th style="width:160px">Share of Total</th>
              </tr>
            </thead>
            <tbody id="courseBody">
              <?php if (empty($byCourse)): ?>
              <tr>
                <td colspan="7" style="text-align:center; padding:32px; color:#888;">No enrollment records yet.</td>
              </tr>
              <?php else: ?>
                <?php foreach ($byCourse as $courseName => $c): ?>
                <?php
                    $share = $grand['total'] > 0 ? round(($c['total'] / $grand['total']) * 100) : 0;
                    $shareClass = $share >= 50 ? 'fill-bar-fill--high' : ($share >= 20 ? 'fill-bar-fill--mid' : 'fill-bar-fill--low');
                ?>
                <tr data-search="<?= htmlspecialchars(strtolower($courseName)) ?>">
                  <td><?= htmlspecialchars($courseName) ?></td>
                  <td><?= $c['y1'] ?></td>
                  <td><?= $c['y2'] ?></td>
                  <td><?= $c['y3'] ?></td>
                  <td><?= $c['y4'] ?></td>
                  <td><strong><?= $c['total'] ?></strong></td>
                  <td>
                    <div class="fill-bar" title="<?= $c['total'] ?> of <?= $grand['total'] ?> enrolees">
                      <div class="fill-bar-track">
                        <div class="fill-bar-fill <?= $shareClass ?>" style="width: <?= $share ?>%;"></div>
                      </div>
                      <span class="fill-bar-label"><?= $share ?>%</span>
                    </div>
                  </td>
                </tr>
                <?php endforeach; ?>
                <tr style="background:#faf7f2">
                  <td><strong>Grand Total</strong></td>
                  <td><strong><?= $grand['y1'] ?></strong></td>
                  <td><strong><?= $grand['y2'] ?></strong></td>
                  <td><strong><?= $grand['y3'] ?></strong></td>
                  <td><strong><?= $grand['y4'] ?></strong></td>
                  <td><strong><?= $grand['total'] ?></strong></td>
                  <td><strong>100%</strong></td>
                </tr>
              <?php endif; ?>
            </tbody>
          </table>
          <div class="empty-state" id="courseEmptyState" style="display:none;">
            <p>No courses match your search.</p>
          </div>
        </div>
      </div>
    </main>
  </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {

  Chart.defaults.font.family = "'Segoe UI', Roboto, Arial, sans-serif";
  Chart.defaults.color = '#6b7280';

  new Chart(document.getElementById('yearLevelChart'), {
    type: 'bar',
    data: {
      labels: ['1st Year', '2nd Year', '3rd Year', '4th Year'],
      datasets: [{
        label: 'Enrolees',
        data: [<?= $grand['y1'] ?>, <?= $grand['y2'] ?>, <?= $grand['y3'] ?>, <?= $grand['y4'] ?>],
        backgroundColor: 'rgba(232,168,32,0.55)',
        borderColor: '#c8911a',
        borderWidth: 1.5,
        borderRadius: 6,
        maxBarThickness: 48,
      }],
    },
    options: {
      responsive: true,
      maintainAspectRatio: false,
      plugins: {
        legend: { display: false },
        tooltip: { backgroundColor: '#1a2340', padding: 10, cornerRadius: 6 },
      },
      scales: {
        y: { beginAtZero: true, ticks: { precision: 0 }, grid: { color: '#f0e8d8' } },
        x: { grid: { display: false } },
      },
    },
  });

  new Chart(document.getElementById('studentTypeChart'), {
    type: 'doughnut',
    data: {
      labels: ['New', 'Continuing', 'Irregular'],
      datasets: [{
        data: [<?= $stats['new'] ?>, <?= $stats['continuing'] ?>, <?= $stats['irregular'] ?>],
        backgroundColor: ['#3b82f6', '#16a34a', '#7c3aed'],
        borderWidth: 2,
        borderColor: '#fff',
      }],
    },
    options: {
      responsive: true,
      maintainAspectRatio: false,
      plugins: {
        legend: { position: 'bottom', labels: { boxWidth: 12, padding: 14 } },
        tooltip: { backgroundColor: '#1a2340', padding: 10, cornerRadius: 6 },
      },
      cutout: '62%',
    },
  });

});
</script>

<?php
$extraScripts = [
    'https://cdn.jsdelivr.net/npm/vue@3/dist/vue.global.prod.js',
    '/SIAdrafts/Frontend/Js/Registrar/' . ($pageScript ?? 'registrar') . '.js',
];
include '../Include/footer.php';
?>
