<?php
if (session_status() === PHP_SESSION_NONE) session_start();
require_once __DIR__ . '/../../../../Backend/csrf.php';
$_pageCsrfToken = csrf_token();
$_logged_in  = !empty($_SESSION['user_id']);
$_role       = $_SESSION['role_name'] ?? '';

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
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Fraunces:ital,wght@0,300;0,400;0,500;1,400;1,500&family=Inter:wght@400;500;600;700&family=IBM+Plex+Mono:wght@400;500;600&display=swap" rel="stylesheet">
<link rel="stylesheet" href="/SIAdrafts/bootstrap-5.3.8-dist/css/bootstrap.min.css">
<link rel="stylesheet" href="/SIAdrafts/Frontend/Css/Admission/style.css">
<link rel="stylesheet" href="/SIAdrafts/Frontend/Css/Admission/admission.css">
<link rel="stylesheet" href="/SIAdrafts/Frontend/Css/Admission/login.css">
<link rel="stylesheet" href="/SIAdrafts/Frontend/Css/Admission/treasury.css">
<link rel="stylesheet" href="/SIAdrafts/Frontend/Css/required.css">
<?php if (isset($activePage)): ?>
<!-- $activePage is only ever set by pages that include Include/sidebar.php
     (13 of the 14 Admission pages routed through this header — every one
     except online_admission.php). Those pages need the real dashboard
     shell (.app-layout/.sidebar/.top-header/.page-content), which lives in
     admin.css and, until now, was never loaded here — so the sidebar and
     its top bar were rendering as an unstyled stack of links dumped above
     the page, which is what made every one of these pages (Treasury
     included) look like it had a huge gap up top. -->
<link rel="stylesheet" href="/SIAdrafts/Frontend/Css/Admin/admin.css">
<?php endif; ?>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script src="https://code.iconify.design/iconify-icon/3.0.0/iconify-icon.min.js"></script>
</head>
<body data-csrf="<?= htmlspecialchars($_pageCsrfToken, ENT_QUOTES) ?>">

  <?php if (!isset($activePage)): ?>
  <div class="nav-wrap" id="navWrap">
    <nav class="navbar <?= !$_logged_in ? 'navbar-guest' : '' ?>">
      <a class="brand" href="<?= $_logged_in ? '/SIAdrafts/Frontend/View/Admission/enrollment.php' : '/SIAdrafts/Frontend/View/index.php' ?>">
        <span class="brand-mark">
          <iconify-icon icon="mdi:school" style="color:#FAF7F0; font-size:19px;"></iconify-icon>
        </span>
        <span class="brand-name">Edu<em>School</em></span>
      </a>
      <ul class="nav-links">
        <?php if ($_logged_in && $_role === 'Staff'): ?>
          <li>
            <a href="/SIAdrafts/Frontend/View/Admission/enrollment.php" <?= in_array($_cur, $_enroll_pages) ? 'class="active"' : '' ?>>
              Enrollment
            </a>
          </li>
          <div class="nav-cta">
            <div class="nav-user-wrap">
              <div class="nav-user-trigger" tabindex="0" role="button" aria-haspopup="true" aria-expanded="false">
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
                <a href="/SIAdrafts/Backend/api/Auth/logout.php" class="nud-logout">
                  <iconify-icon icon="mdi:logout" style="font-size:15px;"></iconify-icon>
                  Log out
                </a>
              </div>
            </div>
            <button class="nav-toggle" id="navToggle" aria-label="Toggle menu" aria-expanded="false">
              <span></span>
            </button>
          </div>
        <?php elseif ($_logged_in && $_role === 'Admission'): ?>
          <li>
              <a href="/SIAdrafts/Frontend/View/Admission/admission.php" <?= in_array($_cur, $_admission_pages) ? 'class="active"' : '' ?>>
              Admission
              </a>
          </li>
          <div class="nav-cta">
            <div class="nav-user-wrap">
              <div class="nav-user-trigger" tabindex="0" role="button" aria-haspopup="true" aria-expanded="false">
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
                <a href="/SIAdrafts/Backend/api/Auth/logout.php" class="nud-logout">
                  <iconify-icon icon="mdi:logout" style="font-size:15px;"></iconify-icon>
                  Log out
                </a>
              </div>
            </div>
            <button class="nav-toggle" id="navToggle" aria-label="Toggle menu" aria-expanded="false">
              <span></span>
            </button>
          </div>
      </ul>
    
      <?php else: ?>
      <div class="nav-cta">
        <a href="/SIAdrafts/Frontend/View/index.php" class="nav-guest-home">
          <iconify-icon icon="mdi:arrow-left" style="font-size:15px;"></iconify-icon>
          Back to site
        </a>
        <button class="nav-toggle" id="navToggle" aria-label="Toggle menu" aria-expanded="false">
          <span></span>
        </button>
      </div>
      <?php endif; ?>
    </nav>
  </div>

  <div class="mobile-panel" id="mobilePanel">
    <?php if ($_logged_in): ?>
      <a href="/SIAdrafts/Frontend/View/Admission/enrollment.php" <?= in_array($_cur, $_enroll_pages) ? 'class="active"' : '' ?>>Enrollment</a>
      <a href="/SIAdrafts/Backend/api/Auth/logout.php" class="btn-enroll" style="background:var(--ink);color:#fff;justify-content:center;">
        Log out <iconify-icon icon="mdi:logout" style="font-size:14px;"></iconify-icon>
      </a>
    <?php else: ?>
      <a href="/SIAdrafts/Frontend/View/index.php">Home</a>
      <a href="/SIAdrafts/Frontend/View/login.php" class="active">Login</a>
    <?php endif; ?>
  </div>
  <?php endif; ?>