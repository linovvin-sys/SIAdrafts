<?php
// index.php — Admin Dashboard
$pageTitle = "ADMIN DASHBOARD";
$activePage = "dashboard";

include 'Include/header.php';
?>

<div class="app-layout">

  <!-- Sidebar (shared) -->
  <?php include 'Include/sidebar.php'; ?>

    <!-- Page Body -->
    <main class="page-content">

      <!-- Stats Row -->
      <div class="stats-grid">

        <div class="stat-card">
            <div class="stat-icon gold">
              <svg xmlns="http://www.w3.org/2000/svg" width="1em" height="1em" viewBox="0 0 256 256">
                <path d="M0 0h256v256H0z" fill="none" />
                <path fill="currentColor" d="m227.79 52.62l-96-32a11.85 11.85 0 0 0-7.58 0l-96 32A12 12 0 0 0 20 63.37a6 6 0 0 0 0 .63v80a12 12 0 0 0 24 0V80.65l23.71 7.9a67.92 67.92 0 0 0 18.42 85A100.36 100.36 0 0 0 46 209.44a12 12 0 1 0 20.1 13.11C80.37 200.59 103 188 128 188s47.63 12.59 61.95 34.55a12 12 0 1 0 20.1-13.11a100.36 100.36 0 0 0-40.18-35.92a67.92 67.92 0 0 0 18.42-85l39.5-13.17a12 12 0 0 0 0-22.76Zm-99.79-8L186.05 64L128 83.35L70 64ZM172 120a44 44 0 1 1-81.06-23.71l33.27 11.09a11.9 11.9 0 0 0 7.58 0l33.27-11.09A43.85 43.85 0 0 1 172 120" />
              </svg>
            </div>
            <div>
              <div class="stat-value">1,284</div>
              <div class="stat-label">Total Students</div>
            </div>
          </div>

        <div class="stat-card">
          <div class="stat-icon blue">
            <svg xmlns="http://www.w3.org/2000/svg" width="1em" height="1em" viewBox="0 0 24 24">
              <path d="M0 0h24v24H0z" fill="none" />
              <path fill="currentColor" d="M15.775 18.525Q16 18.3 16 18t-.225-.525t-.525-.225t-.525.225T14.5 18t.225.525t.525.225t.525-.225m2.75 0q.225-.225.225-.525t-.225-.525T18 17.25t-.525.225t-.225.525t.225.525t.525.225t.525-.225m2.75 0Q21.5 18.3 21.5 18t-.225-.525t-.525-.225t-.525.225T20 18t.225.525t.525.225t.525-.225M5 21q-.825 0-1.413-.587T3 19V5q0-.825.588-1.412T5 3h14q.825 0 1.413.588T21 5v5q0 .425-.288.713T20 11t-.712-.288T19 10V5H5v14h5q.425 0 .713.288T11 20t-.288.713T10 21zm0-3v1V5v6.075V11zm2.287-1.287Q7.575 17 8 17h2.075q.425 0 .713-.288t.287-.712t-.287-.712t-.713-.288H8q-.425 0-.712.288T7 16t.288.713m0-4Q7.575 13 8 13h5q.425 0 .713-.288T14 12t-.288-.712T13 11H8q-.425 0-.712.288T7 12t.288.713m0-4Q7.575 9 8 9h8q.425 0 .713-.288T17 8t-.288-.712T16 7H8q-.425 0-.712.288T7 8t.288.713M18 23q-2.075 0-3.537-1.463T13 18t1.463-3.537T18 13t3.538 1.463T23 18t-1.463 3.538T18 23" />
            </svg>
          </div>
          <div>
            <div class="stat-value">48</div>
            <div class="stat-label">Pending Admissions</div>
          </div>
        </div>

        <div class="stat-card">
          <div class="stat-icon green">
            <svg xmlns="http://www.w3.org/2000/svg" width="1em" height="1em" viewBox="0 0 512 512">
              <path d="M0 0h512v512H0z" fill="none" />
              <path fill="currentColor" d="M18.78 19.5v79.656c44.684 5.582 81.517 24.966 116.657 47.156l-24.75 20.063L212.47 218.28L184.53 106.5l-25.905 21c-20.225-40.01-42.778-77.73-72.75-108zm277.376 0c-15.624 28.765-29.207 58.126-41.78 88.156l-30.19-6.406l25.94 112.25l67.06-92.5l-29.592-6.28c33.29-34.747 67.597-67.793 108.062-95.22zm197.5 93.844c-37.988 2.482-72.04 19.677-105.03 40.906l-12.47-32.53l-80.062 82.843l114.094 5.937l-13.25-34.563c32.24-.934 64.478 1.827 96.718 21.375zm-194.03 128.03c-5.28.12-10.21 2.416-16.938 9.595l-6.563 6.968l-6.813-6.72c-7.387-7.28-13.216-9.29-19.125-9.03c-5.908.26-12.855 3.367-20.625 9.656l-6.218 5.03l-5.906-5.374c-8.9-8.052-16.485-10.438-23.75-10.063c-5.288.274-10.775 2.266-16.25 5.75l40.968 73.688c15.454 9.452 47.033 13.007 68.75 2.063l39.594-73.344c-7.51-3.062-14.26-6.202-20.094-7.406c-2.112-.437-4.072-.756-5.97-.813a21 21 0 0 0-1.06 0m-89.97 96.19c-18.035 12.742-32.516 34.718-38.125 66.905c-5.435 31.196 3.128 52.265 18.282 66.624c15.155 14.36 37.902 21.737 61 21.437c23.1-.3 46.136-8.31 61.625-22.936c15.49-14.627 24.25-35.426 19.282-65.188c-5.137-30.757-18.4-52.148-35.19-65.094c-28.482 15.056-64.094 11.856-86.874-1.75z" />
            </svg>
          </div>
          <div>
            <div class="stat-value">₱2.4M</div>
            <div class="stat-label">Revenue This Term</div>
          </div>
        </div>


        <div class="stat-card">
          <div class="stat-icon purple">
            <svg xmlns="http://www.w3.org/2000/svg" width="1em" height="1em" viewBox="0 0 24 24">
              <path d="M0 0h24v24H0z" fill="none" />
              <g fill="none" stroke="currentColor" stroke-width="1.5">
                <path d="M19.898 16h-12c-.93 0-1.395 0-1.777.102A3 3 0 0 0 4 18.224" />
                <path stroke-linecap="round" d="M8 7h8m-8 3.5h5M10 22c-2.828 0-4.243 0-5.121-.879C4 20.243 4 18.828 4 16V8c0-2.828 0-4.243.879-5.121C5.757 2 7.172 2 10 2h4c2.828 0 4.243 0 5.121.879C20 3.757 20 5.172 20 8m-6 14c2.828 0 4.243 0 5.121-.879C20 20.243 20 18.828 20 16v-4" />
              </g>
            </svg>
          </div>
          <div>
            <div class="stat-value">36</div>
            <div class="stat-label">Active Courses</div>
          </div>
        </div>


      </div>

      <!-- Bottom Panels -->
      <div class="grid-2">

        <!-- Recent Admissions -->
        <div class="panel">
          <div class="panel-header">
            <span class="panel-title">Recent Admissions</span>
            <a href="admin_admission.php" class="btn btn-outline">View All</a>
          </div>
          <div class="panel-body" style="padding:0">
            <table class="data-table">
              <thead>
                <tr>
                  <th>Name</th>
                  <th>Course</th>
                  <th>Status</th>
                </tr>
              </thead>
              <tbody>
                <tr>
                  <td>Maria Santos</td>
                  <td>BS Computer Science</td>
                  <td><span class="badge badge-success">Approved</span></td>
                </tr>
                <tr>
                  <td>Juan dela Cruz</td>
                  <td>BS Accountancy</td>
                  <td><span class="badge badge-pending">Pending</span></td>
                </tr>
                <tr>
                  <td>Ana Reyes</td>
                  <td>BS Nursing</td>
                  <td><span class="badge badge-success">Approved</span></td>
                </tr>
                <tr>
                  <td>Carlo Mendoza</td>
                  <td>BS Education</td>
                  <td><span class="badge badge-danger">Rejected</span></td>
                </tr>
                <tr>
                  <td>Lea Villanueva</td>
                  <td>BS Engineering</td>
                  <td><span class="badge badge-pending">Pending</span></td>
                </tr>
                <tr>
                <td>Lea Villanueva</td>
                <td>BS Engineering</td>
                <td><span class="badge badge-pending">Pending</span></td>
              </tr>
              </tbody>
            </table>
          </div>
        </div>

        <!-- Enrollment Summary -->
        <div class="panel">
          <div class="panel-header">
            <span class="panel-title">Enrollment Summary</span>
            <a href="admin_enrollment.php" class="btn btn-outline">View All</a>
          </div>
          <div class="panel-body" style="padding:0">
            <table class="data-table">
              <thead>
                <tr>
                  <th>Course</th>
                  <th>Enrolled</th>
                  <th>Slots</th>
                </tr>
              </thead>
              <tbody>
                <tr>
                  <td>BS Computer Science</td>
                  <td>312</td>
                  <td>350</td>
                </tr>
                <tr>
                  <td>BS Nursing</td>
                  <td>280</td>
                  <td>300</td>
                </tr>
                <tr>
                  <td>BS Accountancy</td>
                  <td>198</td>
                  <td>250</td>
                </tr>
                <tr>
                  <td>BS Education</td>
                  <td>154</td>
                  <td>200</td>
                </tr>
                <tr>
                  <td>BS Engineering</td>
                  <td>340</td>
                  <td>400</td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>

      </div><!-- .grid-2 -->

    </main>
  </div><!-- .main-content (opened by sidebar.php) -->
</div><!-- .app-layout -->

<?php include 'Include/footer.php'?>
