<?php
$pageTitle  = "SCHEDULE";
$activePage = "schedule";
$pageScript = "schedule";

require_once '../../../Backend/auth.php';
require_once '../../../Backend/require_role.php';
require_role(['Registrar Staff']);

$isHead = current_user_is(['Head Registrar']);

include 'Include/header.php';
?>

<div class="app-layout">

  <?php include 'Include/sidebar.php'; ?>

  <main class="page-content">

    <!-- ===== PAGE HEADER ===== -->
    <div class="sched-page-header">
      <div>
        <h1 class="sched-page-title">Class Schedules</h1>
        <p class="sched-page-sub">
          <?= $isHead
            ? 'View all schedules. New schedules you add are approved immediately.'
            : 'View schedules. New schedules you add are submitted for Head Registrar approval.' ?>
        </p>
      </div>
      <button type="button" class="btn btn-primary" data-open="addScheduleModal">+ Add Schedule</button>
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
      <div class="select-wrapper sched-select">
        <select class="form-input form-select" id="filterStatus">
          <option value="">All Statuses</option>
          <option value="Approved">Approved</option>
          <option value="Pending">Pending</option>
          <option value="Rejected">Rejected</option>
        </select>
      </div>
      <input class="form-input sched-search" type="text" id="searchSchedule" placeholder="Search subject or room…" />
    </div>

    <!-- ===== SCHEDULE TABLE ===== -->
    <div class="panel">
      <div class="panel-body" style="padding:0">
        <table class="sched-table" id="scheduleTable" data-is-head="<?= $isHead ? '1' : '0' ?>">
          <thead>
            <tr>
              <th style="width:44px"></th>
              <th>Course / Year</th>
              <th>Subject / Section</th>
              <th>Type</th>
              <th>Room</th>
              <th>Days</th>
              <th>Time</th>
              <th>Status</th>
              <?php if ($isHead): ?><th style="width:100px">Actions</th><?php endif; ?>
            </tr>
          </thead>
          <tbody id="scheduleBody"></tbody>
        </table>

        <div class="empty-state" id="emptyState" style="display:none">
          <div class="empty-icon">📅</div>
          <p>No schedules found.<br>Try adjusting your filters or add a new one.</p>
        </div>
      </div>
    </div>

    <!-- Add Schedule Modal -->
    <div id="addScheduleModal" class="modal-overlay">
      <div class="modal-box">
        <div class="modal-header">
          <div class="modal-header-left">
            <div class="modal-icon">🗓️</div>
            <div>
              <div class="modal-title">Add Schedule</div>
              <div class="modal-subtitle">
                <?= $isHead ? 'This will be live immediately.' : 'This will be sent for approval.' ?>
              </div>
            </div>
          </div>
          <button type="button" class="modal-close" data-close="addScheduleModal">✕</button>
        </div>

        <div class="modal-body">
          <div class="form-group">
            <label class="form-label">Section<span class="required">*</span></label>
            <select class="form-input form-select" id="schedSection" required></select>
          </div>
          <div class="form-group">
            <label class="form-label">Subject<span class="required">*</span></label>
            <select class="form-input form-select" id="schedSubject" required></select>
          </div>
          <div class="form-group">
            <label class="form-label">Professor</label>
            <select class="form-input form-select" id="schedProfessor"></select>
          </div>
          <div class="form-group">
            <label class="form-label">Room<span class="required">*</span></label>
            <select class="form-input form-select" id="schedRoom" required></select>
          </div>
          <div class="form-group">
            <label class="form-label">Day<span class="required">*</span></label>
            <select class="form-input form-select" id="schedDay" required>
              <option value="Monday">Monday</option>
              <option value="Tuesday">Tuesday</option>
              <option value="Wednesday">Wednesday</option>
              <option value="Thursday">Thursday</option>
              <option value="Friday">Friday</option>
              <option value="Saturday">Saturday</option>
            </select>
          </div>
          <div class="form-group">
            <label class="form-label">Start Time<span class="required">*</span></label>
            <input type="time" class="form-input" id="schedStart" required>
          </div>
          <div class="form-group">
            <label class="form-label">End Time<span class="required">*</span></label>
            <input type="time" class="form-input" id="schedEnd" required>
          </div>
          <div class="form-group">
            <label class="form-label">School Year<span class="required">*</span></label>
            <input type="text" class="form-input" id="schedSchoolYear" placeholder="2026-2027" required>
          </div>
          <div class="form-group">
            <label class="form-label">Semester<span class="required">*</span></label>
            <select class="form-input form-select" id="schedSemester" required>
              <option value="1">1st Semester</option>
              <option value="2">2nd Semester</option>
            </select>
          </div>
        </div>

        <div class="modal-footer">
          <button type="button" class="btn btn-outline" data-close="addScheduleModal">Cancel</button>
          <button type="button" class="btn btn-primary" id="confirmAddSchedule">Save</button>
        </div>
      </div>
    </div>

  </main>
</div>

<?php include 'Include/footer.php'; ?>