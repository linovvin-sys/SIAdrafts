<?php
$pageTitle  = "Groups";
$activePage = "groups";
$pageScript = "groups";

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

<h1 class="sp-greeting">Groups</h1>
<p class="sp-subline">Randomly split a class into groups for an activity — reshuffle any time.</p>

<?php if (empty($classes)): ?>
  <div class="sp-empty">
    <iconify-icon icon="mdi:account-group-outline"></iconify-icon>
    <p><strong>No class assignments yet.</strong></p>
    <p>You'll be able to create groups once the Registrar's Office assigns your classes.</p>
  </div>
<?php else: ?>

<div class="sp-section">
  <?php $selectId = 'groupsClassSelect'; include __DIR__ . '/Include/class_picker.php'; ?>

  <div id="groupsEmptyState" class="sp-empty" style="margin-top:16px;">
    <iconify-icon icon="mdi:cursor-default-click-outline"></iconify-icon>
    <p>Select a class above to create or view groups.</p>
  </div>

  <div id="groupsPanel" hidden style="margin-top:16px;">
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
  </div>
</div>

<?php endif; ?>

<?php include __DIR__ . '/Include/footer.php'; ?>
