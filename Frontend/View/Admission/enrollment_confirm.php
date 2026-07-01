<?php
require_once '../../../Backend/auth.php';
require_once '../../../Backend/db.php';

$db   = new Database();
$conn = $db->connect();

$enroll = $_SESSION['enroll'] ?? null;
if (!$enroll || empty($enroll['subject_ids']) || empty($enroll['section_id'])) {
    header('Location: enrollment.php');
    exit;
}

// Fetch fresh student data from applicants
$stmt = $conn->prepare(
    "SELECT a.applicant_id, a.student_id, a.first_name, a.last_name, a.middle_name,
            st.type_name
     FROM applicants a
     JOIN student_type st ON a.applicant_type_id = st.type_id
     WHERE a.student_id = ?"
);
$stmt->bind_param('s', $enroll['student_id']);
$stmt->execute();
$student = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$student) {
    unset($_SESSION['enroll']);
    header('Location: enrollment.php');
    exit;
}

// Section was chosen in step 3 (enrollment_subjects.php); use it directly
// rather than re-deriving anything from the applicant record.
$section_id   = (int)$enroll['section_id'];
$section_name = $enroll['section_name'] ?? null;

if (!$section_name) {
    $secStmt = $conn->prepare("SELECT section_name FROM section WHERE section_id = ?");
    $secStmt->bind_param('i', $section_id);
    $secStmt->execute();
    $section_name = $secStmt->get_result()->fetch_row()[0] ?? '—';
    $secStmt->close();
}

// Fetch selected subjects with schedule info
$ids = $enroll['subject_ids'];
$ph  = implode(',', array_fill(0, count($ids), '?'));

$types  = str_repeat('i', count($ids));
$params = array_merge([$enroll['school_year'], $enroll['semester'], $section_id], $ids);

$stmt = $conn->prepare(
    "SELECT sub.subject_id, sub.subject_code, sub.subject_name, sub.units,
            sc.category_name,
            sch.day, sch.time_start, sch.time_end,
            CONCAT(p.first_name, ' ', p.last_name) AS professor_name,
            r.room_name
     FROM subject sub
     JOIN subject_category sc ON sub.category_id = sc.category_id
     LEFT JOIN schedule sch ON sch.subject_id = sub.subject_id
         AND sch.school_year = ? AND sch.semester = ? AND sch.section_id = ?
     LEFT JOIN professor p ON sch.professor_id = p.professor_id
     LEFT JOIN room r      ON sch.room_id      = r.room_id
     WHERE sub.subject_id IN ($ph)
     ORDER BY sc.category_name, sub.subject_code"
);

$bind_types = 'sii' . $types;
$stmt->bind_param($bind_types, ...$params);
$stmt->execute();
$subjects = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

$total_units = array_sum(array_column($subjects, 'units'));
$sem_label   = $enroll['semester'] == 1 ? '1st Semester' : '2nd Semester';
$yr_label    = $enroll['year_level'] . match((int)$enroll['year_level']) {
    1 => 'st', 2 => 'nd', 3 => 'rd', default => 'th'
} . ' Year';

// Student type name lookup
$type_stmt = $conn->prepare("SELECT type_name FROM student_type WHERE type_id = ?");
$type_stmt->bind_param('i', $enroll['type_id']);
$type_stmt->execute();
$type_name = $type_stmt->get_result()->fetch_row()[0] ?? 'Regular';
$type_stmt->close();

// ---- Fee breakdown preview (informational only) ----
// The actual payment row + payment_breakdown snapshot are created
// automatically when the student clicks "Finalize Enrollment"
// (see save_enrollment.php). This just previews what they'll owe,
// based on the fee schedule configured for this year level / school
// year, and lets us block finalizing if no schedule exists yet.
const PAYMENT_DUE_DAYS = 3;

$feeStmt = $conn->prepare(
    "SELECT fee_schedule_id, total_amount FROM fee_schedule
     WHERE year_level = ? AND school_year = ? AND is_active = 1
     LIMIT 1"
);
$feeStmt->bind_param('is', $enroll['year_level'], $enroll['school_year']);
$feeStmt->execute();
$feeSchedule = $feeStmt->get_result()->fetch_assoc();
$feeStmt->close();

$feeItems = [];
if ($feeSchedule) {
    $itemsStmt = $conn->prepare(
        "SELECT label, amount FROM fee_schedule_item WHERE fee_schedule_id = ? ORDER BY sort_order"
    );
    $itemsStmt->bind_param('i', $feeSchedule['fee_schedule_id']);
    $itemsStmt->execute();
    $feeItems = $itemsStmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $itemsStmt->close();
}

$preview_due_date = date('F j, Y', strtotime('+' . PAYMENT_DUE_DAYS . ' days'));

$page_scripts = ['/SIAdrafts/Frontend/Js/Admission/enrollment-finalize.js'];

function fmt_id(string $id): string {
    return $id; // already formatted as 2026-XXXXX
}

function student_fullname(array $s): string {
    $ln = $s['last_name'] ?? '';
    $fn = $s['first_name'] ?? '';
    $mn = $s['middle_name'] ?? '';
    return ($ln && $fn) ? $ln . ', ' . $fn . ($mn ? ' ' . $mn : '') : '';
}

function fmt_time(string $t): string {
    if (!$t) return '';
    [$h, $m] = explode(':', $t);
    $hr = (int)$h;
    return ($hr > 12 ? $hr - 12 : ($hr ?: 12)) . ':' . $m . ($hr >= 12 ? 'PM' : 'AM');
}
?>
<?php include '../Admission/Include/header.php'; ?>

<div class="container" style="padding-top: calc(var(--nav-h) + 40px); padding-bottom: 60px;" id="confirm-app" v-cloak>
  <div class="row justify-content-center">
    <div class="col-12 col-lg-8">

      <!-- Stepper -->
      <div class="wizard-steps mb-4 no-print">
        <div class="ws-step done"><span>1</span> Search</div>
        <div class="ws-line done"></div>
        <div class="ws-step done"><span>2</span> Profile</div>
        <div class="ws-line done"></div>
        <div class="ws-step done"><span>3</span> Section</div>
        <div class="ws-line done"></div>
        <div class="ws-step active"><span>4</span> Confirm</div>
      </div>

      <!-- Registration Form -->
      <div class="reg-form-wrap">

        <!-- Header -->
        <div class="reg-header">
          <div class="reg-school-mark">
            <iconify-icon icon="mdi:school"></iconify-icon>
          </div>
          <div>
            <h2 class="reg-title">Enrollment Registration Form</h2>
            <p class="reg-subtitle">
              School Year <?= htmlspecialchars($enroll['school_year']) ?>
              &mdash; <?= htmlspecialchars($sem_label) ?>
            </p>
          </div>
          <button type="button" class="btn-print no-print" onclick="window.print()">
            <iconify-icon icon="mdi:printer"></iconify-icon> Print
          </button>
        </div>

        <!-- Student info -->
        <div class="reg-section">
          <div class="reg-info-grid">
            <div class="reg-info-row">
              <span class="ri-label">Student ID</span>
              <span class="ri-value"><?= htmlspecialchars(fmt_id($student['student_id'])) ?></span>
            </div>
            <div class="reg-info-row">
              <span class="ri-label">Full Name</span>
              <span class="ri-value"><?= htmlspecialchars(student_fullname($student)) ?></span>
            </div>
            <div class="reg-info-row">
              <span class="ri-label">Section</span>
              <span class="ri-value"><?= htmlspecialchars($section_name) ?></span>
            </div>
            <div class="reg-info-row">
              <span class="ri-label">Year Level</span>
              <span class="ri-value"><?= htmlspecialchars($yr_label) ?></span>
            </div>
            <div class="reg-info-row">
              <span class="ri-label">Student Type</span>
              <span class="ri-value"><?= htmlspecialchars($type_name) ?></span>
            </div>
          </div>
        </div>

        <!-- Subjects table -->
        <div class="reg-section">
          <h3 class="reg-section-title">Enrolled Subjects</h3>
          <table class="reg-table">
            <thead>
              <tr>
                <th>Code</th>
                <th>Subject Name</th>
                <th>Units</th>
                <th>Schedule</th>
                <th>Room</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($subjects as $sub): ?>
              <tr>
                <td class="text-mono"><?= htmlspecialchars($sub['subject_code']) ?></td>
                <td><?= htmlspecialchars($sub['subject_name']) ?></td>
                <td class="text-center"><?= number_format((float)$sub['units'], 0) ?></td>
                <td>
                  <?php if ($sub['day']): ?>
                    <?= htmlspecialchars($sub['day']) ?>
                    <?= htmlspecialchars(fmt_time($sub['time_start'])) ?>–<?= htmlspecialchars(fmt_time($sub['time_end'])) ?>
                  <?php else: ?>
                    <span class="tba-tag">TBA</span>
                  <?php endif; ?>
                </td>
                <td><?= $sub['room_name'] ? htmlspecialchars($sub['room_name']) : '<span class="tba-tag">TBA</span>' ?></td>
              </tr>
              <?php endforeach; ?>
            </tbody>
            <tfoot>
              <tr>
                <td colspan="2" class="text-end fw-bold">Total</td>
                <td class="text-center fw-bold"><?= number_format((float)$total_units, 0) ?></td>
                <td colspan="2"></td>
              </tr>
            </tfoot>
          </table>
        </div>

        <!-- Fee breakdown preview -->
        <div class="reg-section">
          <h3 class="reg-section-title">Fee Breakdown</h3>
          <?php if ($feeSchedule): ?>
          <table class="reg-table">
            <thead>
              <tr>
                <th>Fee</th>
                <th class="text-end">Amount</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($feeItems as $item): ?>
              <tr>
                <td><?= htmlspecialchars($item['label']) ?></td>
                <td class="text-end">₱<?= number_format((float)$item['amount'], 2) ?></td>
              </tr>
              <?php endforeach; ?>
            </tbody>
            <tfoot>
              <tr>
                <td class="fw-bold">Total Due</td>
                <td class="text-end fw-bold">₱<?= number_format((float)$feeSchedule['total_amount'], 2) ?></td>
              </tr>
            </tfoot>
          </table>
          <div class="alert-box alert-info mb-3">
            <iconify-icon icon="mdi:information-outline"></iconify-icon>
            This will be billed automatically once enrollment is finalized, due <?= htmlspecialchars($preview_due_date) ?>
            (<?= PAYMENT_DUE_DAYS ?> days from today). No separate treasury setup step is needed.
          </div>
          <?php else: ?>
          <div class="alert-box alert-error mb-3">
            <iconify-icon icon="mdi:alert-circle-outline"></iconify-icon>
            No fee schedule has been configured for <?= htmlspecialchars($yr_label) ?>, SY <?= htmlspecialchars($enroll['school_year']) ?> yet.
            Please contact Treasury to set one up — finalizing is disabled until then.
          </div>
          <?php endif; ?>
        </div>

        <!-- Signatures -->
        <div class="reg-section print-only">
          <div class="sig-row">
            <div class="sig-box">
              <div class="sig-line"></div>
              <span>Student Signature</span>
            </div>
            <div class="sig-box">
              <div class="sig-line"></div>
              <span>Registrar / Staff</span>
            </div>
          </div>
        </div>

      </div><!-- /.reg-form-wrap -->

      <!-- Action buttons -->
      <div class="alert-box alert-error mb-3 no-print" v-if="error">
        <iconify-icon icon="mdi:alert-circle-outline"></iconify-icon>
        {{ error }}
      </div>
      <div class="d-flex justify-content-between align-items-center mt-4 no-print">
        <a href="enrollment_subjects.php" class="btn-back-link">
          <iconify-icon icon="mdi:arrow-left"></iconify-icon> Back to Section
        </a>
        <button
          type="button"
          class="btn-primary-action"
          @click="finalize"
          :disabled="saving || <?= $feeSchedule ? 'false' : 'true' ?>">
          <iconify-icon v-if="saving" icon="mdi:loading" class="spin"></iconify-icon>
          <iconify-icon v-else icon="mdi:check-circle-outline"></iconify-icon>
          {{ saving ? 'Saving…' : 'Finalize Enrollment' }}
        </button>
      </div>

    </div>
  </div>
</div>

<script>
const ENROLLMENT_PAYLOAD = <?= json_encode([
    'student_id'  => $student['applicant_id'],
    'school_year' => $enroll['school_year'],
    'semester'    => $enroll['semester'],
    'year_level'  => $enroll['year_level'],
    'type_id'     => $enroll['type_id'],
    'section_id'  => $section_id,
    'subject_ids' => $enroll['subject_ids'],
]) ?>;
</script>
<script src="https://cdn.jsdelivr.net/npm/vue@3/dist/vue.global.prod.js"></script>
<?php include '../Admission/Include/footer.php';?>