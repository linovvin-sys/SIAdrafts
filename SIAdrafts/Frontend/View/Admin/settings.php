<?php
$pageTitle  = "SETTINGS";
$activePage = "settings";

require_once '../../../Backend/auth.php';
require_once '../../../Backend/roles.php';
require_once '../../../Backend/require_role.php';
require_role([ROLE_ADMIN, ROLE_HEAD_REGISTRAR]);
require_once '../../../Backend/settings.php';
require_once '../../../Backend/csrf.php';

$currentSchoolYear = get_setting('current_school_year');
$currentSemester   = get_setting('current_semester');
$token             = csrf_token();

include '../Include/header.php';
?>

<div class="app-layout">
    <?php include '../Include/sidebar.php'; ?>

    <main class="page-content">
      <div class="card p-4" style="max-width: 480px;">
        <h4 class="mb-3">Current Term</h4>
        <form id="settingsForm">
          <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($token, ENT_QUOTES) ?>">
          <div class="mb-3">
            <label class="form-label">Current School Year (YYYY-YYYY)</label>
            <input type="text" class="form-control" name="current_school_year"
                   value="<?= htmlspecialchars($currentSchoolYear ?? '', ENT_QUOTES) ?>" pattern="\d{4}-\d{4}" required>
          </div>
          <div class="mb-3">
            <label class="form-label">Current Semester</label>
            <select class="form-select" name="current_semester" required>
              <?php foreach (['1' => '1st Semester', '2' => '2nd Semester', '3' => 'Summer'] as $val => $label): ?>
                <option value="<?= $val ?>" <?= $currentSemester === $val ? 'selected' : '' ?>><?= $label ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <button type="submit" class="btn btn-primary">Save</button>
          <div id="settingsMsg" class="mt-2"></div>
        </form>
      </div>
    </main>
</div>

<script>
document.getElementById('settingsForm').addEventListener('submit', async function (e) {
  e.preventDefault();
  const form = e.target;
  const csrfToken = form.csrf_token.value;
  const fields = [
    ['current_school_year', form.current_school_year.value],
    ['current_semester', form.current_semester.value],
  ];
  const msgEl = document.getElementById('settingsMsg');
  msgEl.textContent = 'Saving...';

  for (const [key, value] of fields) {
    const body = new URLSearchParams({ csrf_token: csrfToken, setting_key: key, setting_value: value });
    const res = await fetch('/SIAdrafts/Backend/api/update_setting.php', { method: 'POST', body });
    const data = await res.json();
    if (!data.success) {
      msgEl.textContent = data.error || 'Failed to save ' + key;
      return;
    }
  }
  msgEl.textContent = 'Saved.';
});
</script>

<?php include '../Include/footer.php'; ?>
