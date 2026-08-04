<?php
$pageTitle  = "ENROLLMENT";
$activePage = "enrollment";

require_once '../../../Backend/auth.php';
require_once '../../../Backend/roles.php';
require_once '../../../Backend/require_role.php';
require_role([ROLE_STAFF, ROLE_ADMIN]);

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

$db->close();

include '../Include/header.php';
?>

<div class="app-layout">

<?php include '../Include/sidebar.php'; ?>

<main class="page-content">

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
                        <th style="width:110px;"></th>
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
                                <td>
                                    <a href="enrollment_profile.php?reference_id=<?= urlencode($row['reference_id']) ?>" class="btn btn-outline" style="padding:4px 10px;font-size:12px">
                                        Enroll
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

<?php if (!empty($readyToEnroll)): ?>
<script>
document.addEventListener('DOMContentLoaded', function () {
  initDataTable('#readyToEnrollTable', { order: [[4, 'asc']] });
});
</script>
<?php endif; ?>
<?php include '../Include/footer.php'; ?>
