<?php
$pageTitle  = "DASHBOARD";
$activePage = "dashboard";

require_once '../../../Backend/auth.php';
require_once '../../../Backend/roles.php';
require_once '../../../Backend/require_role.php';
require_role([ROLE_ADMISSION, ROLE_STAFF, ROLE_ADMIN]);
require_once '../../../Backend/db.php';
require_once '../../../Backend/settings.php';

$db   = new Database();
$conn = $db->connect();

// Admission and Staff share this file (both land here after login) but do
// different jobs — Admission verifies applicants, Staff processes
// enrollment — so the stats/trend/table below are branched per role rather
// than showing the same (mostly irrelevant to one side) numbers to both.
$isStaff = current_user_is([ROLE_STAFF]);

// Status pills reuse registrar.css's existing pending/approved/rejected
// palette instead of inventing new colors for admission_status/enrollment
// status strings.
function dash_pill_class(string $status): string {
    return match ($status) {
        'verified', 'Enrolled' => 'approved',
        'pending_verification', 'Pending Payment' => 'pending',
        default => 'rejected',
    };
}

// Last 14 days, oldest first, zero-filled so the trend line doesn't skip
// days with no activity.
function dash_last_14_days(mysqli $conn, string $table, string $dateCol, string $extraWhere = ''): array {
    $days = [];
    for ($i = 13; $i >= 0; $i--) {
        $days[date('Y-m-d', strtotime("-$i days"))] = 0;
    }
    $sql = "SELECT DATE($dateCol) AS d, COUNT(*) AS cnt FROM $table
            WHERE $dateCol >= CURDATE() - INTERVAL 13 DAY" . ($extraWhere ? " AND $extraWhere" : '') . "
            GROUP BY DATE($dateCol)";
    $res = $conn->query($sql);
    if ($res) {
        while ($row = $res->fetch_assoc()) {
            if (isset($days[$row['d']])) $days[$row['d']] = (int)$row['cnt'];
        }
    }
    return $days;
}

if ($isStaff) {

    $currentSchoolYear = get_setting('current_school_year') ?? '';
    $currentSemester   = get_setting('current_semester') ?? '';

    $stats = ['enrolled_today' => 0, 'pending_payment' => 0, 'enrolled_this_term' => 0];

    $res = $conn->query("SELECT COUNT(*) AS cnt FROM enrollment WHERE DATE(created_at) = CURDATE()");
    $stats['enrolled_today'] = (int)($res->fetch_assoc()['cnt'] ?? 0);

    $res = $conn->query("SELECT COUNT(*) AS cnt FROM enrollment WHERE status = 'Pending Payment'");
    $stats['pending_payment'] = (int)($res->fetch_assoc()['cnt'] ?? 0);

    if ($currentSchoolYear !== '' && $currentSemester !== '') {
        $stmt = $conn->prepare("SELECT COUNT(*) AS cnt FROM enrollment WHERE status = 'Enrolled' AND school_year = ? AND semester = ?");
        $stmt->bind_param('si', $currentSchoolYear, $currentSemester);
        $stmt->execute();
        $stats['enrolled_this_term'] = (int)($stmt->get_result()->fetch_assoc()['cnt'] ?? 0);
        $stmt->close();
    }

    $trend = dash_last_14_days($conn, 'enrollment', 'created_at');

    $recent = [];
    $res = $conn->query("
        SELECT e.status, e.school_year, e.semester, e.year_level, e.created_at,
               a.first_name, a.last_name,
               COALESCE(s.student_no, a.reference_id) AS display_id,
               c.course_code, sec.section_name
        FROM enrollment e
        JOIN applicants a  ON a.applicant_id = e.student_id
        LEFT JOIN student s ON s.applicant_id = a.applicant_id
        LEFT JOIN section sec ON sec.section_id = e.section_id
        LEFT JOIN course c ON c.course_id = a.course_id
        ORDER BY e.created_at DESC
        LIMIT 8
    ");
    if ($res) while ($row = $res->fetch_assoc()) $recent[] = $row;

} else {

    $stats = ['pending_review' => 0, 'verified_today' => 0, 'possible_duplicates' => 0];

    $res = $conn->query("SELECT COUNT(*) AS cnt FROM applicants WHERE admission_status = 'pending_verification'");
    $stats['pending_review'] = (int)($res->fetch_assoc()['cnt'] ?? 0);

    $res = $conn->query("SELECT COUNT(*) AS cnt FROM applicants WHERE admission_status = 'verified' AND DATE(verified_at) = CURDATE()");
    $stats['verified_today'] = (int)($res->fetch_assoc()['cnt'] ?? 0);

    $res = $conn->query("SELECT COUNT(*) AS cnt FROM applicants WHERE duplicate_match_status = 'pending_review'");
    $stats['possible_duplicates'] = (int)($res->fetch_assoc()['cnt'] ?? 0);

    $trend = dash_last_14_days($conn, 'applicants', 'created_at');

    $recent = [];
    $res = $conn->query("
        SELECT reference_id, first_name, last_name, program, admission_status, created_at
        FROM applicants
        ORDER BY created_at DESC
        LIMIT 8
    ");
    if ($res) while ($row = $res->fetch_assoc()) $recent[] = $row;
}

$db->close();

include '../Include/header.php';
?>

<div class="app-layout">

    <?php include '../Include/sidebar.php'; ?>

    <main class="page-content">

        <div class="stats-grid">
            <?php if ($isStaff): ?>
                <div class="stat-card">
                    <div class="stat-icon blue"><i class="bi bi-journal-plus"></i></div>
                    <div>
                        <div class="stat-value"><?= $stats['enrolled_today'] ?></div>
                        <div class="stat-label">Enrolled Today</div>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon gold"><i class="bi bi-hourglass-split"></i></div>
                    <div>
                        <div class="stat-value"><?= $stats['pending_payment'] ?></div>
                        <div class="stat-label">Pending Payment</div>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon green"><i class="bi bi-mortarboard"></i></div>
                    <div>
                        <div class="stat-value"><?= $stats['enrolled_this_term'] ?></div>
                        <div class="stat-label">Enrolled This Term</div>
                    </div>
                </div>
            <?php else: ?>
                <div class="stat-card">
                    <div class="stat-icon gold"><i class="bi bi-hourglass-split"></i></div>
                    <div>
                        <div class="stat-value"><?= $stats['pending_review'] ?></div>
                        <div class="stat-label">Pending Review</div>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon green"><i class="bi bi-check-circle"></i></div>
                    <div>
                        <div class="stat-value"><?= $stats['verified_today'] ?></div>
                        <div class="stat-label">Verified Today</div>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon purple"><i class="bi bi-people"></i></div>
                    <div>
                        <div class="stat-value"><?= $stats['possible_duplicates'] ?></div>
                        <div class="stat-label">Possible Returning Students</div>
                    </div>
                </div>
            <?php endif; ?>
        </div>

        <div class="grid-2">

            <div class="panel">
                <div class="panel-header">
                    <span class="panel-title"><?= $isStaff ? 'Enrollment Trend' : 'Application Trend' ?></span>
                    <span class="text-muted" style="font-size:12px;">Last 14 days</span>
                </div>
                <div class="panel-body">
                    <div class="chart-container">
                        <canvas id="trendChart"></canvas>
                    </div>
                </div>
            </div>

            <div class="panel">
                <div class="panel-header">
                    <span class="panel-title">Quick Actions</span>
                </div>
                <div class="panel-body">
                    <?php if ($isStaff): ?>
                        <p><a href="enrollment.php">Look up a student &amp; process enrollment &rarr;</a></p>
                        <p><a href="total_enrolees.php">View total enrolees report &rarr;</a></p>
                        <?php if ($stats['pending_payment'] > 0): ?>
                            <p class="text-muted"><?= $stats['pending_payment'] ?> enrollment<?= $stats['pending_payment'] === 1 ? '' : 's' ?> still awaiting payment at Treasury.</p>
                        <?php endif; ?>
                    <?php else: ?>
                        <p><a href="admission.php">Review pending applicants &rarr;</a></p>
                        <p><a href="total_enrolees.php">View total enrolees report &rarr;</a></p>
                        <?php if ($stats['pending_review'] > 0): ?>
                            <p class="text-muted"><?= $stats['pending_review'] ?> applicant<?= $stats['pending_review'] === 1 ? '' : 's' ?> waiting on document verification.</p>
                        <?php endif; ?>
                        <?php if ($stats['possible_duplicates'] > 0): ?>
                            <p class="text-muted"><?= $stats['possible_duplicates'] ?> possible returning student match<?= $stats['possible_duplicates'] === 1 ? '' : 'es' ?> need review.</p>
                        <?php endif; ?>
                    <?php endif; ?>
                </div>
            </div>

        </div>

        <div class="panel">
            <div class="panel-header">
                <span class="panel-title"><?= $isStaff ? 'Recent Enrollments' : 'Recent Applications' ?></span>
            </div>
            <div class="panel-body" style="padding:0;">
                <div class="table-responsive">
                <table class="data-table" id="recentActivityTable">
                    <thead>
                        <?php if ($isStaff): ?>
                            <tr>
                                <th>Student</th>
                                <th>Course / Section</th>
                                <th>School Year / Sem</th>
                                <th>Status</th>
                                <th>Enrolled</th>
                            </tr>
                        <?php else: ?>
                            <tr>
                                <th>Reference ID</th>
                                <th>Applicant Name</th>
                                <th>Program</th>
                                <th>Submitted</th>
                                <th>Status</th>
                                <th>Action</th>
                            </tr>
                        <?php endif; ?>
                    </thead>
                    <tbody>
                        <?php if (empty($recent)): ?>
                            <tr><td colspan="<?= $isStaff ? 5 : 6 ?>" style="text-align:center;">No <?= $isStaff ? 'enrollments' : 'applications' ?> yet.</td></tr>
                        <?php elseif ($isStaff): ?>
                            <?php foreach ($recent as $row): ?>
                                <tr>
                                    <td>
                                        <?= htmlspecialchars($row['last_name'] . ', ' . $row['first_name']) ?>
                                        <div class="text-muted"><?= htmlspecialchars($row['display_id']) ?></div>
                                    </td>
                                    <td><?= htmlspecialchars($row['section_name'] ?? ($row['course_code'] ?? '—')) ?></td>
                                    <td><?= htmlspecialchars($row['school_year']) ?> &middot; Sem <?= (int)$row['semester'] ?></td>
                                    <td><span class="status-pill status-pill--<?= dash_pill_class($row['status']) ?>"><?= htmlspecialchars($row['status']) ?></span></td>
                                    <td><?= date('M d, Y', strtotime($row['created_at'])) ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <?php foreach ($recent as $row): ?>
                                <tr>
                                    <td><?= htmlspecialchars($row['reference_id']) ?></td>
                                    <td><?= htmlspecialchars($row['last_name'] . ', ' . $row['first_name']) ?></td>
                                    <td><?= htmlspecialchars($row['program']) ?></td>
                                    <td><?= date('M d, Y', strtotime($row['created_at'])) ?></td>
                                    <td><span class="status-pill status-pill--<?= dash_pill_class($row['admission_status']) ?>"><?= htmlspecialchars($row['admission_status']) ?></span></td>
                                    <td>
                                        <a href="admission_confirm.php?ref=<?= urlencode($row['reference_id']) ?>" class="btn btn-outline" style="padding:4px 10px;font-size:12px">
                                            Review
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
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

  initDataTable('#recentActivityTable', { order: [], paging: false, info: false });

  Chart.defaults.font.family = "'Segoe UI', Roboto, Arial, sans-serif";
  Chart.defaults.color = '#6b7280';

  new Chart(document.getElementById('trendChart'), {
    type: 'line',
    data: {
      labels: <?= json_encode(array_map(fn($d) => date('M j', strtotime($d)), array_keys($trend))) ?>,
      datasets: [{
        label: '<?= $isStaff ? 'Enrollments' : 'Applications' ?>',
        data: <?= json_encode(array_values($trend)) ?>,
        borderColor: '#3b82f6',
        backgroundColor: 'rgba(59,130,246,0.12)',
        fill: true,
        tension: 0.3,
        pointRadius: 3,
        pointBackgroundColor: '#3b82f6',
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

<?php include '../Include/footer.php'; ?>
