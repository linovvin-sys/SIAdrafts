<?php
session_start();

if (!empty($_SESSION['student_id'])) {
    header('Location: /SIAdrafts/Frontend/View/Student/dashboard.php');
    exit;
}

$hour = (int)date('G');
$greeting = $hour < 12 ? 'Good morning.' : ($hour < 18 ? 'Good afternoon.' : 'Good evening.');
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Student Portal Login — EduSchool</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Fraunces:ital,wght@0,300;0,400;0,500;1,400;1,500&family=Inter:wght@400;500;600;700&family=IBM+Plex+Mono:wght@400;500;600&display=swap" rel="stylesheet">
<link rel="stylesheet" href="/SIAdrafts/Frontend/Css/Student/student.css">
<script src="https://code.iconify.design/iconify-icon/3.0.0/iconify-icon.min.js"></script>
</head>
<body class="student-body">

<div class="sp-auth-shell">

  <div class="sp-auth-visual">
    <div class="sp-auth-orb sp-auth-orb-1" aria-hidden="true"></div>
    <div class="sp-auth-orb sp-auth-orb-2" aria-hidden="true"></div>

    <div class="sp-auth-visual-topbar">
      <a class="sp-auth-brand" href="/SIAdrafts/Frontend/View/index.php">Edu<em>School</em></a>
      <a class="sp-auth-back" href="/SIAdrafts/Frontend/View/index.php">
        <iconify-icon icon="mdi:arrow-left"></iconify-icon> Back to homepage
      </a>
    </div>

    <div class="sp-auth-visual-body">
      <p class="sp-auth-clock-date" id="authDate">&nbsp;</p>
      <p class="sp-auth-clock-time" id="authClock">--:--</p>
      <h1 class="sp-auth-visual-title"><?= $greeting ?></h1>
      <p class="sp-auth-visual-sub">Sign in to view your enrollment, schedule, and balance for the term.</p>
      <div class="sp-auth-dayprogress" aria-hidden="true">
        <div class="sp-auth-dayprogress-fill" id="authDayProgress"></div>
      </div>

      <ul class="sp-auth-features">
        <li>
          <iconify-icon icon="mdi:calendar-week"></iconify-icon>
          <span>Your weekly class schedule, always up to date</span>
        </li>
        <li>
          <iconify-icon icon="mdi:cash-multiple"></iconify-icon>
          <span>Real-time balance and payment status</span>
        </li>
        <li>
          <iconify-icon icon="mdi:clipboard-check-outline"></iconify-icon>
          <span>Which requirements are still missing, at a glance</span>
        </li>
      </ul>
    </div>

    <p class="sp-auth-visual-foot">Need help getting in? Contact the Registrar's Office.</p>
  </div>

  <div class="sp-auth-form-side">
    <div class="sp-auth-card">
      <div class="sp-auth-icon sp-auth-in" style="--i:0;">
        <iconify-icon icon="mdi:school-outline" style="font-size:19px;"></iconify-icon>
      </div>
      <h2 class="sp-auth-title sp-auth-in" style="--i:1;">Student Portal</h2>
      <p class="sp-auth-sub sp-auth-in" style="--i:2;">Sign in with the student number and password from your enrollment confirmation slip.</p>

      <div class="sp-form-error" id="loginError" role="alert" aria-live="assertive">
        <p class="sp-form-error-msg" id="loginErrorMsg"></p>
        <p class="sp-form-error-hint">Forgot your password or can't sign in? Contact the Registrar's Office for help.</p>
      </div>

      <form id="loginForm" novalidate>
        <div class="sp-form-group sp-auth-in" style="--i:3;">
          <label for="student_no">Student number</label>
          <input type="text" id="student_no" name="student_no" placeholder="2026-00042" autocomplete="username" required>
        </div>
        <div class="sp-form-group sp-auth-in" style="--i:4;">
          <label for="password">Password</label>
          <div class="sp-input-wrap">
            <input type="password" id="password" name="password" autocomplete="current-password" required>
            <button type="button" class="sp-input-toggle" data-for="password" aria-label="Show password" aria-pressed="false">
              <iconify-icon icon="mdi:eye-outline"></iconify-icon>
            </button>
          </div>
        </div>
        <button type="submit" class="sp-btn sp-btn-primary sp-auth-in" style="width:100%; --i:5;" id="loginSubmit">
          <span class="sp-btn-spinner" hidden></span>
          <span class="sp-btn-label">Log in</span>
        </button>
      </form>
    </div>

    <p class="sp-auth-side-note sp-auth-in" style="--i:6;">
      Not enrolled yet? <a href="/SIAdrafts/Frontend/View/Admission/online_admission.php">Start your application →</a>
    </p>
  </div>

</div>

<script src="/SIAdrafts/Frontend/Js/Student/login.js"></script>
</body>
</html>
