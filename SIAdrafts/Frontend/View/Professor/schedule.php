<?php
$pageTitle  = "MY SCHEDULE";
$activePage = "schedule";
$pageScript = "schedule";

require_once '../../../Backend/auth.php';
require_once '../../../Backend/require_role.php';
require_once '../../../Backend/roles.php';
require_role([ROLE_PROFESSOR]);
require_once '../../../Backend/db.php';
require_once '../../../Backend/settings.php';

$db   = new Database();
$conn = $db->connect();

$profStmt = $conn->prepare("SELECT professor_id FROM professor WHERE professor_id = ? LIMIT 1");
$profStmt->bind_param('i', $_SESSION['professor_id']);
$profStmt->execute();
$professor = $profStmt->get_result()->fetch_assoc();
$profStmt->close();

$byDay = [];
$days  = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'];
foreach ($days as $d) { $byDay[$d] = []; }

$terms          = [];
$school_year    = '';
$semester       = 0;
$pendingClasses = [];

if ($professor) {
    $professor_id = $professor['professor_id'];

    $termStmt = $conn->prepare("
        SELECT DISTINCT school_year, semester
        FROM schedule
        WHERE professor_id = ?
        ORDER BY school_year DESC, semester DESC
    ");
    $termStmt->bind_param('i', $professor_id);
    $termStmt->execute();
    $terms = $termStmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $termStmt->close();

    $school_year = $_GET['school_year'] ?? (get_setting('current_school_year') ?? '');
    $semester    = (int)($_GET['semester'] ?? (get_setting('current_semester') ?? 0));

    $stmt = $conn->prepare("
        SELECT s.day, s.time_start, s.time_end, s.school_year, s.semester,
               sub.subject_code, sub.subject_name, sec.section_name, r.room_name
        FROM schedule s
        JOIN subject sub ON sub.subject_id = s.subject_id
        JOIN section sec ON sec.section_id = s.section_id
        JOIN room r       ON r.room_id = s.room_id
        WHERE s.professor_id = ? AND s.is_active = 1 AND s.status = 'Approved'
          AND s.school_year = ? AND s.semester = ?
        ORDER BY FIELD(s.day,'Monday','Tuesday','Wednesday','Thursday','Friday','Saturday'), s.time_start
    ");
    $stmt->bind_param('isi', $professor_id, $school_year, $semester);
    $stmt->execute();
    $rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();

    foreach ($rows as $row) {
        if (isset($byDay[$row['day']])) {
            $byDay[$row['day']][] = $row;
        }
    }

    $pendingStmt = $conn->prepare("
        SELECT sub.subject_code, sub.subject_name, sec.section_name, s.day, s.time_start, s.time_end
        FROM schedule s
        JOIN subject sub ON sub.subject_id = s.subject_id
        JOIN section sec ON sec.section_id = s.section_id
        WHERE s.professor_id = ? AND s.status = 'Pending'
        ORDER BY s.day, s.time_start
    ");
    $pendingStmt->bind_param('i', $professor_id);
    $pendingStmt->execute();
    $pendingClasses = $pendingStmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $pendingStmt->close();
}

$db->close();

$extraCss = ['/SIAdrafts/Frontend/Css/Professor/professor.css'];
include '../Include/header.php';
?>

<div class="app-layout">

  <?php include '../Include/sidebar.php'; ?>

  <main class="page-content">

    <div class="sched-page-header no-print">
      <div>
        <h1 class="sched-page-title">My Weekly Schedule</h1>
        <p class="sched-page-sub">Your class assignments for the selected term, as set by the Registrar's Office.</p>
      </div>
      <button type="button" class="btn btn-outline" onclick="window.print()">Print / Save as PDF</button>
    </div>

    <?php if (!$professor): ?>
      <div class="panel">
        <div class="panel-body" style="padding:24px;">
          <p>Your account is not yet linked to a professor record. Please contact the Registrar's Office.</p>
        </div>
      </div>
    <?php else: ?>

      <?php if (!empty($terms)): ?>
        <form method="get" class="filter-bar no-print" style="margin-bottom:16px;">
          <div class="select-wrapper sched-select">
            <select name="school_year_semester" class="form-input form-select" onchange="
              var v = this.value.split('|');
              window.location = '?school_year=' + encodeURIComponent(v[0]) + '&semester=' + encodeURIComponent(v[1]);
            ">
              <?php foreach ($terms as $t): ?>
                <option value="<?= htmlspecialchars($t['school_year']) ?>|<?= (int)$t['semester'] ?>"
                  <?= ($t['school_year'] === $school_year && (int)$t['semester'] === $semester) ? 'selected' : '' ?>>
                  <?= htmlspecialchars($t['school_year']) ?>, Semester <?= (int)$t['semester'] ?>
                </option>
              <?php endforeach; ?>
            </select>
          </div>
        </form>
      <?php endif; ?>

      <?php if (!empty($pendingClasses)): ?>
        <div class="panel no-print" style="margin-bottom:16px; border-left:3px solid var(--gold);">
          <div class="panel-header">
            <span class="panel-title"><?= count($pendingClasses) ?> class<?= count($pendingClasses) === 1 ? '' : 'es' ?> pending Registrar approval</span>
          </div>
          <div class="panel-body" style="padding:0;">
            <div class="table-responsive">
              <table class="data-table">
                <thead>
                  <tr><th>Subject</th><th>Section</th><th>Day / Time</th></tr>
                </thead>
                <tbody>
                  <?php foreach ($pendingClasses as $p): ?>
                    <tr>
                      <td><?= htmlspecialchars($p['subject_code']) ?><div class="text-muted"><?= htmlspecialchars($p['subject_name']) ?></div></td>
                      <td><?= htmlspecialchars($p['section_name']) ?></td>
                      <td><?= htmlspecialchars($p['day']) ?>, <?= htmlspecialchars(date('g:i A', strtotime($p['time_start']))) ?> – <?= htmlspecialchars(date('g:i A', strtotime($p['time_end']))) ?></td>
                    </tr>
                  <?php endforeach; ?>
                </tbody>
              </table>
            </div>
          </div>
        </div>
      <?php endif; ?>

      <div class="prof-week-grid">
        <?php foreach ($byDay as $day => $classes): ?>
          <div class="prof-day-col">
            <div class="prof-day-head"><?= htmlspecialchars($day) ?></div>
            <div class="prof-day-body">
              <?php if (empty($classes)): ?>
                <div class="prof-day-empty">No classes</div>
              <?php else: ?>
                <?php foreach ($classes as $c): ?>
                  <div class="prof-class-card">
                    <div class="prof-class-time">
                      <?= htmlspecialchars(date('g:i A', strtotime($c['time_start']))) ?> – <?= htmlspecialchars(date('g:i A', strtotime($c['time_end']))) ?>
                    </div>
                    <div class="prof-class-subject"><?= htmlspecialchars($c['subject_code']) ?></div>
                    <div class="prof-class-name"><?= htmlspecialchars($c['subject_name']) ?></div>
                    <div class="prof-class-meta"><?= htmlspecialchars($c['section_name']) ?> · <?= htmlspecialchars($c['room_name']) ?></div>
                  </div>
                <?php endforeach; ?>
              <?php endif; ?>
            </div>
          </div>
        <?php endforeach; ?>
      </div>

    <?php endif; ?>

  </main>
</div>

<?php
$extraScripts = [
    '/SIAdrafts/Frontend/Js/Professor/' . ($pageScript ?? 'professor') . '.js',
];
include '../Include/footer.php';
?>
