<?php
$pageTitle  = "DASHBOARD";
$activePage = "dashboard";
$pageScript = "dashboard";

require_once '../../../Backend/auth.php';
require_once '../../../Backend/require_role.php';
require_role(['Registrar Staff']);
require_once '../../../Backend/db.php';

$db   = new Database();
$conn = $db->connect();

$isHead = current_user_is(['Head Registrar']);

$totalCourses  = (int)($conn->query("SELECT COUNT(*) c FROM course")->fetch_assoc()['c'] ?? 0);
$totalSections = (int)($conn->query("SELECT COUNT(*) c FROM section")->fetch_assoc()['c'] ?? 0);
$totalApproved = (int)($conn->query("SELECT COUNT(*) c FROM schedule WHERE status = 'Approved'")->fetch_assoc()['c'] ?? 0);
$totalPending  = (int)($conn->query("SELECT COUNT(*) c FROM schedule WHERE status = 'Pending'")->fetch_assoc()['c'] ?? 0);

/* ==========================
   Approvals breakdown — Pending / Approved / Rejected
   across the three approval-gated tables
========================== */
function status_counts(mysqli $conn, string $table): array
{
    $counts = ['Pending' => 0, 'Approved' => 0, 'Rejected' => 0];
    $result = $conn->query("SELECT status, COUNT(*) AS total FROM $table GROUP BY status");
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $counts[$row['status']] = (int)$row['total'];
        }
    }
    return $counts;
}

$courseCounts   = status_counts($conn, 'course');
$sectionCounts  = status_counts($conn, 'section');
$scheduleCounts = status_counts($conn, 'schedule');

/* ==========================
   Schedule load by day — approved classes only
========================== */
$dayOrder = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'];
$scheduleByDay = array_fill_keys($dayOrder, 0);

$result = $conn->query("SELECT day, COUNT(*) AS total FROM schedule WHERE status = 'Approved' GROUP BY day");
if ($result) {
    while ($row = $result->fetch_assoc()) {
        if (isset($scheduleByDay[$row['day']])) {
            $scheduleByDay[$row['day']] = (int)$row['total'];
        }
    }
}

/* ==========================
   Recent schedule submissions
   (course/section have no timestamp column, so "recent" only
   applies to schedules)
========================== */
$recentSchedules = [];

$result = $conn->query("
    SELECT sch.status, sch.created_at, sch.day, sch.time_start, sch.time_end,
           sub.subject_code, sub.subject_name,
           sec.section_name, c.course_code,
           CONCAT(u.first_name, ' ', u.last_name) AS requested_by_name
    FROM schedule sch
    JOIN subject sub ON sub.subject_id = sch.subject_id
    JOIN section sec ON sec.section_id = sch.section_id
    JOIN course c     ON c.course_id    = sec.course_id
    LEFT JOIN users u ON u.user_id = sch.requested_by
    ORDER BY sch.created_at DESC
    LIMIT 6
");
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $recentSchedules[] = $row;
    }
}

/* ==========================
   Head Registrar only — Registrar Staff accounts
========================== */
$registrarStaff = [];
if ($isHead) {
    $result = $conn->query("
        SELECT CONCAT(u.first_name, ' ', u.last_name) AS full_name,
               u.username, u.email, s.status_name, u.last_login
        FROM users u
        JOIN roles r     ON r.role_id = u.role_id
        JOIN statuses s  ON s.status_id = u.status_id
        WHERE r.role_name = 'Registrar Staff'
        ORDER BY u.first_name, u.last_name
    ");
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $registrarStaff[] = $row;
        }
    }
}

$db->close();

include 'Include/header.php';
?>

<div class="app-layout">

  <?php include 'Include/sidebar.php'; ?>

  <main class="page-content">

    <div class="stats-grid">
      <div class="stat-card">
        <div class="stat-icon purple">📘</div>
        <div>
          <div class="stat-value"><?= $totalCourses ?></div>
          <div class="stat-label">Courses</div>
        </div>
      </div>
      <div class="stat-card">
        <div class="stat-icon blue">🏫</div>
        <div>
          <div class="stat-value"><?= $totalSections ?></div>
          <div class="stat-label">Sections</div>
        </div>
      </div>
      <div class="stat-card">
        <div class="stat-icon green">✅</div>
        <div>
          <div class="stat-value"><?= $totalApproved ?></div>
          <div class="stat-label">Approved Schedules</div>
        </div>
      </div>
      <div class="stat-card">
        <div class="stat-icon gold">⏳</div>
        <div>
          <div class="stat-value"><?= $totalPending ?></div>
          <div class="stat-label"><?= $isHead ? 'Awaiting Your Approval' : 'Your Pending Submissions' ?></div>
        </div>
      </div>
    </div>

    <div class="grid-2">

      <div class="panel">
        <div class="panel-header">
          <span class="panel-title">Approvals Overview</span>
        </div>
        <div class="panel-body">
          <div class="chart-container">
            <canvas id="approvalsChart"></canvas>
          </div>
        </div>
      </div>

      <div class="panel">
        <div class="panel-header">
          <span class="panel-title">Approved Class Load by Day</span>
        </div>
        <div class="panel-body">
          <div class="chart-container">
            <canvas id="dayLoadChart"></canvas>
          </div>
        </div>
      </div>

    </div>

    <div class="grid-2">

      <div class="panel">
        <div class="panel-header">
          <span class="panel-title">Recent Schedule Submissions</span>
        </div>
        <div class="panel-body" style="padding:16px 24px 0;">
          <div class="filter-bar">
            <input type="text" class="form-input" id="scheduleSearch" placeholder="Search subject, section, or submitter…">
            <div class="select-wrapper">
              <select class="form-input form-select" id="scheduleStatusFilter">
                <option value="">All Statuses</option>
                <option value="Pending">Pending</option>
                <option value="Approved">Approved</option>
                <option value="Rejected">Rejected</option>
              </select>
            </div>
          </div>
        </div>
        <div class="panel-body" style="padding:0;">
          <table class="data-table">
            <thead>
              <tr>
                <th>Subject / Section</th>
                <th>Day / Time</th>
                <th>Submitted By</th>
                <th>Status</th>
              </tr>
            </thead>
            <tbody id="recentSchedulesBody">
              <?php if (!empty($recentSchedules)): ?>
                <?php foreach ($recentSchedules as $row): ?>
                  <?php
                    $scheduleSearchKey = strtolower($row['subject_code'] . ' ' . $row['subject_name'] . ' '
                        . $row['course_code'] . ' ' . $row['section_name'] . ' ' . ($row['requested_by_name'] ?? ''));
                  ?>
                  <tr data-status="<?= htmlspecialchars($row['status']) ?>" data-search="<?= htmlspecialchars($scheduleSearchKey) ?>">
                    <td>
                      <?= htmlspecialchars($row['subject_code'] . ' — ' . $row['subject_name']) ?>
                      <div class="text-muted"><?= htmlspecialchars($row['course_code'] . ' ' . $row['section_name']) ?></div>
                    </td>
                    <td><?= htmlspecialchars(substr($row['day'], 0, 3)) ?>, <?= date('g:i A', strtotime($row['time_start'])) ?>–<?= date('g:i A', strtotime($row['time_end'])) ?></td>
                    <td><?= htmlspecialchars($row['requested_by_name'] ?? '—') ?></td>
                    <td><span class="status-pill status-pill--<?= strtolower($row['status']) ?>"><?= htmlspecialchars($row['status']) ?></span></td>
                  </tr>
                <?php endforeach; ?>
              <?php else: ?>
                <tr><td colspan="4" style="text-align:center;">No schedule submissions yet.</td></tr>
              <?php endif; ?>
            </tbody>
          </table>
          <div class="empty-state" id="scheduleEmptyState" style="display:none;">
            <p>No submissions match your filters.</p>
          </div>
        </div>
      </div>

      <?php if ($isHead): ?>
      <div class="panel">
        <div class="panel-header">
          <span class="panel-title">Registrar Staff Accounts</span>
          <span class="text-muted" style="font-size:12px;"><?= count($registrarStaff) ?> account<?= count($registrarStaff) === 1 ? '' : 's' ?></span>
        </div>
        <div class="panel-body" style="padding:16px 24px 0;">
          <div class="filter-bar">
            <input type="text" class="form-input" id="staffSearch" placeholder="Search name, username, or email…">
            <div class="select-wrapper">
              <select class="form-input form-select" id="staffStatusFilter">
                <option value="">All Statuses</option>
                <option value="Active">Active</option>
                <option value="Inactive">Inactive</option>
              </select>
            </div>
          </div>
        </div>
        <div class="panel-body" style="padding:0;">
          <table class="data-table">
            <thead>
              <tr>
                <th>Name</th>
                <th>Username</th>
                <th>Status</th>
                <th>Last Login</th>
              </tr>
            </thead>
            <tbody id="registrarStaffBody">
              <?php if (!empty($registrarStaff)): ?>
                <?php foreach ($registrarStaff as $row): ?>
                  <?php
                    $badge = strtolower($row['status_name']) === 'active' ? 'success' : 'pending';
                    $staffSearchKey = strtolower($row['full_name'] . ' ' . $row['username'] . ' ' . $row['email']);
                  ?>
                  <tr data-status="<?= htmlspecialchars($row['status_name']) ?>" data-search="<?= htmlspecialchars($staffSearchKey) ?>">
                    <td>
                      <?= htmlspecialchars($row['full_name']) ?>
                      <div class="text-muted"><?= htmlspecialchars($row['email']) ?></div>
                    </td>
                    <td><?= htmlspecialchars($row['username']) ?></td>
                    <td><span class="badge badge-<?= $badge ?>"><?= htmlspecialchars($row['status_name']) ?></span></td>
                    <td><?= !empty($row['last_login']) ? date('M d, Y h:i A', strtotime($row['last_login'])) : 'Never' ?></td>
                  </tr>
                <?php endforeach; ?>
              <?php else: ?>
                <tr><td colspan="4" style="text-align:center;">No Registrar Staff accounts found.</td></tr>
              <?php endif; ?>
            </tbody>
          </table>
          <div class="empty-state" id="staffEmptyState" style="display:none;">
            <p>No accounts match your filters.</p>
          </div>
        </div>
      </div>
      <?php else: ?>
      <div class="panel">
        <div class="panel-header">
          <span class="panel-title">Quick Links</span>
        </div>
        <div class="panel-body">
          <p><a href="courses.php">Manage Courses &amp; Sections</a></p>
          <p><a href="schedule.php">View / Add Class Schedules</a></p>
        </div>
      </div>
      <?php endif; ?>

    </div>

  </main>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {

  Chart.defaults.font.family = "'Segoe UI', Roboto, Arial, sans-serif";
  Chart.defaults.color = '#6b7280';

  new Chart(document.getElementById('approvalsChart'), {
    type: 'bar',
    data: {
      labels: ['Courses', 'Sections', 'Schedules'],
      datasets: [
        {
          label: 'Pending',
          data: [<?= $courseCounts['Pending'] ?>, <?= $sectionCounts['Pending'] ?>, <?= $scheduleCounts['Pending'] ?>],
          backgroundColor: '#e8a820',
          borderRadius: 5,
        },
        {
          label: 'Approved',
          data: [<?= $courseCounts['Approved'] ?>, <?= $sectionCounts['Approved'] ?>, <?= $scheduleCounts['Approved'] ?>],
          backgroundColor: '#16a34a',
          borderRadius: 5,
        },
        {
          label: 'Rejected',
          data: [<?= $courseCounts['Rejected'] ?>, <?= $sectionCounts['Rejected'] ?>, <?= $scheduleCounts['Rejected'] ?>],
          backgroundColor: '#dc2626',
          borderRadius: 5,
        },
      ],
    },
    options: {
      responsive: true,
      maintainAspectRatio: false,
      plugins: {
        legend: { position: 'bottom', labels: { boxWidth: 12, padding: 14 } },
        tooltip: { backgroundColor: '#1a2340', padding: 10, cornerRadius: 6 },
      },
      scales: {
        y: { beginAtZero: true, ticks: { precision: 0 }, grid: { color: '#f0e8d8' } },
        x: { grid: { display: false } },
      },
    },
  });

  new Chart(document.getElementById('dayLoadChart'), {
    type: 'bar',
    data: {
      labels: <?= json_encode(array_keys($scheduleByDay)) ?>,
      datasets: [{
        label: 'Classes',
        data: <?= json_encode(array_values($scheduleByDay)) ?>,
        backgroundColor: 'rgba(59,130,246,0.55)',
        borderColor: '#3b82f6',
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
        tooltip: { backgroundColor: '#1a2340', padding: 10, cornerRadius: 6 },
      },
      scales: {
        y: { beginAtZero: true, ticks: { precision: 0 }, grid: { color: '#f0e8d8' } },
        x: { grid: { display: false } },
      },
    },
  });

});
</script>

<?php include 'Include/footer.php'; ?>
