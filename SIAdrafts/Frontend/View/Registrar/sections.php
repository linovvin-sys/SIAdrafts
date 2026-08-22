<?php
$pageTitle  = "SECTIONS";
$activePage = "sections";
$pageScript = "sections";

require_once '../../../Backend/auth.php';
require_once '../../../Backend/require_role.php';
require_role(['Registrar Staff', 'Head Registrar', 'Admin']);
require_once '../../../Backend/db.php';

$db   = new Database();
$conn = $db->connect();

$sections = $conn->query("
    SELECT s.section_id, s.section_name, s.capacity, s.course_id, s.status, c.course_code
    FROM section s
    LEFT JOIN course c ON c.course_id = s.course_id
    ORDER BY c.course_code, s.section_name
")->fetch_all(MYSQLI_ASSOC);

$courses = $conn->query("SELECT course_id, course_code, course_name, status FROM course ORDER BY course_name")->fetch_all(MYSQLI_ASSOC);

// Only approved courses may be picked when creating a new section.
$approvedCourses = array_values(array_filter($courses, fn($c) => $c['status'] === 'Approved'));

$db->close();

$isHead        = current_user_is(['Head Registrar']);
$isAdminViewer = current_user_is(['Admin']);

include '../Include/header.php';
?>

<div class="app-layout">

  <?php include '../Include/sidebar.php'; ?>

  <main class="page-content">
    <?php include '../Include/readonly_banner.php'; ?>

    <div class="panel">
      <div class="panel-header">
        <span class="panel-title">Sections</span>
        <?php if (!$isAdminViewer): ?>
        <button type="button" class="btn btn-primary" data-open="addSectionModal">+ Add Section</button>
        <?php endif; ?>
      </div>

      <div class="panel-body" style="padding:16px 24px 0;">
        <div class="filter-bar">
          <input type="text" class="form-input" id="sectionSearch" placeholder="Search section or course…">
          <div class="select-wrapper">
            <select class="form-input form-select" id="sectionCourseFilter">
              <option value="">All Courses</option>
              <?php foreach ($courses as $c): ?>
                <option value="<?= (int)$c['course_id'] ?>"><?= htmlspecialchars($c['course_code']) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
        </div>
      </div>

      <div class="panel-body" style="padding:0;">
        <div class="table-wrap">
          <table class="data-table rd-table-stagger" id="sectionTable">
            <thead>
              <tr>
                <th>Section</th>
                <th>Course</th>
                <th>Capacity</th>
                <th>Status</th>
                <th style="width:150px">Actions</th>
              </tr>
            </thead>
            <tbody id="sectionListBody">
              <?php if (!empty($sections)): ?>
                <?php foreach ($sections as $rowIndex => $row): ?>
                  <tr style="--row-i: <?= min((int)$rowIndex, 12) ?>;" data-row-id="<?= (int)$row['section_id'] ?>" data-course-id="<?= (int)$row['course_id'] ?>" data-search="<?= htmlspecialchars(strtolower($row['section_name'] . ' ' . ($row['course_code'] ?? ''))) ?>">
                    <td><?= htmlspecialchars($row['section_name']) ?></td>
                    <td><?= htmlspecialchars($row['course_code'] ?? '—') ?></td>
                    <td><?= htmlspecialchars($row['capacity']) ?></td>
                    <td><span class="status-pill status-pill--<?= strtolower($row['status']) ?>"><?= htmlspecialchars($row['status']) ?></span></td>
                    <td>
                      <div class="row-actions">
                        <button type="button" class="btn btn-outline" style="padding:4px 10px;font-size:12px" data-view-section="<?= (int)$row['section_id'] ?>" data-section-label="<?= htmlspecialchars($row['section_name']) ?>">View</button>
                        <?php if ($isHead): ?>
                          <button type="button" class="btn-remove" data-remove-section="<?= (int)$row['section_id'] ?>">Remove</button>
                        <?php endif; ?>
                      </div>
                    </td>
                  </tr>
                <?php endforeach; ?>
              <?php else: ?>
                <tr><td colspan="5" style="text-align:center;">No sections found.</td></tr>
              <?php endif; ?>
            </tbody>
          </table>
        </div>
      </div>
    </div>

    <!-- Add Section Modal -->
    <div id="addSectionModal" class="modal-overlay">
      <div class="modal-box">
        <div class="modal-header">
          <div class="modal-header-left">
            <div class="modal-icon">🏫</div>
            <div>
              <div class="modal-title">Add Section</div>
              <div class="modal-subtitle">Create a new section</div>
            </div>
          </div>
          <button type="button" class="modal-close" data-close="addSectionModal">✕</button>
        </div>

        <div class="modal-body">
          <div class="form-group">
            <label class="form-label">Section Name<span class="required">*</span></label>
            <input type="text" id="newSectionName" class="form-input" placeholder="e.g. BSIT A2" required>
          </div>
          <div class="form-group">
            <label class="form-label">Capacity</label>
            <input type="number" id="newSectionCapacity" class="form-input" value="40" min="1" required>
          </div>
          <div class="form-group">
            <label class="form-label">Course<span class="required">*</span></label>
            <div class="select-wrapper">
              <select id="newSectionCourse" class="form-input form-select" required>
                <option value="">-- Select Course --</option>
                <?php foreach ($approvedCourses as $c): ?>
                  <option value="<?= (int)$c['course_id'] ?>">
                    <?= htmlspecialchars($c['course_code']) ?> - <?= htmlspecialchars($c['course_name']) ?>
                  </option>
                <?php endforeach; ?>
              </select>
            </div>
          </div>
        </div>

        <div class="modal-footer">
          <button type="button" class="btn btn-outline" data-close="addSectionModal">Cancel</button>
          <button type="button" class="btn btn-primary" id="confirmAddSection">Save Section</button>
        </div>
      </div>
    </div>

    <!-- View Section Modal -->
    <div id="viewSectionModal" class="modal-overlay">
      <div class="modal-box" style="max-width:640px;">
        <div class="modal-header">
          <div class="modal-header-left">
            <div class="modal-icon">🏫</div>
            <div>
              <div class="modal-title">Enrolled Students</div>
              <div class="modal-subtitle" id="viewSectionLabel"></div>
            </div>
          </div>
          <button type="button" class="modal-close" data-close="viewSectionModal">✕</button>
        </div>

        <div class="modal-body" style="max-height:60vh; overflow-y:auto;">
          <div class="table-responsive">
          <table class="data-table">
            <thead>
              <tr>
                <th>Student No.</th>
                <th>Name</th>
                <th>Email</th>
                <th>Contact No.</th>
              </tr>
            </thead>
            <tbody id="viewSectionBody">
              <tr><td colspan="4" style="text-align:center;">Loading…</td></tr>
            </tbody>
          </table>
          </div>
        </div>

        <div class="modal-footer">
          <button type="button" class="btn btn-outline" data-close="viewSectionModal">Close</button>
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
