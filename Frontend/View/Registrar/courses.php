<?php
$pageTitle  = "COURSE & SECTION";
$activePage = "courses";
$pageScript = "courses";

require_once '../../../Backend/auth.php';
require_once '../../../Backend/require_role.php';
require_role(['Head Registrar', 'Registrar Staff']);
require_once '../../../Backend/db.php';

$db   = new Database();
$conn = $db->connect();

$courses = $conn->query("
    SELECT course_id, course_code, course_name, total_units, status
    FROM course
    ORDER BY course_name
")->fetch_all(MYSQLI_ASSOC);

$sections = $conn->query("
    SELECT s.section_id, s.section_name, s.capacity, s.course_id, s.status, c.course_code
    FROM section s
    LEFT JOIN course c ON c.course_id = s.course_id
    ORDER BY c.course_code, s.section_name
")->fetch_all(MYSQLI_ASSOC);

// Only approved courses may be picked when creating a new section.
$approvedCourses = array_values(array_filter($courses, fn($c) => $c['status'] === 'Approved'));

$db->close();

$isHead = current_user_is(['Head Registrar']);

include 'Include/header.php';
?>

<div class="app-layout">

  <?php include 'Include/sidebar.php'; ?>

  <main class="page-content">

    <div class="grid-2">

      <!-- Courses -->
      <div class="panel">
        <div class="panel-header">
          <span class="panel-title">Courses</span>
          <button type="button" class="btn btn-primary" data-open="addCourseModal">+ Add Course</button>
        </div>
        <div class="panel-body" style="padding:0;">
          <table class="data-table" id="courseTable">
            <thead>
              <tr>
                <th>Code</th>
                <th>Course Name</th>
                <th>Units</th>
                <th>Status</th>
                <?php if ($isHead): ?><th style="width:80px"></th><?php endif; ?>
              </tr>
            </thead>
            <tbody>
              <?php if (!empty($courses)): ?>
                <?php foreach ($courses as $row): ?>
                  <tr data-row-id="<?= (int)$row['course_id'] ?>">
                    <td><?= htmlspecialchars($row['course_code']) ?></td>
                    <td><?= htmlspecialchars($row['course_name']) ?></td>
                    <td><?= htmlspecialchars($row['total_units']) ?></td>
                    <td><span class="status-pill status-pill--<?= strtolower($row['status']) ?>"><?= htmlspecialchars($row['status']) ?></span></td>
                    <?php if ($isHead): ?>
                      <td><button type="button" class="btn-remove" data-remove-course="<?= (int)$row['course_id'] ?>">Remove</button></td>
                    <?php endif; ?>
                  </tr>
                <?php endforeach; ?>
              <?php else: ?>
                <tr><td colspan="<?= $isHead ? 5 : 4 ?>" style="text-align:center;">No courses found.</td></tr>
              <?php endif; ?>
            </tbody>
          </table>
        </div>
      </div>

      <!-- Sections -->
      <div class="panel">
        <div class="panel-header">
          <span class="panel-title">Sections</span>
          <button type="button" class="btn btn-primary" data-open="addSectionModal">+ Add Section</button>
        </div>
        <div class="panel-body" style="padding:0;">
          <table class="data-table" id="sectionTable">
            <thead>
              <tr>
                <th>Section</th>
                <th>Course</th>
                <th>Capacity</th>
                <th>Status</th>
                <?php if ($isHead): ?><th style="width:80px"></th><?php endif; ?>
              </tr>
            </thead>
            <tbody>
              <?php if (!empty($sections)): ?>
                <?php foreach ($sections as $row): ?>
                  <tr data-row-id="<?= (int)$row['section_id'] ?>">
                    <td><?= htmlspecialchars($row['section_name']) ?></td>
                    <td><?= htmlspecialchars($row['course_code'] ?? '—') ?></td>
                    <td><?= htmlspecialchars($row['capacity']) ?></td>
                    <td><span class="status-pill status-pill--<?= strtolower($row['status']) ?>"><?= htmlspecialchars($row['status']) ?></span></td>
                    <?php if ($isHead): ?>
                      <td><button type="button" class="btn-remove" data-remove-section="<?= (int)$row['section_id'] ?>">Remove</button></td>
                    <?php endif; ?>
                  </tr>
                <?php endforeach; ?>
              <?php else: ?>
                <tr><td colspan="<?= $isHead ? 5 : 4 ?>" style="text-align:center;">No sections found.</td></tr>
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

  </main>
</div>

<?php include 'Include/footer.php'; ?>