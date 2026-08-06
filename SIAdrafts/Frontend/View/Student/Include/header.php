<?php
/**
 * Shared shell for every Student portal page. Expects $pageTitle and
 * $activePage to be set by the including view, same convention as the
 * staff Include/header.php files.
 */
if (session_status() === PHP_SESSION_NONE) session_start();
require_once __DIR__ . '/../../../../Backend/csrf.php';
$_pageCsrfToken = csrf_token();
$_studentName = htmlspecialchars($_SESSION['student_full_name'] ?? '', ENT_QUOTES);
$_studentNo   = htmlspecialchars($_SESSION['student_no'] ?? '', ENT_QUOTES);

$_nameParts     = array_filter(explode(' ', $_SESSION['student_full_name'] ?? ''));
$_studentInitials = strtoupper(implode('', array_map(fn($p) => mb_substr($p, 0, 1), array_slice($_nameParts, 0, 2))));

$_tabs = [
    ['page' => 'dashboard',       'label' => 'Home',            'icon' => 'mdi:home-variant',        'url' => '/SIAdrafts/Frontend/View/Student/dashboard.php'],
    ['page' => 'registration',    'label' => 'Registration',    'icon' => 'mdi:file-document-outline','url' => '/SIAdrafts/Frontend/View/Student/registration.php'],
    ['page' => 'schedule',        'label' => 'Schedule',        'icon' => 'mdi:calendar-week',       'url' => '/SIAdrafts/Frontend/View/Student/schedule.php'],
    ['page' => 'accountabilities','label' => 'Accountabilities','icon' => 'mdi:cash-multiple',       'url' => '/SIAdrafts/Frontend/View/Student/accountabilities.php'],
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= htmlspecialchars($pageTitle ?? 'Student Portal', ENT_QUOTES) ?> — EduSchool</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Fraunces:ital,wght@0,300;0,400;0,500;1,400;1,500&family=Inter:wght@400;500;600;700&family=IBM+Plex+Mono:wght@400;500;600&display=swap" rel="stylesheet">
<link rel="stylesheet" href="/SIAdrafts/Frontend/Css/Student/student.css">
<script src="https://code.iconify.design/iconify-icon/3.0.0/iconify-icon.min.js"></script>
</head>
<body class="student-body" data-csrf="<?= htmlspecialchars($_pageCsrfToken, ENT_QUOTES) ?>">
<a class="sp-skip-link" href="#sp-content">Skip to content</a>

<nav class="sp-nav" aria-label="Student portal">
  <a class="sp-brand" href="/SIAdrafts/Frontend/View/Student/dashboard.php">
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
      <div class="sp-nav-avatar" aria-hidden="true"><?= htmlspecialchars($_studentInitials ?: '?', ENT_QUOTES) ?></div>
      <div class="sp-nav-user-text">
        <div class="sp-nav-user-name"><?= $_studentName ?></div>
        <div class="sp-nav-user-id"><?= $_studentNo ?></div>
      </div>
    </div>
    <a class="sp-logout" href="/SIAdrafts/Backend/api/student_logout.php" title="Log out" aria-label="Log out">
      <iconify-icon icon="mdi:logout" style="font-size:16px;"></iconify-icon>
    </a>
  </div>
</nav>

<main class="sp-main" id="sp-content">
