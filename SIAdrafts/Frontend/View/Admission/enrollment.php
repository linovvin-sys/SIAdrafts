<?php
$pageTitle  = "ENROLLMENT";
$activePage = "enrollment";

require_once '../../../Backend/auth.php';
require_once '../../../Backend/roles.php';
require_once '../../../Backend/require_role.php';
require_role([ROLE_STAFF, ROLE_ADMIN]);
$isAdminViewer = current_user_is(['Admin']);

// Show success flash if returning from a completed enrollment
$enrolled_ref = isset($_GET['enrolled'], $_GET['ref']) ? (int)$_GET['ref'] : null;

// Quick stat strip so the search screen isn't just a lone card in empty
// space -- gives staff useful context (today's activity) while they type.
require_once __DIR__ . '/../../../Backend/db.php';
$db   = new Database();
$conn = $db->connect();
$quickStats = ['today' => 0, 'pending_payment' => 0, 'total' => 0];
$r = $conn->query("SELECT COUNT(*) AS c FROM enrollment WHERE DATE(created_at) = CURDATE()");
if ($r) $quickStats['today'] = (int)$r->fetch_assoc()['c'];
$r = $conn->query("SELECT COUNT(*) AS c FROM payment WHERE payment_status != 'Fully Paid'");
if ($r) $quickStats['pending_payment'] = (int)$r->fetch_assoc()['c'];
$r = $conn->query("SELECT COUNT(*) AS c FROM enrollment");
if ($r) $quickStats['total'] = (int)$r->fetch_assoc()['c'];

// Verified applicants who don't have a student record yet — i.e. cleared
// admission but never actually enrolled. Browsable list so staff don't
// have to already know a reference ID to start someone's enrollment.
$readyToEnroll = [];
$r = $conn->query("
    SELECT a.reference_id, a.first_name, a.last_name, a.program, a.year_level, a.verified_at
    FROM applicants a
    LEFT JOIN student s ON s.applicant_id = a.applicant_id
    WHERE a.admission_status = 'verified' AND s.student_id IS NULL
    ORDER BY a.verified_at ASC
");
if ($r) {
    while ($row = $r->fetch_assoc()) {
        $readyToEnroll[] = $row;
    }
}

// Already-enrolled students -- this page previously only ever showed who
// was still waiting to be enrolled, with no way to see anyone past that
// point without going to Registrar (whose student_profile.php is gated to
// Registrar roles, not Staff/Admission). Same query/filter-bar pattern as
// Registrar's own enrollment.php (Backend/admin/enrollment.php), copied in
// directly rather than reused -- that file's require_role() would reject
// Staff/Admission outright, and this page already has its own open $conn.
$enrolledStudents = [];
$sql = "
    SELECT
        s.student_no,
        CONCAT(s.first_name, ' ', s.last_name) AS student_name,
        c.course_code,
        c.course_name,
        sec.section_name,
        e.year_level,
        e.semester,
        COALESCE(p.payment_status, 'No Payment') AS payment_status,
        e.status
    FROM enrollment e
    INNER JOIN student s ON s.applicant_id = e.applicant_id
    INNER JOIN applicants a ON a.applicant_id = e.applicant_id
    LEFT JOIN section sec ON sec.section_id = s.section_id
    LEFT JOIN course c ON c.course_id = sec.course_id
    LEFT JOIN payment p ON p.enrollment_id = e.enrollment_id
    ORDER BY a.reference_id DESC
";
$r = $conn->query($sql);
if ($r) {
    while ($row = $r->fetch_assoc()) {
        $enrolledStudents[] = $row;
    }
}

function ordinal_year_label(int $n): string {
    $suffix = $n === 1 ? 'st' : ($n === 2 ? 'nd' : ($n === 3 ? 'rd' : 'th'));
    return $n . $suffix . ' Year';
}

$enrolledYearOptions = [];
$enrolledSemOptions  = [];
$enrolledSectionOptions = [];
foreach ($enrolledStudents as $row) {
    if (!empty($row['year_level'])) $enrolledYearOptions[(int)$row['year_level']] = true;
    if (!empty($row['semester']))   $enrolledSemOptions[(int)$row['semester']] = true;
    if (!empty($row['section_name'])) $enrolledSectionOptions[$row['section_name']] = true;
}
ksort($enrolledYearOptions);
ksort($enrolledSemOptions);
ksort($enrolledSectionOptions);

$db->close();

include '../Include/header.php';
?>

<div class="app-layout">

<?php include '../Include/sidebar.php'; ?>

<main class="page-content">
    <?php include '../Include/readonly_banner.php'; ?>

    <?php if ($enrolled_ref): ?>
    <div class="alert-box alert-success" style="margin-bottom:24px;">
        <i class="bi bi-check-circle-fill"></i>
        Enrollment #<?= $enrolled_ref ?> saved successfully. You can enroll another student below.
    </div>
    <?php endif; ?>

    <div class="stats-grid">
        <div class="stat-card">
            <div class="stat-icon blue"><i class="bi bi-check-circle-fill"></i></div>
            <div>
                <div class="stat-value"><?= $quickStats['today'] ?></div>
                <div class="stat-label">Enrolled Today</div>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon gold"><i class="bi bi-hourglass-split"></i></div>
            <div>
                <div class="stat-value"><?= $quickStats['pending_payment'] ?></div>
                <div class="stat-label">Pending Payment</div>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon green"><i class="bi bi-mortarboard-fill"></i></div>
            <div>
                <div class="stat-value"><?= $quickStats['total'] ?></div>
                <div class="stat-label">Total Enrolled</div>
            </div>
        </div>
    </div>

    <div class="panel" style="margin-top:24px;">
        <div class="panel-header">
            <span class="panel-title">Ready to Enroll</span>
            <span class="text-muted" style="font-size:12px;"><?= count($readyToEnroll) ?> verified applicant<?= count($readyToEnroll) === 1 ? '' : 's' ?> awaiting enrollment</span>
        </div>
        <div class="panel-body" style="padding:0;">
            <div class="table-responsive">
            <table class="data-table" id="readyToEnrollTable">
                <thead>
                    <tr>
                        <th>Reference ID</th>
                        <th>Applicant Name</th>
                        <th>Program</th>
                        <th>Year Level</th>
                        <th>Verified On</th>
                        <?php if (!$isAdminViewer): ?><th style="width:110px;"></th><?php endif; ?>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($readyToEnroll)): ?>
                        <tr><td colspan="6" style="text-align:center;">No verified applicants waiting to enroll right now.</td></tr>
                    <?php else: ?>
                        <?php foreach ($readyToEnroll as $row): ?>
                            <tr>
                                <td class="mono"><?= htmlspecialchars($row['reference_id']) ?></td>
                                <td><?= htmlspecialchars($row['last_name'] . ', ' . $row['first_name']) ?></td>
                                <td><?= htmlspecialchars($row['program']) ?></td>
                                <td><?= (int)$row['year_level'] ?></td>
                                <td><?= !empty($row['verified_at']) ? date('M d, Y', strtotime($row['verified_at'])) : '—' ?></td>
                                <?php if (!$isAdminViewer): ?>
                                <td>
                                    <a href="enrollment_profile.php?reference_id=<?= urlencode($row['reference_id']) ?>" class="btn btn-outline" style="padding:4px 10px;font-size:12px">
                                        Enroll
                                    </a>
                                </td>
                                <?php endif; ?>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
            </div>
        </div>
    </div>

    <div class="panel" style="margin-top:24px;">
        <div class="panel-header">
            <span class="panel-title">Enrolled Students</span>
            <span class="text-muted" style="font-size:12px;"><?= count($enrolledStudents) ?> student<?= count($enrolledStudents) === 1 ? '' : 's' ?> enrolled</span>
        </div>

        <div class="panel-body clay-filter-bar" style="padding:16px 24px 0;">
            <div class="filter-bar">
                <div class="select-wrapper">
                    <select class="form-input form-select" id="enrolledStatusFilter">
                        <option value="">All Statuses</option>
                        <option value="Enrolled">Enrolled</option>
                        <option value="Pending Payment">Pending Payment</option>
                    </select>
                </div>
                <div class="select-wrapper">
                    <select class="form-input form-select" id="enrolledYearFilter">
                        <option value="">All Year Levels</option>
                        <?php foreach (array_keys($enrolledYearOptions) as $year): ?>
                            <option value="<?= $year ?>"><?= htmlspecialchars(ordinal_year_label($year)) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="select-wrapper">
                    <select class="form-input form-select" id="enrolledSemFilter">
                        <option value="">All Semesters</option>
                        <?php foreach (array_keys($enrolledSemOptions) as $sem): ?>
                            <option value="<?= $sem ?>"><?= $sem == 1 ? '1st Semester' : ($sem == 2 ? '2nd Semester' : htmlspecialchars("Sem $sem")) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="select-wrapper">
                    <select class="form-input form-select" id="enrolledSectionFilter">
                        <option value="">All Sections</option>
                        <?php foreach (array_keys($enrolledSectionOptions) as $section): ?>
                            <option value="<?= htmlspecialchars($section) ?>"><?= htmlspecialchars($section) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
        </div>

        <div class="panel-body" style="padding:0;">
            <div class="table-responsive">
            <table class="data-table" id="enrolledStudentsTable">
                <thead>
                    <tr>
                        <th>Student ID</th>
                        <th>Name</th>
                        <th>Course</th>
                        <th>Section</th>
                        <th>Year / Sem</th>
                        <th>Payment</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($enrolledStudents)): ?>
                        <tr><td colspan="7" style="text-align:center;">No enrolled students found.</td></tr>
                    <?php else: ?>
                        <?php foreach ($enrolledStudents as $row): ?>
                            <?php
                                $status = strtolower($row['status']);
                                $badge = in_array($status, ['active', 'enrolled'], true) ? 'approved' : (in_array($status, ['pending', 'pending payment'], true) ? 'pending' : '');
                                $enrolledSearchKey = strtolower($row['student_no'] . ' ' . $row['student_name'] . ' ' . $row['course_name'] . ' ' . $row['section_name']);
                            ?>
                            <tr data-status="<?= htmlspecialchars($row['status']) ?>"
                                data-year="<?= htmlspecialchars($row['year_level'] ?? '') ?>"
                                data-sem="<?= htmlspecialchars($row['semester'] ?? '') ?>"
                                data-section="<?= htmlspecialchars($row['section_name'] ?? '') ?>"
                                data-search="<?= htmlspecialchars($enrolledSearchKey) ?>">
                                <td class="mono"><?= htmlspecialchars($row['student_no']) ?></td>
                                <td><?= htmlspecialchars($row['student_name']) ?></td>
                                <td><?= htmlspecialchars($row['course_name']) ?></td>
                                <td><?= htmlspecialchars($row['section_name']) ?></td>
                                <td class="mono"><?= !empty($row['year_level']) ? htmlspecialchars(ordinal_year_label((int)$row['year_level'])) : '—' ?><?= !empty($row['semester']) ? ' · Sem ' . (int)$row['semester'] : '' ?></td>
                                <td><?= htmlspecialchars($row['payment_status']) ?></td>
                                <td><span class="stamp <?= $badge ?>"><?= htmlspecialchars($row['status']) ?></span></td>
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

<?php if (!empty($readyToEnroll)): ?>
<script>
document.addEventListener('DOMContentLoaded', function () {
  initDataTable('#readyToEnrollTable', { order: [[4, 'asc']] });
});
</script>
<?php endif; ?>
<?php if (!empty($enrolledStudents)): ?>
<script>
document.addEventListener('DOMContentLoaded', function () {
  const dt = initDataTable('#enrolledStudentsTable', { order: [] });
  const statusEl  = document.getElementById('enrolledStatusFilter');
  const yearEl    = document.getElementById('enrolledYearFilter');
  const semEl     = document.getElementById('enrolledSemFilter');
  const sectionEl = document.getElementById('enrolledSectionFilter');

  $.fn.dataTable.ext.search.push(function (settings, searchRow, index) {
    if (settings.nTable.id !== 'enrolledStudentsTable') return true;
    const row = dt.row(index).node();
    if (!row) return true;

    const status  = statusEl?.value || '';
    const year    = yearEl?.value || '';
    const sem     = semEl?.value || '';
    const section = sectionEl?.value || '';

    if (status && row.dataset.status !== status) return false;
    if (year && row.dataset.year !== year) return false;
    if (sem && row.dataset.sem !== sem) return false;
    if (section && row.dataset.section !== section) return false;
    return true;
  });

  [statusEl, yearEl, semEl, sectionEl].forEach(el => {
    if (el) el.addEventListener('change', () => dt.draw());
  });
});
</script>
<?php endif; ?>
<?php include '../Include/footer.php'; ?>
