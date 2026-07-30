<?php
$pageTitle  = "ADMISSION DASHBOARD";
$activePage = "dashboard";

require_once '../../../Backend/auth.php';
require_once '../../../Backend/roles.php';
require_once '../../../Backend/require_role.php';
require_role([ROLE_ADMISSION, ROLE_STAFF, ROLE_ADMIN]);
require_once __DIR__ . '/../../../Backend/admin/admission_dashboard.php';

include '../Include/header.php';
?>

<div class="app-layout">

    <?php include '../Include/sidebar.php'; ?>

    <main class="page-content">

        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-icon gold"><i class="bi bi-hourglass-split"></i></div>
                <div>
                    <div class="stat-value"><?= $dashboard['pending_review'] ?></div>
                    <div class="stat-label">Pending Review</div>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon gold"><i class="bi bi-check-circle"></i></div>
                <div>
                    <div class="stat-value"><?= $dashboard['verified_today'] ?></div>
                    <div class="stat-label">Verified Today</div>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon gold"><i class="bi bi-people"></i></div>
                <div>
                    <div class="stat-value"><?= $dashboard['possible_duplicates'] ?></div>
                    <div class="stat-label">Possible Returning Students</div>
                </div>
            </div>
        </div>

    </main>

</div>

<?php include '../Include/footer.php'; ?>
