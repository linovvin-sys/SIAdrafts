<?php
$pageTitle  = "ENROLLMENT";
$activePage = "enrollment";

require_once '../../../Backend/auth.php';
require_once '../../../Backend/roles.php';
require_once '../../../Backend/require_role.php';
require_role([ROLE_STAFF, ROLE_ADMIN]);
require_once '../../../Backend/db.php';

$db   = new Database();
$conn = $db->connect();

$student       = null;
$students_list = null;
$error         = null;

// year_level, school_year, and semester were already decided at
// application time (online_admission_process.php pulls school_year/
// semester from the active-term settings and year_level from the
// applicant's own form) -- enrollment must not let staff silently pick
// different values here, so we read the applicant's actual record
// instead of defaulting/guessing.
$student_query = "
    SELECT a.applicant_id, a.reference_id, a.first_name, a.last_name, a.middle_name,
           a.applicant_type_id AS default_type_id, a.course_id, a.admission_status,
           a.year_level, a.school_year, a.semester,
           st.type_name, '—' AS section_name, 0 AS section_id
    FROM applicants a
    JOIN student_type st ON a.applicant_type_id = st.type_id
";

// Enrollment must not be reachable until walk-in document verification is
// done (get_student.php's live search already enforces this and explains
// why in its own comment) -- this page was missing the same check, so
// typing a reference ID and hitting Enter could enroll an applicant who'd
// never been verified, silently skipping that step.
function reject_if_unverified(?array $student): ?string {
    if ($student && ($student['admission_status'] ?? '') !== 'verified') {
        return 'This applicant hasn\'t completed document verification yet. Verify them via Admission first, then come back to enroll.';
    }
    return null;
}

// --- Handle POST: save params to session, go to subjects ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $reference_id = trim($_POST['reference_id']  ?? '');  // string now
    $school_year = trim($_POST['school_year']  ?? '');
    $semester    = (int)($_POST['semester']    ?? 0);
    $year_level  = (int)($_POST['year_level']  ?? 0);
    $type_id     = (int)($_POST['type_id']     ?? 1);
    $section_id  = (int)($_POST['section_id']  ?? 0);
    $course_id   = (int)($_POST['course_id']   ?? 0);

    $post_error = null;

    if (!$reference_id || !$school_year || !$semester || !$year_level || !$type_id || !$course_id) {
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
            'reference_id'   => $reference_id,
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
    $stmt = $conn->prepare($student_query . " WHERE a.reference_id = ?");
    $stmt->bind_param('s', $reference_id);
    $stmt->execute();
    $student = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    $error = reject_if_unverified($student);
    if ($error) $student = null;
}

// --- GET: resolve student from URL params ---
if (!$student && !$error && $_SERVER['REQUEST_METHOD'] === 'GET') {
    if (isset($_GET['reference_id'])) {
      $rid  = trim($_GET['reference_id']);
      $stmt = $conn->prepare(
          $student_query . "
          LEFT JOIN student s ON s.applicant_id = a.applicant_id
          WHERE a.reference_id = ? OR s.student_id = ?"
      );
      $stmt->bind_param('ss', $rid, $rid);
        $stmt->execute();
        $student = $stmt->get_result()->fetch_assoc() ?: null;
        $stmt->close();
        if (!$student) {
            $error = 'Student not found.';
        } else {
            $error = reject_if_unverified($student);
            if ($error) $student = null;
        }

    } elseif (isset($_GET['q'])) {
        $q    = trim($_GET['q']);
        $mode = $_GET['mode'] ?? 'id';

        if ($mode === 'id') {
            $stmt = $conn->prepare($student_query . " WHERE a.reference_id = ?");
            $stmt->bind_param('s', $q);
            $stmt->execute();
            $student = $stmt->get_result()->fetch_assoc() ?: null;
            $stmt->close();
            if (!$student) {
                $error = 'No student found with that ID.';
            } else {
                $error = reject_if_unverified($student);
                if ($error) $student = null;
            }
        } else {
            $like = '%' . $q . '%';
            $stmt = $conn->prepare($student_query .
                " WHERE a.first_name LIKE ? OR a.last_name LIKE ?
                  ORDER BY a.last_name, a.first_name LIMIT 20");
            $stmt->bind_param('ss', $like, $like);
            $stmt->execute();
            $rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
            $stmt->close();
            // Same rule as the reference-ID lookups above: an applicant who
            // hasn't cleared document verification can't be enrolled yet,
            // so they don't belong in the name-search picker either.
            $rows = array_values(array_filter($rows, fn($r) => ($r['admission_status'] ?? '') === 'verified'));
            if (count($rows) === 1)      $student       = $rows[0];
            elseif (count($rows) > 1)    $students_list = $rows;
            else                         $error = 'No verified students found with that name. They may still need document verification via Admission.';
        }
    } else {
        header('Location: enrollment.php');
        exit;
    }
}

// Student types for dropdown
$types_result = $conn->query("SELECT type_id, type_name FROM student_type ORDER BY type_id");
$types = $types_result->fetch_all(MYSQLI_ASSOC);

// reference_id into string erp
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
// This page's markup (.enroll-card, .wizard-steps, .btn-primary-action,
// alert-* boxes, etc.) uses the Admission section's own theme classes,
// but includes the shared Include/header.php (same as enrollment.php)
// which doesn't load them by default -- see enrollment.php for the
// same fix and full explanation.
$extraCss = [
    '/SIAdrafts/Frontend/Css/Admission/style.css',
    '/SIAdrafts/Frontend/Css/Admission/login.css',
];
?>
<?php include '../Include/header.php' ?>

<div class="app-layout">

<?php include '../Include/sidebar.php'; ?>

<main class="page-content">

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
              <a href="enrollment_profile.php?reference_id=<?= $s['reference_id'] ?>" class="student-pick-item">
                <div>
                  <strong><?= htmlspecialchars(student_fullname($s)) ?></strong>
                  <small class="d-block text-ink-soft">
                    <?= htmlspecialchars(fmt_id($s['reference_id'])) ?>
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
                <?= htmlspecialchars(fmt_id($student['reference_id'])) ?>
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
            <input type="hidden" name="reference_id"   value="<?= $student['reference_id'] ?>">
            <input type="hidden" name="section_id"   value="<?= $student['section_id'] ?>">
            <input type="hidden" name="student_name" value="<?= htmlspecialchars(student_fullname($student)) ?>">
            <input type="hidden" name="section_name" value="<?= htmlspecialchars($student['section_name']) ?>">
            <input type="hidden" name="course_id"    value="<?= (int)$student['course_id'] ?>">
            
            <?php
              // Fixed, not editable: these three were already decided at
              // application time and must carry through to enrollment
              // unchanged (see the note by $student_query above).
              $lockedSchoolYear = $student['school_year'] ?: date('Y') . '-' . (date('Y') + 1);
              $lockedSemester   = (int)($student['semester'] ?: 1);
              $lockedYearLevel  = (int)($student['year_level'] ?: 1);
              $yearOrdinal      = match ($lockedYearLevel) { 1 => '1st', 2 => '2nd', 3 => '3rd', default => "{$lockedYearLevel}th" };
            ?>

            <div class="mb-3">
              <label class="form-label fw-bold small">School Year</label>
              <input type="text" class="form-control" value="<?= htmlspecialchars($lockedSchoolYear) ?>" disabled>
              <input type="hidden" name="school_year" value="<?= htmlspecialchars($lockedSchoolYear) ?>">
              <div class="form-text">Set when the application was filed — not editable here.</div>
            </div>

            <div class="mb-3">
              <label class="form-label fw-bold small">Semester</label>
              <input type="text" class="form-control" value="<?= $lockedSemester ?><?= $lockedSemester === 1 ? 'st' : 'nd' ?> Semester" disabled>
              <input type="hidden" name="semester" value="<?= $lockedSemester ?>">
              <div class="form-text">Set when the application was filed — not editable here.</div>
            </div>

            <div class="mb-3">
              <label class="form-label fw-bold small">Year Level</label>
              <input type="text" class="form-control" value="<?= $yearOrdinal ?> Year" disabled>
              <input type="hidden" name="year_level" value="<?= $lockedYearLevel ?>">
              <div class="form-text">From the applicant's original application — not editable here.</div>
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

</main>

</div>

<?php
$extraScripts = ['/SIAdrafts/Frontend/Js/Admission/dup-enrollment-check.js'];
include '../Include/footer.php';
?>