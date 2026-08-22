<?php
$pageTitle  = "ADMISSION";
$activePage = "admission";

require_once '../../../Backend/auth.php';
require_once '../../../Backend/roles.php';
require_once '../../../Backend/require_role.php';
require_role([ROLE_ADMISSION, ROLE_ADMIN]);
require_once __DIR__ . '/../../../Backend/admin/admission.php';

include '../Include/header.php';
?>

<div class="app-layout">

    <?php include '../Include/sidebar.php'; ?>

    <main class="page-content">
        <?php include '../Include/readonly_banner.php'; ?>

        <div class="panel">

            <div class="panel-header">
                <span class="panel-title">All Admission Records</span>
            </div>

            <div class="panel-body" style="padding:0;">
                <div class="table-responsive">
                <table class="data-table rd-table-stagger" id="admissionTable">
                    <thead>
                        <tr>
                            <th>Reference ID</th>
                            <th>Applicant Name</th>
                            <th>Program</th>
                            <th>Application Date</th>
                            <th>Status</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($admissions as $rowIndex => $row): ?>
                            <tr style="--row-i: <?= min((int)$rowIndex, 12) ?>;">
                                <td><?= htmlspecialchars($row['reference_id']) ?></td>
                                <td><?= htmlspecialchars($row['applicant_name']) ?></td>
                                <td><?= htmlspecialchars($row['program']) ?></td>
                                <td><?= date('M d, Y', strtotime($row['created_at'])) ?></td>
                                <td><?= htmlspecialchars($row['status']) ?></td>
                                <td>
                                    <a href="admission_confirm.php?ref=<?= urlencode($row['reference_id']) ?>" class="btn btn-outline" style="padding:4px 10px;font-size:12px">
                                        Review
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                </div>
            </div>

        </div>

    </main>

</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
  initDataTable('#admissionTable', { order: [[3, 'desc']] });
});
</script>

<?php include '../Include/footer.php'; ?>
