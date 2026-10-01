<?php /** Quizzes tab for course_detail.php. Expects $scheduleId in scope. */ ?>
<div id="quizEmptyState" class="sp-empty" hidden>
  <iconify-icon icon="mdi:cursor-default-click-outline"></iconify-icon>
  <p>Loading quizzes…</p>
</div>

<div id="quizPanel">
  <button type="button" class="sp-btn sp-btn-primary" id="openGenerateQuizBtn" style="margin-bottom:20px;">
    <iconify-icon icon="mdi:file-upload-outline"></iconify-icon>
    <span>Generate quiz from a lesson file</span>
  </button>

  <details id="manualQuizDetails" style="margin-bottom:20px;">
    <summary class="sp-table-action" style="display:inline-flex; cursor:pointer;">
      <iconify-icon icon="mdi:pencil-plus-outline"></iconify-icon> Or create one manually
    </summary>
  <form id="quizForm" style="margin-top:16px;">
    <div class="sp-form-group">
      <label for="quizTitleInput">Title</label>
      <input type="text" id="quizTitleInput" maxlength="150" required placeholder="e.g. Midterm Quiz — Chapter 3">
    </div>
    <div class="sp-form-group">
      <label for="quizInstructionsInput">Instructions (optional)</label>
      <textarea id="quizInstructionsInput" rows="2" placeholder="Any notes students should see before starting…"></textarea>
    </div>
    <div style="display:flex; gap:12px; flex-wrap:wrap;">
      <div class="sp-form-group" style="flex:1; min-width:140px;">
        <label for="quizTimeLimitInput">Time limit (minutes)</label>
        <input type="number" id="quizTimeLimitInput" min="1" max="180" value="10" required>
      </div>
      <div class="sp-form-group" style="flex:1; min-width:200px;">
        <label for="quizAvailableFromInput">Opens at (optional)</label>
        <input type="datetime-local" id="quizAvailableFromInput">
      </div>
      <div class="sp-form-group" style="flex:1; min-width:200px;">
        <label for="quizAvailableUntilInput">Closes at (optional)</label>
        <input type="datetime-local" id="quizAvailableUntilInput">
      </div>
      <div class="sp-form-group" style="flex:1; min-width:160px;">
        <label for="quizQuestionsPerAttemptInput">Questions per attempt (optional)</label>
        <input type="number" id="quizQuestionsPerAttemptInput" min="1" max="999" placeholder="All questions">
      </div>
    </div>
    <div class="sp-form-error" id="quizFormError" role="alert" aria-live="assertive">
      <p class="sp-form-error-msg" id="quizFormErrorMsg"></p>
    </div>
    <div style="display:flex; gap:10px; align-items:center;">
      <button type="submit" class="sp-btn sp-btn-primary" id="quizPostBtn">
        <span class="sp-btn-spinner" hidden></span>
        <span class="sp-btn-label">Create quiz</span>
      </button>
      <button type="button" class="sp-btn sp-btn-secondary" id="cancelQuizEditBtn" hidden>Cancel edit</button>
    </div>
  </form>
  </details>

  <ul class="sp-announce-list" id="quizList" style="margin-top:20px;"></ul>
</div>

<!-- Generate quiz from file (primary path: one form creates the quiz AND generates its questions) -->
<dialog class="sp-dialog sp-dialog-lg" id="generateQuizDialog">
  <div class="sp-dialog-body">
    <p class="sp-dialog-title">Generate quiz from a lesson file</p>
    <p class="sp-dialog-message">Upload a PDF, DOCX, or PPTX — we turn its content into True/False and identification questions automatically (no AI). You'll land on the review list right after, and nothing is visible to students until you publish.</p>

    <form id="generateQuizForm">
      <div class="sp-form-group">
        <label for="genQuizTitleInput">Title</label>
        <input type="text" id="genQuizTitleInput" maxlength="150" required placeholder="e.g. Midterm Quiz — Chapter 3">
      </div>
      <div style="display:flex; gap:12px; flex-wrap:wrap;">
        <div class="sp-form-group" style="flex:1; min-width:140px;">
          <label for="genQuizTimeLimitInput">Time limit (minutes)</label>
          <input type="number" id="genQuizTimeLimitInput" min="1" max="180" value="10" required>
        </div>
        <div class="sp-form-group" style="flex:1; min-width:160px;">
          <label for="genQuizQuestionsPerAttemptInput">Questions per attempt</label>
          <input type="number" id="genQuizQuestionsPerAttemptInput" min="1" max="999" value="10" required>
        </div>
      </div>
      <div class="sp-form-group">
        <label for="genLessonFileInput">Lesson file</label>
        <input type="file" id="genLessonFileInput" accept=".pdf,.docx,.pptx" required>
      </div>
      <div class="sp-form-error" id="generateQuizFormError" role="alert" aria-live="assertive">
        <p class="sp-form-error-msg" id="generateQuizFormErrorMsg"></p>
      </div>
      <div style="display:flex; gap:10px; align-items:center;">
        <button type="submit" class="sp-btn sp-btn-primary" id="generateQuizBtn">
          <span class="sp-btn-spinner" hidden></span>
          <span class="sp-btn-label">Generate</span>
        </button>
        <button type="button" class="sp-btn sp-btn-secondary" id="cancelGenerateQuizBtn">Cancel</button>
      </div>
    </form>
  </div>
</dialog>

<!-- Question builder dialog (one quiz at a time) -->
<dialog class="sp-dialog sp-dialog-lg" id="questionsDialog">
  <div class="sp-dialog-body">
    <p class="sp-dialog-title" id="questionsDialogTitle">Questions</p>
    <p class="sp-dialog-message">Multiple choice only — mark exactly one choice correct per question.</p>

    <div class="sp-form-error" id="questionFormError" role="alert" aria-live="assertive">
      <p class="sp-form-error-msg" id="questionFormErrorMsg"></p>
    </div>

    <details class="sp-quiz-generate-details" style="margin-bottom:16px;">
      <summary class="sp-table-action" style="display:inline-flex; cursor:pointer;">
        <iconify-icon icon="mdi:file-upload-outline"></iconify-icon> Add more questions from another file
      </summary>
      <div style="margin-top:12px; padding:12px; background:var(--paper-50); border:1px solid var(--line-200); border-radius:var(--sp-radius-sm);">
        <p style="margin:0 0 10px; font-size:12.5px; color:var(--slate-500);">
          Upload a PDF, DOCX, or PPTX to generate more True/False and identification questions into this quiz's pool. No AI involved, and the file itself isn't kept after generating.
        </p>
        <form id="generateForm" style="display:flex; gap:10px; flex-wrap:wrap; align-items:flex-end;">
          <div class="sp-form-group" style="margin-bottom:0; flex:1; min-width:200px;">
            <label for="lessonFileInput">Lesson file</label>
            <input type="file" id="lessonFileInput" accept=".pdf,.docx,.pptx" required>
          </div>
          <div class="sp-form-group" style="margin-bottom:0; width:150px;">
            <label for="generateQuestionsPerAttemptInput">Questions per attempt</label>
            <input type="number" id="generateQuestionsPerAttemptInput" min="1" max="999" value="10">
          </div>
          <button type="submit" class="sp-btn sp-btn-secondary" id="generateBtn">
            <span class="sp-btn-spinner" hidden></span>
            <span class="sp-btn-label">Generate</span>
          </button>
        </form>
        <div class="sp-form-error" id="generateFormError" role="alert" aria-live="assertive">
          <p class="sp-form-error-msg" id="generateFormErrorMsg"></p>
        </div>
      </div>
    </details>

    <form id="questionForm" style="padding:12px; background:var(--paper-50); border:1px solid var(--line-200); border-radius:var(--sp-radius-sm); margin-bottom:16px;">
      <div class="sp-form-group">
        <label for="questionTextInput">Question</label>
        <textarea id="questionTextInput" rows="2" required placeholder="e.g. What is the powerhouse of the cell?"></textarea>
      </div>
      <div class="sp-form-group" style="max-width:120px;">
        <label for="questionPointsInput">Points</label>
        <input type="number" id="questionPointsInput" min="0.01" max="999" step="0.01" value="1">
      </div>
      <div id="choiceRows" style="display:flex; flex-direction:column; gap:8px; margin-bottom:8px;"></div>
      <button type="button" class="sp-table-action" id="addChoiceRowBtn" style="margin-bottom:12px;">
        <iconify-icon icon="mdi:plus"></iconify-icon> Add choice
      </button>
      <div style="display:flex; gap:10px;">
        <button type="submit" class="sp-btn sp-btn-primary" id="questionSaveBtn">
          <span class="sp-btn-spinner" hidden></span>
          <span class="sp-btn-label">Add question</span>
        </button>
        <button type="button" class="sp-btn sp-btn-secondary" id="cancelQuestionEditBtn" hidden>Cancel edit</button>
      </div>
    </form>

    <ul class="sp-announce-list" id="questionList"></ul>
  </div>
  <div class="sp-dialog-actions">
    <button type="button" class="sp-btn sp-btn-secondary" id="closeQuestionsDialog">Done</button>
  </div>
</dialog>

<!-- Roster dialog -->
<dialog class="sp-dialog sp-dialog-lg" id="rosterDialog">
  <div class="sp-dialog-body">
    <p class="sp-dialog-title" id="rosterDialogTitle">Results</p>
    <table class="sp-table" id="rosterTable">
      <thead>
        <tr><th>Student No.</th><th>Full Name</th><th>Status</th><th>Score</th><th>Strikes</th><th></th></tr>
      </thead>
      <tbody id="rosterTableBody"></tbody>
    </table>
  </div>
  <div class="sp-dialog-actions">
    <button type="button" class="sp-btn sp-btn-secondary" id="closeRosterDialog">Close</button>
  </div>
</dialog>

<!-- Attempt review dialog -->
<dialog class="sp-dialog sp-dialog-lg" id="reviewDialog">
  <div class="sp-dialog-body">
    <p class="sp-dialog-title" id="reviewDialogTitle">Attempt review</p>
    <div id="reviewQuestionList"></div>
    <p style="font-weight:600; font-size:13px; margin:16px 0 6px;">Violation timeline</p>
    <ul id="reviewViolationList" style="list-style:none; margin:0; padding:0; display:flex; flex-direction:column; gap:6px;"></ul>
    <p class="sp-announce-empty" id="reviewViolationEmpty" hidden>No violations logged for this attempt.</p>
  </div>
  <div class="sp-dialog-actions">
    <button type="button" class="sp-btn sp-btn-secondary" id="closeReviewDialog">Close</button>
  </div>
</dialog>
