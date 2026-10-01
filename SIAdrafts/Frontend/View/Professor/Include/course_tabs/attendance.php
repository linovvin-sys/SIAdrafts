<?php /** Attendance tab for course_detail.php. Expects $scheduleId in scope. */ ?>
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
