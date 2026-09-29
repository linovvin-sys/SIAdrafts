<?php
$pageTitle  = "Materials";
$activePage = "materials";
$pageScript = "materials";

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

<h1 class="sp-greeting">Course Materials</h1>
<p class="sp-subline">Share files, links, or notes with one of your classes.</p>

<?php if (empty($classes)): ?>
  <div class="sp-empty">
    <iconify-icon icon="mdi:folder-multiple-outline"></iconify-icon>
    <p><strong>No class assignments yet.</strong></p>
    <p>You'll be able to post materials once the Registrar's Office assigns your classes.</p>
  </div>
<?php else: ?>

<div class="sp-section">
  <?php $selectId = 'materialClassSelect'; include __DIR__ . '/Include/class_picker.php'; ?>

  <div id="materialEmptyState" class="sp-empty" style="margin-top:16px;">
    <iconify-icon icon="mdi:cursor-default-click-outline"></iconify-icon>
    <p>Select a class above to view or post materials.</p>
  </div>

  <div id="materialPanel" hidden style="margin-top:16px;">
    <form id="materialForm">
      <div class="sp-form-group">
        <label for="materialTitleInput">Title</label>
        <input type="text" id="materialTitleInput" maxlength="150" required>
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
        <textarea id="materialBodyInput" rows="3"></textarea>
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
  </div>
</div>

<?php endif; ?>

<?php include __DIR__ . '/Include/footer.php'; ?>
