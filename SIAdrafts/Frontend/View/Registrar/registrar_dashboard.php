<?php
$pageTitle  = "DASHBOARD";
$activePage = "dashboard";
$pageScript = "dashboard";

require_once '../../../Backend/auth.php';
require_once '../../../Backend/require_role.php';
require_role(['Head Registrar', 'Registrar Staff']);
require_once '../../../Backend/db.php';

$db   = new Database();
$conn = $db->connect();

$totalCourses  = (int)($conn->query("SELECT COUNT(*) c FROM course")->fetch_assoc()['c'] ?? 0);
$totalSections = (int)($conn->query("SELECT COUNT(*) c FROM section")->fetch_assoc()['c'] ?? 0);
$totalApproved = (int)($conn->query("SELECT COUNT(*) c FROM schedule WHERE status = 'Approved'")->fetch_assoc()['c'] ?? 0);
$totalPending  = (int)($conn->query("SELECT COUNT(*) c FROM schedule WHERE status = 'Pending'")->fetch_assoc()['c'] ?? 0);

$db->close();

$isHead = current_user_is(['Head Registrar']);

include 'Include/header.php';
?>

<div class="app-layout">

  <?php include 'Include/sidebar.php'; ?>

  <main class="page-content">

    <div class="dashboard-cards">
      <div class="dashboard-card">
        <div class="value"><?= $totalCourses ?></div>
        <div class="label">Courses</div>
      </div>
      <div class="dashboard-card">
        <div class="value"><?= $totalSections ?></div>
        <div class="label">Sections</div>
      </div>
      <div class="dashboard-card">
        <div class="value"><?= $totalApproved ?></div>
        <div class="label">Approved Schedules</div>
      </div>
      <div class="dashboard-card">
        <div class="value"><?= $totalPending ?></div>
        <div class="label"><?= $isHead ? 'Awaiting Your Approval' : 'Your Pending Submissions' ?></div>
      </div>
    </div>

    <div class="panel">
      <div class="panel-header">
        <span class="panel-title">Quick Links</span>
      </div>
      <div class="panel-body">
        <p><a href="courses.php">Manage Courses &amp; Sections</a></p>
        <p><a href="schedule.php">View / Add Class Schedules</a></p>
        <?php if ($isHead): ?>
          <p><a href="pending_approval.php">Review Pending Approvals (<?= $totalPending ?>)</a></p>
        <?php endif; ?>
      </div>
    </div>

  </main>
</div>

<?php include 'Include/footer.php'; ?>