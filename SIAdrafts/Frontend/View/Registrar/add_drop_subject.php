<?php
$pageTitle  = "ADD / DROP SUBJECT";
$activePage = "addDrop";
$pageScript = "add_drop_subject";

require_once '../../../Backend/auth.php';
require_once '../../../Backend/require_role.php';
require_role(['Registrar Staff', 'Head Registrar']);
require_once __DIR__ . '/../../../Backend/admin/enrollment.php';

include '../Include/header.php';
?>

<div class="app-layout">

  <?php include '../Include/sidebar.php'; ?>

  <main class="page-content">

    <div class="panel-stack">

    <!-- Search -->
    <div class="panel">
      <div class="panel-header">
        <span class="panel-title">Find Student</span>
      </div>
      <div class="panel-body">
        <div class="addrop-search-row">
          <div class="form-group" style="flex:1; margin-bottom:0;">
            <label class="form-label">Student ID</label>
            <input type="text" id="studentSearchInput" class="form-input" placeholder="e.g. 2026-00005" autocomplete="off">
          </div>
          <button type="button" class="btn btn-primary" id="searchStudentBtn">Search</button>
        </div>
        <div id="searchEmptyState" class="addrop-empty">Search a student ID above, or pick one from the list below, to view their enrollment and manage subjects.</div>
      </div>
    </div>

    <!-- Student info -->
    <div class="panel" id="studentResultPanel" style="display:none;">
      <div class="panel-header">
        <span class="panel-title">Student Information</span>
      </div>
      <div class="panel-body">
        <div class="student-info-grid" id="studentInfoGrid"></div>
      </div>
    </div>

    <!-- Subjects -->
    <div class="panel" id="subjectsPanel" style="display:none;">
      <div class="panel-header">
        <span class="panel-title">Enrolled Subjects</span>
        <button type="button" class="btn btn-primary" id="openAddSubjectBtn">+ Add Subject</button>
      </div>
      <div class="panel-body" style="padding:0;">
        <table class="data-table" id="enrolledSubjectsTable">
          <thead>
            <tr>
              <th>Code</th>
              <th>Subject</th>
              <th>Units</th>
              <th>Schedule</th>
              <th>Professor</th>
              <th>Status</th>
              <th style="width:140px;"></th>
            </tr>
          </thead>
          <tbody id="enrolledSubjectsBody">
          </tbody>
        </table>
      </div>
    </div>

    <!-- All Enrolled Students -->
    <div class="panel">
      <div class="panel-header">
        <span class="panel-title">Enrolled Students</span>
      </div>
      <div class="panel-body" style="padding:16px 24px;">
        <div class="table-responsive">
        <table class="data-table" id="allStudentsTable">
          <thead>
            <tr>
              <th>Student ID</th>
              <th>Name</th>
              <th>Course</th>
              <th>Section</th>
              <th>Year / Sem</th>
              <th>Status</th>
              <th style="width:110px;"></th>
            </tr>
          </thead>
          <tbody>
            <?php if (!empty($enrollmentList)): ?>
              <?php foreach ($enrollmentList as $row): ?>
                <tr>
                  <td class="mono"><?= htmlspecialchars($row['student_no']) ?></td>
                  <td><?= htmlspecialchars($row['student_name']) ?></td>
                  <td><?= htmlspecialchars($row['course_name'] ?? '—') ?></td>
                  <td><?= htmlspecialchars($row['section_name'] ?? '—') ?></td>
                  <td class="mono"><?= !empty($row['year_level']) ? (int)$row['year_level'] . 'Y' : '—' ?><?= !empty($row['semester']) ? ' · Sem ' . (int)$row['semester'] : '' ?></td>
                  <td><span class="status-pill status-pill--<?= strtolower($row['status']) === 'enrolled' ? 'approved' : 'pending' ?>"><?= htmlspecialchars($row['status']) ?></span></td>
                  <td><button type="button" class="btn btn-outline" style="padding:4px 10px;font-size:12px" data-manage-student="<?= htmlspecialchars($row['student_no']) ?>">Manage</button></td>
                </tr>
              <?php endforeach; ?>
            <?php else: ?>
              <tr><td colspan="7" style="text-align:center;">No enrolled students found.</td></tr>
            <?php endif; ?>
          </tbody>
        </table>
        </div>
      </div>
    </div>

    </div>

    <!-- Add Subject Modal -->
    <div id="addSubjectModal" class="modal-overlay">
      <div class="modal-box">
        <div class="modal-header">
          <div class="modal-header-left">
            <div class="modal-icon">📗</div>
            <div>
              <div class="modal-title">Add Subject</div>
              <div class="modal-subtitle">Enroll this student in another subject</div>
            </div>
          </div>
          <button type="button" class="modal-close" data-close="addSubjectModal">✕</button>
        </div>

        <div class="modal-body">
          <div class="form-group">
            <label class="form-label">Subject<span class="required">*</span></label>
            <div class="select-wrapper">
              <select id="addSubjectSelect" class="form-input form-select" required>
                <option value="">-- Select Subject --</option>
              </select>
            </div>
          </div>
          <div class="form-group" id="addSubjectScheduleInfo" style="display:none;">
            <label class="form-label">Schedule</label>
            <div class="addrop-schedule-preview" id="addSubjectScheduleText"></div>
          </div>
        </div>

        <div class="modal-footer">
          <button type="button" class="btn btn-outline" data-close="addSubjectModal">Cancel</button>
          <button type="button" class="btn btn-primary" id="confirmAddSubject">Add Subject</button>
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