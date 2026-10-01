<?php /** Announcements tab for course_detail.php. Expects $scheduleId in scope. */ ?>
<form id="announceForm">
  <div class="sp-form-group">
    <label for="announceTitleInput">Title</label>
    <input type="text" id="announceTitleInput" maxlength="150" required placeholder="e.g. Midterm Exam Schedule">
  </div>
  <div class="sp-form-group">
    <label for="announceBodyInput">Message</label>
    <textarea id="announceBodyInput" rows="3" maxlength="2000" required placeholder="Write your announcement…"></textarea>
  </div>
  <div class="sp-form-error" id="announceFormError" role="alert" aria-live="assertive">
    <p class="sp-form-error-msg" id="announceFormErrorMsg"></p>
  </div>
  <button type="submit" class="sp-btn sp-btn-primary" id="announcePostBtn">
    <span class="sp-btn-spinner" hidden></span>
    <span class="sp-btn-label">Post announcement</span>
  </button>
</form>

<ul class="sp-announce-list" id="announceList" style="margin-top:20px;"></ul>
