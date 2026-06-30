<?php
$pageTitle = "ADMIN DASHBOARD";
$activePage = "dashboard";

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
                <div class="stat-icon gold">
                    <!-- SVG -->
                </div>
                <div>
                    <div class="stat-value"><?= $dashboard['students']; ?></div>
                    <div class="stat-label">Total Students</div>
                </div>
            </div>

            <!-- Pending Admissions -->
            <div class="stat-card">
                <div class="stat-icon blue">
                    <!-- SVG -->
                </div>
                <div>
                    <div class="stat-value"><?= $dashboard['pending']; ?></div>
                    <div class="stat-label">Pending Admissions</div>
                </div>
            </div>

            <!-- Revenue -->
            <div class="stat-card">
                <div class="stat-icon green">
                    <!-- SVG -->
                </div>
                <div>
                    <div class="stat-value">
                        ₱<?= number_format($dashboard['revenue'], 2); ?>
                    </div>
                    <div class="stat-label">Revenue</div>
                </div>
            </div>

            <!-- Courses -->
            <div class="stat-card">
                <div class="stat-icon purple">
                    <!-- SVG -->
                </div>
                <div>
                    <div class="stat-value"><?= $dashboard['courses']; ?></div>
                    <div class="stat-label">Active Courses</div>
                </div>
            </div>

        </div>

        <div class="grid-2">

            <!-- Recent Admissions -->
            <div class="panel">

                <div class="panel-header">
                    <span class="panel-title">Recent Admissions</span>
                    <a href="admin_admission.php" class="btn btn-outline">
                        View All
                    </a>
                </div>

                <div class="panel-body" style="padding:0;">

                    <table class="data-table">

                        <thead>
                            <tr>
                                <th>Name</th>
                                <th>Program</th>
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

                                    <td><?= htmlspecialchars($row['student_name']); ?></td>

                                    <td><?= htmlspecialchars($row['program']); ?></td>

                                    <td>
                                        <span class="badge badge-<?= $badge; ?>">
                                            <?= htmlspecialchars($row['status']); ?>
                                        </span>
                                    </td>

                                </tr>

                            <?php endforeach; ?>

                        <?php else: ?>

                            <tr>
                                <td colspan="3" style="text-align:center;">
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
                    <a href="admin_enrollment.php" class="btn btn-outline">
                        View All
                    </a>
                </div>

                <div class="panel-body" style="padding:0;">

                    <table class="data-table">

                        <thead>
                        <tr>
                            <th>Course</th>
                            <th>Enrolled</th>
                            <th>Sections</th>
                        </tr>
                        </thead>

                        <tbody>
                        

                       <?php if (!empty($courseSummary)): ?>

                        <?php foreach ($courseSummary as $row): ?>

                        <tr>

                            <td><?= htmlspecialchars($row['course_name']); ?></td>

                            <td><?= $row['enrolled']; ?></td>

                            <td><?= $row['sections']; ?></td>

                        </tr>

                        <?php endforeach; ?>
                        
                        
                        <?php else: ?>

                            <tr>
                                <td colspan="3" style="text-align:center;">
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

<?php include 'Include/footer.php'; ?>