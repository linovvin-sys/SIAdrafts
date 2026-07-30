<?php
$pageTitle  = "PENDING APPROVALS";
$activePage = "pending";
$pageScript = "pending";

require_once '../../../Backend/auth.php';
require_once '../../../Backend/require_role.php';
require_role(['Head Registrar']); // this page is Head Registrar only

include '../Include/header.php';
?>

<div class="app-layout">

  <?php include '../Include/sidebar.php'; ?>

  <main class="page-content">

    <div class="sched-page-header">
      <div>
        <h1 class="sched-page-title">Pending Approvals</h1>
        <p class="sched-page-sub">Courses, sections, and schedules submitted by Registrar Staff, waiting for your decision.</p>
      </div>
    </div>

    <!-- ===== PENDING COURSES ===== -->
    <div class="panel">
      <div class="panel-header">
        <span class="panel-title">Pending Courses</span>
      </div>
      <div class="panel-body" style="padding:0">
        <table class="sched-table" id="pendingCourseTable">
          <thead>
            <tr>
              <th>Submitted By</th>
              <th>Code</th>
              <th>Course Name</th>
              <th>Units</th>
              <th style="width:220px">Action</th>
            </tr>
          </thead>
          <tbody id="pendingCourseBody"></tbody>
        </table>
        <div class="empty-state" id="emptyCourseState" style="display:none">
          <div class="empty-state-icon">✓</div>
          <p class="empty-state-text">No pending courses — new submissions from Registrar Staff will show up here.</p>
        </div>
      </div>
    </div>

    <!-- ===== PENDING SECTIONS ===== -->
    <div class="panel">
      <div class="panel-header">
        <span class="panel-title">Pending Sections</span>
      </div>
      <div class="panel-body" style="padding:0">
        <table class="sched-table" id="pendingSectionTable">
          <thead>
            <tr>
              <th>Submitted By</th>
              <th>Section</th>
              <th>Course</th>
              <th>Capacity</th>
              <th style="width:220px">Action</th>
            </tr>
          </thead>
          <tbody id="pendingSectionBody"></tbody>
        </table>
        <div class="empty-state" id="emptySectionState" style="display:none">
          <div class="empty-state-icon">✓</div>
          <p class="empty-state-text">No pending sections — new submissions from Registrar Staff will show up here.</p>
        </div>
      </div>
    </div>

    <!-- ===== PENDING SCHEDULES ===== -->
    <div class="panel">
      <div class="panel-header">
        <span class="panel-title">Pending Schedules</span>
      </div>
      <div class="panel-body" style="padding:0">
        <table class="sched-table" id="pendingTable">
          <thead>
            <tr>
              <th>Submitted By</th>
              <th>Section</th>
              <th>Subject</th>
              <th>Room</th>
              <th>Day</th>
              <th>Time</th>
              <th style="width:220px">Action</th>
            </tr>
          </thead>
          <tbody id="pendingBody"></tbody>
        </table>
        <div class="empty-state" id="emptyState" style="display:none">
          <div class="empty-state-icon">✓</div>
          <p class="empty-state-text">No pending schedules — new submissions from Registrar Staff will show up here.</p>
        </div>
      </div>
    </div>

  </main>
</div>

<?php
$extraScripts = [
    'https://cdn.jsdelivr.net/npm/vue@3/dist/vue.global.prod.js',
    '/SIAdrafts/Frontend/Js/Registrar/' . ($pageScript ?? 'registrar') . '.js',
];
include '../Include/footer.php';
?>
