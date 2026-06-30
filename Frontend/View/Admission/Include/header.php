<?php
if (session_status() === PHP_SESSION_NONE) session_start();
$_logged_in  = !empty($_SESSION['user_id']);
$_full_name  = htmlspecialchars($_SESSION['full_name'] ?? '', ENT_QUOTES);
$_cur        = basename($_SERVER['PHP_SELF']);
$_enroll_pages = ['enrollment.php','enrollment_profile.php','enrollment_subjects.php','enrollment_confirm.php'];
$_admission_pages = ['admission.php','admission_process.php','admission_confirm.php'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>EduSchool</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="/SIAdrafts/Frontend/Css/Admission/style.css">
<link rel="stylesheet" href="/SIAdrafts/Frontend/Css/Admission/admission.css">
<link rel="stylesheet" href="/SIAdrafts/Frontend/Css/Admission/login.css">

<script src="https://code.iconify.design/iconify-icon/3.0.0/iconify-icon.min.js"></script>
</head>
<body>

  <div class="nav-wrap" id="navWrap">
    <nav class="navbar <?= !$_logged_in ? 'navbar-guest' : '' ?>">
      <a class="brand" href="<?= $_logged_in ? 'enrollment.php' : 'index.php' ?>">
        <span class="brand-mark">
          <iconify-icon icon="mdi:school" style="color:#FAF7F0; font-size:19px;"></iconify-icon>
        </span>
        <span class="brand-name">Edu<em>School</em></span>
      </a>

      <?php if ($_logged_in): ?>
      <ul class="nav-links">
        <li>
          <a href="enrollment.php" <?= in_array($_cur, $_enroll_pages) ? 'class="active"' : '' ?>>
            Enrollment
          </a>
        </li>
        <li>
            <a href="admission.php" <?= in_array($_cur, $_admission_pages) ? 'class="active"' : '' ?>>
            Admission
            </a>
        </li>
      </ul>
      <div class="nav-cta">
        <div class="nav-user-wrap">
          <div class="nav-user-trigger">
            <iconify-icon icon="mdi:account-circle-outline" style="font-size:17px;"></iconify-icon>
            <span><?= $_full_name ?></span>
            <iconify-icon icon="mdi:chevron-down" class="nav-user-chevron"></iconify-icon>
          </div>
          <div class="nav-user-dropdown">
            <div class="nud-name">
              <iconify-icon icon="mdi:account-circle" style="font-size:16px;"></iconify-icon>
              <?= $_full_name ?>
            </div>
            <div class="nud-divider"></div>
            <a href="/SIAdrafts/Backend/api/logout.php" class="nud-logout">
              <iconify-icon icon="mdi:logout" style="font-size:15px;"></iconify-icon>
              Log out
            </a>
          </div>
        </div>
        <button class="nav-toggle" id="navToggle" aria-label="Toggle menu" aria-expanded="false">
          <span></span>
        </button>
      </div>
      <?php else: ?>
      <div class="nav-cta">
        <button class="nav-toggle" id="navToggle" aria-label="Toggle menu" aria-expanded="false">
          <span></span>
        </button>
      </div>
      <?php endif; ?>
    </nav>
  </div>

  <div class="mobile-panel" id="mobilePanel">
    <?php if ($_logged_in): ?>
      <a href="enrollment.php" <?= in_array($_cur, $_enroll_pages) ? 'class="active"' : '' ?>>Enrollment</a>
      <a href="/SIAdrafts/Backend/api/logout.php" class="btn-enroll" style="background:var(--ink);color:#fff;justify-content:center;">
        Log out <iconify-icon icon="mdi:logout" style="font-size:14px;"></iconify-icon>
      </a>
    <?php else: ?>
      <a href="index.php" class="active">Login</a>
    <?php endif; ?>
  </div>