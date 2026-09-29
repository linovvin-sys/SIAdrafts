<?php
$pageTitle  = "Assignments";
$activePage = "assignments";
$pageScript = "assignments";

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

<h1 class="sp-greeting">Assignments</h1>
<p class="sp-subline">Post, edit, and grade assignments for one of your classes.</p>

<?php if (empty($classes)): ?>
  <div class="sp-empty">
    <iconify-icon icon="mdi:file-document-edit-outline"></iconify-icon>
    <p><strong>No class assignments yet.</strong></p>
    <p>You'll be able to post assignments once the Registrar's Office assigns your classes.</p>
  </div>
<?php else: ?>

<div class="sp-section">
  <?php $selectId = 'assignmentClassSelect'; include __DIR__ . '/Include/class_picker.php'; ?>

  <div id="assignmentEmptyState" class="sp-empty" style="margin-top:16px;">
    <iconify-icon icon="mdi:cursor-default-click-outline"></iconify-icon>
    <p>Select a class above to view or post assignments.</p>
  </div>

  <div id="assignmentPanel" hidden style="margin-top:16px;">
    <details style="margin-bottom:16px;">
      <summary class="sp-table-action" style="display:inline-flex; cursor:pointer;">
        <iconify-icon icon="mdi:shape-outline"></iconify-icon> Manage categories (for weighted grading)
      </summary>
      <div style="margin-top:12px; padding:12px; background:var(--paper-100); border-radius:var(--sp-radius-sm);">
        <div style="display:flex; gap:10px; flex-wrap:wrap; align-items:flex-end;">
          <div class="sp-form-group" style="margin-bottom:0; flex:1; min-width:140px;">
            <label for="categoryNameInput">Category name</label>
            <input type="text" id="categoryNameInput" maxlength="100" placeholder="e.g. Quizzes">
          </div>
          <div class="sp-form-group" style="margin-bottom:0; width:110px;">
            <label for="categoryWeightInput">Weight %</label>
            <input type="number" id="categoryWeightInput" min="1" max="100" step="1">
          </div>
          <button type="button" class="sp-btn sp-btn-secondary" id="addCategoryBtn">Add</button>
        </div>
        <ul id="categoryList" style="list-style:none; margin:12px 0 0; padding:0; display:flex; flex-direction:column; gap:6px;"></ul>
      </div>
    </details>

    <form id="assignmentForm">
      <div class="sp-form-group">
        <label for="assignmentTitleInput">Title</label>
        <input type="text" id="assignmentTitleInput" maxlength="150" required>
      </div>
      <div class="sp-form-group">
        <label for="assignmentInstructionsInput">Instructions</label>
        <textarea id="assignmentInstructionsInput" rows="3" required></textarea>
      </div>
      <div style="display:flex; gap:12px; flex-wrap:wrap;">
        <div class="sp-form-group" style="flex:1; min-width:160px;">
          <label for="assignmentDueDateInput">Due date (optional)</label>
          <input type="date" id="assignmentDueDateInput">
        </div>
        <div class="sp-form-group" style="flex:1; min-width:160px;">
          <label for="assignmentMaxScoreInput">Max score (optional)</label>
          <input type="number" id="assignmentMaxScoreInput" min="1" max="9999" step="0.01">
        </div>
        <div class="sp-form-group" style="flex:1; min-width:160px;">
          <label for="assignmentCategoryInput">Category (optional)</label>
          <select id="assignmentCategoryInput"><option value="">None</option></select>
        </div>
      </div>
      <div class="sp-form-error" id="assignmentFormError" role="alert" aria-live="assertive">
        <p class="sp-form-error-msg" id="assignmentFormErrorMsg"></p>
      </div>
      <div style="display:flex; gap:10px; align-items:center;">
        <button type="submit" class="sp-btn sp-btn-primary" id="assignmentPostBtn">
          <span class="sp-btn-spinner" hidden></span>
          <span class="sp-btn-label">Post assignment</span>
        </button>
        <button type="button" class="sp-btn sp-btn-secondary" id="cancelAssignmentEditBtn" hidden>Cancel edit</button>
      </div>
    </form>

    <ul class="sp-announce-list" id="assignmentList" style="margin-top:20px;"></ul>
  </div>
</div>

<!-- Submissions dialog -->
<dialog class="sp-dialog sp-dialog-lg" id="submissionsDialog">
  <div class="sp-dialog-body">
    <p class="sp-dialog-title" id="submissionsTitle">Submissions</p>
    <p class="sp-dialog-message" id="submissionsSubtitle"></p>
    <div class="sp-form-error" id="submissionsFormError" role="alert" aria-live="assertive">
      <p class="sp-form-error-msg" id="submissionsFormErrorMsg"></p>
    </div>
    <div class="sp-form-success" id="submissionsFormSuccess" role="status" aria-live="polite">
      <p class="sp-form-success-msg" id="submissionsFormSuccessMsg"></p>
    </div>
    <table class="sp-table" id="submissionsTable">
      <thead>
        <tr><th>Student No.</th><th>Full Name</th><th>File</th><th>Submitted</th><th>Score</th><th>Feedback</th><th>Comments</th></tr>
      </thead>
      <tbody id="submissionsTableBody"></tbody>
    </table>

    <div id="commentThreadPanel" hidden style="margin-top:16px; padding:12px; background:var(--paper-100); border-radius:var(--sp-radius-sm);">
      <p style="font-weight:600; font-size:13.5px; margin:0 0 8px;" id="commentThreadTitle">Comments</p>
      <ul id="commentThreadList" style="list-style:none; margin:0 0 10px; padding:0; display:flex; flex-direction:column; gap:6px; max-height:180px; overflow-y:auto;"></ul>
      <div style="display:flex; gap:8px;">
        <input type="text" id="commentThreadInput" class="sp-table-input" style="flex:1;" maxlength="1000" placeholder="Add a comment…">
        <button type="button" class="sp-btn sp-btn-secondary" id="commentThreadSendBtn">Send</button>
      </div>
    </div>
  </div>
  <div class="sp-dialog-actions">
    <button type="button" class="sp-btn sp-btn-secondary" id="closeSubmissionsDialog">Close</button>
    <button type="button" class="sp-btn sp-btn-primary" id="saveSubmissionScoresBtn">
      <span class="sp-btn-spinner" hidden></span>
      <span class="sp-btn-label">Save scores</span>
    </button>
  </div>
</dialog>

<?php endif; ?>

<?php include __DIR__ . '/Include/footer.php'; ?>
