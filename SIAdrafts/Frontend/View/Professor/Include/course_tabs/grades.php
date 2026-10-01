<?php /** Grades tab for course_detail.php. Expects $scheduleId in scope. */ ?>
<div class="sp-form-group" style="max-width:220px; margin-bottom:16px;">
  <label for="gradesPeriodSelect">Grading period</label>
  <select class="sp-select" id="gradesPeriodSelect">
    <option value="Prelim">Prelim</option>
    <option value="Midterm">Midterm</option>
    <option value="Prefinal">Prefinal</option>
    <option value="Final">Final</option>
  </select>
</div>

<div class="sp-form-error" id="gradesFormError" role="alert" aria-live="assertive">
  <p class="sp-form-error-msg" id="gradesFormErrorMsg"></p>
</div>
<div class="sp-form-success" id="gradesFormSuccess" role="status" aria-live="polite">
  <p class="sp-form-success-msg" id="gradesFormSuccessMsg"></p>
</div>
<table class="sp-table sp-table-stagger" id="gradesTable">
  <thead>
    <tr><th>Student No.</th><th>Full Name</th><th>Grade</th><th>Remarks</th></tr>
  </thead>
  <tbody id="gradesTableBody"></tbody>
</table>
<button type="button" class="sp-btn sp-btn-primary" id="saveGradesBtn" style="margin-top:16px;">
  <span class="sp-btn-spinner" hidden></span>
  <span class="sp-btn-label">Save grades</span>
</button>
