<?php
/**
 * Shared shell for every Professor portal page. Expects $pageTitle and
 * $activePage to be set by the including view — same convention as the
 * Student portal's Include/header.php, which this mirrors structurally
 * (own tabs, own nav-user slot) rather than pulling from the staff
 * Backend/nav_config.php system.
 */
if (session_status() === PHP_SESSION_NONE) session_start();
require_once __DIR__ . '/../../../../Backend/csrf.php';
$_pageCsrfToken = csrf_token();
$_professorName = htmlspecialchars($_SESSION['full_name'] ?? '', ENT_QUOTES);
$_professorDept = htmlspecialchars($_SESSION['professor_department'] ?? '', ENT_QUOTES);

$_nameParts        = array_filter(explode(' ', $_SESSION['full_name'] ?? ''));
$_professorInitials = strtoupper(implode('', array_map(fn($p) => mb_substr($p, 0, 1), array_slice($_nameParts, 0, 2))));

$_tabs = [
    ['page' => 'dashboard', 'label' => 'Home',     'icon' => 'mdi:home-variant',           'url' => '/SIAdrafts/Frontend/View/Professor/professor_dashboard.php'],
    ['page' => 'schedule',  'label' => 'Schedule',  'icon' => 'mdi:calendar-week',          'url' => '/SIAdrafts/Frontend/View/Professor/schedule.php'],
    ['page' => 'classes',   'label' => 'Classes',   'icon' => 'mdi:google-classroom',       'url' => '/SIAdrafts/Frontend/View/Professor/classes.php'],
    ['page' => 'profile',   'label' => 'Profile',   'icon' => 'mdi:account-circle-outline', 'url' => '/SIAdrafts/Frontend/View/Professor/profile.php'],
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= htmlspecialchars($pageTitle ?? 'Professor Portal', ENT_QUOTES) ?> — EduSchool</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Fraunces:ital,wght@0,300;0,400;0,500;1,400;1,500&family=Inter:wght@400;500;600;700&family=IBM+Plex+Mono:wght@400;500;600&display=swap" rel="stylesheet">
<link rel="stylesheet" href="/SIAdrafts/Frontend/Css/Student/student.css">
<link rel="stylesheet" href="/SIAdrafts/Frontend/Css/Professor/professor.css">
<link rel="stylesheet" href="/SIAdrafts/Frontend/Css/required.css">
<script src="https://code.iconify.design/iconify-icon/3.0.0/iconify-icon.min.js"></script>
</head>
<body class="student-body" data-csrf="<?= htmlspecialchars($_pageCsrfToken, ENT_QUOTES) ?>">
<a class="sp-skip-link" href="#sp-content">Skip to content</a>

<nav class="sp-nav" aria-label="Professor portal">
  <a class="sp-brand" href="/SIAdrafts/Frontend/View/Professor/professor_dashboard.php">
    Edu<em>School</em>
  </a>

  <ul class="sp-nav-links">
    <?php foreach ($_tabs as $t): ?>
      <li>
        <a class="sp-nav-link <?= ($activePage ?? '') === $t['page'] ? 'active' : '' ?>" href="<?= $t['url'] ?>">
          <iconify-icon icon="<?= $t['icon'] ?>"></iconify-icon>
          <?= $t['label'] ?>
        </a>
      </li>
    <?php endforeach; ?>
  </ul>

  <div class="sp-nav-user">
    <div class="sp-nav-user-info">
      <div class="sp-nav-avatar" aria-hidden="true"><?= htmlspecialchars($_professorInitials ?: '?', ENT_QUOTES) ?></div>
      <div class="sp-nav-user-text">
        <div class="sp-nav-user-name"><?= $_professorName ?></div>
        <div class="sp-nav-user-id"><?= $_professorDept ?></div>
      </div>
    </div>
    <a class="sp-logout" href="/SIAdrafts/Backend/api/Auth/logout.php" title="Log out" aria-label="Log out">
      <iconify-icon icon="mdi:logout" style="font-size:16px;"></iconify-icon>
    </a>
  </div>
</nav>

<main class="sp-main" id="sp-content">