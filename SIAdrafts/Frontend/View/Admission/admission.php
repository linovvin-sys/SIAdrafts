<?php 
$page_scripts = ['/SIAdrafts/Frontend/Js/Admission/admission.js'];
require_once '../../../Backend/auth.php';
require_once '../../../Backend/db.php';

$db   = new Database();
$conn = $db->connect();

$courses = [];
$result = $conn->query("SELECT course_id, course_code, course_name FROM course ORDER BY course_name");
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $courses[] = $row;
    }
}

include '../Admission/Include/header.php';

?>

<div class="admission-page">
 
  <div class="admission-head">
    <span class="admission-eyebrow">
      <iconify-icon icon="mdi:school"></iconify-icon>
      BS Information Technology — Walk-in Enrollment
    </span>
    <h1>New Student Application</h1>
    <p>Staff use only. Fill out every section while the applicant is at the counter, then confirm submitted documents before saving.</p>
  </div>
 
  <div class="step-rail">
    <div class="seg done"></div>
    <div class="seg active"></div>
    <div class="seg"></div>
    <div class="seg"></div>
    <div class="seg"></div>
  </div>
 
  <form id="admissionForm" action="/SIAdrafts/Backend/api/admission_process.php" method="POST" class="admission-card" autocomplete="off">
 
    <div id="formBanner" style="display:none; margin:20px 32px 0;"></div>
 
 
    <!--  1. STUDENT INFORMATION  -->
    <div class="form-section">
      <div class="section-head">
        <span class="section-num">1</span>
        <div>
          <h2>Student Information</h2>
          <p>Basic personal and contact details, taken from the applicant's valid ID.</p>
        </div>
      </div>
 
      <div class="row g-3">
        <div class="col-md-4">
          <label class="form-label">Last name</label>
          <input type="text" class="form-control" name="last_name" required>
        </div>
        <div class="col-md-4">
          <label class="form-label">First name</label>
          <input type="text" class="form-control" name="first_name" required>
        </div>
        <div class="col-md-4">
          <label class="form-label">Middle name</label>
          <input type="text" class="form-control" name="middle_name">
        </div>
 
        <div class="col-md-4">
          <label class="form-label">Date of birth</label>
          <input type="date" class="form-control" name="birth_date" required>
        </div>
        <div class="col-md-4">
          <label class="form-label">Sex</label>
          <select class="form-select" name="sex" required>
            <option value="" selected disabled>Select</option>
            <option>Male</option>
            <option>Female</option>
          </select>
        </div>
        <div class="col-md-4">
          <label class="form-label">Civil status</label>
          <select class="form-select" name="civil_status">
            <option>Single</option>
            <option>Married</option>
            <option>Other</option>
          </select>
        </div>
 
        <div class="col-md-6">
          <label class="form-label">Contact number</label>
          <input type="tel" class="form-control" name="contact_number" placeholder="09XX XXX XXXX" required>
        </div>
        <div class="col-md-6">
          <label class="form-label">Email address</label>
          <input type="email" class="form-control" name="email" placeholder="optional">
        </div>
 
        <div class="col-12">
          <label class="form-label">Complete home address</label>
          <input type="text" class="form-control" name="home_address" placeholder="House/Street, Barangay, City, Province" required>
        </div>
      </div>
    </div>
 
    <!--  2. ACADEMIC HISTORY  -->
    <div class="form-section">
      <div class="section-head">
        <span class="section-num">2</span>
        <div>
          <h2>Academic History</h2>
          <p>Most recent school attended. Add another entry if the applicant has transferred before.</p>
        </div>
      </div>
 
      <div id="historyRows">
        <div class="history-row">
          <span class="row-tag">Most recent</span>
          <div class="row g-3">
            <div class="col-md-6">
              <label class="form-label">School name</label>
              <input type="text" class="form-control" name="school_name[]" required>
            </div>
            <div class="col-md-6">
              <label class="form-label">School address</label>
              <input type="text" class="form-control" name="school_address[]" required>
            </div>
            <div class="col-md-4">
              <label class="form-label">Year graduated / last attended</label>
              <input type="text" class="form-control" name="school_year[]" placeholder="e.g. 2025" required>
            </div>
            <div class="col-md-4">
              <label class="form-label">Strand / track (if SHS)</label>
              <input type="text" class="form-control" name="school_strand[]" placeholder="e.g. STEM, TVL">
            </div>
            <div class="col-md-4">
              <label class="form-label">General average / GPA</label>
              <input type="text" class="form-control" name="school_gpa[]" placeholder="e.g. 88">
            </div>
          </div>
        </div>
      </div>
 
      <button type="button" class="btn-add-row" id="addHistoryRow">
        + Add another school
      </button>
    </div>
 
    <!--  3. GUARDIAN INFORMATION  -->
    <div class="form-section">
      <div class="section-head">
        <span class="section-num">3</span>
        <div>
          <h2>Guardian Information</h2>
          <p>For identification purposes only — copy details straight from the guardian's physical ID.</p>
        </div>
      </div>
 
      <div class="row g-3">
        <div class="col-md-6">
          <label class="form-label">Guardian full name</label>
          <input type="text" class="form-control" name="guardian_name" required>
        </div>
        <div class="col-md-3">
          <label class="form-label">Relationship to applicant</label>
          <input type="text" class="form-control" name="guardian_relationship" placeholder="e.g. Mother" required>
        </div>
        <div class="col-md-3">
          <label class="form-label">Contact number</label>
          <input type="tel" class="form-control" name="guardian_contact" required>
        </div>
 
        <div class="col-md-4">
          <label class="form-label">ID type presented</label>
          <select class="form-select" name="guardian_id_type" required>
            <option value="" selected disabled>Select</option>
            <option>Philippine National ID</option>
            <option>Driver's License</option>
            <option>UMID</option>
            <option>Passport</option>
            <option>Voter's ID</option>
            <option>Other</option>
          </select>
        </div>
        <div class="col-md-4">
          <label class="form-label">ID number</label>
          <input type="text" class="form-control" name="guardian_id_number" required>
        </div>
        <div class="col-md-4">
          <label class="form-label">ID verified by (staff ID)</label>
          <input type="text" class="form-control" name="id_verified_by" required>
        </div>
      </div>
    </div>
 
    <!--  4. PROGRAM DETAILS  -->
    <div class="form-section">
      <div class="section-head">
        <span class="section-num">4</span>
        <div>
          <h2>Program Details</h2>
          <p>Select the program the applicant is enrolling into.</p>
        </div>
      </div>
 
      <div class="row g-3">
        <div class="col-md-6">
          <label class="form-label">Program</label>
          <select class="form-select" name="course_id" required>
            <option value="" selected disabled>Select a program</option>
            <?php foreach ($courses as $c): ?>
              <option value="<?= (int)$c['course_id'] ?>">
                <?= htmlspecialchars($c['course_code']) ?> — <?= htmlspecialchars($c['course_name']) ?>
              </option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="col-md-3">
          <label class="form-label">Year level</label>
          <select class="form-select" name="year_level" required>
            <option value="" selected disabled>Select</option>
            <option value="1">1st Year</option>
            <option value="2">2nd Year (transferee)</option>
            <option value="3">3rd Year (transferee)</option>
            <option value="4">4th Year (transferee)</option>
          </select>
        </div>
        <div class="col-md-3">
          <label class="form-label">Intended start term</label>
          <input type="text" class="form-control" name="start_term" placeholder="e.g. AY 2026-2027, 1st Sem" required>
        </div>
        <div class="col-12">
          <label class="form-label">Applicant type</label>
          <select class="form-select" name="applicant_type">
            <option>Freshman</option>
            <option>Transferee</option>
            <option>Returning student</option>
          </select>
        </div>
      </div>
    </div>
 
    <!--  5. DOCUMENT CHECKLIST  -->
    <div class="form-section">
      <div class="section-head">
        <span class="section-num">5</span>
        <div>
          <h2>Document Checklist</h2>
          <p>Check off only what the applicant has physically handed over today.</p>
        </div>
      </div>
 
      <div class="doc-checklist">
        <div class="doc-item">
          <input type="checkbox" id="doc1" name="docs[]" value="Form 137 / SHS Card">
          <label for="doc1">Form 137 / SHS Report Card</label>
          <span class="req">Required</span>
        </div>
        <div class="doc-item">
          <input type="checkbox" id="doc2" name="docs[]" value="Certificate of Good Moral">
          <label for="doc2">Certificate of Good Moral</label>
          <span class="req">Required</span>
        </div>
        <div class="doc-item">
          <input type="checkbox" id="doc3" name="docs[]" value="Birth Certificate (PSA)">
          <label for="doc3">Birth Certificate (PSA)</label>
          <span class="req">Required</span>
        </div>
        <div class="doc-item">
          <input type="checkbox" id="doc4" name="docs[]" value="2x2 ID Photos">
          <label for="doc4">2x2 ID Photos (2 copies)</label>
          <span class="req">Required</span>
        </div>
        <div class="doc-item">
          <input type="checkbox" id="doc5" name="docs[]" value="Guardian Valid ID Photocopy">
          <label for="doc5">Guardian Valid ID (photocopy)</label>
        </div>
        <div class="doc-item">
          <input type="checkbox" id="doc6" name="docs[]" value="Transcript of Records">
          <label for="doc6">Transcript of Records (if transferee)</label>
        </div>
      </div>
    </div>
 
    <!--  6. ACTIONS  -->
    <div class="form-actions">
      <span class="hint">Double-check guardian ID details before saving — this record can't auto-verify them.</span>
      <div style="display:flex; gap:10px; flex-wrap:wrap;">
        <button type="button" class="btn btn-save-draft">Save as draft</button>
        <button type="submit" class="btn btn-submit">
          Submit application
          <iconify-icon icon="mdi:arrow-right"></iconify-icon>
        </button>
      </div>
    </div>
 
  </form>
</div>



<?php include '../Admission/Include/footer.php' ?>
