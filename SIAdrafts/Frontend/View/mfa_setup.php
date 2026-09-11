<?php
require_once __DIR__ . '/../../Backend/session_security.php';
require_once __DIR__ . '/../../Backend/db.php';
require_once __DIR__ . '/../../Backend/csrf.php';
require_once __DIR__ . '/../../Backend/roles.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Usable by either a staff or a professor session — MFA applies to both
// independently (see staff_mfa's migration), so this resolves which one
// is signed in rather than merging the two otherwise-isolated auth
// systems the way require_role()/require_professor() individually would.
if (!empty($_SESSION['user_id']) && !empty($_SESSION['role_name'])) {
    session_touch_or_expire();
    $loginType = 'staff';
    $accountId = (int)($_SESSION['user_id'] ?? 0);
    $displayName = $_SESSION['full_name'] ?? $_SESSION['username'] ?? 'your account';
    $backUrl = staff_dashboard_url($_SESSION['role_name']);
} elseif (!empty($_SESSION['professor_id'])) {
    session_touch_or_expire();
    $loginType = 'professor';
    $accountId = (int)($_SESSION['professor_id'] ?? 0);
    $displayName = $_SESSION['full_name'] ?? $_SESSION['username'] ?? 'your account';
    $backUrl = staff_dashboard_url('professor');
} else {
    header('Location: /SIAdrafts/Frontend/View/login.php');
    exit;
}

$db   = new Database();
$conn = $db->connect();

$stmt = $conn->prepare("SELECT enabled FROM staff_mfa WHERE login_type = ? AND account_id = ?");
$stmt->bind_param('si', $loginType, $accountId);
$stmt->execute();
$row = $stmt->get_result()->fetch_assoc();
$stmt->close();
$db->close();

$mfaEnabled = $row && (int)$row['enabled'] === 1;
$csrfToken  = csrf_token();
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Two-Factor Authentication — EduSchool</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Fraunces:ital,wght@0,300;0,400;0,500;1,400;1,500&family=Inter:wght@400;500;600;700&family=IBM+Plex+Mono:wght@400;500;600&display=swap" rel="stylesheet">
<style>
  :root {
    --ink: #1c1b19;
    --paper: #f7f5f1;
    --card: #ffffff;
    --line: #e2ded4;
    --muted: #6b6459;
    --accent: #2f5d3f;
    --accent-soft: #e6efe6;
    --danger: #9a3324;
    --danger-soft: #f6e8e5;
  }
  * { box-sizing: border-box; }
  body {
    margin: 0;
    background: var(--paper);
    color: var(--ink);
    font-family: 'Inter', system-ui, sans-serif;
    padding: 40px 20px 80px;
  }
  .wrap { max-width: 560px; margin: 0 auto; }
  a.back { color: var(--muted); font-size: 13px; text-decoration: none; }
  a.back:hover { text-decoration: underline; }
  h1 {
    font-family: 'Fraunces', serif;
    font-weight: 500;
    font-size: 30px;
    margin: 18px 0 4px;
  }
  .sub { color: var(--muted); margin: 0 0 28px; font-size: 15px; }
  .card {
    background: var(--card);
    border: 1px solid var(--line);
    border-radius: 12px;
    padding: 24px;
    margin-bottom: 16px;
  }
  .status-pill {
    display: inline-block;
    font-family: 'IBM Plex Mono', monospace;
    font-size: 11px;
    letter-spacing: .05em;
    text-transform: uppercase;
    padding: 4px 10px;
    border-radius: 99px;
    margin-bottom: 14px;
  }
  .status-pill.on { color: var(--accent); background: var(--accent-soft); }
  .status-pill.off { color: var(--muted); background: #efece4; }
  label { display: block; font-size: 13px; font-weight: 600; margin-bottom: 6px; }
  input[type=text], input[type=password] {
    width: 100%;
    font-size: 16px;
    padding: 10px 12px;
    border: 1px solid var(--line);
    border-radius: 8px;
    margin-bottom: 14px;
    font-family: inherit;
  }
  .secret-box {
    font-family: 'IBM Plex Mono', monospace;
    font-size: 18px;
    letter-spacing: .08em;
    background: var(--accent-soft);
    padding: 14px;
    border-radius: 8px;
    text-align: center;
    margin-bottom: 14px;
    word-break: break-all;
  }
  button {
    font-family: inherit;
    font-size: 15px;
    font-weight: 600;
    padding: 11px 18px;
    border-radius: 8px;
    border: none;
    cursor: pointer;
  }
  button.primary { background: var(--accent); color: #fff; }
  button.danger { background: var(--danger); color: #fff; }
  button.ghost { background: transparent; color: var(--muted); border: 1px solid var(--line); }
  .msg { font-size: 14px; margin-top: 10px; }
  .msg.error { color: var(--danger); }
  .msg.ok { color: var(--accent); }
  .recovery-list {
    font-family: 'IBM Plex Mono', monospace;
    font-size: 14px;
    background: var(--danger-soft);
    border-radius: 8px;
    padding: 14px 18px;
    margin: 14px 0;
    line-height: 1.8;
  }
  [hidden] { display: none !important; }
</style>
</head>
<body>
<div class="wrap">
  <a class="back" href="<?= htmlspecialchars($backUrl, ENT_QUOTES) ?>">&larr; Back to dashboard</a>
  <h1>Two-Factor Authentication</h1>
  <p class="sub">Signed in as <?= htmlspecialchars($displayName, ENT_QUOTES) ?></p>

  <div class="card">
    <span class="status-pill <?= $mfaEnabled ? 'on' : 'off' ?>">
      <?= $mfaEnabled ? 'Enabled' : 'Not enabled' ?>
    </span>

    <div id="disabledView" <?= $mfaEnabled ? 'hidden' : '' ?>>
      <p>Add a second step at login using an authenticator app (Google Authenticator, Authy, 1Password, etc.).</p>
      <button class="primary" id="startBtn" type="button">Enable two-factor authentication</button>
      <div id="setupArea" hidden>
        <p style="margin-top:20px;">Enter this code manually into your authenticator app (there's no QR scan on this page):</p>
        <div class="secret-box" id="secretDisplay"></div>
        <label for="confirmCode">Enter the 6-digit code your app shows</label>
        <input type="text" id="confirmCode" inputmode="numeric" autocomplete="one-time-code" maxlength="6" placeholder="123456">
        <button class="primary" id="confirmBtn" type="button">Confirm and enable</button>
      </div>
      <div id="recoveryArea" hidden>
        <p><strong>Save these recovery codes now.</strong> Each one can be used once if you lose access to your authenticator app. They won't be shown again.</p>
        <div class="recovery-list" id="recoveryList"></div>
        <button class="ghost" id="doneBtn" type="button">I've saved these — done</button>
      </div>
      <div class="msg" id="setupMsg"></div>
    </div>

    <div id="enabledView" <?= $mfaEnabled ? '' : 'hidden' ?>>
      <p>Two-factor authentication is protecting this account. Disabling it requires your password.</p>
      <label for="disablePassword">Current password</label>
      <input type="password" id="disablePassword" autocomplete="current-password">
      <button class="danger" id="disableBtn" type="button">Disable two-factor authentication</button>
      <div class="msg" id="disableMsg"></div>
    </div>
  </div>
</div>

<script>
const CSRF_TOKEN = <?= json_encode($csrfToken) ?>;

async function postForm(url, data) {
  const body = new URLSearchParams({ csrf_token: CSRF_TOKEN, ...data });
  const res = await fetch(url, { method: 'POST', body });
  return res.json();
}

const startBtn = document.getElementById('startBtn');
const setupArea = document.getElementById('setupArea');
const secretDisplay = document.getElementById('secretDisplay');
const confirmBtn = document.getElementById('confirmBtn');
const confirmCode = document.getElementById('confirmCode');
const setupMsg = document.getElementById('setupMsg');
const recoveryArea = document.getElementById('recoveryArea');
const recoveryList = document.getElementById('recoveryList');
const doneBtn = document.getElementById('doneBtn');

startBtn?.addEventListener('click', async () => {
  setupMsg.textContent = '';
  const result = await postForm('/SIAdrafts/Backend/api/Auth/mfa_setup_start.php', {});
  if (result.error) {
    setupMsg.textContent = result.error;
    setupMsg.className = 'msg error';
    return;
  }
  secretDisplay.textContent = result.secret_grouped;
  startBtn.hidden = true;
  setupArea.hidden = false;
});

confirmBtn?.addEventListener('click', async () => {
  setupMsg.textContent = '';
  const result = await postForm('/SIAdrafts/Backend/api/Auth/mfa_setup_confirm.php', { code: confirmCode.value });
  if (result.error) {
    setupMsg.textContent = result.error;
    setupMsg.className = 'msg error';
    return;
  }
  setupArea.hidden = true;
  recoveryList.innerHTML = result.recovery_codes.map(c => c.replace(/[<>&]/g, '')).join('<br>');
  recoveryArea.hidden = false;
});

doneBtn?.addEventListener('click', () => {
  window.location.reload();
});

const disableBtn = document.getElementById('disableBtn');
const disablePassword = document.getElementById('disablePassword');
const disableMsg = document.getElementById('disableMsg');

disableBtn?.addEventListener('click', async () => {
  disableMsg.textContent = '';
  const result = await postForm('/SIAdrafts/Backend/api/Auth/mfa_disable.php', { password: disablePassword.value });
  if (result.error) {
    disableMsg.textContent = result.error;
    disableMsg.className = 'msg error';
    return;
  }
  window.location.reload();
});
</script>
</body>
</html>
