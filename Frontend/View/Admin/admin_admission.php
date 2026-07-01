<?php
$pageTitle = "ADMISSION";
$activePage = "admission";

require_once '../../../Backend/auth.php';
require_once __DIR__ . '/../../../Backend/admin/admission.php';

include 'Include/header.php';
?>

<div class="app-layout">

    <?php include 'Include/sidebar.php'; ?>

    <main class="page-content">

        <div class="panel">

            <div class="panel-header">
                <span class="panel-title">All Admission Records</span>
                
            </div>

            <div class="panel-body" style="padding:0;">

                <table class="data-table">

                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Applicant Name</th>
                            <th>Program</th>
                            <th>Application Date</th>
                            <th>Status</th>
                            <th>Action</th>
                        </tr>
                    </thead>

                    <tbody>

                    <?php if (!empty($admissions)): ?>

                        <?php foreach ($admissions as $row): ?>

                            <?php
                                $status = strtolower($row['status']);

                                switch ($status) {
                                    case 'approved':
                                        $badge = 'success';
                                        break;

                                    case 'pending':
                                        $badge = 'pending';
                                        break;

                                    case 'rejected':
                                        $badge = 'danger';
                                        break;

                                    default:
                                        $badge = 'secondary';
                                }
                            ?>

                            <tr>

                                <td><?= $row['student_id']; ?></td>

                                <td><?= htmlspecialchars($row['applicant_name']); ?></td>

                                <td><?= htmlspecialchars($row['program']); ?></td>

                                <td><?= date('M d, Y', strtotime($row['created_at'])); ?></td>

                                <td>
                                    <span class="badge badge-<?= $badge; ?>">
                                        <?= htmlspecialchars($row['status']); ?>
                                    </span>
                                </td>

                                <td>
                                    <a href="admin_admission_view.php?id=<?= $row['student_id']; ?>"
                                       class="btn btn-outline"
                                       style="padding:4px 10px;font-size:12px">
                                        View
                                    </a>
                                </td>

                            </tr>

                        <?php endforeach; ?>

                    <?php else: ?>

                        <tr>
                            <td colspan="6" style="text-align:center;">
                                No admission records found.
                            </td>
                        </tr>

                    <?php endif; ?>

                    </tbody>

                </table>

            </div>

        </div>

    </main>

</div>

<?php include 'Include/footer.php'; ?>