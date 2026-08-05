<?php
$pageTitle  = "Registration";
$activePage = "registration";

require_once __DIR__ . '/../../../Backend/require_student.php';
require_student();
require_once __DIR__ . '/../../../Backend/db.php';
require_once __DIR__ . '/../../../Backend/Student/registration_data.php';

$db   = new Database();
$conn = $db->connect();

$applicantId = (int)$_SESSION['student_id'];

$registrationData = get_registration_data($conn, $applicantId);
$applicant  = $registrationData['applicant'];
$enrollment = $registrationData['enrollment'];
$subjects   = $registrationData['subjects'];
$totalUnits = array_sum(array_column($subjects, 'units'));

$db->close();

$statusPillClass = match ($enrollment['status'] ?? '') {
    'Enrolled' => 'enrolled',
    'Pending Payment' => 'pending',
    default => 'attention',
};

include __DIR__ . '/Include/header.php';
?>

<div style="display:flex; align-items:flex-start; justify-content:space-between; gap:16px; flex-wrap:wrap;">
  <div>
    <h1 class="sp-greeting">Registration</h1>
    <p class="sp-subline">Your official record for the current term, as recorded by the Registrar's Office.</p>
  </div>
  <?php if ($enrollment): ?>
    <button type="button" class="sp-btn sp-btn-secondary sp-print-hide" onclick="window.print()">
      <iconify-icon icon="mdi:printer-outline"></iconify-icon> Print / Save as PDF
    </button>
  <?php endif; ?>
</div>

<?php if (!$enrollment): ?>
  <div class="sp-empty">
    <iconify-icon icon="mdi:file-document-outline"></iconify-icon>
    <p><strong>No registration on file.</strong></p>
    <p>Your registration summary will appear here once you've enrolled for the term.</p>
  </div>
<?php else: ?>

<div class="sp-print-letterhead">
  <h2>EduSchool — Certificate of Registration</h2>
  <p><?= htmlspecialchars($enrollment['school_year'] . ', Semester ' . $enrollment['semester'], ENT_QUOTES) ?> · Printed <?= date('F j, Y') ?></p>
</div>

<div class="sp-section">
  <h2 class="sp-section-title">Student information</h2>
  <div class="sp-field-grid">
    <div class="sp-field">
      <p class="sp-field-label">Full name</p>
      <p class="sp-field-value"><?= htmlspecialchars(trim($applicant['first_name'] . ' ' . $applicant['middle_name'] . ' ' . $applicant['last_name']), ENT_QUOTES) ?></p>
    </div>
    <div class="sp-field">
      <p class="sp-field-label">Student number</p>
      <p class="sp-field-value" style="font-family:var(--font-mono);"><?= htmlspecialchars($applicant['student_no'] ?? '—', ENT_QUOTES) ?></p>
    </div>
    <div class="sp-field">
      <p class="sp-field-label">Course &amp; year level</p>
      <p class="sp-field-value"><?= htmlspecialchars(($enrollment['course_code'] ?? $applicant['program']) . ' — Year ' . $enrollment['year_level'], ENT_QUOTES) ?></p>
    </div>
    <div class="sp-field">
      <p class="sp-field-label">Section</p>
      <p class="sp-field-value"><?= htmlspecialchars($enrollment['section_name'] ?? 'Irregular', ENT_QUOTES) ?></p>
    </div>
    <div class="sp-field">
      <p class="sp-field-label">Term</p>
      <p class="sp-field-value"><?= htmlspecialchars($enrollment['school_year'] . ', Semester ' . $enrollment['semester'], ENT_QUOTES) ?></p>
    </div>
    <div class="sp-field">
      <p class="sp-field-label">Status</p>
      <p class="sp-field-value"><span class="sp-pill <?= $statusPillClass ?>"><?= htmlspecialchars($enrollment['status'], ENT_QUOTES) ?></span></p>
    </div>
  </div>
</div>

<div class="sp-section">
  <h2 class="sp-section-title">Enrolled subjects</h2>
  <?php if (empty($subjects)): ?>
    <p style="color:var(--slate-500); font-size:14px;">No subjects on record yet for this term.</p>
  <?php else: ?>
    <table class="sp-table sp-table-stagger">
      <thead>
        <tr><th>Code</th><th>Subject</th><th style="text-align:right;">Units</th></tr>
      </thead>
      <tbody>
        <?php foreach ($subjects as $i => $s): ?>
          <tr style="--row-i: <?= $i ?>;">
            <td class="sp-num"><?= htmlspecialchars($s['subject_code'], ENT_QUOTES) ?></td>
            <td><?= htmlspecialchars($s['subject_name'], ENT_QUOTES) ?></td>
            <td class="sp-num" style="text-align:right;"><?= (float)$s['units'] ?></td>
          </tr>
        <?php endforeach; ?>
      </tbody>
      <tfoot>
        <tr>
          <td colspan="2" style="text-align:right; font-weight:600;">Total units</td>
          <td class="sp-num" style="text-align:right; font-weight:600;"><?= (float)$totalUnits ?></td>
        </tr>
      </tfoot>
    </table>
  <?php endif; ?>
</div>

<?php endif; ?>

<?php include __DIR__ . '/Include/footer.php'; ?>
