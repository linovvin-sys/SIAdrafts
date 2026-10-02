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
require_once '../../../Backend/api/Admission/validation_rules.php';

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

$page_scripts = [
    '/SIAdrafts/Frontend/Js/Admission/ph-address-picker.js',
    '/SIAdrafts/Frontend/Js/Admission/school-autocomplete.js',
    '/SIAdrafts/Frontend/Js/Admission/online-admission.js',
];
include '../Admission/Include/header.php';
?>

<main class="admission-page">
  <div class="admission-head">
    <h1>Start Your Application</h1>
    <p>Fill out this form to apply. After submitting, you'll get a reference number —
       bring it (printed or on your phone) along with your documents to campus to
       complete verification.</p>
  </div>

  <!-- Sticky, tracks real per-section completion (not just scroll position)
       and doubles as jump navigation -- see online-admission.js. -->
  <nav class="progress-rail" aria-label="Application progress">
    <div class="progress-track" id="progressTrack">
      <button type="button" class="progress-step" data-target="section-personal">
        <span class="progress-dot"><span class="progress-dot-num">1</span><iconify-icon icon="mdi:check"></iconify-icon></span>
        <span class="progress-label">Personal</span>
      </button>
      <span class="progress-connector"></span>
      <button type="button" class="progress-step" data-target="section-guardian">
        <span class="progress-dot"><span class="progress-dot-num">2</span><iconify-icon icon="mdi:check"></iconify-icon></span>
        <span class="progress-label">Guardian</span>
      </button>
      <span class="progress-connector"></span>
      <button type="button" class="progress-step" data-target="section-program">
        <span class="progress-dot"><span class="progress-dot-num">3</span><iconify-icon icon="mdi:check"></iconify-icon></span>
        <span class="progress-label">Program</span>
      </button>
      <span class="progress-connector"></span>
      <button type="button" class="progress-step" data-target="section-history">
        <span class="progress-dot"><span class="progress-dot-num">4</span><iconify-icon icon="mdi:check"></iconify-icon></span>
        <span class="progress-label">Academic</span>
      </button>
      <span class="progress-connector"></span>
      <button type="button" class="progress-step" data-target="section-requirements">
        <span class="progress-dot"><span class="progress-dot-num">5</span><iconify-icon icon="mdi:check"></iconify-icon></span>
        <span class="progress-label">Documents</span>
      </button>
    </div>
  </nav>

  <!-- Shown after a successful submit; hidden until then. Sits above the
       form itself so the reference ID is the first thing visible, not
       something you have to scroll past a long form (now empty/reset) to
       find. -->
  <div style="max-width:900px;margin:0 auto 18px;padding:0 16px;">
    <div id="referenceBanner" class="form-banner" style="display:none;"></div>
  </div>

  <?php $recaptchaSiteKey = config('RECAPTCHA_SITE_KEY'); ?>
  <?php if ($recaptchaSiteKey): ?>
    <script src="https://www.google.com/recaptcha/api.js" async defer></script>
  <?php endif; ?>

  <!-- TODO: confirm this matches the real route to online_admission_process.php
       (it lives under Backend/api/, this page lives under Frontend/.../Admission/Online/) -->
  <form id="admissionForm" action="/SIAdrafts/Backend/api/Admission/online_admission_process.php" enctype="multipart/form-data" novalidate>

    <!-- Honeypot: real applicants never see or fill this in.
         Swap in a real CAPTCHA before this goes fully public. -->
    <div style="position:absolute; left:-9999px;" aria-hidden="true">
      <label for="website">Leave this field blank</label>
      <input type="text" id="website" name="website" tabindex="-1" autocomplete="off">
    </div>

    <div style="max-width:900px;margin:0 auto 18px;padding:0 16px;">
      <div id="draftBanner" class="form-banner draft" style="display:none;" role="status" aria-live="polite"></div>
      <div id="formBanner" class="form-banner" style="display:none;" role="alert" aria-live="polite"></div>
    </div>

    <div class="admission-card">

      <section class="form-section" id="section-personal">
        <div class="section-head">
          <span class="section-num section-num--index">01</span>
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
            <label class="form-label">Home Address</label>
            <div class="address-picker">
              <div class="row g-2">
                <div class="col-md-4">
                  <select class="form-control address-region" required></select>
                </div>
                <div class="col-md-4">
                  <select class="form-control address-province" required disabled></select>
                </div>
                <div class="col-md-4">
                  <select class="form-control address-city" required disabled></select>
                </div>
              </div>
              <input type="text" class="form-control address-detail mt-2" placeholder="Street, Barangay, House / Unit No." required>
              <input type="hidden" name="home_address" data-required>
            </div>
          </div>
        </div>
      </section>

      <section class="form-section" id="section-guardian">
        <div class="section-head">
          <span class="section-num section-num--index">02</span>
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
            <input type="text" class="form-control" id="guardian_id_number" name="guardian_id_number" required autocomplete="off">
            <div class="field-hint" id="guardianIdHint"></div>
          </div>
        </div>
      </section>

      <section class="form-section" id="section-program">
        <div class="section-head">
          <span class="section-num section-num--index">03</span>
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

      <section class="form-section" id="section-history">
        <div class="section-head">
          <span class="section-num section-num--index">04</span>
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
                <div class="school-autocomplete">
                  <input type="text" class="form-control" name="school_name[]" autocomplete="off" role="combobox" aria-expanded="false" aria-autocomplete="list" placeholder="Start typing to search…">
                  <div class="school-suggestions" role="listbox" hidden></div>
                </div>
              </div>
              <div class="col-md-6">
                <label class="form-label">School address</label>
                <div class="address-picker address-picker--compact">
                  <div class="row g-2">
                    <div class="col-12 col-md-4">
                      <select class="form-control address-region"></select>
                    </div>
                    <div class="col-12 col-md-4">
                      <select class="form-control address-province" disabled></select>
                    </div>
                    <div class="col-12 col-md-4">
                      <select class="form-control address-city" disabled></select>
                    </div>
                  </div>
                  <input type="text" class="form-control address-detail mt-2" placeholder="Street, Barangay (optional)">
                  <input type="hidden" name="school_address[]">
                </div>
              </div>
              <div class="col-md-6">
                <label class="form-label">Year graduated / last attended</label>
                <input type="text" class="form-control" name="school_year[]">
              </div>
              <div class="col-md-6">
                <label class="form-label">Strand / track (if SHS)</label>
                <input type="text" class="form-control" name="school_strand[]">
              </div>
              <!-- GPA/general average deliberately not collected here --
                   the applicant brings Form 138 (which already has it) as
                   a physical requirement, so asking for it again here was
                   just one more thing to mistype and have cross-checked
                   later for no benefit. -->
            </div>
          </div>
        </div>
      </section>

      <section class="form-section" id="section-requirements">
        <div class="section-head">
          <span class="section-num section-num--index">05</span>
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
          <div class="requirement-row" data-group="<?= htmlspecialchars($req['group']) ?>">
            <div class="requirement-label">
              <?php if ($groupAlreadyRendered): ?>
                <span class="requirement-alt">or —</span>
              <?php endif; ?>
              <label class="form-label" style="margin:0;"><?= htmlspecialchars($req['label']) ?><span class="required">*</span></label>
            </div>

            <label class="file-drop" data-key="<?= htmlspecialchars($req['key']) ?>">
              <iconify-icon icon="mdi:tray-arrow-up" class="file-drop-icon"></iconify-icon>
              <span class="file-drop-text">Choose file or drag here</span>
              <input type="file" class="form-control requirement-file" name="requirement_file[<?= htmlspecialchars($req['key']) ?>]" accept="application/pdf,image/*">
            </label>

            <div class="requirement-later-wrap">
              <label class="requirement-later-label">
                <input type="checkbox" class="requirement-later" name="requirement_status[<?= htmlspecialchars($req['key']) ?>]" value="later">
                I'll submit this at campus
              </label>
            </div>
          </div>
        <?php endforeach; ?>
      </section>

      <?php if ($recaptchaSiteKey): ?>
        <div style="margin-bottom:18px;">
          <div class="g-recaptcha" data-sitekey="<?= htmlspecialchars($recaptchaSiteKey, ENT_QUOTES) ?>"></div>
        </div>
      <?php endif; ?>

      <div class="form-actions">
        <span class="hint">Double-check your details — you'll need matching documents on campus.<br><span id="draftSaveStatus" style="opacity:0.75;"></span></span>
        <button type="submit" class="btn-submit">Submit application <iconify-icon icon="mdi:arrow-right"></iconify-icon></button>
      </div>

    </div>

  </form>

</main>

<?php include '../Admission/Include/footer.php' ?>