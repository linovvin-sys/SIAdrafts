<?php
$pageTitle  = "COURSES";
$activePage = "courses";
$pageScript = "courses";

require_once '../../../Backend/auth.php';
require_once '../../../Backend/require_role.php';
require_role(['Registrar Staff', 'Head Registrar', 'Admin']);
require_once '../../../Backend/db.php';

$db   = new Database();
$conn = $db->connect();

$courses = $conn->query("
    SELECT c.course_id, c.course_code, c.course_name, c.total_units, c.status,
           (SELECT COUNT(*) FROM subject WHERE course_id = c.course_id) AS subject_count
    FROM course c
    ORDER BY c.course_name
")->fetch_all(MYSQLI_ASSOC);

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
        <span class="panel-title">Courses</span>
        <?php if (!$isAdminViewer): ?>
        <button type="button" class="btn btn-primary" data-open="addCourseModal">+ Add Course</button>
        <?php endif; ?>
      </div>

      <div class="panel-body" style="padding:0;">
        <div class="table-wrap">
          <table class="data-table" id="courseTable">
            <thead>
              <tr>
                <th>Code</th>
                <th>Course Name</th>
                <th>Units</th>
                <th>Status</th>
                <th style="width:150px">Actions</th>
              </tr>
            </thead>
            <tbody id="courseListBody">
              <?php if (!empty($courses)): ?>
                <?php foreach ($courses as $row): ?>
                  <tr data-row-id="<?= (int)$row['course_id'] ?>" data-search="<?= htmlspecialchars(strtolower($row['course_code'] . ' ' . $row['course_name'])) ?>">
                    <td><?= htmlspecialchars($row['course_code']) ?></td>
                    <td><?= htmlspecialchars($row['course_name']) ?></td>
                    <td>
                      <?= htmlspecialchars($row['total_units']) ?>
                      <div class="text-muted"><?= (int)$row['subject_count'] ?> subject<?= (int)$row['subject_count'] === 1 ? '' : 's' ?></div>
                    </td>
                    <td><span class="status-pill status-pill--<?= strtolower($row['status']) ?>"><?= htmlspecialchars($row['status']) ?></span></td>
                    <td>
                      <div class="row-actions">
                        <button type="button" class="btn btn-outline" style="padding:4px 10px;font-size:12px" data-view-course="<?= (int)$row['course_id'] ?>" data-course-label="<?= htmlspecialchars($row['course_code'] . ' — ' . $row['course_name']) ?>">View</button>
                        <?php if ($isHead): ?>
                          <button type="button" class="btn-remove" data-remove-course="<?= (int)$row['course_id'] ?>">Remove</button>
                        <?php endif; ?>
                      </div>
                    </td>
                  </tr>
                <?php endforeach; ?>
              <?php else: ?>
                <tr><td colspan="5" style="text-align:center;">No courses found.</td></tr>
              <?php endif; ?>
            </tbody>
          </table>
        </div>
      </div>
    </div>

    <!-- Add Course Modal -->
    <div id="addCourseModal" class="modal-overlay">
      <div class="modal-box">
        <div class="modal-header">
          <div class="modal-header-left">
            <div class="modal-icon">📘</div>
            <div>
              <div class="modal-title">Add Course</div>
              <div class="modal-subtitle">Create a new course record</div>
            </div>
          </div>
          <button type="button" class="modal-close" data-close="addCourseModal">✕</button>
        </div>

        <div class="modal-body">
          <div class="form-group">
            <label class="form-label">Course Code<span class="required">*</span></label>
            <input type="text" id="newCourseCode" class="form-input" placeholder="e.g. BSCS" required>
          </div>
          <div class="form-group">
            <label class="form-label">Course Name<span class="required">*</span></label>
            <input type="text" id="newCourseName" class="form-input" placeholder="e.g. Bachelor of Science in Computer Science" required>
          </div>
          <div class="form-group">
            <label class="form-label">Total Units</label>
            <input type="number" id="newCourseUnits" class="form-input" value="120" min="0" required>
          </div>
        </div>

        <div class="modal-footer">
          <button type="button" class="btn btn-outline" data-close="addCourseModal">Cancel</button>
          <button type="button" class="btn btn-primary" id="confirmAddCourse">Save Course</button>
        </div>
      </div>
    </div>

    <!-- View Course Modal -->
    <div id="viewCourseModal" class="modal-overlay">
      <div class="modal-box" style="max-width:640px;">
        <div class="modal-header">
          <div class="modal-header-left">
            <div class="modal-icon">📘</div>
            <div>
              <div class="modal-title">Subjects</div>
              <div class="modal-subtitle" id="viewCourseLabel"></div>
            </div>
          </div>
          <button type="button" class="modal-close" data-close="viewCourseModal">✕</button>
        </div>

        <div class="modal-body" style="max-height:60vh; overflow-y:auto;">
          <div class="subject-card-grid" id="viewCourseBody">
            <p style="text-align:center;">Loading…</p>
          </div>
          <div class="modal-total-units">
            <strong>Total Units</strong>
            <strong id="viewCourseTotalUnits">—</strong>
          </div>
        </div>

        <div class="modal-footer">
          <a href="subjects.php" id="viewCourseManageLink" class="btn btn-outline">Manage subjects &rarr;</a>
          <button type="button" class="btn btn-outline" data-close="viewCourseModal">Close</button>
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
