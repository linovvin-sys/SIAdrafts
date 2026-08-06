<?php
$pageTitle  = "Dashboard";
$activePage = "dashboard";
$pageScript = "dashboard";

require_once __DIR__ . '/../../../Backend/require_professor.php';
require_professor();
require_once __DIR__ . '/../../../Backend/db.php';
require_once __DIR__ . '/../../../Backend/settings.php';
require_once __DIR__ . '/../../../Backend/Professor/schedule_data.php';

$db   = new Database();
$conn = $db->connect();

$professorId = (int)$_SESSION['professor_id'];

$profStmt = $conn->prepare("
    SELECT p.first_name, p.last_name, d.department_code, d.department_name
    FROM professor p JOIN department d ON d.department_id = p.department_id
    WHERE p.professor_id = ? LIMIT 1
");
$profStmt->bind_param('i', $professorId);
$profStmt->execute();
$professor = $profStmt->get_result()->fetch_assoc();
$profStmt->close();

$schoolYear = get_setting('current_school_year') ?? '';
$semester   = (int)(get_setting('current_semester') ?? 0);

$scheduleList = order_professor_schedule_by_next_occurrence(get_professor_schedule($conn, $professorId, $schoolYear, $semester));
$totalClasses = count($scheduleList);
$nextClass    = $scheduleList[0] ?? null;
$upcoming     = array_slice($scheduleList, 1, 6);

$tintClasses  = ['tint-1', 'tint-2', 'tint-3', 'tint-4'];
$subjectTints = [];
foreach ($scheduleList as $s) {
    if (!isset($subjectTints[$s['subject_code']])) {
        $subjectTints[$s['subject_code']] = $tintClasses[count($subjectTints) % count($tintClasses)];
    }
}

// Distinct students across this term's approved offerings — same
// applicant_id join fix used everywhere else (enrollment.student_id is
// really applicants.applicant_id, not student.student_id).
$headcountStmt = $conn->prepare("
    SELECT COUNT(DISTINCT st.student_id) AS cnt
    FROM schedule s
    JOIN enrollment e ON e.section_id = s.section_id AND e.school_year = s.school_year AND e.semester = s.semester
    JOIN enrollment_subject es ON es.enrollment_id = e.enrollment_id AND es.subject_id = s.subject_id AND es.status = 'Enrolled'
    JOIN student st ON st.applicant_id = e.student_id
    WHERE s.professor_id = ? AND s.is_active = 1 AND s.status = 'Approved'
      AND s.school_year = ? AND s.semester = ?
");
$headcountStmt->bind_param('isi', $professorId, $schoolYear, $semester);
$headcountStmt->execute();
$totalStudents = (int)($headcountStmt->get_result()->fetch_assoc()['cnt'] ?? 0);
$headcountStmt->close();

$pendingClasses = get_professor_pending($conn, $professorId);

$db->close();

$firstName = $professor['first_name'] ?? '';
$hour = (int)date('G');
$greeting = $hour < 12 ? 'Good morning' : ($hour < 18 ? 'Good afternoon' : 'Good evening');

include __DIR__ . '/Include/header.php';
?>

<h1 class="sp-greeting"><?= $greeting ?><?= $firstName ? ', ' . htmlspecialchars($firstName, ENT_QUOTES) : '' ?>.</h1>
<p class="sp-subline">
  <?= htmlspecialchars(($professor['department_code'] ?? '') . ' · ' . $schoolYear . ', Semester ' . $semester, ENT_QUOTES) ?>
</p>

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
      · Section <?= htmlspecialchars($nextClass['section_name'], ENT_QUOTES) ?>
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
      <span class="sp-today-status-label">No classes scheduled</span>
    </div>
    <p class="sp-today-meta">Your teaching schedule will appear here once the Registrar's Office assigns your classes.</p>
  <?php endif; ?>
</div>

<div class="sp-dash-grid">
  <div class="sp-dash-main">
    <div class="sp-card-grid">
      <a href="/SIAdrafts/Frontend/View/Professor/classes.php" class="sp-card">
        <div class="sp-card-icon"><iconify-icon icon="mdi:google-classroom" style="font-size:19px;"></iconify-icon></div>
        <p class="sp-card-label">Active class offerings</p>
        <p class="sp-card-value"><?= $totalClasses ?></p>
        <p class="sp-card-hint">This term</p>
      </a>

      <a href="/SIAdrafts/Frontend/View/Professor/classes.php" class="sp-card">
        <div class="sp-card-icon"><iconify-icon icon="mdi:account-group-outline" style="font-size:19px;"></iconify-icon></div>
        <p class="sp-card-label">Students this term</p>
        <p class="sp-card-value"><?= $totalStudents ?></p>
        <p class="sp-card-hint">Across all sections</p>
      </a>

      <div class="sp-card <?= count($pendingClasses) > 0 ? 'is-warning' : 'is-good' ?>">
        <div class="sp-card-icon"><iconify-icon icon="mdi:clock-alert-outline" style="font-size:19px;"></iconify-icon></div>
        <p class="sp-card-label">Pending approval</p>
        <p class="sp-card-value"><?= count($pendingClasses) ?></p>
        <p class="sp-card-hint"><?= count($pendingClasses) > 0 ? 'Awaiting Registrar sign-off' : 'Nothing pending' ?></p>
      </div>
    </div>

    <?php if (!empty($pendingClasses)): ?>
      <div class="sp-section" style="margin-top:20px;">
        <h2 class="sp-section-title">Pending approval</h2>
        <table class="sp-table sp-table-stagger">
          <thead>
            <tr><th>Subject</th><th>Section</th><th>Schedule</th></tr>
          </thead>
          <tbody>
            <?php foreach ($pendingClasses as $i => $p): ?>
              <tr style="--row-i: <?= $i ?>;">
                <td><?= htmlspecialchars($p['subject_code'], ENT_QUOTES) ?> — <?= htmlspecialchars($p['subject_name'], ENT_QUOTES) ?></td>
                <td><span class="sp-pill pending"><?= htmlspecialchars($p['section_name'], ENT_QUOTES) ?></span></td>
                <td class="sp-num"><?= htmlspecialchars($p['day'], ENT_QUOTES) ?>, <?= date('g:ia', strtotime($p['time_start'])) ?>–<?= date('g:ia', strtotime($p['time_end'])) ?></td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    <?php endif; ?>
  </div>

  <aside class="sp-dash-side">
    <div class="sp-section sp-float">
      <h2 class="sp-section-title">Quick actions</h2>
      <ul class="sp-quickactions">
        <li>
          <a href="/SIAdrafts/Frontend/View/Professor/schedule.php">
            <span class="sp-quickaction-icon"><iconify-icon icon="mdi:calendar-week"></iconify-icon></span>
            <span>View schedule</span>
            <iconify-icon icon="mdi:chevron-right" class="sp-quickaction-chevron"></iconify-icon>
          </a>
        </li>
        <?php if ($nextClass): ?>
        <li>
          <a href="/SIAdrafts/Backend/api/professor_schedule_ics.php" class="js-ics-download">
            <span class="sp-quickaction-icon"><iconify-icon icon="mdi:calendar-plus-outline"></iconify-icon></span>
            <span>Add schedule to calendar</span>
            <iconify-icon icon="mdi:chevron-right" class="sp-quickaction-chevron"></iconify-icon>
          </a>
        </li>
        <?php endif; ?>
        <li>
          <a href="/SIAdrafts/Frontend/View/Professor/classes.php">
            <span class="sp-quickaction-icon"><iconify-icon icon="mdi:account-group-outline"></iconify-icon></span>
            <span>My classes &amp; rosters</span>
            <iconify-icon icon="mdi:chevron-right" class="sp-quickaction-chevron"></iconify-icon>
          </a>
        </li>
        <li>
          <a href="/SIAdrafts/Frontend/View/Professor/profile.php">
            <span class="sp-quickaction-icon"><iconify-icon icon="mdi:account-circle-outline"></iconify-icon></span>
            <span>My profile</span>
            <iconify-icon icon="mdi:chevron-right" class="sp-quickaction-chevron"></iconify-icon>
          </a>
        </li>
      </ul>
    </div>
  </aside>
</div>

<?php include __DIR__ . '/Include/footer.php'; ?>
