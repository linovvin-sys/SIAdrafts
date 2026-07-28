<?php
if (session_status() === PHP_SESSION_NONE) session_start();
$extraCss = $extraCss ?? [];
require_once __DIR__ . '/../../../Backend/csrf.php';
$_pageCsrfToken = csrf_token();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>EduSchool — <?= htmlspecialchars($pageTitle ?? '', ENT_QUOTES) ?></title>
  <link rel="stylesheet" href="/SIAdrafts/bootstrap-5.3.8-dist/css/bootstrap.min.css" />
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" />
  <link rel="stylesheet" href="https://cdn.datatables.net/1.13.8/css/dataTables.bootstrap5.min.css" />
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Fraunces:ital,wght@0,300;0,400;0,500;1,400;1,500&family=Inter:wght@400;500;600;700&family=IBM+Plex+Mono:wght@400;500;600&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="/SIAdrafts/Frontend/Css/Admin/admin.css" />
  <link rel="stylesheet" href="/SIAdrafts/Frontend/Css/Admin/schedule.css" />
  <link rel="stylesheet" href="/SIAdrafts/Frontend/Css/Admin/modal.css" />
  <link rel="stylesheet" href="/SIAdrafts/Frontend/Css/Registrar/registrar.css" />
  <?php foreach ($extraCss as $href): ?>
    <link rel="stylesheet" href="<?= htmlspecialchars($href, ENT_QUOTES) ?>" />
  <?php endforeach; ?>
  <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
  <script src="https://cdn.jsdelivr.net/npm/chart.js@4"></script>
</head>
<body data-csrf="<?= htmlspecialchars($_pageCsrfToken, ENT_QUOTES) ?>">
