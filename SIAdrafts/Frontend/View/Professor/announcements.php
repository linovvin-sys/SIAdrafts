<?php
$pageTitle  = "Announcements";
$activePage = "announcements";
$pageScript = "announcements";

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

<h1 class="sp-greeting">Announcements</h1>
<p class="sp-subline">Post updates for one of your classes. Everyone enrolled in that class will see it.</p>

<?php if (empty($classes)): ?>
  <div class="sp-empty">
    <iconify-icon icon="mdi:bullhorn-outline"></iconify-icon>
    <p><strong>No class assignments yet.</strong></p>
    <p>You'll be able to post announcements once the Registrar's Office assigns your classes.</p>
  </div>
<?php else: ?>

<div class="sp-section">
  <?php $selectId = 'announceClassSelect'; include __DIR__ . '/Include/class_picker.php'; ?>

  <div id="announceEmptyState" class="sp-empty" style="margin-top:16px;">
    <iconify-icon icon="mdi:cursor-default-click-outline"></iconify-icon>
    <p>Select a class above to view or post announcements.</p>
  </div>

  <div id="announcePanel" hidden style="margin-top:16px;">
    <form id="announceForm">
      <div class="sp-form-group">
        <label for="announceTitleInput">Title</label>
        <input type="text" id="announceTitleInput" maxlength="150" required>
      </div>
      <div class="sp-form-group">
        <label for="announceBodyInput">Message</label>
        <textarea id="announceBodyInput" rows="3" maxlength="2000" required></textarea>
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
  </div>
</div>

<?php endif; ?>

<?php include __DIR__ . '/Include/footer.php'; ?>
