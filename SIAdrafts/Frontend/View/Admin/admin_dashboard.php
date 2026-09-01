<?php
$pageTitle = "ADMIN DASHBOARD";
$activePage = "dashboard";

require_once '../../../Backend/auth.php';
require_once '../../../Backend/roles.php';
require_once '../../../Backend/require_role.php';
require_role([ROLE_ADMIN]);
require_once __DIR__ . '/../../../Backend/admin/dashboard.php';

include '../Include/header.php';
?>

<div class="app-layout">

    <?php include '../Include/sidebar.php'; ?>

    <main class="page-content clay-page-bg">

        <!-- Statistics — clay treatment, proving ground per the approved
             token plan. Icons: single accent green, no rainbow chips. -->
        <div class="clay-stat-grid" style="grid-template-columns:repeat(4,1fr);">

            <!-- Students -->
            <div class="clay-stat-card">
                <div class="clay-icon"><i class="bi bi-mortarboard-fill"></i></div>
                <div>
                    <div class="stat-value"><?= $dashboard['students']; ?></div>
                    <div class="stat-label">Total Students</div>
                </div>
            </div>

            <!-- Pending Admissions -->
            <div class="clay-stat-card">
                <div class="clay-icon"><i class="bi bi-hourglass-split"></i></div>
                <div>
                    <div class="stat-value"><?= $dashboard['pending']; ?></div>
                    <div class="stat-label">Pending Admissions</div>
                </div>
            </div>

            <!-- Approved Admissions — the seal: this is a genuinely "signed
                 off" figure, unlike the other three stats, so it gets the
                 system's one official mark instead of another icon chip. -->
            <div class="clay-stat-card">
                <div class="seal"><i class="bi bi-check-lg"></i></div>
                <div>
                    <div class="stat-value"><?= $dashboard['approved']; ?></div>
                    <div class="stat-label">Approved Admissions</div>
                </div>
            </div>

            <!-- Courses -->
            <div class="clay-stat-card">
                <div class="clay-icon"><i class="bi bi-book-half"></i></div>
                <div>
                    <div class="stat-value"><?= $dashboard['courses']; ?></div>
                    <div class="stat-label">Active Courses</div>
                </div>
            </div>

        </div>

        <!-- Trends -->
        <div class="grid-2" style="margin-bottom:32px; gap:26px;">

            <!-- Admissions Trend -->
            <div class="clay-panel">
                <div class="panel-header">
                    <span class="panel-title">Admissions Trend</span>
                    <span class="text-muted" style="font-size:12px;">Last 6 months</span>
                </div>
                <div class="panel-body">
                    <div class="chart-container">
                        <canvas id="admissionsTrendChart"></canvas>
                    </div>
                </div>
            </div>

            <!-- Admission Status Breakdown -->
            <div class="clay-panel">
                <div class="panel-header">
                    <span class="panel-title">Admission Status</span>
                </div>
                <div class="panel-body">
                    <div class="chart-container" style="display:flex; align-items:center; gap:20px;">
                        <div style="position:relative; flex:0 0 160px; height:160px;">
                            <canvas id="admissionStatusChart"></canvas>
                            <div id="donutCenterLabel" style="position:absolute; inset:0; display:flex; flex-direction:column; align-items:center; justify-content:center; pointer-events:none;">
                                <div style="font-size:22px; font-weight:800; color:var(--navy); line-height:1;"></div>
                                <div style="font-size:11px; color:var(--text-muted); margin-top:2px;">Total</div>
                            </div>
                        </div>
                        <div id="donutLegend" style="flex:1; display:flex; flex-direction:column; gap:8px; font-size:13px;"></div>
                    </div>
                </div>
            </div>

        </div>

        <div class="grid-2">

            <!-- Recent Admissions -->
            <div class="surface-2" style="padding:20px 4px;">

                <div class="rd-section-title" style="padding:0 18px;">Recent Admissions</div>

                <?php if (!empty($recentAdmissions)): ?>
                    <div class="ledger">
                        <?php
                        $avatarTints = ['seal', 'sky', 'gold', 'teal'];
                        foreach ($recentAdmissions as $i => $row):
                            $status = strtolower($row['status']);
                            switch ($status) {
                                case 'approved':
                                case 'fully paid':
                                    $stamp = 'approved'; break;
                                case 'pending':
                                case 'downpayment paid':
                                    $stamp = 'pending'; break;
                                case 'rejected':
                                    $stamp = 'rejected'; break;
                                default:
                                    $stamp = '';
                            }
                            $tint = $avatarTints[$i % count($avatarTints)];
                            $initials = '';
                            foreach (preg_split('/\s+/', trim($row['student_name'])) as $p) {
                                if ($p !== '') $initials .= strtoupper($p[0]);
                                if (strlen($initials) >= 2) break;
                            }
                        ?>
                        <div class="ledger-row">
                            <div class="rd-avatar <?= $tint ?>" style="width:30px;height:30px;font-size:11px;"><?= htmlspecialchars($initials ?: '?') ?></div>
                            <div style="flex:1;">
                                <div class="row-primary"><?= htmlspecialchars($row['student_name']) ?> — <?= htmlspecialchars($row['program']) ?></div>
                                <div class="row-secondary mono"><?= !empty($row['created_at']) ? date('M d, Y', strtotime($row['created_at'])) : '—' ?></div>
                            </div>
                            <span class="stamp <?= $stamp ?>"><?= htmlspecialchars($row['status']) ?></span>
                        </div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <div class="rd-empty-state">
                        <div class="rd-empty-icon">✓</div>
                        <div class="rd-empty-title">No recent admissions</div>
                        <div class="rd-empty-sub">New applications will show up here as they come in.</div>
                    </div>
                <?php endif; ?>

            </div>

            <!-- Enrollment Summary -->
            <div class="clay-panel">

                <div class="panel-header">
                    <span class="panel-title">Enrollment Summary</span>
                </div>

                <div class="panel-body" style="padding:0;">

                    <div class="table-responsive">
                    <table class="data-table" id="enrollmentSummaryTable">

                        <thead>
                        <tr>
                            <th>Course</th>
                            <th>Enrolled</th>
                            <th>Sections</th>
                            <th style="width:140px">Fill Rate</th>
                        </tr>
                        </thead>

                        <tbody>

                       <?php if (!empty($courseSummary)): ?>

                        <?php foreach ($courseSummary as $row): ?>

                        <tr>

                            <td><?= htmlspecialchars($row['course_name']); ?></td>

                            <td><?= $row['enrolled']; ?></td>

                            <td><?= $row['sections']; ?></td>

                            <td>
                                <?php
                                    $fillClass = $row['fill_rate'] >= 80 ? 'fill-bar-fill--high'
                                        : ($row['fill_rate'] >= 40 ? 'fill-bar-fill--mid' : 'fill-bar-fill--low');
                                ?>
                                <div class="fill-bar" title="<?= (int)$row['enrolled'] ?> / <?= (int)$row['capacity'] ?> seats">
                                    <div class="fill-bar-track">
                                        <div class="fill-bar-fill <?= $fillClass ?>" style="width: <?= (int)$row['fill_rate'] ?>%;"></div>
                                    </div>
                                    <span class="fill-bar-label"><?= (int)$row['fill_rate'] ?>%</span>
                                </div>
                            </td>

                        </tr>

                        <?php endforeach; ?>

                        <?php else: ?>

                            <tr>
                                <td colspan="4" style="text-align:center;">
                                    No enrollment data found.
                                </td>
                            </tr>

                        <?php endif; ?>

                        </tbody>

                    </table>
                    </div>

                </div>

            </div>

        </div>

    </main>

</div>

<script>
document.addEventListener('DOMContentLoaded', function () {

  if (document.getElementById('enrollmentSummaryTable')) {
    initDataTable('#enrollmentSummaryTable', { order: [] });
  }

  Chart.defaults.font.family = "'Segoe UI', Roboto, Arial, sans-serif";
  Chart.defaults.color = '#6b7280';

  // Smoothed area chart instead of bars: with mostly-historical zero
  // months and one current spike, individual bars read as a rendering
  // glitch. A filled line reads as "the trend building up to now."
  const trendData = <?= json_encode($admissionsTrendData) ?>;
  const trendHasAnyData = trendData.some(v => v > 0);

  new Chart(document.getElementById('admissionsTrendChart'), {
    type: 'line',
    data: {
      labels: <?= json_encode($admissionsTrendLabels) ?>,
      datasets: [{
        label: 'Applicants',
        data: trendData,
        fill: true,
        backgroundColor: 'rgba(232,162,61,0.18)',
        borderColor: '#c8911a',
        borderWidth: 2,
        tension: 0.35,
        pointRadius: 4,
        pointBackgroundColor: '#c8911a',
        pointBorderColor: '#fff',
        pointBorderWidth: 1.5,
      }],
    },
    options: {
      responsive: true,
      maintainAspectRatio: false,
      plugins: {
        legend: { display: false },
        tooltip: {
          backgroundColor: '#1B2340',
          padding: 10,
          cornerRadius: 6,
          titleFont: { weight: '600' },
        },
      },
      scales: {
        y: { beginAtZero: true, suggestedMax: trendHasAnyData ? undefined : 5, ticks: { precision: 0 }, grid: { color: '#f0e8d8' } },
        x: { grid: { display: false } },
      },
    },
  });

  const statusLabels = <?= json_encode(array_column($admissionStatusBreakdown, 'status')) ?>;
  const statusData   = <?= json_encode(array_map('intval', array_column($admissionStatusBreakdown, 'total'))) ?>;
  const statusColors = {
    'Pending': '#378ADD',
    'Approved': '#1D9E75',
    'Rejected': '#D85A30',
    'Downpayment Paid': '#E8A23D',
    'Fully Paid': '#1B2340',
  };
  const statusTotal = statusData.reduce((a, b) => a + b, 0);

  document.querySelector('#donutCenterLabel > div').textContent = statusTotal;

  const legendEl = document.getElementById('donutLegend');
  statusLabels.forEach((label, i) => {
    const color = statusColors[label] || '#94a3b8';
    const count = statusData[i];
    const pct = statusTotal ? Math.round((count / statusTotal) * 100) : 0;
    const row = document.createElement('div');
    row.style.cssText = 'display:flex; align-items:center; gap:8px;';
    row.innerHTML = `
      <span style="width:10px; height:10px; border-radius:3px; background:${color}; flex-shrink:0;"></span>
      <span style="flex:1; color:var(--navy);">${label}</span>
      <span style="font-weight:700; color:var(--navy); font-variant-numeric:tabular-nums;">${count}</span>
      <span style="color:var(--text-muted); font-size:11.5px; min-width:34px; text-align:right;">${pct}%</span>
    `;
    legendEl.appendChild(row);
  });

  new Chart(document.getElementById('admissionStatusChart'), {
    type: 'doughnut',
    data: {
      labels: statusLabels,
      datasets: [{
        data: statusData,
        backgroundColor: statusLabels.map(s => statusColors[s] || '#94a3b8'),
        borderWidth: 2,
        borderColor: '#fff',
      }],
    },
    options: {
      responsive: true,
      maintainAspectRatio: false,
      plugins: {
        legend: { display: false },
        tooltip: { backgroundColor: '#1B2340', padding: 10, cornerRadius: 6 },
      },
      cutout: '68%',
    },
  });

});
</script>

<?php include '../Include/footer.php'; ?>
