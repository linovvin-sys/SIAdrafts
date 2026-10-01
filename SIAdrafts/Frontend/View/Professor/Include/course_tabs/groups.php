<?php /** Groups tab for course_detail.php. Expects $scheduleId in scope. */ ?>
<div style="display:flex; align-items:flex-end; gap:12px; flex-wrap:wrap;">
  <div class="sp-form-group" style="margin-bottom:0;">
    <label for="groupCountInput">Number of groups</label>
    <input type="number" id="groupCountInput" min="1" value="4" style="width:100px;">
  </div>
  <button type="button" class="sp-btn sp-btn-primary" id="randomizeBtn">
    <span class="sp-btn-spinner" hidden></span>
    <span class="sp-btn-label"><iconify-icon icon="mdi:shuffle-variant"></iconify-icon> Randomize groups</span>
  </button>
  <span id="rosterCountLabel" style="color:var(--slate-300); font-size:13px;"></span>
</div>
<div class="sp-form-error" id="groupsFormError" role="alert" aria-live="assertive">
  <p class="sp-form-error-msg" id="groupsFormErrorMsg"></p>
</div>

<div id="groupsGrid" style="display:grid; grid-template-columns: repeat(auto-fill, minmax(220px, 1fr)); gap:16px; margin-top:20px;"></div>
