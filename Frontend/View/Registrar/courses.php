<?php
$pageTitle  = "COURSE & SECTION";
$activePage = "courses";
$pageScript = "courses";

require_once '../../../Backend/auth.php';
require_once '../../../Backend/require_role.php';
require_role(['Registrar Staff']);
require_once '../../../Backend/db.php';

$db   = new Database();
$conn = $db->connect();

$courses = $conn->query("
    SELECT c.course_id, c.course_code, c.course_name, c.total_units, c.status,
           (SELECT COUNT(*) FROM subject WHERE course_id = c.course_id) AS subject_count
    FROM course c
    ORDER BY c.course_name
")->fetch_all(MYSQLI_ASSOC);

$sections = $conn->query("
    SELECT s.section_id, s.section_name, s.capacity, s.course_id, s.status, c.course_code
    FROM section s
    LEFT JOIN course c ON c.course_id = s.course_id
    ORDER BY c.course_code, s.section_name
")->fetch_all(MYSQLI_ASSOC);

// Only approved courses may be picked when creating a new section.
$approvedCourses = array_values(array_filter($courses, fn($c) => $c['status'] === 'Approved'));

$subjects = $conn->query("
    SELECT sub.subject_id, sub.subject_code, sub.subject_name, sub.units,
           sc.category_name, c.course_code
    FROM subject sub
    LEFT JOIN subject_category sc ON sc.category_id = sub.category_id
    LEFT JOIN course c ON c.course_id = sub.course_id
    ORDER BY c.course_code, sub.subject_code
")->fetch_all(MYSQLI_ASSOC);

$categories = $conn->query("SELECT category_id, category_name FROM subject_category ORDER BY category_name")->fetch_all(MYSQLI_ASSOC);

$db->close();

$isHead = current_user_is(['Head Registrar']);

// Stable color assignment per course code, so the same course always gets
// the same accent bar across page loads (not random per-render).
function course_accent_class(?string $courseCode): string {
    $accents = ['accent-blue', 'accent-coral', 'accent-teal', 'accent-gold'];
    if (!$courseCode) return $accents[0];
    $index = crc32($courseCode) % count($accents);
    return $accents[$index];
}

include '../Include/header.php';
?>

<div class="app-layout">

  <?php include '../Include/sidebar.php'; ?>

  <main class="page-content">

    <div class="grid-2">

      <!-- Courses -->
      <div class="panel">
        <div class="panel-header">
          <span class="panel-title">Courses</span>
          <button type="button" class="btn btn-primary" data-open="addCourseModal">+ Add Course</button>
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

      <!-- Sections -->
      <div class="panel">
        <div class="panel-header">
          <span class="panel-title">Sections</span>
          <button type="button" class="btn btn-primary" data-open="addSectionModal">+ Add Section</button>
        </div>

        <div class="panel-body" style="padding:0;">
          <div class="table-wrap">
            <table class="data-table" id="sectionTable">
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
                  <?php foreach ($sections as $row): ?>
                    <tr data-row-id="<?= (int)$row['section_id'] ?>" data-search="<?= htmlspecialchars(strtolower($row['section_name'] . ' ' . ($row['course_code'] ?? ''))) ?>">
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

    </div>

    <!-- Subjects -->
    <div class="panel" style="margin-top:24px;">
      <div class="panel-header">
        <span class="panel-title">Subjects</span>
        <button type="button" class="btn btn-primary" data-open="addSubjectModal">+ Add Subject</button>
      </div>

      <div class="panel-body" style="padding:16px 24px 0;">
        <div class="filter-bar">
          <input type="text" class="form-input" id="subjectSearch" placeholder="Search code, subject name, or course…">
        </div>
      </div>

      <div class="panel-body">
        <div class="subject-card-grid" id="subjectCardGrid">
          <?php if (!empty($subjects)): ?>
            <?php foreach ($subjects as $s): ?>
              <div class="subject-card <?= course_accent_class($s['course_code']) ?>" data-search="<?= htmlspecialchars(strtolower($s['subject_code'] . ' ' . $s['subject_name'] . ' ' . ($s['course_code'] ?? ''))) ?>">
                <div class="subject-card-top">
                  <span class="subject-card-code mono"><?= htmlspecialchars($s['subject_code']) ?></span>
                  <span class="subject-card-category"><?= htmlspecialchars($s['category_name'] ?? 'Uncategorized') ?></span>
                </div>
                <div class="subject-card-name"><?= htmlspecialchars($s['subject_name']) ?></div>
                <div class="subject-card-footer">
                  <span><?= htmlspecialchars($s['units']) ?> unit<?= (float)$s['units'] == 1 ? '' : 's' ?></span>
                  <span class="mono"><?= htmlspecialchars($s['course_code'] ?? '—') ?></span>
                </div>
              </div>
            <?php endforeach; ?>
          <?php endif; ?>
        </div>
        <div class="empty-state" id="subjectListEmptyState" style="display:<?= empty($subjects) ? 'block' : 'none' ?>;">
          <p>No subjects found.</p>
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

    <!-- Add Subject Modal -->
    <div id="addSubjectModal" class="modal-overlay">
      <div class="modal-box">
        <div class="modal-header">
          <div class="modal-header-left">
            <div class="modal-icon">📗</div>
            <div>
              <div class="modal-title">Add Subject</div>
              <div class="modal-subtitle">Create a new subject</div>
            </div>
          </div>
          <button type="button" class="modal-close" data-close="addSubjectModal">✕</button>
        </div>

        <div class="modal-body">
          <div class="form-group">
            <label class="form-label">Course<span class="required">*</span></label>
            <div class="select-wrapper">
              <select id="newSubjectCourse" class="form-input form-select" required>
                <option value="">-- Select Course --</option>
                <?php foreach ($approvedCourses as $c): ?>
                  <option value="<?= (int)$c['course_id'] ?>">
                    <?= htmlspecialchars($c['course_code']) ?> - <?= htmlspecialchars($c['course_name']) ?>
                  </option>
                <?php endforeach; ?>
              </select>
            </div>
          </div>
          <div class="form-group">
            <label class="form-label">Subject Code<span class="required">*</span></label>
            <input type="text" id="newSubjectCode" class="form-input" placeholder="e.g. CS101" required>
          </div>
          <div class="form-group">
            <label class="form-label">Subject Name<span class="required">*</span></label>
            <input type="text" id="newSubjectName" class="form-input" placeholder="e.g. Introduction to Programming" required>
          </div>
          <div class="form-group">
            <label class="form-label">Units</label>
            <input type="number" id="newSubjectUnits" class="form-input" value="3" min="0" step="0.5" required>
          </div>
          <div class="form-group">
            <label class="form-label">Category<span class="required">*</span></label>
            <div class="select-wrapper">
              <select id="newSubjectCategory" class="form-input form-select" required>
                <option value="">-- Select Category --</option>
                <?php foreach ($categories as $cat): ?>
                  <option value="<?= (int)$cat['category_id'] ?>"><?= htmlspecialchars($cat['category_name']) ?></option>
                <?php endforeach; ?>
              </select>
            </div>
          </div>
          <div class="form-group">
            <label class="form-label">Year Level<span class="required">*</span></label>
            <div class="select-wrapper">
              <select id="newSubjectYearLevel" class="form-input form-select" required>
                <option value="">-- Select Year Level --</option>
                <option value="1">1st Year</option>
                <option value="2">2nd Year</option>
                <option value="3">3rd Year</option>
                <option value="4">4th Year</option>
              </select>
            </div>
          </div>
          <div class="form-group">
            <label class="form-label">Semester<span class="required">*</span></label>
            <div class="select-wrapper">
              <select id="newSubjectSemester" class="form-input form-select" required>
                <option value="">-- Select Semester --</option>
                <option value="1">1st Semester</option>
                <option value="2">2nd Semester</option>
              </select>
            </div>
          </div>
        </div>

        <div class="modal-footer">
          <button type="button" class="btn btn-outline" data-close="addSubjectModal">Cancel</button>
          <button type="button" class="btn btn-primary" id="confirmAddSubject">Save Subject</button>
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
          <button type="button" class="btn btn-outline" data-close="viewCourseModal">Close</button>
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