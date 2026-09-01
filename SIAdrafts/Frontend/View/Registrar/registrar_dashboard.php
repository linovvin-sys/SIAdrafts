<?php
$pageTitle  = "DASHBOARD";
$activePage = "dashboard";
$pageScript = "dashboard";

require_once '../../../Backend/auth.php';
require_once '../../../Backend/require_role.php';
require_role(['Registrar Staff', 'Head Registrar', 'Admin']);
require_once '../../../Backend/db.php';

$db   = new Database();
$conn = $db->connect();

$isHead        = current_user_is(['Head Registrar']);
$isAdminViewer = current_user_is(['Admin']);

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

$firstName = explode(' ', trim($_SESSION['full_name'] ?? ''))[0] ?? '';
$hour = (int)date('G');
$greeting = $hour < 12 ? 'Good morning' : ($hour < 18 ? 'Good afternoon' : 'Good evening');

function rd_initials(string $name): string {
    $parts = preg_split('/\s+/', trim($name));
    $letters = '';
    foreach ($parts as $p) {
        if ($p === '') continue;
        $letters .= strtoupper($p[0]);
        if (strlen($letters) >= 2) break;
    }
    return $letters ?: '?';
}

include '../Include/header.php';
?>

<div class="app-layout">

  <?php include '../Include/sidebar.php'; ?>

  <main class="page-content">
    <?php include '../Include/readonly_banner.php'; ?>

    <h1 class="rd-greeting"><?= $greeting ?><?= $firstName ? ', ' . htmlspecialchars($firstName, ENT_QUOTES) : '' ?>.</h1>
    <p class="rd-greeting-sub">
      <?php if ($isAdminViewer): ?>
        <?= $totalPending ?> schedule<?= $totalPending === 1 ? '' : 's' ?> currently pending across the Registrar's Office.
      <?php elseif ($isHead): ?>
        <?= $totalPending > 0
            ? $totalPending . ' schedule' . ($totalPending === 1 ? '' : 's') . ' waiting on your approval.'
            : "You're all caught up — nothing waiting on approval." ?>
      <?php else: ?>
        <?= $totalPending > 0
            ? $totalPending . ' of your submissions ' . ($totalPending === 1 ? 'is' : 'are') . ' still pending.'
            : 'All your submissions have been reviewed.' ?>
      <?php endif; ?>
    </p>

    <?php if ($isHead && !$isAdminViewer && $totalPending > 0): ?>
      <a href="pending_approval.php" class="rd-hero rd-hero--attention">
        <div class="rd-hero-status">
          <span class="rd-hero-dot"></span>
          <span class="rd-hero-status-label">Needs your attention</span>
        </div>
        <p class="rd-hero-figure"><?= $totalPending ?> schedule<?= $totalPending === 1 ? '' : 's' ?> pending approval</p>
        <p class="rd-hero-meta">Review and approve or reject &rarr;</p>
      </a>
    <?php endif; ?>

    <div class="stats-grid">
      <div class="stat-card">
        <div class="stat-icon purple">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round"><path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20"/><path d="M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2z"/></svg>
        </div>
        <div>
          <div class="stat-value"><?= $totalCourses ?></div>
          <div class="stat-label">Courses</div>
        </div>
      </div>
      <div class="stat-card">
        <div class="stat-icon blue">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round"><polygon points="12 2 2 7 12 12 22 7 12 2"/><polyline points="2 17 12 22 22 17"/><polyline points="2 12 12 17 22 12"/></svg>
        </div>
        <div>
          <div class="stat-value"><?= $totalSections ?></div>
          <div class="stat-label">Sections</div>
        </div>
      </div>
      <div class="stat-card">
        <div class="stat-icon green">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>
        </div>
        <div>
          <div class="stat-value"><?= $totalApproved ?></div>
          <div class="stat-label">Approved Schedules</div>
        </div>
      </div>
      <div class="stat-card">
        <div class="stat-icon gold">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
        </div>
        <div>
          <div class="stat-value"><?= $totalPending ?></div>
          <div class="stat-label"><?= $isHead ? 'Awaiting Your Approval' : 'Your Pending Submissions' ?></div>
        </div>
      </div>
    </div>

    <div class="rd-dash-grid">

      <div class="rd-dash-main">

        <div class="grid-2" style="margin-bottom:24px;">
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
              <span class="panel-title">Class Load by Day</span>
            </div>
            <div class="panel-body">
              <div class="chart-container">
                <canvas id="dayLoadChart"></canvas>
              </div>
            </div>
          </div>
        </div>

        <div class="panel">
          <div class="panel-header">
            <span class="panel-title">Recent Schedule Submissions</span>
          </div>
          <div class="panel-body clay-filter-bar" style="padding:16px 24px 0;">
            <div class="filter-bar">
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
            <div class="table-responsive">
            <table class="data-table rd-table-stagger" id="recentSchedulesTable">
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
                  <?php foreach ($recentSchedules as $rowIndex => $row): ?>
                    <?php
                      $scheduleSearchKey = strtolower($row['subject_code'] . ' ' . $row['subject_name'] . ' '
                          . $row['course_code'] . ' ' . $row['section_name'] . ' ' . ($row['requested_by_name'] ?? ''));
                    ?>
                    <tr style="--row-i: <?= min((int)$rowIndex, 12) ?>;" data-status="<?= htmlspecialchars($row['status']) ?>" data-search="<?= htmlspecialchars($scheduleSearchKey) ?>">
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
            </div>
          </div>
        </div>

      </div>

      <aside class="rd-dash-aside">

        <?php if ($isHead): ?>
        <div class="panel">
          <div class="panel-header">
            <span class="panel-title">Registrar Staff</span>
            <span class="text-muted" style="font-size:12px;"><?= count($registrarStaff) ?></span>
          </div>
          <div class="panel-body">
            <?php if (!empty($registrarStaff)): ?>
              <div class="rd-people-list">
                <?php foreach ($registrarStaff as $row): ?>
                  <div class="rd-people-row">
                    <span class="rd-avatar sky"><?= htmlspecialchars(rd_initials($row['full_name'])) ?></span>
                    <div style="min-width:0; flex:1;">
                      <div class="rd-people-name"><?= htmlspecialchars($row['full_name']) ?></div>
                      <div class="rd-people-meta"><?= !empty($row['last_login']) ? 'Active ' . date('M d', strtotime($row['last_login'])) : 'Never logged in' ?></div>
                    </div>
                    <span class="badge badge-<?= strtolower($row['status_name']) === 'active' ? 'success' : 'pending' ?>"><?= htmlspecialchars($row['status_name']) ?></span>
                  </div>
                <?php endforeach; ?>
              </div>
            <?php else: ?>
              <p class="text-muted" style="font-size:13px;">No Registrar Staff accounts found.</p>
            <?php endif; ?>
          </div>
        </div>
        <?php endif; ?>

        <div class="panel">
          <div class="panel-header">
            <span class="panel-title">Quick Links</span>
          </div>
          <div class="panel-body">
            <ul class="rd-quicklinks">
              <li>
                <a href="courses.php">
                  <span class="rd-quicklink-icon"><iconify-icon icon="mdi:book-open-page-variant"></iconify-icon></span>
                  <span>Manage Courses &amp; Sections</span>
                  <iconify-icon icon="mdi:chevron-right" class="rd-quicklink-chevron"></iconify-icon>
                </a>
              </li>
              <li>
                <a href="schedule.php">
                  <span class="rd-quicklink-icon"><iconify-icon icon="mdi:calendar-week"></iconify-icon></span>
                  <span>View / Add Class Schedules</span>
                  <iconify-icon icon="mdi:chevron-right" class="rd-quicklink-chevron"></iconify-icon>
                </a>
              </li>
              <?php if ($isHead && !$isAdminViewer): ?>
              <li>
                <a href="pending_approval.php">
                  <span class="rd-quicklink-icon"><iconify-icon icon="mdi:checkbox-marked-outline"></iconify-icon></span>
                  <span>Review Pending Approvals</span>
                  <iconify-icon icon="mdi:chevron-right" class="rd-quicklink-chevron"></iconify-icon>
                </a>
              </li>
              <?php endif; ?>
            </ul>
          </div>
        </div>

      </aside>

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

<?php
$extraScripts = [
    'https://cdn.jsdelivr.net/npm/vue@3/dist/vue.global.prod.js',
    '/SIAdrafts/Frontend/Js/Registrar/' . ($pageScript ?? 'registrar') . '.js',
];
include '../Include/footer.php';
?>
