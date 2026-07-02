<?php
require_once '../../../Backend/auth.php';
require_once '../../../Backend/db.php';

$db   = new Database();
$conn = $db->connect();

$page_scripts = ['/SIAdrafts/Frontend/Js/Admission/dup-enrollment-check.js'];

$student       = null;
$students_list = null;
$error         = null;

$student_query = "
    SELECT a.applicant_id, a.student_id, a.first_name, a.last_name, a.middle_name,
           a.applicant_type_id AS default_type_id, a.course_id,
           st.type_name, '—' AS section_name, 0 AS section_id
    FROM applicants a
    JOIN student_type st ON a.applicant_type_id = st.type_id
";

// --- Handle POST: save params to session, go to subjects ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $student_id  = trim($_POST['student_id']  ?? '');  // string now
    $school_year = trim($_POST['school_year']  ?? '');
    $semester    = (int)($_POST['semester']    ?? 0);
    $year_level  = (int)($_POST['year_level']  ?? 0);
    $type_id     = (int)($_POST['type_id']     ?? 1);
    $section_id  = (int)($_POST['section_id']  ?? 0);
    $course_id   = (int)($_POST['course_id']   ?? 0);

    $post_error = null;

    if (!$student_id || !$school_year || !$semester || !$year_level || !$type_id || !$course_id) {
        $post_error = 'All fields are required.';
    } elseif (!preg_match('/^\d{4}-\d{4}$/', $school_year)) {
        $post_error = 'School year must be in YYYY-YYYY format (e.g. 2025-2026).';
    } elseif ($semester < 1 || $semester > 2) {
        $post_error = 'Invalid semester.';
    } elseif ($year_level < 1 || $year_level > 5) {
        $post_error = 'Invalid year level.';
    }

    if (!$post_error) {
        $_SESSION['enroll'] = [
            'student_id'   => $student_id,
            'student_name' => trim($_POST['student_name'] ?? ''),
            'section_name' => trim($_POST['section_name'] ?? ''),
            'section_id'   => $section_id,
            'school_year'  => $school_year,
            'semester'     => $semester,
            'year_level'   => $year_level,
            'type_id'      => $type_id,
            'course_id'    => $course_id,
        ];
        header('Location: enrollment_subjects.php');
        exit;
    }

    // Re-fetch student on POST error
    $stmt = $conn->prepare($student_query . " WHERE a.student_id = ?");
    $stmt->bind_param('s', $student_id);
    $stmt->execute();
    $student = $stmt->get_result()->fetch_assoc();
    $stmt->close();
}

// --- GET: resolve student from URL params ---
if (!$student && $_SERVER['REQUEST_METHOD'] === 'GET') {
    if (isset($_GET['student_id'])) {
        $sid  = trim($_GET['student_id']);  // string, no int cast
        $stmt = $conn->prepare($student_query . " WHERE a.student_id = ?");
        $stmt->bind_param('s', $sid);
        $stmt->execute();
        $student = $stmt->get_result()->fetch_assoc() ?: null;
        $stmt->close();
        if (!$student) $error = 'Student not found.';

    } elseif (isset($_GET['q'])) {
        $q    = trim($_GET['q']);
        $mode = $_GET['mode'] ?? 'id';

        if ($mode === 'id') {
            $stmt = $conn->prepare($student_query . " WHERE a.student_id = ?");
            $stmt->bind_param('s', $q);
            $stmt->execute();
            $student = $stmt->get_result()->fetch_assoc() ?: null;
            $stmt->close();
            if (!$student) $error = 'No student found with that ID.';
        } else {
            $like = '%' . $q . '%';
            $stmt = $conn->prepare($student_query .
                " WHERE a.first_name LIKE ? OR a.last_name LIKE ?
                  ORDER BY a.last_name, a.first_name LIMIT 20");
            $stmt->bind_param('ss', $like, $like);
            $stmt->execute();
            $rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
            $stmt->close();
            if (count($rows) === 1)      $student       = $rows[0];
            elseif (count($rows) > 1)    $students_list = $rows;
            else                         $error = 'No students found with that name.';
        }
    } else {
        header('Location: enrollment.php');
        exit;
    }
}

// Detect year level from section name
$detected_year = 1;

// Default school year / semester based on current month
$now        = new DateTime();
$yr         = (int)$now->format('Y');
$mo         = (int)$now->format('n');
$default_sy  = $mo >= 6 ? "$yr-" . ($yr + 1) : ($yr - 1) . "-$yr";
$default_sem = $mo >= 6 ? 1 : 2;

// Student types for dropdown
$types_result = $conn->query("SELECT type_id, type_name FROM student_type ORDER BY type_id");
$types = $types_result->fetch_all(MYSQLI_ASSOC);

// student_id is already formatted as 2026-XXXXX
function fmt_id(string $id): string {
    return $id;
}

function student_fullname(array $s): string {
    $ln = $s['last_name']  ?? '';
    $fn = $s['first_name'] ?? '';
    $mn = $s['middle_name'] ?? '';
    return ($ln && $fn)
        ? $ln . ', ' . $fn . ($mn ? ' ' . $mn : '')
        : '';
}
?>
<?php include '../Admission/Include/header.php' ?>

<div class="container" style="padding-top: calc(var(--nav-h) + 40px); padding-bottom: 60px;">
  <div class="row justify-content-center">
    <div class="col-12 col-sm-10 col-md-8 col-lg-6">

      <!-- Stepper -->
      <div class="wizard-steps mb-4">
        <div class="ws-step done"><span>1</span> Search</div>
        <div class="ws-line done"></div>
        <div class="ws-step active"><span>2</span> Profile</div>
        <div class="ws-line"></div>
        <div class="ws-step"><span>3</span> Subjects</div>
        <div class="ws-line"></div>
        <div class="ws-step"><span>4</span> Confirm</div>
      </div>

      <?php if ($error): ?>
        <div class="alert-box alert-error mb-4">
          <iconify-icon icon="mdi:alert-circle-outline"></iconify-icon>
          <?= htmlspecialchars($error) ?>
        </div>
        <a href="enrollment.php" class="btn-back-link">
          <iconify-icon icon="mdi:arrow-left"></iconify-icon> Back to Search
        </a>

      <?php elseif ($students_list): ?>
        <div class="card enroll-card p-4">
          <h2 class="h5 fw-bold mb-1">Multiple students found</h2>
          <p class="text-ink-soft small mb-3">Select the correct student to proceed.</p>
          <div class="student-pick-list">
            <?php foreach ($students_list as $s): ?>
              <a href="enrollment_profile.php?student_id=<?= $s['student_id'] ?>" class="student-pick-item">
                <div>
                  <strong><?= htmlspecialchars(student_fullname($s)) ?></strong>
                  <small class="d-block text-ink-soft">
                    <?= htmlspecialchars(fmt_id((int)$s['student_id'])) ?>
                    &mdash; <?= htmlspecialchars($s['section_name']) ?>
                    &mdash; <?= htmlspecialchars($s['type_name']) ?>
                  </small>
                </div>
                <iconify-icon icon="mdi:chevron-right"></iconify-icon>
              </a>
            <?php endforeach; ?>
          </div>
        </div>
        <a href="enrollment.php" class="btn-back-link mt-3">
          <iconify-icon icon="mdi:arrow-left"></iconify-icon> Back to Search
        </a>

      <?php elseif ($student): ?>
        <!-- Student info (read-only) -->
        <div class="card enroll-card p-4 mb-3">
          <div class="d-flex align-items-center gap-3 mb-3">
            <div class="student-avatar">
              <iconify-icon icon="mdi:account"></iconify-icon>
            </div>
            <div>
              <h2 class="h5 fw-bold mb-0"><?= htmlspecialchars(student_fullname($student)) ?></h2>
              <p class="text-ink-soft small mb-0">
                <?= htmlspecialchars(fmt_id($student['student_id'])) ?>
                &mdash; <?= htmlspecialchars($student['section_name']) ?>
              </p>
            </div>
          </div>
          <div class="profile-readonly-row">
            <span class="pro-label">Section</span>
            <span class="pro-value"><?= htmlspecialchars($student['section_name']) ?></span>
          </div>
          <div class="profile-readonly-row">
            <span class="pro-label">Current Type</span>
            <span class="pro-value"><?= htmlspecialchars($student['type_name']) ?></span>
          </div>
        </div>

        <!-- Enrollment parameters form -->
        <div class="card enroll-card p-4" id="profile-form-wrap">
          <h3 class="h6 fw-bold text-ink-soft text-uppercase letter-spacing mb-3">Enrollment Details</h3>

          <?php if (!empty($post_error)): ?>
            <div class="alert-box alert-error mb-3">
              <iconify-icon icon="mdi:alert-circle-outline"></iconify-icon>
              <?= htmlspecialchars($post_error) ?>
            </div>
          <?php endif; ?>

          <!-- Duplicate enrollment warning (filled by JS) -->
          <div id="dup-warning" class="alert-box alert-warning mb-3" hidden>
            <iconify-icon icon="mdi:alert-outline"></iconify-icon>
            <span id="dup-msg"></span>
          </div>

          <form method="POST" action="enrollment_profile.php">
            <input type="hidden" name="student_id"   value="<?= $student['student_id'] ?>">
            <input type="hidden" name="section_id"   value="<?= $student['section_id'] ?>">
            <input type="hidden" name="student_name" value="<?= htmlspecialchars(student_fullname($student)) ?>">
            <input type="hidden" name="section_name" value="<?= htmlspecialchars($student['section_name']) ?>">
            <input type="hidden" name="course_id"    value="<?= (int)$student['course_id'] ?>">
            
            <div class="mb-3">
              <label class="form-label fw-bold small">School Year</label>
              <input type="text" name="school_year" id="field-sy"
                     class="form-control"
                     value="<?= htmlspecialchars($_POST['school_year'] ?? $default_sy) ?>"
                     placeholder="e.g. 2025-2026"
                     pattern="\d{4}-\d{4}" required>
              <div class="form-text">Format: YYYY-YYYY</div>
            </div>

            <div class="mb-3">
              <label class="form-label fw-bold small">Semester</label>
              <select name="semester" id="field-sem" class="form-select" required>
                <option value="1" <?= (($_POST['semester'] ?? $default_sem) == 1) ? 'selected' : '' ?>>1st Semester</option>
                <option value="2" <?= (($_POST['semester'] ?? $default_sem) == 2) ? 'selected' : '' ?>>2nd Semester</option>
              </select>
            </div>

            <div class="mb-3">
              <label class="form-label fw-bold small">Year Level</label>
              <select name="year_level" class="form-select" required>
                <?php for ($y = 1; $y <= 4; $y++): ?>
                  <option value="<?= $y ?>" <?= (($_POST['year_level'] ?? $detected_year) == $y) ? 'selected' : '' ?>>
                    <?= $y ?><?= match($y){1=>'st',2=>'nd',3=>'rd',default=>'th'} ?> Year
                  </option>
                <?php endfor; ?>
              </select>
            </div>

            <div class="mb-4">
              <label class="form-label fw-bold small">Student Type</label>
              <select name="type_id" class="form-select" required>
                <?php foreach ($types as $t): ?>
                  <option value="<?= $t['type_id'] ?>"
                    <?= (($_POST['type_id'] ?? $student['default_type_id']) == $t['type_id']) ? 'selected' : '' ?>>
                    <?= htmlspecialchars($t['type_name']) ?>
                  </option>
                <?php endforeach; ?>
              </select>
            </div>

            <button type="submit" class="btn-primary-action w-100">
              Proceed to Subject Selection
              <iconify-icon icon="mdi:arrow-right"></iconify-icon>
            </button>
          </form>
        </div>

        <a href="enrollment.php" class="btn-back-link mt-3">
          <iconify-icon icon="mdi:arrow-left"></iconify-icon> Back to Search
        </a>
      <?php endif; ?>

    </div>
  </div>
</div>

<?php include '../Admission/Include/footer.php' ?>