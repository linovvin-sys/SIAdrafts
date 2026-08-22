<?php
if (session_status() === PHP_SESSION_NONE) session_start();
if (!empty($_SESSION['user_id'])) {
    header('Location: dashboard.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Staff Login — EduSchool</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Fraunces:ital,wght@0,300;0,400;0,500;1,400;1,500&family=Inter:wght@400;500;600;700&family=IBM+Plex+Mono:wght@400;500;600&display=swap" rel="stylesheet">
<link rel="stylesheet" href="/SIAdrafts/Frontend/Css/staff-login.css">
<script src="https://code.iconify.design/iconify-icon/3.0.0/iconify-icon.min.js"></script>
</head>
<body class="badge-body">

  <div class="badge-texture" aria-hidden="true"></div>

  <a class="badge-wordmark" href="/SIAdrafts/Frontend/View/index.php">Edu<em>School</em></a>

  <a class="badge-home-btn" href="/SIAdrafts/Frontend/View/index.php">
    <iconify-icon icon="mdi:arrow-left"></iconify-icon>
    <span>Back to homepage</span>
  </a>

  <main class="badge-stage">
    <div class="badge-shell">
      <span class="badge-strap" aria-hidden="true"></span>
      <span class="badge-clip" aria-hidden="true"></span>

      <div class="badge-brand">
        <div class="badge-brand-ambient" aria-hidden="true">
          <span class="badge-brand-orb"></span>
        </div>
        <div class="badge-brand-mark">
          <iconify-icon icon="mdi:school"></iconify-icon>
        </div>
        <p class="badge-brand-name">Edu<em>School</em></p>
        <p class="badge-brand-tagline">Registrar, Admission, and Faculty tools — all in one place.</p>
        <div class="badge-brand-lines" aria-hidden="true"><span></span><span></span><span></span></div>
      </div>

      <div class="badge-card">
        <div class="badge-photo">
          <iconify-icon icon="mdi:badge-account-horizontal-outline"></iconify-icon>
        </div>

        <p class="badge-eyebrow">Staff &amp; Faculty Access</p>
        <h1 class="badge-title">Access your workspace</h1>
        <p class="badge-sub">Sign in with your registrar-issued credentials.</p>

        <form id="login-form" action="/SIAdrafts/Backend/api/Auth/login.php" method="POST" autocomplete="off" novalidate>

          <div class="badge-field">
            <label for="login-username">Username / Email</label>
            <input type="text" id="login-username" name="username" placeholder="e.g. jdelacruz" required autocomplete="username">
          </div>

          <div class="badge-field">
            <label for="login-password">Password</label>
            <div class="badge-input-wrap">
              <input type="password" id="login-password" name="password" placeholder="Enter your password" required autocomplete="current-password">
              <button type="button" class="badge-eye" data-target="login-password" aria-label="Show password">
                <iconify-icon icon="mdi:eye-outline"></iconify-icon>
              </button>
            </div>
          </div>

          <button type="submit" id="login-btn" class="badge-submit">
            <span class="btn-login-sheen" aria-hidden="true"></span>
            <iconify-icon icon="mdi:badge-account-outline"></iconify-icon>
            <span>Sign in</span>
          </button>

        </form>

        <p class="badge-footnote">
          Are you a student? <a href="/SIAdrafts/Frontend/View/Student/login.php">Go to the Student Portal &rarr;</a>
        </p>
      </div>
    </div>
  </main>

  <div class="badge-terminal" id="loginTerminal" aria-hidden="true">
    <div class="badge-terminal-backdrop" data-terminal-dismiss></div>
    <div class="badge-terminal-card" role="dialog" aria-modal="true" aria-labelledby="loginTerminalStep">
      <div class="badge-terminal-stage">
        <div class="badge-terminal-ring">
          <svg class="badge-terminal-glyph badge-terminal-check" viewBox="0 0 52 52" aria-hidden="true">
            <circle class="badge-terminal-glyph-ring" cx="26" cy="26" r="23"/>
            <path class="badge-terminal-glyph-mark" d="M15 27l7.2 7.2L37.5 19"/>
          </svg>
          <svg class="badge-terminal-glyph badge-terminal-x" viewBox="0 0 52 52" aria-hidden="true">
            <circle class="badge-terminal-glyph-ring" cx="26" cy="26" r="23"/>
            <path class="badge-terminal-glyph-mark" d="M18 18l16 16M34 18L18 34"/>
          </svg>
        </div>
        <p class="badge-terminal-step" id="loginTerminalStep" aria-live="polite">Checking credentials&hellip;</p>
        <div class="badge-terminal-track"><div class="badge-terminal-fill" id="loginTerminalFill"></div></div>
      </div>
      <div class="badge-terminal-result" id="loginTerminalResult" aria-live="polite"></div>
    </div>
  </div>

<script src="/SIAdrafts/Frontend/Js/Admission/login.js"></script>
</body>
</html>
