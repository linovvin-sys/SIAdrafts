<?php
$pageTitle  = "SETTINGS";
$activePage = "settings";

require_once '../../../Backend/auth.php';
require_once '../../../Backend/roles.php';
require_once '../../../Backend/require_role.php';
require_role([ROLE_ADMIN, ROLE_HEAD_REGISTRAR]);
require_once '../../../Backend/settings.php';
require_once '../../../Backend/school_branding.php';
require_once '../../../Backend/csrf.php';

$currentSchoolYear = get_setting('current_school_year');
$currentSemester   = get_setting('current_semester');
$branding          = get_school_branding();
$token             = csrf_token();

include '../Include/header.php';
?>

<div class="app-layout">
    <?php include '../Include/sidebar.php'; ?>

    <main class="page-content">
      <div class="panel-stack rd-settings-stack">

        <div class="panel">
          <div class="panel-header">
            <span class="panel-title">Current Term</span>
          </div>
          <div class="panel-body">
            <form id="settingsForm">
              <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($token, ENT_QUOTES) ?>">
              <div class="rd-field">
                <label>Current School Year (YYYY-YYYY)</label>
                <input type="text" class="mono" name="current_school_year"
                       value="<?= htmlspecialchars($currentSchoolYear ?? '', ENT_QUOTES) ?>" pattern="\d{4}-\d{4}" required>
              </div>
              <div class="rd-field">
                <label>Current Semester</label>
                <select name="current_semester" required>
                  <?php foreach (['1' => '1st Semester', '2' => '2nd Semester', '3' => 'Summer'] as $val => $label): ?>
                    <option value="<?= $val ?>" <?= $currentSemester === $val ? 'selected' : '' ?>><?= $label ?></option>
                  <?php endforeach; ?>
                </select>
              </div>
              <button type="submit" class="btn btn-primary">Save</button>
              <div id="settingsMsg" class="row-secondary" style="margin-top:8px;"></div>
            </form>
          </div>
        </div>

        <div class="panel">
          <div class="panel-header">
            <span class="panel-title">Branding</span>
          </div>
          <div class="panel-body">
            <p class="row-secondary" style="margin:-6px 0 18px;">What students, staff, and anyone visiting this site see as the school's identity — the name, the logo in the nav, and the browser-tab icon.</p>

            <form id="brandNameForm">
              <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($token, ENT_QUOTES) ?>">
              <div class="rd-field">
                <label>School Name</label>
                <input type="text" name="school_name" maxlength="80"
                       value="<?= htmlspecialchars($branding['name'], ENT_QUOTES) ?>"
                       placeholder="e.g. Saint Michael's Academy" required>
              </div>
              <button type="submit" class="btn btn-primary">Save Name</button>
              <div id="brandNameMsg" class="row-secondary" style="margin-top:8px;"></div>
            </form>

            <div class="rd-divider-tick"></div>

            <div class="rd-field">
              <label>Logo</label>
              <div class="rd-asset-row">
                <div class="rd-asset-preview">
                  <img id="logoPreview" src="<?= htmlspecialchars($branding['logo'], ENT_QUOTES) ?>" alt="">
                </div>
                <div class="rd-asset-info">
                  <div class="rd-asset-label">Shown in the nav/sidebar on every page</div>
                  <div class="rd-asset-hint">SVG, PNG, or JPG — max 2MB</div>
                </div>
                <label class="btn btn-outline rd-upload-btn">
                  Change
                  <input type="file" id="logoInput" accept=".svg,.png,.jpg,.jpeg">
                </label>
              </div>
              <div id="logoMsg" class="rd-asset-status"></div>
            </div>

            <div class="rd-field" style="margin-top:18px;">
              <label>Favicon</label>
              <div class="rd-asset-row">
                <div class="rd-asset-preview">
                  <img id="faviconPreview" src="<?= htmlspecialchars($branding['favicon'], ENT_QUOTES) ?>" alt="">
                </div>
                <div class="rd-asset-info">
                  <div class="rd-asset-label">Browser tab icon</div>
                  <div class="rd-asset-hint">SVG, PNG, or ICO — max 2MB</div>
                </div>
                <label class="btn btn-outline rd-upload-btn">
                  Change
                  <input type="file" id="faviconInput" accept=".svg,.png,.ico">
                </label>
              </div>
              <div id="faviconMsg" class="rd-asset-status"></div>
            </div>

            <p class="row-secondary" style="margin-top:18px;">Changes apply immediately — reload any open page to see them.</p>
          </div>
        </div>

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
    const res = await fetch('/SIAdrafts/Backend/api/Settings/update_setting.php', { method: 'POST', body });
    const data = await res.json();
    if (!data.success) {
      msgEl.textContent = data.error || 'Failed to save ' + key;
      return;
    }
  }
  msgEl.textContent = 'Saved.';
});

document.getElementById('brandNameForm').addEventListener('submit', async function (e) {
  e.preventDefault();
  const form = e.target;
  const msgEl = document.getElementById('brandNameMsg');
  msgEl.textContent = 'Saving...';
  const body = new URLSearchParams({
    csrf_token: form.csrf_token.value,
    setting_key: 'school_name',
    setting_value: form.school_name.value,
  });
  const res = await fetch('/SIAdrafts/Backend/api/Settings/update_setting.php', { method: 'POST', body });
  const data = await res.json();
  msgEl.textContent = data.success ? 'Saved — reload any open page to see it everywhere.' : (data.error || 'Failed to save.');
});

// Shared upload wiring for logo + favicon -- same endpoint, just a
// different asset_type and which preview <img>/status line it updates.
function wireBrandingUpload(inputId, previewId, msgId, assetType) {
  const input = document.getElementById(inputId);
  const preview = document.getElementById(previewId);
  const msgEl = document.getElementById(msgId);
  input.addEventListener('change', async function () {
    const file = input.files[0];
    if (!file) return;
    msgEl.className = 'rd-asset-status';
    msgEl.textContent = 'Uploading...';
    const body = new FormData();
    body.append('csrf_token', document.querySelector('input[name="csrf_token"]').value);
    body.append('asset_type', assetType);
    body.append('file', file);
    try {
      const res = await fetch('/SIAdrafts/Backend/api/Settings/upload_branding_asset.php', { method: 'POST', body });
      const data = await res.json();
      if (!data.success) {
        msgEl.className = 'rd-asset-status is-error';
        msgEl.textContent = data.error || 'Upload failed.';
        return;
      }
      preview.src = data.path + '&v=' + Date.now();
      msgEl.className = 'rd-asset-status is-ok';
      msgEl.textContent = 'Saved — reload any open page to see it everywhere.';
    } catch (err) {
      msgEl.className = 'rd-asset-status is-error';
      msgEl.textContent = 'Could not reach the server. Please try again.';
    }
  });
}
wireBrandingUpload('logoInput', 'logoPreview', 'logoMsg', 'logo');
wireBrandingUpload('faviconInput', 'faviconPreview', 'faviconMsg', 'favicon');
</script>

<?php include '../Include/footer.php'; ?>
