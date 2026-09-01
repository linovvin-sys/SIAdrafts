<?php
$pageTitle  = "SUBJECTS";
$activePage = "subjects";
$pageScript = "subjects";

require_once '../../../Backend/auth.php';
require_once '../../../Backend/require_role.php';
require_role(['Registrar Staff', 'Head Registrar', 'Admin']);
require_once '../../../Backend/db.php';

$db   = new Database();
$conn = $db->connect();

$isAdminViewer = current_user_is(['Admin']);

$courses = $conn->query("SELECT course_id, course_code, course_name, status FROM course ORDER BY course_name")->fetch_all(MYSQLI_ASSOC);
$approvedCourses = array_values(array_filter($courses, fn($c) => $c['status'] === 'Approved'));

$subjects = $conn->query("
    SELECT sub.subject_id, sub.subject_code, sub.subject_name, sub.units, sub.course_id,
           sc.category_name, c.course_code
    FROM subject sub
    LEFT JOIN subject_category sc ON sc.category_id = sub.category_id
    LEFT JOIN course c ON c.course_id = sub.course_id
    ORDER BY c.course_code, sub.subject_code
")->fetch_all(MYSQLI_ASSOC);

$categories = $conn->query("SELECT category_id, category_name FROM subject_category ORDER BY category_name")->fetch_all(MYSQLI_ASSOC);

$db->close();

$totalSubjects  = count($subjects);
$totalSubjUnits = array_sum(array_column($subjects, 'units'));
$categoryCount  = count($categories);
$subjCoursesCovered = count(array_unique(array_filter(array_column($subjects, 'course_id'))));

// Arrived via a "Manage subjects ->" link from a specific course — pre-select
// that course in both the filter dropdown and the Add Subject modal.
$lockedCourseId = 0;
if (isset($_GET['course_id']) && ctype_digit((string)$_GET['course_id'])) {
    $candidate = (int)$_GET['course_id'];
    foreach ($courses as $c) {
        if ((int)$c['course_id'] === $candidate) { $lockedCourseId = $candidate; break; }
    }
}

// Stable color assignment per course code, built directly from the actual
// course list instead of a hash. crc32($code) % 4 looked "random" but with
// only a handful of real course codes it produced real collisions — e.g.
// BSCRIM, BSCS, and BSPSYCH all happened to hash to the same bucket
// (gold), leaving blue used once and teal unused entirely. Assigning in
// order from the real course list guarantees no two courses share a color
// as long as there are 4 or fewer non-BSIT courses (true today); a 5th
// would cycle back and share with the 1st, which is still strictly better
// than the hash's uneven 3-way collision.
function build_course_accent_map(array $courses): array {
    $accents = ['accent-blue', 'accent-coral', 'accent-teal', 'accent-gold'];
    $map = [];
    $i = 0;
    foreach ($courses as $c) {
        $code = $c['course_code'];
        if ($code === 'BSIT') {
            $map[$code] = 'accent-green';
            continue;
        }
        $map[$code] = $accents[$i % count($accents)];
        $i++;
    }
    return $map;
}
$courseAccentMap = build_course_accent_map($courses);

function course_accent_class(?string $courseCode, array $map): string {
    if (!$courseCode) return 'accent-blue';
    return $map[$courseCode] ?? 'accent-blue';
}

include '../Include/header.php';
?>

<div class="app-layout">

  <?php include '../Include/sidebar.php'; ?>

  <main class="page-content">
    <?php include '../Include/readonly_banner.php'; ?>

    <div class="clay-stat-grid clay-stat-grid--compact">
      <div class="clay-stat-card">
        <div class="clay-icon"><i class="bi bi-journal-bookmark-fill"></i></div>
        <div>
          <div class="stat-value"><?= $totalSubjects ?></div>
          <div class="stat-label">Total Subjects</div>
        </div>
      </div>
      <div class="clay-stat-card">
        <div class="clay-icon"><i class="bi bi-mortarboard-fill"></i></div>
        <div>
          <div class="stat-value"><?= $totalSubjUnits ?></div>
          <div class="stat-label">Total Units</div>
        </div>
      </div>
      <div class="clay-stat-card">
        <div class="clay-icon"><i class="bi bi-tags-fill"></i></div>
        <div>
          <div class="stat-value"><?= $categoryCount ?></div>
          <div class="stat-label">Categories</div>
        </div>
      </div>
      <div class="clay-stat-card">
        <div class="clay-icon"><i class="bi bi-book-half"></i></div>
        <div>
          <div class="stat-value"><?= $subjCoursesCovered ?></div>
          <div class="stat-label">Courses Covered</div>
        </div>
      </div>
    </div>

    <div class="panel">
      <div class="panel-header">
        <span class="panel-title">Subjects</span>
        <?php if (!$isAdminViewer): ?>
        <div class="row-actions">
          <a href="/SIAdrafts/Backend/api/Curriculum/curriculum_template.php" class="btn btn-outline">Download template</a>
          <button type="button" class="btn btn-outline" data-open="importCurriculumModal">Import Curriculum (CSV)</button>
          <button type="button" class="btn btn-primary" data-open="addSubjectModal">+ Add Subject</button>
        </div>
        <?php endif; ?>
      </div>

      <div class="panel-body clay-filter-bar" style="padding:16px 24px 0;">
        <div class="filter-bar">
          <input type="text" class="form-input" id="subjectSearch" placeholder="Search code, subject name, or course…">
          <div class="select-wrapper">
            <select class="form-input form-select" id="subjectCourseFilter">
              <option value="">All Courses</option>
              <?php foreach ($courses as $c): ?>
                <option value="<?= (int)$c['course_id'] ?>" <?= $lockedCourseId === (int)$c['course_id'] ? 'selected' : '' ?>><?= htmlspecialchars($c['course_code']) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
        </div>
      </div>

      <div class="panel-body">
        <div class="subject-card-grid" id="subjectCardGrid">
          <?php if (!empty($subjects)): ?>
            <?php foreach ($subjects as $s): ?>
              <div class="subject-card <?= course_accent_class($s['course_code'], $courseAccentMap) ?>" data-course-id="<?= (int)$s['course_id'] ?>" data-search="<?= htmlspecialchars(strtolower($s['subject_code'] . ' ' . $s['subject_name'] . ' ' . ($s['course_code'] ?? ''))) ?>">
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
        <div class="rd-paginate" id="subjectPaginate" style="display:none;">
          <span class="rd-paginate-info" id="subjectPaginateInfo"></span>
          <ul class="pagination" id="subjectPaginateList"></ul>
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
                  <option value="<?= (int)$c['course_id'] ?>" <?= $lockedCourseId === (int)$c['course_id'] ? 'selected' : '' ?>>
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
            <input type="number" id="newSubjectUnits" class="form-input" value="3" min="0.5" step="0.5" required>
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

    <!-- Import Curriculum Modal -->
    <div id="importCurriculumModal" class="modal-overlay">
      <div class="modal-box" style="max-width:760px;">
        <div class="modal-header">
          <div class="modal-header-left">
            <div class="modal-icon"><i class="bi bi-file-earmark-spreadsheet"></i></div>
            <div>
              <div class="modal-title">Import Curriculum</div>
              <div class="modal-subtitle">Bulk-add subjects from a CSV file instead of one at a time.</div>
            </div>
          </div>
          <button type="button" class="modal-close" data-close="importCurriculumModal">✕</button>
        </div>

        <div class="modal-body" style="max-height:65vh; overflow-y:auto;">
          <div class="form-group">
            <label class="form-label">CSV file<span class="required">*</span></label>
            <input type="file" id="curriculumCsvFile" class="form-input" accept=".csv,text/csv">
            <div class="text-muted" style="margin-top:6px;font-size:12px;">
              Columns: course_code, subject_code, subject_name, units, category_name, year_level, semester, prerequisite_code (optional).
              <a href="/SIAdrafts/Backend/api/Curriculum/curriculum_template.php">Download a template</a>.
            </div>
          </div>

          <div id="curriculumImportStatus" style="display:none;"></div>

          <div id="curriculumPreviewWrap" style="display:none;margin-top:16px;">
            <div id="curriculumPreviewSummary" class="text-muted" style="margin-bottom:10px;font-size:13px;"></div>
            <div class="table-responsive">
              <table class="data-table">
                <thead>
                  <tr>
                    <th style="width:50px;">Line</th>
                    <th>Subject</th>
                    <th>Course</th>
                    <th style="width:140px;">Status</th>
                  </tr>
                </thead>
                <tbody id="curriculumPreviewBody"></tbody>
              </table>
            </div>
          </div>
        </div>

        <div class="modal-footer">
          <button type="button" class="btn btn-outline" data-close="importCurriculumModal">Cancel</button>
          <button type="button" class="btn btn-outline" id="previewCurriculumBtn">Preview</button>
          <button type="button" class="btn btn-primary" id="confirmCurriculumImport" disabled>Confirm Import</button>
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
