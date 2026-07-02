<?php

$pageTitle = "ENROLLMENT";
$activePage = "enrollment";

require_once '../../../Backend/auth.php';
require_once __DIR__ . '/../../../Backend/admin/enrollment.php';


include 'Include/header.php';

?>

<div class="app-layout">

    <?php include 'Include/sidebar.php'; ?>

    <main class="page-content">

        <!-- Statistics -->
        <div class="stats-grid" style="grid-template-columns:repeat(3,1fr);margin-bottom:24px;">

            <div class="stat-card">
                <div class="stat-icon gold">📝</div>
                <div>
                    <div class="stat-value"><?= $dashboard['enrolled']; ?></div>
                    <div class="stat-label">Total Enrolled</div>
                </div>
            </div>

            <div class="stat-card">
                <div class="stat-icon blue">⏳</div>
                <div>
                    <div class="stat-value"><?= $dashboard['pending_payment']; ?></div>
                    <div class="stat-label">Awaiting Payment</div>
                </div>
            </div>

            <div class="stat-card">
                <div class="stat-icon green">✅</div>
                <div>
                    <div class="stat-value"><?= $dashboard['fully_enrolled']; ?></div>
                    <div class="stat-label">Fully Enrolled</div>
                </div>
            </div>

        </div>

        <!-- Enrollment List -->
        <div class="panel">

            <div class="panel-header">
                <span class="panel-title">Enrollment List</span>
            </div>

            <div class="panel-body" style="padding:0;">

                <table class="data-table">

                    <thead>
                        <tr>
                            <th>Student ID</th>
                            <th>Name</th>
                            <th>Course</th>
                            <th>Section</th>
                            <th>Payment</th>
                            <th>Status</th>
                        </tr>
                    </thead>

                    <tbody>

                    <?php if (!empty($enrollmentList)): ?>

                        <?php foreach ($enrollmentList as $row): ?>

                            <?php

                            $status = strtolower($row['status']);

                            switch ($status) {

                                case 'active':
                                case 'enrolled':
                                    $badge = 'success';
                                    break;

                                case 'pending':
                                case 'pending payment':
                                    $badge = 'pending';
                                    break;

                                default:
                                    $badge = 'secondary';
                            }

                            ?>

                            <tr>

                                <td><?= htmlspecialchars($row['student_id']); ?></td>

                                <td><?= htmlspecialchars($row['student_name']); ?></td>

                                <td><?= htmlspecialchars($row['course_name']); ?></td>

                                <td><?= htmlspecialchars($row['section_name']); ?></td>

                                <td><?= htmlspecialchars($row['payment_status']); ?></td>

                                <td>
                                    <span class="badge badge-<?= $badge; ?>">
                                        <?= htmlspecialchars($row['status']); ?>
                                    </span>
                                </td>

                            </tr>

                        <?php endforeach; ?>

                    <?php else: ?>

                        <tr>
                            <td colspan="6" style="text-align:center;">
                                No enrolled students found.
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