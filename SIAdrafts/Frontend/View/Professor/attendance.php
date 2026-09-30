<?php
$pageTitle  = "Attendance";
$activePage = "attendance";
$pageScript = "attendance";

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

<h1 class="sp-greeting">Attendance</h1>
<p class="sp-subline">Mark today's attendance for a class, or review a past date.</p>

<?php if (empty($classes)): ?>
  <div class="sp-empty">
    <iconify-icon icon="mdi:clipboard-check-outline"></iconify-icon>
    <p><strong>No class assignments yet.</strong></p>
    <p>You'll be able to take attendance once the Registrar's Office assigns your classes.</p>
  </div>
<?php else: ?>

<div class="sp-section">
  <?php $selectId = 'attendanceClassSelect'; include __DIR__ . '/Include/class_picker.php'; ?>

  <div id="attendanceEmptyState" class="sp-empty" style="margin-top:16px;">
    <iconify-icon icon="mdi:cursor-default-click-outline"></iconify-icon>
    <p>Select a class above to take or review attendance.</p>
  </div>

  <div id="attendancePanel" hidden style="margin-top:16px;">
    <div style="display:flex; align-items:flex-end; gap:12px; flex-wrap:wrap;">
      <div class="sp-form-group" style="margin-bottom:0;">
        <label for="attendanceDateInput">Session date</label>
        <input type="date" id="attendanceDateInput">
      </div>
      <button type="button" class="sp-btn sp-btn-secondary" id="markAllPresentBtn">Mark all Present</button>
    </div>
    <div class="sp-form-error" id="attendanceFormError" role="alert" aria-live="assertive">
      <p class="sp-form-error-msg" id="attendanceFormErrorMsg"></p>
    </div>

    <table class="sp-table sp-table-stagger" style="margin-top:16px;">
      <thead><tr><th>Student</th><th>Status</th></tr></thead>
      <tbody id="attendanceRosterBody"></tbody>
    </table>

    <button type="button" class="sp-btn sp-btn-primary" id="saveAttendanceBtn" style="margin-top:16px;">
      <span class="sp-btn-spinner" hidden></span>
      <span class="sp-btn-label">Save attendance</span>
    </button>
  </div>
</div>

<?php endif; ?>

<?php include __DIR__ . '/Include/footer.php'; ?>
