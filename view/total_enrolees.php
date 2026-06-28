<?php
$pageTitle = "TOTAL ENROLEES";
$activePage = "total_enrolees";
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

      <div class="stats-grid" style="grid-template-columns:repeat(4,1fr); margin-bottom:24px;">
        <div class="stat-card">
          <div class="stat-icon gold">👥</div>
          <div><div class="stat-value">1,284</div><div class="stat-label">Total Enrolees</div></div>
        </div>
        <div class="stat-card">
          <div class="stat-icon blue">🆕</div>
          <div><div class="stat-value">523</div><div class="stat-label">New Students</div></div>
        </div>
        <div class="stat-card">
          <div class="stat-icon green">🔄</div>
          <div><div class="stat-value">761</div><div class="stat-label">Continuing</div></div>
        </div>
        <div class="stat-card">
          <div class="stat-icon purple">👨‍🎓</div>
          <div><div class="stat-value">48</div><div class="stat-label">Irregular</div></div>
        </div>
      </div>

      <div class="panel">
        <div class="panel-header">
          <span class="panel-title">Enrolees by Course & Year Level</span>
          <button class="btn btn-outline">Export Report</button>
        </div>
        <div class="panel-body" style="padding:0">
          <table class="data-table">
            <thead>
              <tr>
                <th>Course</th>
                <th>1st Year</th>
                <th>2nd Year</th>
                <th>3rd Year</th>
                <th>4th Year</th>
                <th>Total</th>
              </tr>
            </thead>
            <tbody>
              <tr>
                <td>BS Computer Science</td>
                <td>98</td>
                <td>84</td>
                <td>78</td>
                <td>52</td>
                <td><strong>312</strong></td>
              </tr>
              <tr>
                <td>BS Nursing</td>
                <td>90</td>
                <td>75</td>
                <td>68</td>
                <td>47</td>
                <td><strong>280</strong></td>
              </tr>
              <tr>
                <td>BS Accountancy</td>
                <td>62</td>
                <td>55</td>
                <td>48</td>
                <td>33</td>
                <td><strong>198</strong></td>
              </tr>
              <tr>
                <td>BS Education</td>
                <td>50</td>
                <td>42</td>
                <td>38</td>
                <td>24</td>
                <td><strong>154</strong></td>
              </tr>
              <tr>
                <td>BS Engineering</td>
                <td>110</td>
                <td>95</td>
                <td>82</td>
                <td>53</td>
                <td><strong>340</strong></td>
              </tr>
              <tr style="background:#faf7f2">
                <td><strong>Grand Total</strong></td>
                <td><strong>410</strong></td>
                <td><strong>351</strong></td>
                <td><strong>314</strong></td>
                <td><strong>209</strong></td>
                <td><strong>1,284</strong></td>
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
