<?php
$pageTitle = "SCHEDULE";
$activePage = "schedule";
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>EduSchool — <?= $pageTitle ?></title>
  <link rel="stylesheet" href="../css/admin.css?v=2" />
  <link rel="stylesheet" href="../css/schedule.css?v=3" />
</head>
<body>
<div class="app-layout">

  <?php include 'sidebar.php'; ?>

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
          <option value="BSIT">BSIT</option>
          <option value="BSCS">BSCS</option>
          <option value="BSACC">BSACC</option>
          <option value="BSN">BSN</option>
          <option value="BSED">BSED</option>
          <option value="BSENG">BSENG</option>
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
          <option value="Mon">Monday</option>
          <option value="Tue">Tuesday</option>
          <option value="Wed">Wednesday</option>
          <option value="Thu">Thursday</option>
          <option value="Fri">Friday</option>
          <option value="Sat">Saturday</option>
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

            <!-- ====== GROUP: BSIT — 1st Year ====== -->
            <tr class="section-group-row" data-group="BSIT-Y1" onclick="toggleGroup('BSIT-Y1')">
              <td class="expand-cell">
                <span class="expand-arrow open" id="arrow-BSIT-Y1">▼</span>
              </td>
              <td>
                <div class="group-label">
                  <span class="course-badge blue">BSIT</span>
                  <span class="year-label">1st Year</span>
                </div>
              </td>
              <td class="group-meta">2 subjects &middot; 1 section</td>
              <td></td>
              <td>—</td>
              <td>
                <span class="day-badge">Mon</span>
                <span class="day-badge">Tue</span>
                <span class="day-badge">Wed</span>
                <span class="day-badge">Thu</span>
                <span class="day-badge">Fri</span>
              </td>
              <td class="group-meta">7:30 AM – 10:30 AM</td>
              <td><span class="badge badge-success">Active</span></td>
              <td></td>
            </tr>
            <tr class="subject-row" data-parent="BSIT-Y1" data-id="1" data-section="BSIT-1A" data-subject="Introduction to Computing" data-type="lab" data-room="Lab 101" data-days='["Mon","Wed","Fri"]' data-start="07:30" data-end="09:00" data-status="active">
              <td class="child-indent-cell"><span class="child-arrow">↳</span></td>
              <td><span class="sec-tag">1-A</span></td>
              <td class="subject-name">Introduction to Computing</td>
              <td><span class="type-badge type-lab">LAB</span></td>
              <td class="room-cell">Lab 101</td>
              <td>
                <span class="day-badge">Mon</span>
                <span class="day-badge">Wed</span>
                <span class="day-badge">Fri</span>
              </td>
              <td class="time-cell">7:30 AM – 9:00 AM<br><span>1 hr 30 min</span></td>
              <td><span class="badge badge-success">Active</span></td>
              <td class="actions-cell">
                <button class="btn btn-outline action-btn" onclick="openEdit(1);event.stopPropagation()">Edit</button>
                <button class="btn btn-danger action-btn" onclick="openDelete(1);event.stopPropagation()">Delete</button>
              </td>
            </tr>
            <tr class="subject-row" data-parent="BSIT-Y1" data-id="2" data-section="BSIT-1A" data-subject="Mathematics in the Modern World" data-type="lec" data-room="Rm 203" data-days='["Tue","Thu"]' data-start="09:00" data-end="10:30" data-status="active">
              <td class="child-indent-cell"><span class="child-arrow">↳</span></td>
              <td><span class="sec-tag">1-A</span></td>
              <td class="subject-name">Mathematics in the Modern World</td>
              <td><span class="type-badge type-lec">LEC</span></td>
              <td class="room-cell">Rm 203</td>
              <td>
                <span class="day-badge">Tue</span>
                <span class="day-badge">Thu</span>
              </td>
              <td class="time-cell">9:00 AM – 10:30 AM<br><span>1 hr 30 min</span></td>
              <td><span class="badge badge-success">Active</span></td>
              <td class="actions-cell">
                <button class="btn btn-outline action-btn" onclick="openEdit(2);event.stopPropagation()">Edit</button>
                <button class="btn btn-danger action-btn" onclick="openDelete(2);event.stopPropagation()">Delete</button>
              </td>
            </tr>

            <!-- ====== GROUP: BSIT — 2nd Year ====== -->
            <tr class="section-group-row" data-group="BSIT-Y2" onclick="toggleGroup('BSIT-Y2')">
              <td class="expand-cell">
                <span class="expand-arrow open" id="arrow-BSIT-Y2">▼</span>
              </td>
              <td>
                <div class="group-label">
                  <span class="course-badge blue">BSIT</span>
                  <span class="year-label">2nd Year</span>
                </div>
              </td>
              <td class="group-meta">1 subject &middot; 1 section</td>
              <td></td>
              <td>—</td>
              <td>
                <span class="day-badge">Mon</span>
                <span class="day-badge">Wed</span>
              </td>
              <td class="group-meta">1:00 PM – 3:00 PM</td>
              <td><span class="badge badge-success">Active</span></td>
              <td></td>
            </tr>
            <tr class="subject-row" data-parent="BSIT-Y2" data-id="3" data-section="BSIT-2A" data-subject="Data Structures and Algorithms" data-type="lab" data-room="Lab 102" data-days='["Mon","Wed"]' data-start="13:00" data-end="15:00" data-status="active">
              <td class="child-indent-cell"><span class="child-arrow">↳</span></td>
              <td><span class="sec-tag">2-A</span></td>
              <td class="subject-name">Data Structures and Algorithms</td>
              <td><span class="type-badge type-lab">LAB</span></td>
              <td class="room-cell">Lab 102</td>
              <td>
                <span class="day-badge">Mon</span>
                <span class="day-badge">Wed</span>
              </td>
              <td class="time-cell">1:00 PM – 3:00 PM<br><span>2 hrs</span></td>
              <td><span class="badge badge-success">Active</span></td>
              <td class="actions-cell">
                <button class="btn btn-outline action-btn" onclick="openEdit(3);event.stopPropagation()">Edit</button>
                <button class="btn btn-danger action-btn" onclick="openDelete(3);event.stopPropagation()">Delete</button>
              </td>
            </tr>

            <!-- ====== GROUP: BSCS — 1st Year ====== -->
            <tr class="section-group-row" data-group="BSCS-Y1" onclick="toggleGroup('BSCS-Y1')">
              <td class="expand-cell">
                <span class="expand-arrow open" id="arrow-BSCS-Y1">▼</span>
              </td>
              <td>
                <div class="group-label">
                  <span class="course-badge purple">BSCS</span>
                  <span class="year-label">1st Year</span>
                </div>
              </td>
              <td class="group-meta">1 subject &middot; 1 section</td>
              <td></td>
              <td>—</td>
              <td>
                <span class="day-badge">Tue</span>
                <span class="day-badge">Thu</span>
                <span class="day-badge">Sat</span>
              </td>
              <td class="group-meta">10:30 AM – 12:00 PM</td>
              <td><span class="badge badge-pending">Inactive</span></td>
              <td></td>
            </tr>
            <tr class="subject-row" data-parent="BSCS-Y1" data-id="4" data-section="BSCS-1A" data-subject="Discrete Mathematics" data-type="lec" data-room="Rm 101" data-days='["Tue","Thu","Sat"]' data-start="10:30" data-end="12:00" data-status="inactive">
              <td class="child-indent-cell"><span class="child-arrow">↳</span></td>
              <td><span class="sec-tag">1-A</span></td>
              <td class="subject-name">Discrete Mathematics</td>
              <td><span class="type-badge type-lec">LEC</span></td>
              <td class="room-cell">Rm 101</td>
              <td>
                <span class="day-badge">Tue</span>
                <span class="day-badge">Thu</span>
                <span class="day-badge">Sat</span>
              </td>
              <td class="time-cell">10:30 AM – 12:00 PM<br><span>1 hr 30 min</span></td>
              <td><span class="badge badge-pending">Inactive</span></td>
              <td class="actions-cell">
                <button class="btn btn-outline action-btn" onclick="openEdit(4);event.stopPropagation()">Edit</button>
                <button class="btn btn-danger action-btn" onclick="openDelete(4);event.stopPropagation()">Delete</button>
              </td>
            </tr>

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
            <option value="BSIT-1A">BSIT 1-A</option>
            <option value="BSIT-1B">BSIT 1-B</option>
            <option value="BSIT-2A">BSIT 2-A</option>
            <option value="BSIT-2B">BSIT 2-B</option>
            <option value="BSCS-1A">BSCS 1-A</option>
            <option value="BSCS-2A">BSCS 2-A</option>
          </select>
        </div>
        <span class="error-msg" id="errSection"></span>
      </div>
      <div class="form-group">
        <label class="form-label">Subject / Course <span class="required">*</span></label>
        <input class="form-input" type="text" id="fSubject" placeholder="e.g. Introduction to Computing" />
        <span class="error-msg" id="errSubject"></span>
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
        <input class="form-input" type="text" id="fRoom" placeholder="e.g. Lab 101" />
        <span class="error-msg" id="errRoom"></span>
      </div>
      <div class="form-group">
        <label class="form-label">Day(s) <span class="required">*</span></label>
        <div class="days-check-group">
          <label class="day-check-item"><input type="checkbox" value="Mon" class="day-cb"> Mon</label>
          <label class="day-check-item"><input type="checkbox" value="Tue" class="day-cb"> Tue</label>
          <label class="day-check-item"><input type="checkbox" value="Wed" class="day-cb"> Wed</label>
          <label class="day-check-item"><input type="checkbox" value="Thu" class="day-cb"> Thu</label>
          <label class="day-check-item"><input type="checkbox" value="Fri" class="day-cb"> Fri</label>
          <label class="day-check-item"><input type="checkbox" value="Sat" class="day-cb"> Sat</label>
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

<script src="../js/admin.js?v=2"></script>
<script src="../js/schedule.js?v=2"></script>
<script>
function toggleGroup(groupId) {
  const rows = document.querySelectorAll(`.subject-row[data-parent="${groupId}"]`);
  const arrow = document.getElementById(`arrow-${groupId}`);
  const isOpen = arrow.classList.contains('open');
  rows.forEach(r => r.style.display = isOpen ? 'none' : '');
  arrow.classList.toggle('open', !isOpen);
  arrow.textContent = isOpen ? '▶' : '▼';
}
</script>
</body>
</html>
