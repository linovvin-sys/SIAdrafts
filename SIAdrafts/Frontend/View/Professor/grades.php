<?php
$pageTitle  = "Grades";
$activePage = "grades";
$pageScript = "grades";

require_once __DIR__ . '/../../../Backend/require_professor.php';
require_professor();
require_once __DIR__ . '/../../../Backend/db.php';
require_once __DIR__ . '/../../../Backend/Professor/schedule_data.php';

$db   = new Database();
$conn = $db->connect();

$professorId = (int)$_SESSION['professor_id'];
$classes     = get_professor_active_classes($conn, $professorId);

$db->close();

include __DIR__ . '/Include/header.php';
?>

<h1 class="sp-greeting">Grades</h1>
<p class="sp-subline">Encode grades for one of your classes, one grading period at a time.</p>

<?php if (empty($classes)): ?>
  <div class="sp-empty">
    <iconify-icon icon="mdi:school-outline"></iconify-icon>
    <p><strong>No class assignments yet.</strong></p>
    <p>You'll be able to encode grades once the Registrar's Office assigns your classes.</p>
  </div>
<?php else: ?>

<div class="sp-section">
  <div style="display:flex; gap:16px; flex-wrap:wrap;">
    <?php $selectId = 'gradesClassSelect'; include __DIR__ . '/Include/class_picker.php'; ?>

    <div class="sp-form-group" style="max-width:220px;">
      <label for="gradesPeriodSelect">Grading period</label>
      <select class="sp-select" id="gradesPeriodSelect">
        <option value="Prelim">Prelim</option>
        <option value="Midterm">Midterm</option>
        <option value="Prefinal">Prefinal</option>
        <option value="Final">Final</option>
      </select>
    </div>
  </div>

  <div id="gradesEmptyState" class="sp-empty" style="margin-top:16px;">
    <iconify-icon icon="mdi:cursor-default-click-outline"></iconify-icon>
    <p>Select a class above to view or encode grades.</p>
  </div>

  <div id="gradesPanel" hidden style="margin-top:16px;">
    <div class="sp-form-error" id="gradesFormError" role="alert" aria-live="assertive">
      <p class="sp-form-error-msg" id="gradesFormErrorMsg"></p>
    </div>
    <div class="sp-form-success" id="gradesFormSuccess" role="status" aria-live="polite">
      <p class="sp-form-success-msg" id="gradesFormSuccessMsg"></p>
    </div>
    <table class="sp-table" id="gradesTable">
      <thead>
        <tr><th>Student No.</th><th>Full Name</th><th>Grade</th><th>Remarks</th></tr>
      </thead>
      <tbody id="gradesTableBody"></tbody>
    </table>
    <button type="button" class="sp-btn sp-btn-primary" id="saveGradesBtn" style="margin-top:16px;">
      <span class="sp-btn-spinner" hidden></span>
      <span class="sp-btn-label">Save grades</span>
    </button>
  </div>
</div>

<?php endif; ?>

<?php include __DIR__ . '/Include/footer.php'; ?>
