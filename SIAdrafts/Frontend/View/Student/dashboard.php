<?php
$pageTitle  = "Dashboard";
$activePage = "dashboard";
$pageScript = "dashboard";

require_once __DIR__ . '/../../../Backend/require_student.php';
require_student();
require_once __DIR__ . '/../../../Backend/db.php';
require_once __DIR__ . '/../../../Backend/requirements.php';
require_once __DIR__ . '/../../../Backend/Student/schedule_data.php';
require_once __DIR__ . '/../../../Backend/Student/dashboard_data.php';
require_once __DIR__ . '/../../../Backend/Student/announcement_data.php';

$db   = new Database();
$conn = $db->connect();

$applicantId = (int)$_SESSION['student_id'];

$dashboardData   = get_dashboard_data($conn, $applicantId);
$enrollment      = $dashboardData['enrollment'];
$payment         = $dashboardData['payment'];
$subjectCount    = $dashboardData['subjectCount'];
$totalUnits      = $dashboardData['totalUnits'];
$submittedLabels = $dashboardData['submittedLabels'];
$activity        = $dashboardData['activity'];

$labelToKey = [];
foreach (REQUIREMENT_DEFINITIONS as $def) { $labelToKey[$def['label']] = $def['key']; }
$submittedKeys = array_values(array_filter(array_map(fn($l) => $labelToKey[$l] ?? null, $submittedLabels)));
$missingGroups = missing_requirement_groups($submittedKeys);

$totalGroups     = count(requirement_groups());
$submittedGroups = $totalGroups - count($missingGroups);
$reqPercent      = $totalGroups > 0 ? (int)round($submittedGroups / $totalGroups * 100) : 100;

// Next class + the rest of the upcoming week, computed from the same
// schedule data the full weekly grid uses, wrapping Sat -> Mon.
$scheduleList = order_schedule_by_next_occurrence(get_student_schedule($conn, $applicantId)['scheduled']);
$nextClass    = $scheduleList[0] ?? null;
$upcoming     = array_slice($scheduleList, 1, 6);

$tintClasses  = ['tint-1', 'tint-2', 'tint-3', 'tint-4'];
$subjectTints = [];
foreach ($scheduleList as $s) {
    if (!isset($subjectTints[$s['subject_code']])) {
        $subjectTints[$s['subject_code']] = $tintClasses[count($subjectTints) % count($tintClasses)];
    }
}

$announcements = get_student_announcements($conn, $applicantId, 20);

$db->close();

$firstName = explode(' ', $_SESSION['student_full_name'] ?? '')[0] ?? '';
$hour = (int)date('G');
$greeting = $hour < 12 ? 'Good morning' : ($hour < 18 ? 'Good afternoon' : 'Good evening');

$balance = $payment ? (float)$payment['balance'] : 0;
$enrollmentStatus = $enrollment ? $enrollment['status'] : null;
$statusPillClass = match ($enrollmentStatus) {
    'Enrolled' => 'enrolled',
    'Pending Payment' => 'pending',
    default => 'attention',
};

function sp_time_ago(string $ts): string
{
    $diff = time() - strtotime($ts);
    if ($diff < 60) return 'just now';
    if ($diff < 3600) return (int)($diff / 60) . 'm ago';
    if ($diff < 86400) return (int)($diff / 3600) . 'h ago';
    $days = (int)($diff / 86400);
    if ($days < 7) return $days . 'd ago';
    return date('M j', strtotime($ts));
}

include __DIR__ . '/Include/header.php';
?>

<h1 class="sp-greeting"><?= $greeting ?><?= $firstName ? ', ' . htmlspecialchars($firstName, ENT_QUOTES) : '' ?>.</h1>
<p class="sp-subline">
  <?= $enrollment
      ? htmlspecialchars(($enrollment['course_code'] ?? '') . ' · ' . $enrollment['school_year'] . ', Semester ' . $enrollment['semester'], ENT_QUOTES)
      : 'You do not have an active enrollment yet.' ?>
</p>

<?php if (!$enrollment): ?>
  <div class="sp-empty">
    <iconify-icon icon="mdi:school-outline"></iconify-icon>
    <p><strong>No enrollment on file.</strong></p>
    <p>Once the Registrar's Office processes your enrollment, your registration, schedule, and balance will appear here.</p>
  </div>
<?php else: ?>

<div class="sp-today <?= $nextClass ? ($nextClass['is_live'] ? 'is-live' : '') : 'is-empty' ?>">
  <?php if ($nextClass): ?>
    <div class="sp-today-status">
      <span class="sp-today-dot"></span>
      <span class="sp-today-status-label"><?= $nextClass['is_live'] ? 'Happening now' : 'Next class' ?></span>
    </div>
    <p class="sp-today-subject"><?= htmlspecialchars($nextClass['subject_code'], ENT_QUOTES) ?> · <?= htmlspecialchars($nextClass['subject_name'], ENT_QUOTES) ?></p>
    <p class="sp-today-meta">
      <?= htmlspecialchars($nextClass['day'], ENT_QUOTES) ?>, <?= date('g:ia', strtotime($nextClass['time_start'])) ?>–<?= date('g:ia', strtotime($nextClass['time_end'])) ?>
      · <?= htmlspecialchars($nextClass['room_name'] ?? 'TBA', ENT_QUOTES) ?>
      · <?= htmlspecialchars(trim($nextClass['professor_name'] ?? '') ?: 'TBA', ENT_QUOTES) ?>
    </p>
    <p class="sp-today-countdown" data-start="<?= $nextClass['start_epoch_ms'] ?>" data-end="<?= $nextClass['end_epoch_ms'] ?>">&nbsp;</p>

    <?php if ($upcoming): ?>
      <ul class="sp-today-upcoming">
        <?php foreach ($upcoming as $u): ?>
          <li class="<?= $subjectTints[$u['subject_code']] ?>">
            <?= htmlspecialchars($u['subject_code'], ENT_QUOTES) ?>
            <span class="sp-today-upcoming-when"><?= substr($u['day'], 0, 3) ?>, <?= date('g:ia', strtotime($u['time_start'])) ?></span>
          </li>
        <?php endforeach; ?>
      </ul>
    <?php endif; ?>
  <?php else: ?>
    <div class="sp-today-status">
      <span class="sp-today-dot"></span>
      <span class="sp-today-status-label">No upcoming classes</span>
    </div>
    <p class="sp-today-meta">Your schedule will appear here once the Registrar's Office finalizes it.</p>
  <?php endif; ?>
</div>

<div class="sp-dash-grid">
  <div class="sp-dash-main">
    <div class="sp-dash-top">
      <div class="sp-section sp-float sp-announce-hero">
        <h2 class="sp-section-title">Announcements</h2>
        <?php if ($announcements): ?>
          <div class="sp-announce-hero-list">
            <?php foreach ($announcements as $a):
                $tint = $subjectTints[$a['subject_code']] ?? 'tint-1';
            ?>
              <div class="sp-announce-tile <?= $tint ?>">
                <div class="sp-announce-tile-head">
                  <span class="sp-announce-tile-subject"><?= htmlspecialchars($a['subject_code'], ENT_QUOTES) ?></span>
                  <span class="sp-announce-tile-time"><?= sp_time_ago($a['created_at']) ?></span>
                </div>
                <p class="sp-announce-tile-title"><?= htmlspecialchars($a['title'], ENT_QUOTES) ?></p>
                <p class="sp-announce-tile-body"><?= nl2br(htmlspecialchars($a['body'], ENT_QUOTES)) ?></p>
                <p class="sp-announce-tile-from">— <?= htmlspecialchars($a['professor_name'], ENT_QUOTES) ?></p>
              </div>
            <?php endforeach; ?>
          </div>
        <?php else: ?>
          <p class="sp-card-hint">No announcements from your professors yet.</p>
        <?php endif; ?>
      </div>

      <div class="sp-card-grid">
      <a href="/SIAdrafts/Frontend/View/Student/registration.php" class="sp-card">
        <div class="sp-card-icon"><iconify-icon icon="mdi:file-document-outline" style="font-size:19px;"></iconify-icon></div>
        <p class="sp-card-label">Enrollment status</p>
        <p class="sp-card-value" style="font-size:16px;">
          <span class="sp-pill <?= $statusPillClass ?>"><?= htmlspecialchars($enrollmentStatus, ENT_QUOTES) ?></span>
        </p>
        <p class="sp-card-hint">View your registration</p>
      </a>

      <a href="/SIAdrafts/Frontend/View/Student/registration.php" class="sp-card">
        <div class="sp-card-icon"><iconify-icon icon="mdi:book-open-variant" style="font-size:19px;"></iconify-icon></div>
        <p class="sp-card-label">Units enrolled</p>
        <p class="sp-card-value"><?= rtrim(rtrim(number_format($totalUnits, 1), '0'), '.') ?> units</p>
        <p class="sp-card-hint"><?= $subjectCount ?> subject<?= $subjectCount === 1 ? '' : 's' ?> this term</p>
      </a>

      <a href="/SIAdrafts/Frontend/View/Student/accountabilities.php" class="sp-card <?= $balance > 0 ? 'is-warning' : 'is-good' ?>" data-celebrate="balance" data-value="<?= $balance ?>">
        <div class="sp-card-icon"><iconify-icon icon="mdi:cash-multiple" style="font-size:19px;"></iconify-icon></div>
        <p class="sp-card-label">Balance due</p>
        <p class="sp-card-value sp-vt-balance">&#8369;<?= number_format($balance, 2) ?></p>
        <p class="sp-card-hint"><?= $balance > 0 ? 'Payment needed' : 'Fully settled' ?></p>
      </a>

      <a href="/SIAdrafts/Frontend/View/Student/accountabilities.php" class="sp-card <?= count($missingGroups) > 0 ? 'is-warning' : 'is-good' ?>" data-celebrate="requirements" data-value="<?= $submittedGroups ?>" data-total="<?= $totalGroups ?>">
        <div class="sp-card-icon"><iconify-icon icon="mdi:clipboard-list-outline" style="font-size:19px;"></iconify-icon></div>
        <p class="sp-card-label">Requirements</p>
        <p class="sp-card-value sp-vt-requirements"><?= $submittedGroups ?>/<?= $totalGroups ?> submitted</p>
        <div class="sp-progress-track">
          <div class="sp-progress-fill" data-pct="<?= $reqPercent ?>"></div>
        </div>
        <p class="sp-card-hint"><?= count($missingGroups) > 0 ? 'Action needed' : 'All submitted' ?></p>
      </a>
      </div>
    </div>
  </div>

  <aside class="sp-dash-side">
    <div class="sp-section sp-float sp-activity-section">
      <h2 class="sp-section-title">Recent activity</h2>
      <?php if ($activity): ?>
        <ul class="sp-activity-list">
          <?php foreach ($activity as $a): ?>
            <li>
              <span class="sp-activity-icon is-<?= htmlspecialchars($a['type'], ENT_QUOTES) ?>"><iconify-icon icon="<?= htmlspecialchars($a['icon'], ENT_QUOTES) ?>"></iconify-icon></span>
              <span class="sp-activity-label"><?= htmlspecialchars($a['label'], ENT_QUOTES) ?></span>
              <span class="sp-activity-time"><?= sp_time_ago($a['ts']) ?></span>
            </li>
          <?php endforeach; ?>
        </ul>
      <?php else: ?>
        <p class="sp-card-hint">Nothing recorded yet this term.</p>
      <?php endif; ?>
    </div>

    <div class="sp-section sp-float">
      <h2 class="sp-section-title">Quick actions</h2>
      <ul class="sp-quickactions">
        <li>
          <a href="/SIAdrafts/Frontend/View/Student/schedule.php">
            <span class="sp-quickaction-icon"><iconify-icon icon="mdi:calendar-week"></iconify-icon></span>
            <span>View schedule</span>
            <iconify-icon icon="mdi:chevron-right" class="sp-quickaction-chevron"></iconify-icon>
          </a>
        </li>
        <?php if ($nextClass): ?>
        <li>
          <a href="/SIAdrafts/Backend/api/Scheduling/student_schedule_ics.php" class="js-ics-download">
            <span class="sp-quickaction-icon"><iconify-icon icon="mdi:calendar-plus-outline"></iconify-icon></span>
            <span>Add schedule to calendar</span>
            <iconify-icon icon="mdi:chevron-right" class="sp-quickaction-chevron"></iconify-icon>
          </a>
        </li>
        <?php endif; ?>
        <li>
          <a href="/SIAdrafts/Frontend/View/Student/registration.php">
            <span class="sp-quickaction-icon"><iconify-icon icon="mdi:printer-outline"></iconify-icon></span>
            <span>Print registration</span>
            <iconify-icon icon="mdi:chevron-right" class="sp-quickaction-chevron"></iconify-icon>
          </a>
        </li>
      </ul>
    </div>
  </aside>
</div>

<?php endif; ?>

<?php include __DIR__ . '/Include/footer.php'; ?>
