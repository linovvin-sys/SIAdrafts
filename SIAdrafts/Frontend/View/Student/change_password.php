<?php
require_once __DIR__ . '/../../../Backend/require_student.php';
require_student();
require_once __DIR__ . '/../../../Backend/csrf.php';
$_pageCsrfToken = csrf_token();
$_forced = !empty($_SESSION['must_change_password']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Set a new password — EduSchool</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Fraunces:ital,wght@0,300;0,400;0,500;1,400;1,500&family=Inter:wght@400;500;600;700&family=IBM+Plex+Mono:wght@400;500;600&display=swap" rel="stylesheet">
<link rel="stylesheet" href="/SIAdrafts/Frontend/Css/Student/student.css">
<script src="https://code.iconify.design/iconify-icon/3.0.0/iconify-icon.min.js"></script>
</head>
<body class="student-body" data-csrf="<?= htmlspecialchars($_pageCsrfToken, ENT_QUOTES) ?>">

<div class="sp-auth-shell">

  <div class="sp-auth-visual">
    <div class="sp-auth-orb sp-auth-orb-1" aria-hidden="true"></div>
    <div class="sp-auth-orb sp-auth-orb-2" aria-hidden="true"></div>

    <span class="sp-auth-brand">Edu<em>School</em></span>

    <div class="sp-auth-visual-body">
      <p class="sp-auth-clock-date" id="authDate">&nbsp;</p>
      <p class="sp-auth-clock-time" id="authClock">--:--</p>
      <h1 class="sp-auth-visual-title"><?= $_forced ? 'Almost there.' : 'Stay secure.' ?></h1>
      <p class="sp-auth-visual-sub">
        <?= $_forced
          ? "You're signed in with a temporary password — set your own before continuing to the portal."
          : 'Choose a new password for your account.' ?>
      </p>
      <div class="sp-auth-dayprogress" aria-hidden="true">
        <div class="sp-auth-dayprogress-fill" id="authDayProgress"></div>
      </div>
    </div>

    <p class="sp-auth-visual-foot">Forgot your current password? Contact the Registrar's Office.</p>
  </div>

  <div class="sp-auth-form-side">
    <div class="sp-auth-card">
      <div class="sp-auth-icon sp-auth-in" style="--i:0; margin-bottom:18px;">
        <iconify-icon icon="mdi:lock-reset" style="font-size:19px;"></iconify-icon>
      </div>
      <h2 class="sp-auth-title sp-auth-in" style="--i:1;">Set a new password</h2>
      <p class="sp-auth-sub sp-auth-in" style="--i:2;">
        <?= $_forced
          ? 'For your security, replace the temporary password before continuing.'
          : 'Choose a new password for your account.' ?>
      </p>

      <div class="sp-form-error" id="cpError" role="alert" aria-live="assertive">
        <p class="sp-form-error-msg" id="cpErrorMsg"></p>
        <p class="sp-form-error-hint">Forgot your current password? Contact the Registrar's Office to have it reset.</p>
      </div>

      <form id="cpForm" novalidate>
        <div class="sp-form-group sp-auth-in" style="--i:3;">
          <label for="current_password">Current password</label>
          <div class="sp-input-wrap">
            <input type="password" id="current_password" name="current_password" autocomplete="current-password" required>
            <button type="button" class="sp-input-toggle" data-for="current_password" aria-label="Show password" aria-pressed="false">
              <iconify-icon icon="mdi:eye-outline"></iconify-icon>
            </button>
          </div>
        </div>
        <div class="sp-form-group sp-auth-in" style="--i:4;">
          <label for="new_password">New password</label>
          <div class="sp-input-wrap">
            <input type="password" id="new_password" name="new_password" autocomplete="new-password" minlength="8" required>
            <button type="button" class="sp-input-toggle" data-for="new_password" aria-label="Show password" aria-pressed="false">
              <iconify-icon icon="mdi:eye-outline"></iconify-icon>
            </button>
          </div>
        </div>
        <div class="sp-form-group sp-auth-in" style="--i:5;">
          <label for="confirm_password">Confirm new password</label>
          <div class="sp-input-wrap">
            <input type="password" id="confirm_password" name="confirm_password" autocomplete="new-password" minlength="8" required>
            <button type="button" class="sp-input-toggle" data-for="confirm_password" aria-label="Show password" aria-pressed="false">
              <iconify-icon icon="mdi:eye-outline"></iconify-icon>
            </button>
          </div>
        </div>
        <button type="submit" class="sp-btn sp-btn-primary sp-auth-in" style="width:100%; --i:6;" id="cpSubmit">
          <span class="sp-btn-spinner" hidden></span>
          <span class="sp-btn-label">Save password</span>
        </button>
      </form>
    </div>
  </div>

</div>

<script src="/SIAdrafts/Frontend/Js/Student/change_password.js"></script>
</body>
</html>
