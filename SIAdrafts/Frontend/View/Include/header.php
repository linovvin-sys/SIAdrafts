<?php
if (session_status() === PHP_SESSION_NONE) session_start();
$extraCss = $extraCss ?? [];
require_once __DIR__ . '/../../../Backend/csrf.php';
$_pageCsrfToken = csrf_token();

// Cache-busting: appends the file's last-modified time as a query string,
// so the browser fetches a fresh copy the moment a local CSS/JS file
// changes, instead of silently serving a stale cached one indefinitely
// (these files have no version string at all otherwise, and a normal
// reload doesn't reliably invalidate a cached stylesheet).
function asset_url(string $publicPath): string {
    $fsPath = __DIR__ . '/../../../' . ltrim(str_replace('/SIAdrafts/', '', $publicPath), '/');
    $mtime = @filemtime($fsPath);
    return $publicPath . ($mtime ? '?v=' . $mtime : '');
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>EduSchool — <?= htmlspecialchars($pageTitle ?? '', ENT_QUOTES) ?></title>
  <script>
    // Applies the saved theme before first paint, so there's no flash of
    // light mode before dark mode kicks in. Must run synchronously, here
    // in <head>, before any CSS that depends on [data-theme] is used.
    (function () {
      try {
        var saved = localStorage.getItem('sia_theme');
        if (saved === 'dark' || saved === 'light') {
          document.documentElement.setAttribute('data-theme', saved);
        }
      } catch (e) {}
    })();
  </script>
  <link rel="stylesheet" href="/SIAdrafts/bootstrap-5.3.8-dist/css/bootstrap.min.css" />
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" />
  <link rel="stylesheet" href="https://cdn.datatables.net/1.13.8/css/dataTables.bootstrap5.min.css" />
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Fraunces:ital,wght@0,300;0,400;0,500;1,400;1,500&family=Inter:wght@400;500;600;700&family=IBM+Plex+Mono:wght@400;500;600&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="<?= htmlspecialchars(asset_url('/SIAdrafts/Frontend/Css/Admin/admin.css'), ENT_QUOTES) ?>" />
  <link rel="stylesheet" href="<?= htmlspecialchars(asset_url('/SIAdrafts/Frontend/Css/Admin/schedule.css'), ENT_QUOTES) ?>" />
  <link rel="stylesheet" href="<?= htmlspecialchars(asset_url('/SIAdrafts/Frontend/Css/Admin/modal.css'), ENT_QUOTES) ?>" />
  <link rel="stylesheet" href="<?= htmlspecialchars(asset_url('/SIAdrafts/Frontend/Css/Registrar/registrar.css'), ENT_QUOTES) ?>" />
  <link rel="stylesheet" href="<?= htmlspecialchars(asset_url('/SIAdrafts/Frontend/Css/required.css'), ENT_QUOTES) ?>" />
  <?php foreach ($extraCss as $href): ?>
    <link rel="stylesheet" href="<?= htmlspecialchars(asset_url($href), ENT_QUOTES) ?>" />
  <?php endforeach; ?>
  <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
  <script src="https://cdn.jsdelivr.net/npm/chart.js@4"></script>
  <script src="https://code.iconify.design/iconify-icon/3.0.0/iconify-icon.min.js"></script>
</head>
<body data-csrf="<?= htmlspecialchars($_pageCsrfToken, ENT_QUOTES) ?>" data-readonly="<?= (($_SESSION['role_name'] ?? '') === 'Admin') ? '1' : '0' ?>">
