<?php
$pageTitle  = "SCHEDULE DELETION LOG";
$activePage = "schedule_deletion_log";

require_once '../../../Backend/auth.php';
require_once '../../../Backend/require_role.php';
require_role(['Registrar Staff', 'Head Registrar', 'Admin']);
require_once '../../../Backend/db.php';

$db   = new Database();
$conn = $db->connect();

// Snapshot rows written by Backend/api/Scheduling/delete_schedule.php --
// once a schedule is deleted, this log (plus who did it and why) is the
// only record that it ever existed. Capped at 200 rows since this is a
// growing audit trail, not a working list.
$logs = $conn->query("
    SELECT l.log_id, l.schedule_id, l.subject_code, l.subject_name, l.section_name,
           l.day, l.time_start, l.time_end, l.school_year, l.semester, l.reason,
           l.deleted_at, u.first_name, u.last_name
    FROM schedule_deletion_log l
    JOIN users u ON u.user_id = l.deleted_by
    ORDER BY l.deleted_at DESC
    LIMIT 200
")->fetch_all(MYSQLI_ASSOC);

$db->close();

include '../Include/header.php';
?>

<div class="app-layout">

  <?php include '../Include/sidebar.php'; ?>

  <main class="page-content">
    <?php include '../Include/readonly_banner.php'; ?>

    <div class="sched-page-header">
      <div>
        <h1 class="sched-page-title">Schedule Deletion Log</h1>
        <p class="sched-page-sub">An audit trail of every class schedule that's been removed, who removed it, and why. Most recent first.</p>
      </div>
    </div>

    <div class="panel">
      <div class="panel-header">
        <span class="panel-title">Deleted Schedules</span>
      </div>

      <div class="panel-body" style="padding:0;">
        <div class="table-responsive">
          <table class="data-table">
            <thead>
              <tr>
                <th>Deleted</th>
                <th>Subject</th>
                <th>Section</th>
                <th>Meeting</th>
                <th>Term</th>
                <th>Reason</th>
                <th>Deleted By</th>
              </tr>
            </thead>
            <tbody>
              <?php if (empty($logs)): ?>
                <tr><td colspan="7" style="text-align:center; padding:32px;">No schedules have been deleted yet.</td></tr>
              <?php else: ?>
                <?php foreach ($logs as $l): ?>
                  <tr>
                    <td class="mono"><?= htmlspecialchars(date('M j, Y g:ia', strtotime($l['deleted_at']))) ?></td>
                    <td>
                      <?= htmlspecialchars($l['subject_code'] ?? '—') ?>
                      <div class="text-muted"><?= htmlspecialchars($l['subject_name'] ?? '') ?></div>
                    </td>
                    <td><?= htmlspecialchars($l['section_name'] ?? '—') ?></td>
                    <td>
                      <?php if ($l['day'] && $l['time_start'] && $l['time_end']): ?>
                        <?= htmlspecialchars($l['day']) ?>, <?= htmlspecialchars(date('g:ia', strtotime($l['time_start']))) ?>–<?= htmlspecialchars(date('g:ia', strtotime($l['time_end']))) ?>
                      <?php else: ?>
                        —
                      <?php endif; ?>
                    </td>
                    <td><?= htmlspecialchars($l['school_year'] ?? '—') ?><?= $l['semester'] ? ', Sem ' . (int)$l['semester'] : '' ?></td>
                    <td><?= htmlspecialchars($l['reason']) ?></td>
                    <td><?= htmlspecialchars(trim($l['first_name'] . ' ' . $l['last_name'])) ?></td>
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

<?php include '../Include/footer.php'; ?>
