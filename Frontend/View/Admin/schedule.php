<?php
$pageTitle = "SCHEDULE";
$activePage = "schedule";

require_once '../../../Backend/auth.php';
include 'Include/header.php';

?>

<div class="app-layout">

  <?php include 'Include/sidebar.php'; ?>

  <main class="page-content">

    <!-- ===== PAGE HEADER ===== -->
    <div class="sched-page-header">
      <div>
        <h1 class="sched-page-title">Class Schedules</h1>
        <p class="sched-page-sub">View all section schedules</p>
      </div>
    </div>

    <!-- ===== FILTER BAR ===== -->
    <div class="sched-filter-bar">
      <div class="select-wrapper sched-select">
        <select class="form-input form-select" id="filterCourse">
          <option value="">All Courses</option>
        </select>
      </div>
      <div class="select-wrapper sched-select">
        <select class="form-input form-select" id="filterYear">
          <option value="">All Year Levels</option>
          <option value="1">1st Year</option>
          <option value="2">2nd Year</option>
          <option value="3">3rd Year</option>
          <option value="4">4th Year</option>
        </select>
      </div>
      <div class="select-wrapper sched-select">
        <select class="form-input form-select" id="filterDay">
          <option value="">All Days</option>
          <option value="Monday">Monday</option>
          <option value="Tuesday">Tuesday</option>
          <option value="Wednesday">Wednesday</option>
          <option value="Thursday">Thursday</option>
          <option value="Friday">Friday</option>
          <option value="Saturday">Saturday</option>
        </select>
      </div>
      <input class="form-input sched-search" type="text" id="searchSchedule" placeholder="Search subject or room…" />
    </div>

    <!-- ===== SCHEDULE TABLE ===== -->
    <div class="panel">
      <div class="panel-body" style="padding:0">
        <table class="sched-table" id="scheduleTable">
          <thead>
            <tr>
              <th style="width:44px"></th>
              <th>Course / Year</th>
              <th>Subject / Course</th>
              <th>Type</th>
              <th>Room</th>
              <th>Days</th>
              <th>Time</th>
              <th>Status</th>
            </tr>
          </thead>
          <tbody id="scheduleBody">
            <!-- rows are rendered dynamically by schedule.js -->
          </tbody>
        </table>

        <div class="empty-state" id="emptyState" style="display:none">
          <div class="empty-icon">📅</div>
          <p>No schedules found.<br>Try adjusting your filters or add a new schedule.</p>
        </div>
      </div>
    </div>

  </main>
</div>

<?php include 'Include/footer.php'?>