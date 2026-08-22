<?php
$pageTitle  = "Schedule";
$activePage = "schedule";
$pageScript = "schedule";

require_once __DIR__ . '/../../../Backend/require_student.php';
require_student();
require_once __DIR__ . '/../../../Backend/db.php';
require_once __DIR__ . '/../../../Backend/Student/schedule_data.php';

$db   = new Database();
$conn = $db->connect();

$applicantId = (int)$_SESSION['student_id'];

$sched      = get_student_schedule($conn, $applicantId);
$enrollment = $sched['enrollment'];
$scheduled  = $sched['scheduled'];
$db->close();

$days = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'];
$todayName = date('l');
$slotMinutes = 30;

$toMinutes = fn(string $t) => (int)substr($t, 0, 2) * 60 + (int)substr($t, 3, 2);

$minMinutes = 7 * 60;
$maxMinutes = 18 * 60;
foreach ($scheduled as $s) {
    $start = (int)(floor($toMinutes($s['time_start']) / 60) * 60);
    $end   = (int)(ceil($toMinutes($s['time_end']) / 60) * 60);
    $minMinutes = min($minMinutes, $start);
    $maxMinutes = max($maxMinutes, $end);
}
$rowCount = (int)(($maxMinutes - $minMinutes) / $slotMinutes);

$tintClasses = ['tint-1', 'tint-2', 'tint-3', 'tint-4'];
$subjectTints = [];
$tintIndex = 0;
foreach ($scheduled as $s) {
    if (!isset($subjectTints[$s['subject_code']])) {
        $subjectTints[$s['subject_code']] = $tintClasses[$tintIndex % count($tintClasses)];
        $tintIndex++;
    }
}

include __DIR__ . '/Include/header.php';
?>

<div style="display:flex; align-items:flex-start; justify-content:space-between; gap:16px; flex-wrap:wrap;">
  <div>
    <h1 class="sp-greeting">Weekly schedule</h1>
    <p class="sp-subline">Your class schedule for <?= $enrollment ? htmlspecialchars($enrollment['school_year'] . ', Semester ' . $enrollment['semester'], ENT_QUOTES) : 'the current term' ?>.</p>
  </div>
  <?php if (!empty($scheduled)): ?>
    <a href="/SIAdrafts/Backend/api/Scheduling/student_schedule_ics.php" class="sp-btn sp-btn-secondary js-ics-download">
      <iconify-icon icon="mdi:calendar-plus-outline"></iconify-icon> Add to calendar
    </a>
  <?php endif; ?>
</div>

<?php if (empty($scheduled)): ?>
  <div class="sp-empty">
    <iconify-icon icon="mdi:calendar-blank-outline"></iconify-icon>
    <p><strong>No schedule to show yet.</strong></p>
    <p>Your class schedule will appear here once the Registrar's Office finalizes your section or subject schedule.</p>
  </div>
<?php else: ?>

<?php
// Keyed by "row_col" of the STARTING slot, so each block can be nested
// inside its starting cell and absolutely positioned to overflow down
// into the following (otherwise-empty) cells below it. This renders far
// more reliably than placing blocks as separate, overlapping CSS Grid
// items sharing cells with the background grid — some browsers (headless
// Chromium confirmed) silently fail to paint overlapping grid items even
// though layout/computed style is correct.
$blocksByStartCell = [];
foreach ($scheduled as $s) {
    $dayIndex = array_search($s['day'], $days, true);
    if ($dayIndex === false) continue;
    $rowStart = 2 + (int)(($toMinutes($s['time_start']) - $minMinutes) / $slotMinutes);
    $rowEnd   = 2 + (int)(($toMinutes($s['time_end'])   - $minMinutes) / $slotMinutes);
    $blocksByStartCell["{$rowStart}_" . ($dayIndex + 2)] = $s + ['rowSpan' => $rowEnd - $rowStart];
}
?>
<div class="sp-section">
  <div class="sp-schedule-scroll">
    <div class="sp-schedule" style="grid-template-rows: auto repeat(<?= $rowCount ?>, 24px);">
      <div class="sp-sch-corner"></div>
      <?php foreach ($days as $d): ?>
        <div class="sp-sch-day <?= $d === $todayName ? 'is-today' : '' ?>"><?= substr($d, 0, 3) ?></div>
      <?php endforeach; ?>

      <?php for ($r = 0; $r < $rowCount; $r++):
          $rowMinutes = $minMinutes + $r * $slotMinutes;
          $isHour = $rowMinutes % 60 === 0;
          $gridRow = $r + 2;
      ?>
        <div class="sp-sch-time" style="grid-row: <?= $gridRow ?>; grid-column: 1; border-top-color: <?= $isHour ? 'var(--line-200)' : 'transparent' ?>;">
          <?= $isHour ? date('g A', strtotime(sprintf('%02d:%02d', intdiv($rowMinutes, 60), $rowMinutes % 60))) : '' ?>
        </div>
        <?php foreach ($days as $di => $d):
            $gridCol = $di + 2;
            $block = $blocksByStartCell["{$gridRow}_{$gridCol}"] ?? null;
        ?>
          <div class="sp-sch-cell <?= $d === $todayName ? 'is-today' : '' ?>" style="grid-row: <?= $gridRow ?>; grid-column: <?= $gridCol ?>;">
            <?php if ($block): ?>
              <div class="sp-sch-block <?= $subjectTints[$block['subject_code']] ?>" style="height: <?= $block['rowSpan'] * 24 - 6 ?>px;">
                <span class="sp-sch-subject"><?= htmlspecialchars($block['subject_code'], ENT_QUOTES) ?></span>
                <span class="sp-sch-meta"><?= date('g:ia', strtotime($block['time_start'])) ?>–<?= date('g:ia', strtotime($block['time_end'])) ?></span>
                <span class="sp-sch-meta"><?= htmlspecialchars($block['room_name'] ?? 'TBA', ENT_QUOTES) ?></span>
              </div>
            <?php endif; ?>
          </div>
        <?php endforeach; ?>
      <?php endfor; ?>
    </div>
  </div>

  <?php $tintColorVar = ['tint-1' => 'accent-600', 'tint-2' => 'info-600', 'tint-3' => 'success-600', 'tint-4' => 'danger-600']; ?>
  <div class="sp-legend">
    <?php foreach ($subjectTints as $code => $tint): ?>
      <div class="sp-legend-item">
        <span class="sp-legend-swatch" style="background: var(--<?= $tintColorVar[$tint] ?>);"></span>
        <?= htmlspecialchars($code, ENT_QUOTES) ?>
      </div>
    <?php endforeach; ?>
  </div>
</div>

<div class="sp-section">
  <h2 class="sp-section-title">Class list</h2>
  <table class="sp-table sp-table-stagger">
    <thead>
      <tr><th>Code</th><th>Subject</th><th>Schedule</th><th>Room</th><th>Professor</th></tr>
    </thead>
    <tbody>
      <?php foreach ($scheduled as $i => $s): ?>
        <tr style="--row-i: <?= $i ?>;">
          <td class="sp-num"><?= htmlspecialchars($s['subject_code'], ENT_QUOTES) ?></td>
          <td><?= htmlspecialchars($s['subject_name'], ENT_QUOTES) ?></td>
          <td class="sp-num"><?= htmlspecialchars($s['day'], ENT_QUOTES) ?>, <?= date('g:ia', strtotime($s['time_start'])) ?>–<?= date('g:ia', strtotime($s['time_end'])) ?></td>
          <td><?= htmlspecialchars($s['room_name'] ?? 'TBA', ENT_QUOTES) ?></td>
          <td><?= htmlspecialchars(trim($s['professor_name'] ?? '') ?: 'TBA', ENT_QUOTES) ?></td>
        </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
</div>

<?php endif; ?>

<?php include __DIR__ . '/Include/footer.php'; ?>
