<?php
if (session_status() === PHP_SESSION_NONE) session_start();
$extraCss = $extraCss ?? [];
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
<body>
