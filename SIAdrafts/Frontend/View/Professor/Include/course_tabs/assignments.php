<?php
/**
 * Assignments tab content for course_detail.php. Expects $scheduleId in
 * scope. This is the same panel assignments.php's #assignmentPanel used
 * to render, minus the class-picker and "select a class first" empty
 * state -- the page is already scoped to one class by the time this is
 * included, so assignments.js's fixedScheduleId path takes over instead
 * of the dropdown-driven one.
 */
?>
<details style="margin-bottom:16px;">
  <summary class="sp-table-action" style="display:inline-flex; cursor:pointer;">
    <iconify-icon icon="mdi:shape-outline"></iconify-icon> Manage categories (for weighted grading)
  </summary>
  <div style="margin-top:12px; padding:12px; background:var(--paper-50); border:1px solid var(--line-200); border-radius:var(--sp-radius-sm);">
    <div style="display:flex; gap:10px; flex-wrap:wrap; align-items:flex-end;">
      <div class="sp-form-group" style="margin-bottom:0; flex:1; min-width:140px;">
        <label for="categoryNameInput">Category name</label>
        <input type="text" id="categoryNameInput" maxlength="100" placeholder="e.g. Quizzes">
      </div>
      <div class="sp-form-group" style="margin-bottom:0; width:110px;">
        <label for="categoryWeightInput">Weight %</label>
        <input type="number" id="categoryWeightInput" min="1" max="100" step="1" placeholder="e.g. 20">
      </div>
      <button type="button" class="sp-btn sp-btn-secondary" id="addCategoryBtn">Add</button>
    </div>
    <ul id="categoryList" style="list-style:none; margin:12px 0 0; padding:0; display:flex; flex-direction:column; gap:6px;"></ul>
  </div>
</details>

<form id="assignmentForm">
  <div class="sp-form-group">
    <label for="assignmentTitleInput">Title</label>
    <input type="text" id="assignmentTitleInput" maxlength="150" required placeholder="e.g. Problem Set 3 — Chapter 4 Exercises">
  </div>
  <div class="sp-form-group">
    <label for="assignmentInstructionsInput">Instructions</label>
    <textarea id="assignmentInstructionsInput" rows="3" required placeholder="Describe what students need to do and submit…"></textarea>
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

    <div id="commentThreadPanel" hidden style="margin-top:16px; padding:12px; background:var(--paper-50); border:1px solid var(--line-200); border-radius:var(--sp-radius-sm);">
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
