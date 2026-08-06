<?php
$pageTitle  = "MY PROFILE";
$activePage = "profile";
$pageScript = "profile";

require_once '../../../Backend/auth.php';
require_once '../../../Backend/require_role.php';
require_once '../../../Backend/roles.php';
require_role([ROLE_PROFESSOR]);
require_once '../../../Backend/db.php';

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

$extraCss = ['/SIAdrafts/Frontend/Css/Professor/professor.css'];
include '../Include/header.php';
?>

<div class="app-layout">

  <?php include '../Include/sidebar.php'; ?>

  <main class="page-content">

    <?php if (!$professor): ?>
      <div class="panel">
        <div class="panel-body" style="padding:24px;">
          <p>Your account is not yet linked to a professor record. Please contact the Registrar's Office.</p>
        </div>
      </div>
    <?php else: ?>

      <div class="panel" style="margin-bottom:20px;">
        <div class="panel-header">
          <span class="panel-title">My Information</span>
        </div>
        <div class="panel-body" style="padding:20px 24px;">
          <div class="form-group">
            <label class="form-label">Name</label>
            <div><?= htmlspecialchars(trim($professor['first_name'] . ' ' . ($professor['middle_name'] ?? '') . ' ' . $professor['last_name'])) ?></div>
          </div>
          <div class="form-group">
            <label class="form-label">Department</label>
            <div><?= htmlspecialchars($professor['department_code']) ?> — <?= htmlspecialchars($professor['department_name']) ?></div>
          </div>
          <div class="form-group">
            <label class="form-label">Username</label>
            <div><?= htmlspecialchars($professor['username'] ?? '') ?></div>
          </div>
        </div>
      </div>

      <div class="panel" style="margin-bottom:20px;">
        <div class="panel-header">
          <span class="panel-title">Update Email</span>
        </div>
        <div class="panel-body" style="padding:20px 24px;">
          <div class="form-group">
            <label class="form-label">Email</label>
            <input type="email" id="profileEmail" class="form-input" value="<?= htmlspecialchars($professor['email'] ?? '') ?>">
          </div>
          <button type="button" class="btn btn-primary" id="saveEmailBtn">Save Email</button>
        </div>
      </div>

      <div class="panel">
        <div class="panel-header">
          <span class="panel-title">Change Password</span>
        </div>
        <div class="panel-body" style="padding:20px 24px;">
          <div class="form-group">
            <label class="form-label">Current Password</label>
            <input type="password" id="currentPassword" class="form-input">
          </div>
          <div class="form-group">
            <label class="form-label">New Password</label>
            <input type="password" id="newPassword" class="form-input" minlength="8" placeholder="Min. 8 characters">
          </div>
          <div class="form-group">
            <label class="form-label">Confirm New Password</label>
            <input type="password" id="confirmPassword" class="form-input">
          </div>
          <button type="button" class="btn btn-primary" id="changePasswordBtn">Change Password</button>
        </div>
      </div>

    <?php endif; ?>

  </main>
</div>

<?php
$extraScripts = [
    '/SIAdrafts/Frontend/Js/Professor/' . ($pageScript ?? 'professor') . '.js',
];
include '../Include/footer.php';
?>
