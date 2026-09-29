<?php
$pageTitle  = "Grades";
$activePage = "grades";
$pageScript = "grades";

require_once __DIR__ . '/../../../Backend/require_student.php';
require_student();
require_once __DIR__ . '/../../../Backend/db.php';
require_once __DIR__ . '/../../../Backend/Student/grade_data.php';

$db   = new Database();
$conn = $db->connect();

$applicantId = (int)$_SESSION['student_id'];

$currentTerm = get_student_current_term($conn, $applicantId);
$yearLevel   = (int)($_GET['year_level'] ?? $currentTerm['year_level']);
$semester    = (int)($_GET['semester'] ?? $currentTerm['semester']);
if ($yearLevel < 1 || $yearLevel > 4) $yearLevel = 1;
if ($semester < 1 || $semester > 2) $semester = 1;

$subjects = get_student_grades_for_term($conn, $applicantId, $yearLevel, $semester);
$db->close();

$yearLabels = [1 => '1st Year', 2 => '2nd Year', 3 => '3rd Year', 4 => '4th Year'];

include __DIR__ . '/Include/header.php';
?>

<div style="display:flex; align-items:flex-start; justify-content:space-between; gap:16px; flex-wrap:wrap;">
  <div>
    <h1 class="sp-greeting">Grades</h1>
    <p class="sp-subline">Grades encoded by your professors for <?= htmlspecialchars($yearLabels[$yearLevel], ENT_QUOTES) ?>, Semester <?= $semester ?>.</p>
  </div>
  <div class="sp-print-hide">
    <select class="sp-select" id="termSelect">
      <?php foreach (grade_term_options() as $t): ?>
        <option value="<?= $t['year_level'] ?>|<?= $t['semester'] ?>"
          <?= ($t['year_level'] === $yearLevel && $t['semester'] === $semester) ? 'selected' : '' ?>>
          <?= htmlspecialchars($yearLabels[$t['year_level']], ENT_QUOTES) ?>, Semester <?= $t['semester'] ?>
        </option>
      <?php endforeach; ?>
    </select>
  </div>
</div>

<?php if (empty($subjects)): ?>
  <div class="sp-empty">
    <iconify-icon icon="mdi:school-outline"></iconify-icon>
    <p><strong>No grades for this term.</strong></p>
    <p>Grades will appear here once you're enrolled and your professors encode them.</p>
  </div>
<?php else: ?>

  <div class="sp-section">
    <table class="sp-table sp-table-stagger">
      <thead><tr><th>Subject</th><th>Prelim</th><th>Midterm</th><th>Prefinal</th><th>Final</th><th>Remarks</th></tr></thead>
      <tbody>
        <?php foreach ($subjects as $i => $s): ?>
          <tr style="--row-i: <?= $i ?>;">
            <td><?= htmlspecialchars($s['subject_code'], ENT_QUOTES) ?><div style="color:var(--slate-300); font-size:12.5px;"><?= htmlspecialchars($s['subject_name'], ENT_QUOTES) ?></div></td>
            <td class="sp-num"><?= $s['prelim'] !== null ? htmlspecialchars($s['prelim'], ENT_QUOTES) : '—' ?></td>
            <td class="sp-num"><?= $s['midterm'] !== null ? htmlspecialchars($s['midterm'], ENT_QUOTES) : '—' ?></td>
            <td class="sp-num"><?= $s['prefinal'] !== null ? htmlspecialchars($s['prefinal'], ENT_QUOTES) : '—' ?></td>
            <td class="sp-num"><?= $s['final'] !== null ? htmlspecialchars($s['final'], ENT_QUOTES) : '—' ?></td>
            <td><?= $s['final_remarks'] ? htmlspecialchars($s['final_remarks'], ENT_QUOTES) : '—' ?></td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>

<?php endif; ?>

<?php include __DIR__ . '/Include/footer.php'; ?>
