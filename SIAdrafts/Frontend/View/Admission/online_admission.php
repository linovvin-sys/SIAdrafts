<?php
/**
 * Public online admission form (page, not an API endpoint).
 *
 * No login is required to view or submit this — but header.php already
 * handles the logged-out case on its own (guest nav branch), so we include
 * the real header/footer instead of a standalone shell.
 *
 * TODO: the include path below assumes this file sits at the same folder
 * depth as treasury.php (a sibling of the Admission/ folder), since both
 * use the same '../../../Backend/...' depth to reach Backend/. Adjust if
 * this file actually lives inside Admission/ itself (path would then be
 * '../Include/header.php' instead of '../Admission/Include/header.php').
 */

require_once '../../../Backend/db.php';
require_once '../../../Backend/requirements.php';
require_once '../../../Backend/api/validation_rules.php';

$db   = new Database();
$conn = $db->connect();

// Populate the program dropdown from the course table.
$courses = [];
$courseResult = $conn->query("SELECT course_id, course_name FROM course WHERE status = 'Approved' ORDER BY course_name ASC");
if ($courseResult) {
    $courses = $courseResult->fetch_all(MYSQLI_ASSOC);
}
$conn->close();

// Optional course lock, arrived via the landing page's "Apply Now" flow.
$lockedCourseId = 0;
if (isset($_GET['course_id']) && ctype_digit((string)$_GET['course_id'])) {
    $lockedCourseId = (int)$_GET['course_id'];
    $lockedValid = false;
    foreach ($courses as $c) {
        if ((int)$c['course_id'] === $lockedCourseId) { $lockedValid = true; break; }
    }
    if (!$lockedValid) $lockedCourseId = 0;
}

$page_scripts = ['/SIAdrafts/Frontend/Js/Admission/online-admission.js'];
include '../Admission/Include/header.php';
?>

<main class="admission-page">
  <div class="admission-head">
    <span class="admission-eyebrow">
      <iconify-icon icon="mdi:file-document-edit-outline"></iconify-icon>
      Online Application
    </span>
    <h1>Start Your Application</h1>
    <p>Fill out this form to apply. After submitting, you'll get a reference number —
       bring it (printed or on your phone) along with your documents to campus to
       complete verification.</p>
  </div>

  <!-- TODO: confirm this matches the real route to online_admission_process.php
       (it lives under Backend/api/, this page lives under Frontend/.../Admission/Online/) -->
  <form id="admissionForm" action="/SIAdrafts/Backend/api/online_admission_process.php" enctype="multipart/form-data" novalidate>

    <!-- Honeypot: real applicants never see or fill this in.
         Swap in a real CAPTCHA before this goes fully public. -->
    <div style="position:absolute; left:-9999px;" aria-hidden="true">
      <label for="website">Leave this field blank</label>
      <input type="text" id="website" name="website" tabindex="-1" autocomplete="off">
    </div>

    <div style="max-width:900px;margin:0 auto 18px;padding:0 16px;">
      <div id="formBanner" class="form-banner" style="display:none;" role="alert" aria-live="polite"></div>
    </div>

    <div class="admission-card">

      <section class="form-section">
        <div class="section-head">
          <span class="section-num">1</span>
          <div>
            <h2>Personal Information</h2>
            <p>Tell us a bit about yourself.</p>
          </div>
        </div>
        <div class="row g-3">
          <div class="col-md-4">
            <label class="form-label" for="last_name">Last Name</label>
            <input type="text" class="form-control" id="last_name" name="last_name" required>
          </div>
          <div class="col-md-4">
            <label class="form-label" for="first_name">First Name</label>
            <input type="text" class="form-control" id="first_name" name="first_name" required>
          </div>
          <div class="col-md-4">
            <label class="form-label" for="middle_name">Middle Name</label>
            <input type="text" class="form-control" id="middle_name" name="middle_name" required>
          </div>
          <div class="col-md-4">
            <label class="form-label" for="birth_date">Birth Date</label>
            <input type="date" class="form-control" id="birth_date" name="birth_date" required>
          </div>
          <div class="col-md-4">
            <label class="form-label" for="sex">Sex</label>
            <select class="form-control" id="sex" name="sex" required>
              <option value="">Select</option>
              <option value="Male">Male</option>
              <option value="Female">Female</option>
            </select>
          </div>
          <div class="col-md-4">
            <label class="form-label" for="civil_status">Civil Status</label>
            <select class="form-control" id="civil_status" name="civil_status" required>
              <option value="">Select</option>
              <option value="Single">Single</option>
              <option value="Married">Married</option>
              <option value="Widowed">Widowed</option>
              <option value="Separated">Separated</option>
            </select>
          </div>
          <div class="col-md-4">
            <label class="form-label" for="nationality">Nationality</label>
            <select class="form-control" id="nationality" name="nationality" required>
              <?php foreach (NATIONALITY_OPTIONS as $nat): ?>
                <option value="<?= htmlspecialchars($nat) ?>"><?= htmlspecialchars($nat) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="col-md-6">
            <label class="form-label" for="contact_number">Contact Number</label>
            <input type="tel" class="form-control" id="contact_number" name="contact_number" placeholder="09XXXXXXXXX" required>
          </div>
          <div class="col-md-6">
            <label class="form-label" for="email">Email Address</label>
            <input type="email" class="form-control" id="email" name="email" required>
          </div>
          <div class="col-12">
            <label class="form-label" for="home_address">Home Address</label>
            <textarea class="form-control" id="home_address" name="home_address" rows="2" required></textarea>
          </div>
        </div>
      </section>

      <section class="form-section">
        <div class="section-head">
          <span class="section-num">2</span>
          <div>
            <h2>Guardian Information</h2>
            <p>Whoever we should contact on your behalf.</p>
          </div>
        </div>
        <div class="row g-3">
          <div class="col-md-6">
            <label class="form-label" for="guardian_name">Guardian's Full Name</label>
            <input type="text" class="form-control" id="guardian_name" name="guardian_name" required>
          </div>
          <div class="col-md-6">
            <label class="form-label" for="guardian_relationship">Relationship to Applicant</label>
            <select class="form-control" id="guardian_relationship" name="guardian_relationship" required>
              <option value="">Select</option>
              <?php foreach (RELATIONSHIP_OPTIONS as $rel): ?>
                <option value="<?= htmlspecialchars($rel) ?>"><?= htmlspecialchars($rel) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="col-md-4">
            <label class="form-label" for="guardian_contact">Guardian's Contact Number</label>
            <input type="tel" class="form-control" id="guardian_contact" name="guardian_contact" placeholder="09XXXXXXXXX" required>
          </div>
          <div class="col-md-4">
            <label class="form-label" for="guardian_id_type">Guardian's Valid ID Type</label>
            <select class="form-control" id="guardian_id_type" name="guardian_id_type" required>
                <option value="">Select</option>
                <option value="Philhealth ID">Philhealth ID</option>
                <option value="Driver's License">Driver's License</option>
                <option value="Passport">Passport</option>
                <option value="Philippine National ID">Philippine National ID</option>
                <option value="Voter's ID">Voter's ID</option>
                <option value="SSS ID">SSS ID</option>
                <option value="UMID">UMID</option>
                <option value="Other">Other</option>
            </select>
          </div>
          <div class="col-md-4">
            <label class="form-label" for="guardian_id_number">Guardian's ID Number</label>
            <input type="text" class="form-control" id="guardian_id_number" name="guardian_id_number" required>
          </div>
        </div>
      </section>

      <section class="form-section">
        <div class="section-head">
          <span class="section-num">3</span>
          <div>
            <h2>Program</h2>
            <p>What you'd like to take, and when you'd like to start.</p>
          </div>
        </div>
        <div class="row g-3">
          <div class="col-md-6" id="programFieldWrap">
            <label class="form-label" for="course_id">Program</label>
            <?php if ($lockedCourseId): ?>
              <?php
                $lockedName = '';
                foreach ($courses as $c) {
                    if ((int)$c['course_id'] === $lockedCourseId) { $lockedName = $c['course_name']; break; }
                }
              ?>
              <input type="text" class="form-control" value="<?= htmlspecialchars($lockedName) ?>" disabled id="programDisplay">
              <input type="hidden" name="course_id" id="course_id" value="<?= $lockedCourseId ?>">
              <button type="button" class="btn-link" id="unlockProgramBtn" style="padding:4px 0;">Not your program? Change</button>
              <template id="programOptionsTemplate"><option value="">Select a program</option><?php foreach ($courses as $course): ?><option value="<?= (int)$course['course_id'] ?>"><?= htmlspecialchars($course['course_name']) ?></option><?php endforeach; ?></template>
            <?php else: ?>
              <select class="form-control" id="course_id" name="course_id" required>
                <option value="">Select a program</option>
                <?php foreach ($courses as $course): ?>
                  <option value="<?= (int)$course['course_id'] ?>">
                    <?= htmlspecialchars($course['course_name']) ?>
                  </option>
                <?php endforeach; ?>
              </select>
            <?php endif; ?>
          </div>
          <div class="col-md-3">
            <label class="form-label" for="year_level">Year Level</label>
            <select class="form-control" id="year_level" name="year_level" required>
              <option value="">Select</option>
              <option value="1">1st Year</option>
              <option value="2">2nd Year</option>
              <option value="3">3rd Year</option>
              <option value="4">4th Year</option>
            </select>
          </div>
          <div class="col-md-3">
            <label class="form-label" for="applicant_type">Applicant Type</label>
            <select class="form-control" id="applicant_type" name="applicant_type" required>
              <option value="">Select</option>
              <option value="New">New</option>
              <option value="Transferee">Transferee</option>
              <option value="Returning">Returning</option>
            </select>
          </div>
        </div>
      </section>

      <section class="form-section">
        <div class="section-head">
          <span class="section-num">4</span>
          <div>
            <h2>Academic History</h2>
            <p>Add at least one school you've attended.</p>
          </div>
        </div>

        <div id="historyRows">
          <div class="history-row">
            <div class="row g-3">
              <div class="col-md-6">
                <label class="form-label">School name</label>
                <input type="text" class="form-control" name="school_name[]">
              </div>
              <div class="col-md-6">
                <label class="form-label">School address</label>
                <input type="text" class="form-control" name="school_address[]">
              </div>
              <div class="col-md-4">
                <label class="form-label">Year graduated / last attended</label>
                <input type="text" class="form-control" name="school_year[]">
              </div>
              <div class="col-md-4">
                <label class="form-label">Strand / track (if SHS)</label>
                <input type="text" class="form-control" name="school_strand[]">
              </div>
              <div class="col-md-4">
                <label class="form-label">General average / GPA</label>
                <input type="text" class="form-control" name="school_gpa[]">
              </div>
            </div>
          </div>
        </div>
      </section>

      <section class="form-section">
        <div class="section-head">
          <span class="section-num">5</span>
          <div>
            <h2>Requirements</h2>
            <p>Upload now if you have them ready, or mark them to bring at campus — nothing here blocks your application.</p>
          </div>
        </div>

        <?php
          $renderedGroups = [];
          foreach (REQUIREMENT_DEFINITIONS as $req):
            $groupAlreadyRendered = isset($renderedGroups[$req['group']]);
            $renderedGroups[$req['group']] = true;
        ?>
          <div class="row g-2 align-items-center requirement-row" data-group="<?= htmlspecialchars($req['group']) ?>" style="margin-bottom:10px;">
            <div class="col-md-4">
              <?php if ($groupAlreadyRendered): ?>
                <label class="form-label" style="opacity:.6;">or — <?= htmlspecialchars($req['label']) ?><span class="required">*</span></label>
              <?php else: ?>
                <label class="form-label"><?= htmlspecialchars($req['label']) ?><span class="required">*</span></label>
              <?php endif; ?>
            </div>
            <div class="col-md-4">
              <input type="file" class="form-control requirement-file" name="requirement_file[<?= htmlspecialchars($req['key']) ?>]" accept="application/pdf,image/*">
            </div>
            <div class="col-md-4">
              <label class="form-check-label" style="font-size:13px;">
                <input type="checkbox" class="requirement-later" name="requirement_status[<?= htmlspecialchars($req['key']) ?>]" value="later">
                I'll submit this at campus
              </label>
            </div>
          </div>
        <?php endforeach; ?>
      </section>

      <div class="form-actions">
        <span class="hint">Double-check your details — you'll need matching documents on campus.</span>
        <button type="submit" class="btn-submit">Submit application <iconify-icon icon="mdi:arrow-right"></iconify-icon></button>
      </div>

    </div>

  </form>

  <!-- Shown after a successful submit; hidden until then. -->
  <div style="max-width:900px;margin:18px auto 0;padding:0 16px;">
    <div id="referenceBanner" class="form-banner" style="display:none;"></div>
  </div>

</main>

<?php include '../Admission/Include/footer.php' ?>