<?php
/** Materials tab content for course_detail.php. Expects $scheduleId in scope. See assignments.php tab partial for the pattern. */
?>
<form id="materialForm">
  <div class="sp-form-group">
    <label for="materialTitleInput">Title</label>
    <input type="text" id="materialTitleInput" maxlength="150" required placeholder="e.g. Week 5 Lecture Slides">
  </div>
  <div class="sp-form-group">
    <label for="materialTypeSelect">Type</label>
    <select class="sp-select" id="materialTypeSelect">
      <option value="file">File</option>
      <option value="link">Link</option>
      <option value="text">Text</option>
    </select>
  </div>
  <div class="sp-form-group" id="materialFileGroup">
    <label for="materialFileInput">File</label>
    <input type="file" id="materialFileInput">
  </div>
  <div class="sp-form-group" id="materialUrlGroup" hidden>
    <label for="materialUrlInput">Link URL</label>
    <input type="url" id="materialUrlInput" placeholder="https://…" maxlength="500">
  </div>
  <div class="sp-form-group" id="materialBodyGroup" hidden>
    <label for="materialBodyInput">Text content</label>
    <textarea id="materialBodyInput" rows="3" placeholder="Paste or type the note content…"></textarea>
  </div>
  <div class="sp-form-group">
    <label for="materialVisibleFromInput">Visible from (optional — leave blank to show immediately)</label>
    <input type="date" id="materialVisibleFromInput">
  </div>
  <div class="sp-form-error" id="materialFormError" role="alert" aria-live="assertive">
    <p class="sp-form-error-msg" id="materialFormErrorMsg"></p>
  </div>
  <div style="display:flex; gap:10px; align-items:center;">
    <button type="submit" class="sp-btn sp-btn-primary" id="materialPostBtn">
      <span class="sp-btn-spinner" hidden></span>
      <span class="sp-btn-label">Post material</span>
    </button>
    <button type="button" class="sp-btn sp-btn-secondary" id="cancelMaterialEditBtn" hidden>Cancel edit</button>
  </div>
</form>

<ul class="sp-announce-list" id="materialList" style="margin-top:20px;"></ul>
