<?php
$pageTitle = "ENROLLMENT";
$activePage = "enrollment";
include 'Include/header.php';
?>

<div class="app-layout">

  <?php include 'Include/sidebar.php'; ?>

    <main class="page-content">
      <div class="stats-grid" style="grid-template-columns: repeat(3,1fr); margin-bottom:24px;">
        <div class="stat-card">
          <div class="stat-icon gold">📝</div>
          <div><div class="stat-value">1,284</div><div class="stat-label">Total Enrolled</div></div>
        </div>
        <div class="stat-card">
          <div class="stat-icon blue">⏳</div>
          <div><div class="stat-value">62</div><div class="stat-label">Awaiting Payment</div></div>
        </div>
        <div class="stat-card">
          <div class="stat-icon green">✅</div>
          <div><div class="stat-value">1,222</div><div class="stat-label">Fully Enrolled</div></div>
        </div>
      </div>

      <div class="panel">
        <div class="panel-header">
          <span class="panel-title">Enrollment List</span>
          <button class="btn btn-primary">+ Enroll Student</button>
        </div>
        <div class="panel-body" style="padding:0">
          <table class="data-table">
            <thead>
              <tr>
                <th>Student ID</th>
                <th>Name</th>
                <th>Course</th>
                <th>Section</th>
                <th>Payment</th>
                <th>Status</th>
              </tr>
            </thead>
            <tbody>
              <tr>
                <td>2025-0001</td>
                <td>Maria Santos</td>
                <td>BS Computer Science</td>
                <td>CS-1A</td>
                <td>Full</td>
                <td><span class="badge badge-success">Active</span></td>
              </tr>
              <tr>
                <td>2025-0002</td>
                <td>Juan dela Cruz</td>
                <td>BS Accountancy</td>
                <td>ACC-2B</td>
                <td>Partial</td>
                <td><span class="badge badge-pending">Pending</span></td>
              </tr>
              <tr>
                <td>2025-0003</td>
                <td>Ana Reyes</td>
                <td>BS Nursing</td>
                <td>NUR-1C</td>
                <td>Full</td>
                <td><span class="badge badge-success">Active</span></td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
    </main>
  </div>
</div>
<?php include 'Include/footer.php'?>