<?php
$pageTitle  = "My Profile";
$activePage = "profile";
$pageScript = "profile";

require_once __DIR__ . '/../../../Backend/require_professor.php';
require_professor();
require_once __DIR__ . '/../../../Backend/db.php';

$db   = new Database();
$conn = $db->connect();

$profStmt = $conn->prepare("
    SELECT p.professor_id, p.first_name, p.middle_name, p.last_name, p.username, p.email,
           d.department_code, d.department_name
    FROM professor p
    JOIN department d ON d.department_id = p.department_id
    WHERE p.professor_id = ? LIMIT 1
");
$profStmt->bind_param('i', $_SESSION['professor_id']);
$profStmt->execute();
$professor = $profStmt->get_result()->fetch_assoc();
$profStmt->close();
$db->close();

include __DIR__ . '/Include/header.php';
?>

<h1 class="sp-greeting">My Profile</h1>
<p class="sp-subline">Your account information and portal credentials.</p>

<?php if (!$professor): ?>
  <div class="sp-empty">
    <iconify-icon icon="mdi:account-alert-outline"></iconify-icon>
    <p><strong>Your account is not yet linked to a professor record.</strong></p>
    <p>Please contact the Registrar's Office.</p>
  </div>
<?php else: ?>

  <div class="sp-section">
    <h2 class="sp-section-title">My Information</h2>
    <div class="sp-field-grid">
      <div class="sp-field">
        <p class="sp-field-label">Name</p>
        <p class="sp-field-value"><?= htmlspecialchars(trim($professor['first_name'] . ' ' . ($professor['middle_name'] ?? '') . ' ' . $professor['last_name']), ENT_QUOTES) ?></p>
      </div>
      <div class="sp-field">
        <p class="sp-field-label">Department</p>
        <p class="sp-field-value"><?= htmlspecialchars($professor['department_code'] . ' — ' . $professor['department_name'], ENT_QUOTES) ?></p>
      </div>
      <div class="sp-field">
        <p class="sp-field-label">Username</p>
        <p class="sp-field-value"><?= htmlspecialchars($professor['username'] ?? '', ENT_QUOTES) ?></p>
      </div>
    </div>
  </div>

  <div class="sp-section">
    <h2 class="sp-section-title">Update Email</h2>
    <div class="sp-form-error" id="emailError" role="alert" aria-live="assertive">
      <p class="sp-form-error-msg" id="emailErrorMsg"></p>
    </div>
    <div class="sp-form-group">
      <label for="profileEmail">Email</label>
      <input type="email" id="profileEmail" value="<?= htmlspecialchars($professor['email'] ?? '', ENT_QUOTES) ?>">
    </div>
    <button type="button" class="sp-btn sp-btn-primary" id="saveEmailBtn">
      <span class="sp-btn-spinner" hidden></span>
      <span class="sp-btn-label">Save Email</span>
    </button>
  </div>

  <div class="sp-section">
    <h2 class="sp-section-title">Change Password</h2>
    <div class="sp-form-error" id="pwError" role="alert" aria-live="assertive">
      <p class="sp-form-error-msg" id="pwErrorMsg"></p>
    </div>
    <div class="sp-form-group">
      <label for="currentPassword">Current password</label>
      <div class="sp-input-wrap">
        <input type="password" id="currentPassword" autocomplete="current-password">
        <button type="button" class="sp-input-toggle" data-for="currentPassword" aria-label="Show password" aria-pressed="false">
          <iconify-icon icon="mdi:eye-outline"></iconify-icon>
        </button>
      </div>
    </div>
    <div class="sp-form-group">
      <label for="newPassword">New password</label>
      <div class="sp-input-wrap">
        <input type="password" id="newPassword" autocomplete="new-password" minlength="8">
        <button type="button" class="sp-input-toggle" data-for="newPassword" aria-label="Show password" aria-pressed="false">
          <iconify-icon icon="mdi:eye-outline"></iconify-icon>
        </button>
      </div>
    </div>
    <div class="sp-form-group">
      <label for="confirmPassword">Confirm new password</label>
      <div class="sp-input-wrap">
        <input type="password" id="confirmPassword" autocomplete="new-password" minlength="8">
        <button type="button" class="sp-input-toggle" data-for="confirmPassword" aria-label="Show password" aria-pressed="false">
          <iconify-icon icon="mdi:eye-outline"></iconify-icon>
        </button>
      </div>
    </div>
    <button type="button" class="sp-btn sp-btn-primary" id="changePasswordBtn">
      <span class="sp-btn-spinner" hidden></span>
      <span class="sp-btn-label">Change Password</span>
    </button>
  </div>

<?php endif; ?>

<?php include __DIR__ . '/Include/footer.php'; ?>
