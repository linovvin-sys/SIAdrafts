<?php
$pageTitle = "REVENUE";
$activePage = "revenue";
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>EduSchool — <?= $pageTitle ?></title>
  <link rel="stylesheet" href="../css/admin.css" />
</head>
<body>
<div class="app-layout">

  <?php include 'sidebar.php'; ?>

    <main class="page-content">

      <div class="stats-grid" style="grid-template-columns: repeat(3,1fr); margin-bottom:24px;">
        <div class="stat-card">
          <div class="stat-icon green">💰</div>
          <div><div class="stat-value">₱2.4M</div><div class="stat-label">Total Revenue</div></div>
        </div>
        <div class="stat-card">
          <div class="stat-icon gold">📈</div>
          <div><div class="stat-value">₱1.9M</div><div class="stat-label">Collected</div></div>
        </div>
        <div class="stat-card">
          <div class="stat-icon blue">⏳</div>
          <div><div class="stat-value">₱500K</div><div class="stat-label">Outstanding</div></div>
        </div>
      </div>

      <div class="panel">
        <div class="panel-header">
          <span class="panel-title">Revenue by Course</span>
          <button class="btn btn-outline">Export CSV</button>
        </div>
        <div class="panel-body" style="padding:0">
          <table class="data-table">
            <thead>
              <tr>
                <th>Course</th>
                <th>Enrolled</th>
                <th>Fee / Student</th>
                <th>Total Expected</th>
                <th>Collected</th>
                <th>Balance</th>
              </tr>
            </thead>
            <tbody>
              <tr>
                <td>BS Computer Science</td>
                <td>312</td>
                <td>₱18,000</td>
                <td>₱5,616,000</td>
                <td>₱4,800,000</td>
                <td>₱816,000</td>
              </tr>
              <tr>
                <td>BS Nursing</td>
                <td>280</td>
                <td>₱22,000</td>
                <td>₱6,160,000</td>
                <td>₱5,500,000</td>
                <td>₱660,000</td>
              </tr>
              <tr>
                <td>BS Accountancy</td>
                <td>198</td>
                <td>₱16,000</td>
                <td>₱3,168,000</td>
                <td>₱2,900,000</td>
                <td>₱268,000</td>
              </tr>
              <tr>
                <td>BS Education</td>
                <td>154</td>
                <td>₱14,000</td>
                <td>₱2,156,000</td>
                <td>₱2,000,000</td>
                <td>₱156,000</td>
              </tr>
              <tr>
                <td>BS Engineering</td>
                <td>340</td>
                <td>₱20,000</td>
                <td>₱6,800,000</td>
                <td>₱6,200,000</td>
                <td>₱600,000</td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
    </main>
  </div>
</div>
<script src="../js/admin.js"></script>
</body>
</html>
