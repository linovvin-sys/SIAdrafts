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
        <p class="sched-page-sub">Manage and view all section schedules</p>
      </div>
      <button class="btn btn-primary" id="openAddModal">+ Add Schedule</button>
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
              <th style="width:130px">Actions</th>
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

<!-- ===== ADD / EDIT SCHEDULE MODAL ===== -->
<div class="modal-overlay" id="scheduleModal">
  <div class="modal-box">
    <div class="modal-header">
      <div class="modal-header-left">
        <div class="modal-icon">📅</div>
        <div>
          <div class="modal-title" id="modalTitle">Add Schedule</div>
          <div class="modal-subtitle" id="modalSubtitle">Fill in the schedule details below</div>
        </div>
      </div>
      <button class="modal-close" id="closeModal" aria-label="Close">✕</button>
    </div>
    <div class="modal-body">
      <div class="form-group">
        <label class="form-label">Section <span class="required">*</span></label>
        <div class="select-wrapper">
          <select class="form-input form-select" id="fSection">
            <option value="" disabled selected>Select section</option>
          </select>
        </div>
        <span class="error-msg" id="errSection"></span>
      </div>
      <div class="form-group">
        <label class="form-label">Subject / Course <span class="required">*</span></label>
        <div class="select-wrapper">
          <select class="form-input form-select" id="fSubject">
            <option value="" disabled selected>Select subject</option>
          </select>
        </div>
        <span class="error-msg" id="errSubject"></span>
      </div>
      <div class="form-group">
        <label class="form-label">Professor</label>
        <div class="select-wrapper">
          <select class="form-input form-select" id="fProfessor">
            <option value="">Unassigned</option>
          </select>
        </div>
      </div>
      <div class="form-group">
        <label class="form-label">Type <span class="required">*</span></label>
        <div class="days-check-group">
          <label class="day-check-item"><input type="radio" name="fType" value="lab" id="fTypeLab"> Laboratory</label>
          <label class="day-check-item"><input type="radio" name="fType" value="lec" id="fTypeLec"> Lecture</label>
        </div>
        <span class="error-msg" id="errType"></span>
      </div>
      <div class="form-group">
        <label class="form-label">Room <span class="required">*</span></label>
        <div class="select-wrapper">
          <select class="form-input form-select" id="fRoom">
            <option value="" disabled selected>Select room</option>
          </select>
        </div>
        <span class="error-msg" id="errRoom"></span>
      </div>
      <div class="form-group">
        <label class="form-label">Day(s) <span class="required">*</span></label>
        <div class="days-check-group">
          <label class="day-check-item"><input type="checkbox" value="Monday" class="day-cb"> Mon</label>
          <label class="day-check-item"><input type="checkbox" value="Tuesday" class="day-cb"> Tue</label>
          <label class="day-check-item"><input type="checkbox" value="Wednesday" class="day-cb"> Wed</label>
          <label class="day-check-item"><input type="checkbox" value="Thursday" class="day-cb"> Thu</label>
          <label class="day-check-item"><input type="checkbox" value="Friday" class="day-cb"> Fri</label>
          <label class="day-check-item"><input type="checkbox" value="Saturday" class="day-cb"> Sat</label>
        </div>
        <span class="error-msg" id="errDays"></span>
      </div>
      <div class="modal-form-row">
        <div class="form-group">
          <label class="form-label">Start Time <span class="required">*</span></label>
          <input class="form-input" type="time" id="fStart" />
          <span class="error-msg" id="errTime"></span>
        </div>
        <div class="form-group">
          <label class="form-label">End Time <span class="required">*</span></label>
          <input class="form-input" type="time" id="fEnd" />
        </div>
      </div>
      <div class="modal-form-row">
        <div class="form-group">
          <label class="form-label">School Year <span class="required">*</span></label>
          <input class="form-input" type="text" id="fSchoolYear" placeholder="e.g. 2026-2027" />
          <span class="error-msg" id="errSchoolYear"></span>
        </div>
        <div class="form-group">
          <label class="form-label">Semester <span class="required">*</span></label>
          <div class="select-wrapper">
            <select class="form-input form-select" id="fSemester">
              <option value="1">1st Semester</option>
              <option value="2">2nd Semester</option>
            </select>
          </div>
        </div>
      </div>
      <div class="modal-divider"></div>
      <div class="modal-status-row">
        <div>
          <div class="status-label">Active Schedule</div>
          <div class="status-desc">Schedule will appear in the class timetable</div>
        </div>
        <label class="toggle-switch">
          <input type="checkbox" id="fStatus" checked />
          <span class="toggle-slider"></span>
        </label>
      </div>
    </div>
    <div class="modal-footer">
      <button class="btn btn-outline" id="cancelModal">Cancel</button>
      <button class="btn btn-primary" id="submitSchedule">+ Add Schedule</button>
    </div>
  </div>
</div>

<!-- ===== DELETE CONFIRM MODAL ===== -->
<div class="modal-overlay" id="deleteModal">
  <div class="modal-box">
    <div class="modal-header">
      <div class="modal-header-left">
        <div class="modal-icon">🗑️</div>
        <div>
          <div class="modal-title">Delete Schedule</div>
          <div class="modal-subtitle">This action cannot be undone</div>
        </div>
      </div>
      <button class="modal-close" id="closeDeleteModal" aria-label="Close">✕</button>
    </div>
    <div class="modal-body">
      <div class="delete-warning">
        <div class="warn-icon">⚠️</div>
        <p>Are you sure you want to delete the schedule for<br><strong id="deleteLabel">—</strong>?</p>
      </div>
    </div>
    <div class="modal-footer">
      <button class="btn btn-outline" id="cancelDelete">Cancel</button>
      <button class="btn btn-red" id="confirmDelete">Delete</button>
    </div>
  </div>
</div>

<?php include 'Include/footer.php'?>