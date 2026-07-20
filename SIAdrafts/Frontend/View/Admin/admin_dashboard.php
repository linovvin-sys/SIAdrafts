<?php
$pageTitle = "ADMIN DASHBOARD";
$activePage = "dashboard";

require_once '../../../Backend/auth.php';
require_once '../../../Backend/roles.php';
require_once '../../../Backend/require_role.php';
require_role([ROLE_ADMIN]);
require_once __DIR__ . '/../../../Backend/admin/dashboard.php';

include 'Include/header.php';
?>

<div class="app-layout">

    <?php include 'Include/sidebar.php'; ?>

    <main class="page-content">

        <!-- Statistics -->
        <div class="stats-grid">

            <!-- Students -->
            <div class="stat-card">
                <div class="stat-icon gold">🎓</div>
                <div>
                    <div class="stat-value"><?= $dashboard['students']; ?></div>
                    <div class="stat-label">Total Students</div>
                </div>
            </div>

            <!-- Pending Admissions -->
            <div class="stat-card">
                <div class="stat-icon blue">⏳</div>
                <div>
                    <div class="stat-value"><?= $dashboard['pending']; ?></div>
                    <div class="stat-label">Pending Admissions</div>
                </div>
            </div>

            <!-- Approved Admissions -->
            <div class="stat-card">
                <div class="stat-icon green">✅</div>
                <div>
                    <div class="stat-value"><?= $dashboard['approved']; ?></div>
                    <div class="stat-label">Approved Admissions</div>
                </div>
            </div>

            <!-- Courses -->
            <div class="stat-card">
                <div class="stat-icon purple">📘</div>
                <div>
                    <div class="stat-value"><?= $dashboard['courses']; ?></div>
                    <div class="stat-label">Active Courses</div>
                </div>
            </div>

        </div>

        <!-- Trends -->
        <div class="grid-2">

            <!-- Admissions Trend -->
            <div class="panel">
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
            <div class="panel">
                <div class="panel-header">
                    <span class="panel-title">Admission Status</span>
                </div>
                <div class="panel-body">
                    <div class="chart-container">
                        <canvas id="admissionStatusChart"></canvas>
                    </div>
                </div>
            </div>

        </div>

        <div class="grid-2">

            <!-- Recent Admissions -->
            <div class="panel">

                <div class="panel-header">
                    <span class="panel-title">Recent Admissions</span>
                </div>

                <div class="panel-body" style="padding:0;">

                    <table class="data-table">

                        <thead>
                            <tr>
                                <th>Name</th>
                                <th>Program</th>
                                <th>Date Applied</th>
                                <th>Status</th>
                            </tr>
                        </thead>

                        <tbody>

                        <?php if (!empty($recentAdmissions)): ?>

                            <?php foreach ($recentAdmissions as $row): ?>

                                <?php
                                     $status = strtolower($row['status']);

                                        switch ($status) {
                                            case 'approved':
                                            case 'fully paid':
                                                $badge = 'success';
                                                break;

                                            case 'pending':
                                                $badge = 'pending';
                                                break;

                                            case 'downpayment paid':
                                                $badge = 'info';
                                                break;

                                            case 'rejected':
                                                $badge = 'danger';
                                                break;

                                            default:
                                                $badge = 'secondary';
                                        }

                                ?>

                                <tr>

                                    <td><?= htmlspecialchars($row['student_name']); ?></td>

                                    <td><?= htmlspecialchars($row['program']); ?></td>

                                    <td><?= !empty($row['created_at']) ? date('M d, Y', strtotime($row['created_at'])) : '—'; ?></td>

                                    <td>
                                        <span class="badge badge-<?= $badge; ?>">
                                            <?= htmlspecialchars($row['status']); ?>
                                        </span>
                                    </td>

                                </tr>

                            <?php endforeach; ?>

                        <?php else: ?>

                            <tr>
                                <td colspan="4" style="text-align:center;">
                                    No recent admissions found.
                                </td>
                            </tr>

                        <?php endif; ?>

                        </tbody>

                    </table>

                </div>

            </div>

            <!-- Enrollment Summary -->
            <div class="panel">

                <div class="panel-header">
                    <span class="panel-title">Enrollment Summary</span>
                </div>

                <div class="panel-body" style="padding:0;">

                    <table class="data-table">

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

    </main>

</div>

<script>
document.addEventListener('DOMContentLoaded', function () {

  Chart.defaults.font.family = "'Segoe UI', Roboto, Arial, sans-serif";
  Chart.defaults.color = '#6b7280';

  new Chart(document.getElementById('admissionsTrendChart'), {
    type: 'bar',
    data: {
      labels: <?= json_encode($admissionsTrendLabels) ?>,
      datasets: [{
        label: 'Applicants',
        data: <?= json_encode($admissionsTrendData) ?>,
        backgroundColor: 'rgba(232,168,32,0.55)',
        borderColor: '#c8911a',
        borderWidth: 1.5,
        borderRadius: 6,
        maxBarThickness: 42,
      }],
    },
    options: {
      responsive: true,
      maintainAspectRatio: false,
      plugins: {
        legend: { display: false },
        tooltip: {
          backgroundColor: '#1a2340',
          padding: 10,
          cornerRadius: 6,
          titleFont: { weight: '600' },
        },
      },
      scales: {
        y: { beginAtZero: true, ticks: { precision: 0 }, grid: { color: '#f0e8d8' } },
        x: { grid: { display: false } },
      },
    },
  });

  const statusLabels = <?= json_encode(array_column($admissionStatusBreakdown, 'status')) ?>;
  const statusData   = <?= json_encode(array_map('intval', array_column($admissionStatusBreakdown, 'total'))) ?>;
  const statusColors = {
    'Pending': '#3b82f6',
    'Approved': '#16a34a',
    'Rejected': '#dc2626',
    'Downpayment Paid': '#e8a820',
    'Fully Paid': '#7c3aed',
  };

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
        legend: { position: 'bottom', labels: { boxWidth: 12, padding: 14 } },
        tooltip: { backgroundColor: '#1a2340', padding: 10, cornerRadius: 6 },
      },
      cutout: '62%',
    },
  });

});
</script>

<?php include 'Include/footer.php'; ?>
