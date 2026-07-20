# Admission Module Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Implement the Admission module updates from the design spec: public application form changes (nationality/relationship dropdowns, optional requirement uploads, Form 138, automation), an Admission Authorization staff note, returning-student detection, an Admission staff sidebar + DataTables list, and a dynamic course showcase with direct-to-apply flow on the landing page.

**Architecture:** Backend changes follow the existing codebase's defensive-validation pattern (server re-derives/re-validates, never trusts client input) and the Foundation sub-project's RBAC pattern (`require_role()` with `Backend/roles.php` constants). The Admission staff pages move onto Foundation's shared layout (`Frontend/View/Include/*`, `Backend/nav_config.php`), same as Admin/Registrar/Head Registrar. New endpoints follow the existing `Backend/api/*.php` conventions (session check → `require_role()` → method check → validate → prepared statements).

**Tech Stack:** PHP 8.3, MySQL, Bootstrap 5 + DataTables (from Foundation), Vue 3 (existing pattern for interactive widgets like the walk-in verification tool), SweetAlert2 (existing pattern for confirmations).

## Global Constraints

- Server-side re-validates everything client-sent — never trust `course_id`, `applicant_type`, or file uploads' claimed type without re-checking (matches `save_enrollment.php`'s existing pattern).
- No requirement upload blocks form submission — every requirement row is optional (file or "submit at campus" checkbox), enforced both client- and server-side.
- Returning-student matches are flagged for staff confirmation only — never auto-applied.
- Semester/School Year on new applications comes from `get_setting('current_school_year')` / `get_setting('current_semester')` (Foundation), not free-text entry.
- New/modified endpoints use `require_role()` with `Backend/roles.php` constants — no raw string role literals.
- Nationality options: Filipino (default/first), American, Chinese, Korean, Japanese, Indian, Other. Relationship options: Parent, Mother, Father, Guardian, Sibling, Grandparent, Other.
- Treasury pages (`treasury.php`, `revenue_paid.php`, `revenue_process.php`) are out of scope — do not migrate them to the shared layout in this plan.

---

## Task 1: Schema migration + shared requirements config

**Files:**
- Create: `SIAdrafts/Backend/migrations/2026_07_20_admission_module_schema.sql`
- Create: `SIAdrafts/Backend/requirements.php`
- Create: `SIAdrafts/Backend/uploads/requirements/.gitkeep`
- Modify: `SIAdrafts/.gitignore`

**Interfaces:**
- Produces: `REQUIREMENT_DEFINITIONS` (array constant in `requirements.php`) — each entry `['key' => ..., 'label' => ..., 'group' => ...]`, where entries sharing the same `group` value are alternatives satisfying one requirement (PSA/NSO birth certificate).

- [ ] **Step 1: Create the migration SQL**

```sql
ALTER TABLE applicants
  ADD COLUMN nationality VARCHAR(50) NOT NULL DEFAULT 'Filipino' AFTER sex,
  ADD COLUMN school_year VARCHAR(9) NULL AFTER applicant_type,
  ADD COLUMN semester TINYINT NULL AFTER school_year,
  ADD COLUMN possible_duplicate_student_id INT NULL AFTER admission_status,
  ADD COLUMN duplicate_match_status ENUM('none','pending_review','confirmed','dismissed') NOT NULL DEFAULT 'none' AFTER possible_duplicate_student_id,
  ADD COLUMN authorization_note TEXT NULL AFTER duplicate_match_status,
  ADD COLUMN authorized_by INT NULL AFTER authorization_note,
  ADD COLUMN authorized_at TIMESTAMP NULL AFTER authorized_by,
  ADD COLUMN cleared_by INT NULL AFTER authorized_at,
  ADD COLUMN cleared_at TIMESTAMP NULL AFTER cleared_by,
  ADD CONSTRAINT fk_applicants_dup_student FOREIGN KEY (possible_duplicate_student_id) REFERENCES student(student_id),
  ADD CONSTRAINT fk_applicants_authorized_by FOREIGN KEY (authorized_by) REFERENCES users(user_id),
  ADD CONSTRAINT fk_applicants_cleared_by FOREIGN KEY (cleared_by) REFERENCES users(user_id);

ALTER TABLE applicant_documents
  ADD COLUMN file_path VARCHAR(255) NULL AFTER document_name,
  ADD COLUMN source ENUM('applicant','staff') NOT NULL DEFAULT 'staff' AFTER file_path;
```

- [ ] **Step 2: Apply the migration**

Run: `/Applications/MAMP/Library/bin/mysql80/bin/mysql -h 127.0.0.1 -P 8889 -u root -proot enrollment_db_sia_final < /Applications/MAMP/htdocs/SIAdrafts/Backend/migrations/2026_07_20_admission_module_schema.sql`
Expected: no errors. Verify with `DESCRIBE applicants;` and `DESCRIBE applicant_documents;` showing the new columns.

- [ ] **Step 3: Create `Backend/requirements.php`**

```php
<?php
/**
 * Requirement definitions for the online application's optional
 * requirements section and the walk-in confirm_admission.php checklist.
 * Entries sharing the same 'group' are alternatives — satisfying either
 * one satisfies the group's requirement (e.g. PSA or NSO birth certificate).
 */
const REQUIREMENT_DEFINITIONS = [
    ['key' => 'form_137',    'label' => 'Form 137 / SHS Card',              'group' => 'form_137'],
    ['key' => 'good_moral',  'label' => 'Certificate of Good Moral',        'group' => 'good_moral'],
    ['key' => 'birth_psa',   'label' => 'Birth Certificate (PSA)',          'group' => 'birth_cert'],
    ['key' => 'birth_nso',   'label' => 'Birth Certificate (NSO)',          'group' => 'birth_cert'],
    ['key' => 'photo_2x2',   'label' => '2x2 ID Photos',                    'group' => 'photo_2x2'],
    ['key' => 'form_138',    'label' => 'Form 138',                         'group' => 'form_138'],
];

/** Distinct requirement groups — one entry per group is enough to satisfy it. */
function requirement_groups(): array
{
    $groups = [];
    foreach (REQUIREMENT_DEFINITIONS as $def) {
        $groups[$def['group']] = true;
    }
    return array_keys($groups);
}

/** Given a list of submitted requirement keys, return which groups are still missing. */
function missing_requirement_groups(array $submittedKeys): array
{
    $submittedGroups = [];
    foreach (REQUIREMENT_DEFINITIONS as $def) {
        if (in_array($def['key'], $submittedKeys, true)) {
            $submittedGroups[$def['group']] = true;
        }
    }
    return array_values(array_diff(requirement_groups(), array_keys($submittedGroups)));
}
```

- [ ] **Step 4: Create the upload directory placeholder**

Create `SIAdrafts/Backend/uploads/requirements/.gitkeep` (empty file), matching the existing `Backend/uploads/messages/.gitkeep` pattern.

- [ ] **Step 5: Update `.gitignore`**

Add to `/Applications/MAMP/htdocs/.gitignore`:
```
SIAdrafts/Backend/uploads/requirements/*
!SIAdrafts/Backend/uploads/requirements/.gitkeep
```

- [ ] **Step 6: Verify**

Run: `/Applications/MAMP/bin/php/php8.3.30/bin/php -l /Applications/MAMP/htdocs/SIAdrafts/Backend/requirements.php`
Run: `/Applications/MAMP/bin/php/php8.3.30/bin/php -r "require '/Applications/MAMP/htdocs/SIAdrafts/Backend/requirements.php'; print_r(missing_requirement_groups(['form_137','birth_psa']));"`
Expected: array showing `good_moral`, `photo_2x2`, `form_138` as missing (birth_cert satisfied by birth_psa).

- [ ] **Step 7: Commit**

```bash
git add SIAdrafts/Backend/migrations/2026_07_20_admission_module_schema.sql SIAdrafts/Backend/requirements.php SIAdrafts/Backend/uploads/requirements/.gitkeep .gitignore
git commit -m "Add Admission module schema migration and shared requirements config"
```

---

## Task 2: Validation rules for nationality and relationship

**Files:**
- Modify: `SIAdrafts/Backend/api/validation_rules.php`

**Interfaces:**
- Produces: `NATIONALITY_OPTIONS`, `RELATIONSHIP_OPTIONS` (array constants), `validate_nationality(string $value): ?string`, `validate_relationship(string $value): ?string`

- [ ] **Step 1: Add constants and validators to `validation_rules.php`**

Add near the top of the file, after the `<?php` line:

```php
const NATIONALITY_OPTIONS = ['Filipino', 'American', 'Chinese', 'Korean', 'Japanese', 'Indian', 'Other'];
const RELATIONSHIP_OPTIONS = ['Parent', 'Mother', 'Father', 'Guardian', 'Sibling', 'Grandparent', 'Other'];

function validate_nationality($value) {
    if ($value === '') return null;
    if (!in_array($value, NATIONALITY_OPTIONS, true)) {
        return 'Nationality must be one of the listed options.';
    }
    return null;
}

function validate_relationship($value) {
    if ($value === '') return null;
    if (!in_array($value, RELATIONSHIP_OPTIONS, true)) {
        return "Guardian's relationship must be one of the listed options.";
    }
    return null;
}
```

- [ ] **Step 2: Register both in `get_field_validator()`'s `$map` array**

In the existing `$map = [...]` array inside `get_field_validator()`, add two entries:

```php
        'nationality'           => fn($v) => validate_nationality($v),
        'guardian_relationship' => fn($v) => validate_relationship($v),
```

(This replaces `guardian_relationship`'s previous behavior of having no dedicated validator — it was previously unvalidated free text.)

- [ ] **Step 3: Verify**

Run: `/Applications/MAMP/bin/php/php8.3.30/bin/php -l /Applications/MAMP/htdocs/SIAdrafts/Backend/api/validation_rules.php`
Run: `/Applications/MAMP/bin/php/php8.3.30/bin/php -r "require '/Applications/MAMP/htdocs/SIAdrafts/Backend/api/validation_rules.php'; var_dump(validate_relationship('Parent')); var_dump(validate_relationship('Bestie'));"`
Expected: `NULL` then a string error.

- [ ] **Step 4: Commit**

```bash
git add SIAdrafts/Backend/api/validation_rules.php
git commit -m "Add nationality and guardian-relationship dropdown validators"
```

---

## Task 3: Online application form — dropdowns, requirements section, program lock

**Files:**
- Modify: `SIAdrafts/Frontend/View/Admission/online_admission.php`

**Interfaces:**
- Consumes: `REQUIREMENT_DEFINITIONS` (Task 1), `NATIONALITY_OPTIONS`/`RELATIONSHIP_OPTIONS` (Task 2).
- Produces: form fields `nationality`, `guardian_relationship` (now a `<select>`), `requirement_status[<key>]` (`'file'` or `'later'`), `requirement_file[<key>]` (file input, only meaningful when status is `'file'`); query param `?course_id=` read server-side to pre-select/lock the Program field.

- [ ] **Step 1: Add requires for the new constants at the top of the file**

After the existing `require_once '../../../Backend/db.php';` line, add:

```php
require_once '../../../Backend/requirements.php';
require_once '../../../Backend/api/validation_rules.php';
```

- [ ] **Step 2: Read the `course_id` query param for the program lock**

After the existing `$courses = ...` query block, add:

```php
$lockedCourseId = 0;
if (isset($_GET['course_id']) && ctype_digit((string)$_GET['course_id'])) {
    $lockedCourseId = (int)$_GET['course_id'];
    $lockedValid = false;
    foreach ($courses as $c) {
        if ((int)$c['course_id'] === $lockedCourseId) { $lockedValid = true; break; }
    }
    if (!$lockedValid) $lockedCourseId = 0;
}
```

- [ ] **Step 3: Add the Nationality field to the Personal Information section**

In the "Personal Information" `<section>` (section-num "1"), immediately after the `civil_status` `<div class="col-md-4">` block, insert:

```php
          <div class="col-md-4">
            <label class="form-label" for="nationality">Nationality</label>
            <select class="form-control" id="nationality" name="nationality" required>
              <?php foreach (NATIONALITY_OPTIONS as $nat): ?>
                <option value="<?= htmlspecialchars($nat) ?>"><?= htmlspecialchars($nat) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
```

- [ ] **Step 4: Convert the Relationship field to a dropdown**

Replace:
```php
          <div class="col-md-6">
            <label class="form-label" for="guardian_relationship">Relationship to Applicant</label>
            <input type="text" class="form-control" id="guardian_relationship" name="guardian_relationship" required>
          </div>
```
with:
```php
          <div class="col-md-6">
            <label class="form-label" for="guardian_relationship">Relationship to Applicant</label>
            <select class="form-control" id="guardian_relationship" name="guardian_relationship" required>
              <option value="">Select</option>
              <?php foreach (RELATIONSHIP_OPTIONS as $rel): ?>
                <option value="<?= htmlspecialchars($rel) ?>"><?= htmlspecialchars($rel) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
```

- [ ] **Step 5: Lock the Program field and remove Preferred Start Term**

Replace the "Program" section's `course_id` select block and remove the `start_term` input entirely:

```php
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
```

Remove the entire `<div class="col-md-6">...Preferred Start Term...</div>` block that followed the old `course_id` select.

- [ ] **Step 6: Add the Requirements section**

Insert a new `<section class="form-section">` between the existing "Academic History" section (section-num "4") and the `<div class="form-actions">` block:

```php
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
                <label class="form-label" style="opacity:.6;">or — <?= htmlspecialchars($req['label']) ?></label>
              <?php else: ?>
                <label class="form-label"><?= htmlspecialchars($req['label']) ?></label>
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
```

- [ ] **Step 7: Verify**

Run: `/Applications/MAMP/bin/php/php8.3.30/bin/php -l /Applications/MAMP/htdocs/SIAdrafts/Frontend/View/Admission/online_admission.php`

- [ ] **Step 8: Commit**

```bash
git add SIAdrafts/Frontend/View/Admission/online_admission.php
git commit -m "Add nationality/relationship dropdowns, requirements section, and program lock to online application form"
```

---

## Task 4: Online application form JS — auto-suggest, program unlock, requirement toggles

**Files:**
- Modify: `SIAdrafts/Frontend/Js/Admission/online-admission.js`

**Interfaces:**
- Consumes: DOM elements added in Task 3 (`#unlockProgramBtn`, `#programFieldWrap`, `.requirement-file`, `.requirement-later`, `#applicant_type` — already exists).

- [ ] **Step 1: Add the program-unlock handler**

Near the top of the file (after the existing `addHistoryRow` listener block), add:

```javascript
const unlockBtn = document.getElementById('unlockProgramBtn');
if (unlockBtn) {
  unlockBtn.addEventListener('click', function () {
    const wrap = document.getElementById('programFieldWrap');
    const hidden = document.getElementById('course_id');
    const select = document.createElement('select');
    select.className = 'form-control';
    select.id = 'course_id';
    select.name = 'course_id';
    select.required = true;
    select.innerHTML = document.getElementById('programDisplay').dataset.options || '<option value="">Select a program</option>';
    wrap.innerHTML = '<label class="form-label" for="course_id">Program</label>';
    wrap.appendChild(select);
  });
}
```

- [ ] **Step 2: Add per-row requirement file/later toggle**

After the program-unlock block, add:

```javascript
document.querySelectorAll('.requirement-row').forEach(function (row) {
  const fileInput = row.querySelector('.requirement-file');
  const laterCheckbox = row.querySelector('.requirement-later');
  if (!fileInput || !laterCheckbox) return;

  laterCheckbox.addEventListener('change', function () {
    fileInput.disabled = laterCheckbox.checked;
    if (laterCheckbox.checked) fileInput.value = '';
  });
  fileInput.addEventListener('change', function () {
    if (fileInput.files.length > 0) {
      laterCheckbox.checked = false;
    }
  });
});
```

- [ ] **Step 3: Add applicant-type auto-suggest from academic history**

After the requirement-toggle block, add:

```javascript
function suggestApplicantType() {
  const typeSelect = document.getElementById('applicant_type');
  if (!typeSelect || typeSelect.dataset.userChanged === 'true') return;
  const hasHistory = Array.from(document.querySelectorAll('input[name="school_name[]"]'))
    .some(function (input) { return input.value.trim() !== ''; });
  typeSelect.value = hasHistory ? 'Transferee' : 'New';
}

document.getElementById('applicant_type').addEventListener('change', function () {
  this.dataset.userChanged = 'true';
});

document.getElementById('historyRows').addEventListener('input', function (e) {
  if (e.target.name === 'school_name[]') suggestApplicantType();
});
```

- [ ] **Step 4: Verify (manual code trace, no automated JS test runner in this codebase)**

Read the modified file top to bottom to confirm no duplicate `const`/function name collisions with the existing `submitApplication`/`showBanner` code below, and that all three new blocks reference only elements that exist per Task 3's markup.

- [ ] **Step 5: Commit**

```bash
git add SIAdrafts/Frontend/Js/Admission/online-admission.js
git commit -m "Add program-unlock, requirement-toggle, and applicant-type auto-suggest JS to online application form"
```

---

## Task 5: Online admission processing — nationality, requirements upload, returning-student match

**Files:**
- Modify: `SIAdrafts/Backend/api/online_admission_process.php`

**Interfaces:**
- Consumes: `NATIONALITY_OPTIONS`/`validate_nationality`/`validate_relationship` (Task 2), `REQUIREMENT_DEFINITIONS`/`missing_requirement_groups` (Task 1), `get_setting()` (Foundation).
- Produces: inserts `nationality`, `school_year`, `semester`, `possible_duplicate_student_id`, `duplicate_match_status` into `applicants`; inserts `applicant_documents` rows with `source = 'applicant'`.

- [ ] **Step 1: Add requires**

After the existing `require 'validation_rules.php';` line, add:

```php
require '../requirements.php';
require '../settings.php';
```

- [ ] **Step 2: Collect the new fields**

In the `$fields = [...]` array, add `'nationality' => clean($_POST['nationality'] ?? ''),` after the `sex` entry, and remove the `'start_term' => clean($_POST['start_term'] ?? ''),` line entirely (superseded by Step 5 below).

- [ ] **Step 3: Validate nationality alongside the existing checks**

In the `$check = [...]` array, add:

```php
    validate_nationality($fields['nationality']),
    validate_relationship($fields['guardian_relationship']),
```

- [ ] **Step 4: Handle requirement uploads and statuses**

After the academic-history collection block (`$history = [...]` loop), add:

```php
//  requirements: each is either an uploaded file or "submit at campus" —
//  never required, never blocks submission.
$requirementRows = []; // ['key' => ..., 'status' => 'Submitted Online'|'Will Submit Later', 'file_path' => ...|null]
$uploadDir = __DIR__ . '/../uploads/requirements/';

foreach (REQUIREMENT_DEFINITIONS as $req) {
    $key = $req['key'];
    $later = ($_POST['requirement_status'][$key] ?? '') === 'later';
    $fileError = null;

    if ($later) {
        $requirementRows[] = ['key' => $key, 'status' => 'Will Submit Later', 'file_path' => null];
        continue;
    }

    if (!empty($_FILES['requirement_file']['name'][$key])) {
        $tmpPath  = $_FILES['requirement_file']['tmp_name'][$key];
        $errCode  = $_FILES['requirement_file']['error'][$key];
        $origName = $_FILES['requirement_file']['name'][$key];

        if ($errCode !== UPLOAD_ERR_OK) {
            $fileError = 'Upload failed for ' . $req['label'] . '.';
        } else {
            $mime = mime_content_type($tmpPath);
            $allowedMimes = ['application/pdf', 'image/jpeg', 'image/png'];
            if (!in_array($mime, $allowedMimes, true)) {
                $fileError = $req['label'] . ' must be a PDF, JPG, or PNG file.';
            } elseif (filesize($tmpPath) > 5 * 1024 * 1024) {
                $fileError = $req['label'] . ' file is too large (max 5MB).';
            } else {
                $ext = pathinfo($origName, PATHINFO_EXTENSION);
                $storedName = $key . '_' . bin2hex(random_bytes(8)) . '.' . $ext;
                if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);
                if (move_uploaded_file($tmpPath, $uploadDir . $storedName)) {
                    $requirementRows[] = ['key' => $key, 'status' => 'Submitted Online', 'file_path' => 'requirements/' . $storedName];
                } else {
                    $fileError = 'Could not save the uploaded file for ' . $req['label'] . '.';
                }
            }
        }

        if ($fileError !== null) {
            $errors[] = $fileError;
        }
    }
    // else: no file, not marked "later" — simply not recorded; not an error.
}
```

- [ ] **Step 5: Replace start_term with settings-derived school year/semester**

Immediately before the `$errors` check block (`if (!empty($errors)) { ... exit; }` after the history/requirements validation), add:

```php
$fields['school_year'] = get_setting('current_school_year') ?? '';
$fields['semester']    = (int)(get_setting('current_semester') ?? 1);
```

- [ ] **Step 6: Returning-student match query**

Immediately after `$fields['school_year']`/`$fields['semester']` are set (Step 5), add:

```php
$possible_duplicate_student_id = null;
$duplicate_match_status = 'none';

$dupStmt = $conn->prepare("
    SELECT student_id FROM student
    WHERE last_name = ? AND first_name = ? AND birth_date = ?
    LIMIT 1
");
$dupStmt->bind_param('sss', $fields['last_name'], $fields['first_name'], $fields['birth_date']);
$dupStmt->execute();
$dupRow = $dupStmt->get_result()->fetch_assoc();
$dupStmt->close();

if ($dupRow) {
    $possible_duplicate_student_id = (int)$dupRow['student_id'];
    $duplicate_match_status = 'pending_review';
}
```

- [ ] **Step 7: Update the INSERT statement**

Replace the `INSERT INTO applicants (...)` statement (columns list, placeholders, and `bind_param` call) to include the new columns. The column list becomes:

```php
    $stmt = $conn->prepare("
        INSERT INTO applicants
            (reference_id, last_name, first_name, middle_name, birth_date, sex, nationality, civil_status,
            contact_number, email, home_address,
            guardian_name, guardian_relationship, guardian_contact,
            guardian_id_type, guardian_id_number, id_verified_by, admission_status,
            program, course_id, year_level, school_year, semester, applicant_type,
            possible_duplicate_student_id, duplicate_match_status, created_at)
        VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,NULL,'pending_verification',?,?,?,?,?,?,?,?, NOW())
    ");
```

and the `bind_param` call becomes:

```php
    $stmt->bind_param(
        'ssssssssssssssssisisisis',
        $reference_id,
        $fields['last_name'], $fields['first_name'], $fields['middle_name'],
        $fields['birth_date'], $fields['sex'], $fields['nationality'], $fields['civil_status'],
        $fields['contact_number'], $fields['email'], $fields['home_address'],
        $fields['guardian_name'], $fields['guardian_relationship'], $fields['guardian_contact'],
        $fields['guardian_id_type'], $fields['guardian_id_number'],
        $fields['program'], $fields['course_id'], $fields['year_level'], $fields['school_year'], $fields['semester'],
        $fields['applicant_type'], $possible_duplicate_student_id, $duplicate_match_status
    );
```

(Count the type-string characters against the bind list carefully: `s`(ref) `s s s s s s s`(last,first,middle,birth,sex,nat,civil) `s s s`(contact,email,addr) `s s s`(gname,grel,gcontact) `s s`(gidtype,gidnum) `s i s s i`(program,course_id,year_level,school_year,semester) `s i s`(applicant_type,possible_dup,dup_status) — the literal string in the code above (`'ssssssssssssssssisisisis'`) must match this exact sequence; when writing the file, count each placeholder against each bound variable one at a time rather than trusting the string as transcribed here, since a single wrong character causes a silent mysqli type-juggling bug, not a syntax error.)

- [ ] **Step 8: Insert `applicant_documents` rows for submitted requirements**

After the existing `applicant_school_history` insert loop (`$histStmt` block) and before `$conn->close();`, add:

```php
if (!empty($requirementRows)) {
    $reqStmt = $conn->prepare("
        INSERT INTO applicant_documents (applicant_id, document_name, status, file_path, source)
        VALUES (?, ?, ?, ?, 'applicant')
    ");
    foreach ($requirementRows as $row) {
        $label = '';
        foreach (REQUIREMENT_DEFINITIONS as $def) {
            if ($def['key'] === $row['key']) { $label = $def['label']; break; }
        }
        $reqStmt->bind_param('isss', $applicant_id, $label, $row['status'], $row['file_path']);
        $reqStmt->execute();
    }
    $reqStmt->close();
}
```

- [ ] **Step 9: Update the success response's `summary`**

Replace `'start_term' => $fields['start_term'],` in the final `echo json_encode([...])` block with `'school_year' => $fields['school_year'] . ' — Semester ' . $fields['semester'],`.

- [ ] **Step 10: Verify**

Run: `/Applications/MAMP/bin/php/php8.3.30/bin/php -l /Applications/MAMP/htdocs/SIAdrafts/Backend/api/online_admission_process.php`

Manually trace the `bind_param` type-string against the variable list from Step 7 — write out each character and its corresponding variable in the report to prove the count matches (18 columns before `applicant_type`... recount precisely against the actual column list written into the file, not the illustrative count in this task text).

- [ ] **Step 11: Commit**

```bash
git add SIAdrafts/Backend/api/online_admission_process.php
git commit -m "Add nationality, requirement uploads, and returning-student detection to online admission processing"
```

---

## Task 6: Update reference-slip JS for school_year (replaces start_term)

**Files:**
- Modify: `SIAdrafts/Frontend/Js/Admission/online-admission.js`

**Interfaces:**
- Consumes: `data.summary.school_year` (Task 5's renamed response field, replacing `data.summary.start_term`).

- [ ] **Step 1: Update `renderReferenceSlip`**

Replace:
```javascript
      '<p>Intended start: ' + escapeHtml(summary.start_term) + '</p>' +
```
with:
```javascript
      '<p>Term: ' + escapeHtml(summary.school_year) + '</p>' +
```

- [ ] **Step 2: Verify**

Read the file to confirm no other reference to `summary.start_term` remains.

- [ ] **Step 3: Commit**

```bash
git add SIAdrafts/Frontend/Js/Admission/online-admission.js
git commit -m "Update reference slip to show auto-assigned school year instead of free-text start term"
```

---

## Task 7: Get-applicant endpoint — align with renamed/new columns

**Files:**
- Modify: `SIAdrafts/Backend/api/get_applicant.php`

**Interfaces:**
- Produces: applicant record response now includes `nationality`, `school_year`, `semester`, `possible_duplicate_student_id`, `duplicate_match_status`, `authorization_note` instead of `start_term`.

- [ ] **Step 1: Read the current SELECT list**

Read `SIAdrafts/Backend/api/get_applicant.php` around line 34 to see the exact current column list and surrounding query structure before editing (its exact formatting wasn't captured verbatim in this plan — confirm the surrounding code, e.g. whether it's a single-line or multi-line SELECT, table aliases used, etc., before making the edit).

- [ ] **Step 2: Replace `start_term` with the new columns in the SELECT list**

Change the column list from including `start_term` to including `nationality, school_year, semester, possible_duplicate_student_id, duplicate_match_status, authorization_note` in its place (keep every other selected column as-is).

- [ ] **Step 3: Verify**

Run: `/Applications/MAMP/bin/php/php8.3.30/bin/php -l /Applications/MAMP/htdocs/SIAdrafts/Backend/api/get_applicant.php`

- [ ] **Step 4: Commit**

```bash
git add SIAdrafts/Backend/api/get_applicant.php
git commit -m "Update get_applicant.php to return new Admission-module applicant fields"
```

---

## Task 8: Walk-in verification — Form 138/PSA-NSO alternatives, Admission Authorization, duplicate-match review

**Files:**
- Modify: `SIAdrafts/Frontend/Js/Admission/admission-confirm.js`
- Modify: `SIAdrafts/Backend/api/confirm_admission.php`
- Modify: `SIAdrafts/Frontend/View/Admission/admission.php` (this is the walk-in tool at this point in the plan — renamed to `admission_confirm.php` in Task 9; edit it at its current path here, since Task 9's rename is a separate mechanical step)
- Create: `SIAdrafts/Backend/api/update_admission_authorization.php`
- Create: `SIAdrafts/Backend/api/update_duplicate_match.php`

**Interfaces:**
- Consumes: `REQUIREMENT_DEFINITIONS`/`missing_requirement_groups` (Task 1), `ROLE_ADMISSION`/`ROLE_ADMIN` (existing `Backend/roles.php`).
- Produces: two new endpoints, both POST, both `require_role([ROLE_ADMISSION, ROLE_ADMIN], true)`.

- [ ] **Step 1: Update `admission-confirm.js`'s hardcoded `requiredDocs` list**

Replace:
```javascript
      requiredDocs: [
        'Form 137 / SHS Card',
        'Certificate of Good Moral',
        'Birth Certificate (PSA)',
        '2x2 ID Photos',
      ],
```
with:
```javascript
      requiredDocs: [
        'Form 137 / SHS Card',
        'Certificate of Good Moral',
        'Birth Certificate (PSA)',
        'Birth Certificate (NSO)',
        '2x2 ID Photos',
        'Form 138',
      ],
      requiredGroups: {
        'Form 137 / SHS Card': 'form_137',
        'Certificate of Good Moral': 'good_moral',
        'Birth Certificate (PSA)': 'birth_cert',
        'Birth Certificate (NSO)': 'birth_cert',
        '2x2 ID Photos': 'photo_2x2',
        'Form 138': 'form_138',
      },
```

- [ ] **Step 2: Update the `missing` computation to be group-aware**

Find the existing `const missing = this.requiredDocs.filter(d => !this.checkedDocs.includes(d));` line and replace it with:

```javascript
        const checkedGroups = this.checkedDocs.map(d => this.requiredGroups[d]);
        const allGroups = [...new Set(Object.values(this.requiredGroups))];
        const missingGroups = allGroups.filter(g => !checkedGroups.includes(g));
        const missing = missingGroups.map(g => {
          // show the first label belonging to this group for the error message
          return Object.keys(this.requiredGroups).find(k => this.requiredGroups[k] === g);
        });
```

(Read the surrounding function body first to confirm `missing` is used identically afterward — e.g. joined into an error message — and that this replacement doesn't change that usage's shape, since `missing` remains an array of label strings either way.)

- [ ] **Step 3: Update `confirm_admission.php`'s required-docs list to match**

Replace:
```php
$required_docs = [
    'Form 137 / SHS Card',
    'Certificate of Good Moral',
    'Birth Certificate (PSA)',
    '2x2 ID Photos',
];
$missing_required = array_diff($required_docs, $docs_submitted);
```
with:
```php
require '../requirements.php';

$submittedKeys = [];
$labelToKey = [];
foreach (REQUIREMENT_DEFINITIONS as $def) {
    $labelToKey[$def['label']] = $def['key'];
}
foreach ($docs_submitted as $label) {
    if (isset($labelToKey[$label])) $submittedKeys[] = $labelToKey[$label];
}
$missingGroups = missing_requirement_groups($submittedKeys);
$missing_required = $missingGroups; // group keys, used only for the error message below
```

And update the error message line that reads `implode(', ', $missing_required)` to still work sensibly — since `$missing_required` is now group keys (e.g. `birth_cert`) rather than full labels, change that line to map back to labels:

```php
if (!empty($missing_required)) {
    $missingLabels = [];
    foreach ($missing_required as $groupKey) {
        foreach (REQUIREMENT_DEFINITIONS as $def) {
            if ($def['group'] === $groupKey) { $missingLabels[] = $def['label']; break; }
        }
    }
    $errors[] = 'Missing required documents: ' . implode(', ', $missingLabels) . '.';
}
```

- [ ] **Step 4: Add `file_path`/`source` to the `applicant_documents` INSERT in `confirm_admission.php`**

The existing insert:
```php
$docStmt = $conn->prepare("
    INSERT INTO applicant_documents (applicant_id, document_name, status, verified_by)
    VALUES (?, ?, 'submitted', ?)
");
```
stays as-is (walk-in verification doesn't upload files — `file_path` stays NULL and `source` defaults to `'staff'` per the Task 1 migration's column default, so no change needed here beyond confirming the default is correct).

- [ ] **Step 5: Create `Backend/api/update_admission_authorization.php`**

```php
<?php
session_start();
require '../db.php';
require_once '../roles.php';
require_once '../require_role.php';
require_once '../csrf.php';

header('Content-Type: application/json');

if (empty($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'errors' => ['Unauthorized.']]);
    exit;
}

require_role([ROLE_ADMISSION, ROLE_ADMIN], true);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'errors' => ['Invalid request method.']]);
    exit;
}

csrf_verify();

$applicant_id = (int)($_POST['applicant_id'] ?? 0);
$action       = $_POST['action'] ?? '';

if ($applicant_id <= 0 || !in_array($action, ['set', 'clear'], true)) {
    http_response_code(422);
    echo json_encode(['success' => false, 'errors' => ['Invalid request.']]);
    exit;
}

$db   = new Database();
$conn = $db->connect();

if ($action === 'set') {
    $note = trim($_POST['note'] ?? '');
    if ($note === '') {
        echo json_encode(['success' => false, 'errors' => ['Authorization note cannot be empty.']]);
        exit;
    }
    $stmt = $conn->prepare("
        UPDATE applicants
        SET authorization_note = ?, authorized_by = ?, authorized_at = NOW(), cleared_by = NULL, cleared_at = NULL
        WHERE applicant_id = ?
    ");
    $stmt->bind_param('sii', $note, $_SESSION['user_id'], $applicant_id);
} else {
    $stmt = $conn->prepare("
        UPDATE applicants
        SET cleared_by = ?, cleared_at = NOW()
        WHERE applicant_id = ?
    ");
    $stmt->bind_param('ii', $_SESSION['user_id'], $applicant_id);
}

if (!$stmt->execute()) {
    http_response_code(500);
    echo json_encode(['success' => false, 'errors' => ['Database error: ' . $stmt->error]]);
    exit;
}
$stmt->close();
$db->close();

echo json_encode(['success' => true]);
```

- [ ] **Step 6: Create `Backend/api/update_duplicate_match.php`**

```php
<?php
session_start();
require '../db.php';
require_once '../roles.php';
require_once '../require_role.php';
require_once '../csrf.php';

header('Content-Type: application/json');

if (empty($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'errors' => ['Unauthorized.']]);
    exit;
}

require_role([ROLE_ADMISSION, ROLE_ADMIN], true);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'errors' => ['Invalid request method.']]);
    exit;
}

csrf_verify();

$applicant_id = (int)($_POST['applicant_id'] ?? 0);
$action       = $_POST['action'] ?? '';

if ($applicant_id <= 0 || !in_array($action, ['confirm', 'dismiss'], true)) {
    http_response_code(422);
    echo json_encode(['success' => false, 'errors' => ['Invalid request.']]);
    exit;
}

$newStatus = $action === 'confirm' ? 'confirmed' : 'dismissed';

$db   = new Database();
$conn = $db->connect();

$stmt = $conn->prepare("
    UPDATE applicants
    SET duplicate_match_status = ?
    WHERE applicant_id = ? AND duplicate_match_status = 'pending_review'
");
$stmt->bind_param('si', $newStatus, $applicant_id);

if (!$stmt->execute()) {
    http_response_code(500);
    echo json_encode(['success' => false, 'errors' => ['Database error: ' . $stmt->error]]);
    exit;
}

if ($stmt->affected_rows === 0) {
    $stmt->close();
    $db->close();
    http_response_code(409);
    echo json_encode(['success' => false, 'errors' => ['This match was already reviewed.']]);
    exit;
}

$stmt->close();
$db->close();

echo json_encode(['success' => true]);
```

- [ ] **Step 7: Add the Admission Authorization UI and duplicate-match banner to `admission.php` (walk-in tool)**

In `Frontend/View/Admission/admission.php`, inside the `<div v-if="applicant">` block, immediately after the existing applicant-summary `.form-section` (the one showing `applicant.full_name`, contact, etc.), add:

```html
      <div v-if="applicant.duplicate_match_status === 'pending_review'" class="alert-box alert-warning mb-3">
        Possible returning student — a matching record was found.
        <button type="button" class="btn btn-outline" style="margin-left:8px;" @click="reviewDuplicate('confirm')">Confirm match</button>
        <button type="button" class="btn btn-outline" style="margin-left:8px;" @click="reviewDuplicate('dismiss')">Dismiss</button>
      </div>

      <div class="form-section">
        <div class="section-head">
          <span class="section-num"><iconify-icon icon="mdi:shield-check-outline"></iconify-icon></span>
          <div>
            <h2>Admission Authorization</h2>
            <p>Optional staff note authorizing this applicant to proceed, if applicable.</p>
          </div>
        </div>
        <div v-if="applicant.authorization_note && !applicant.cleared_at" class="alert-box alert-info mb-2">
          {{ applicant.authorization_note }}
          <button type="button" class="btn btn-outline" style="margin-left:8px;" @click="clearAuthorization">Clear</button>
        </div>
        <div v-else class="row g-2">
          <div class="col-md-9">
            <input type="text" class="form-control" v-model="authorizationNote" placeholder="e.g. Authorized pending PSA submission">
          </div>
          <div class="col-md-3">
            <button type="button" class="btn btn-submit w-100" @click="setAuthorization">Save note</button>
          </div>
        </div>
      </div>
```

- [ ] **Step 8: Add the corresponding Vue methods to `admission-confirm.js`**

Inside the `methods: { ... }` object, add three methods alongside the existing `search`/`confirm` methods:

```javascript
      async setAuthorization() {
        if (!this.authorizationNote || !this.authorizationNote.trim()) return;
        const body = new URLSearchParams({
          applicant_id: this.applicant.applicant_id,
          action: 'set',
          note: this.authorizationNote,
        });
        const res = await fetch('/SIAdrafts/Backend/api/update_admission_authorization.php', { method: 'POST', body });
        const data = await res.json();
        if (data.success) {
          this.applicant.authorization_note = this.authorizationNote;
          this.applicant.cleared_at = null;
          this.authorizationNote = '';
        } else {
          Swal.fire({ icon: 'error', title: 'Could not save', text: (data.errors || []).join(' ') });
        }
      },
      async clearAuthorization() {
        const body = new URLSearchParams({ applicant_id: this.applicant.applicant_id, action: 'clear' });
        const res = await fetch('/SIAdrafts/Backend/api/update_admission_authorization.php', { method: 'POST', body });
        const data = await res.json();
        if (data.success) {
          this.applicant.cleared_at = new Date().toISOString();
        } else {
          Swal.fire({ icon: 'error', title: 'Could not clear', text: (data.errors || []).join(' ') });
        }
      },
      async reviewDuplicate(action) {
        const body = new URLSearchParams({ applicant_id: this.applicant.applicant_id, action: action });
        const res = await fetch('/SIAdrafts/Backend/api/update_duplicate_match.php', { method: 'POST', body });
        const data = await res.json();
        if (data.success) {
          this.applicant.duplicate_match_status = action === 'confirm' ? 'confirmed' : 'dismissed';
        } else {
          Swal.fire({ icon: 'error', title: 'Could not update', text: (data.errors || []).join(' ') });
        }
      },
```

Also add `authorizationNote: '',` to the `data: () => ({ ... })` object.

**Note:** these two new endpoints don't yet have a CSRF token supplied by the calling JS (`csrf_verify()` will reject them as written). This is a known, deliberate gap consistent with Foundation's documented CSRF debt (new endpoints in this plan should ideally get CSRF, but wiring a token through this Vue app's fetch calls needs the token embedded in the page — flag this in the implementer's report as `DONE_WITH_CONCERNS` if not resolved; the simplest fix is embedding `<?php echo csrf_token(); ?>` into a data attribute the Vue app reads on init and includes in each `URLSearchParams` body, e.g. `csrf_token: window.CSRF_TOKEN`. Prefer doing this now since it's a small addition, but do not let it block the rest of this task if it proves awkward within the file's existing structure — surface it clearly instead.)

- [ ] **Step 9: Verify**

Run `php -l` on both new PHP files and `confirm_admission.php`. Manually trace the group-aware missing-docs logic against a test case (e.g. submitting `birth_nso` but not `birth_psa` should NOT report birth certificate as missing).

- [ ] **Step 10: Commit**

```bash
git add SIAdrafts/Frontend/Js/Admission/admission-confirm.js SIAdrafts/Backend/api/confirm_admission.php SIAdrafts/Frontend/View/Admission/admission.php SIAdrafts/Backend/api/update_admission_authorization.php SIAdrafts/Backend/api/update_duplicate_match.php
git commit -m "Add Form 138/PSA-NSO alternatives, Admission Authorization, and duplicate-match review to walk-in verification"
```

---

## Task 9: Rename walk-in tool, add Admission list page

**Files:**
- Rename: `SIAdrafts/Frontend/View/Admission/admission.php` → `SIAdrafts/Frontend/View/Admission/admission_confirm.php`
- Rename: `SIAdrafts/Frontend/Js/Admission/admission-confirm.js` stays at its current path (already named for its role; no rename needed — only the PHP view file that includes/references it moves)
- Create: `SIAdrafts/Frontend/View/Admission/admission.php` (new — list/table view)
- Modify: `SIAdrafts/Backend/admin/admission.php` (add `ROLE_ADMISSION` to its `require_role()` call)

**Interfaces:**
- Consumes: `$admissions` array (produced by `Backend/admin/admission.php`, already built and RBAC-gated from Foundation).
- Produces: `admission.php` (list) links each row's "Review" action to `admission_confirm.php?ref=<reference_id>`.

- [ ] **Step 1: Rename the walk-in tool**

```bash
git mv SIAdrafts/Frontend/View/Admission/admission.php SIAdrafts/Frontend/View/Admission/admission_confirm.php
```

- [ ] **Step 2: Update `admission_confirm.php`'s own self-references, if any**

Read the renamed file for any hardcoded reference to its own old filename (e.g. in a "back" link or breadcrumb) and update it — based on the file's content read in Task 8, it has none currently (it's a standalone tool with no self-link), but confirm this by reading the full file before finishing this step.

- [ ] **Step 3: Add `?ref=` pre-fill support to `admission-confirm.js`**

At the top of the file (before the existing `if (document.getElementById('confirm-app'))` block), add nothing yet — instead, inside the Vue app's setup, after `data: () => ({...})`, add a `mounted()` hook:

```javascript
    mounted() {
      const params = new URLSearchParams(window.location.search);
      const ref = params.get('ref');
      if (ref) {
        this.referenceId = ref;
        this.search();
      }
    },
```

(Place this as a sibling key to `data` and `methods` inside the `Vue.createApp({...})` object.)

- [ ] **Step 4: Add `ROLE_ADMISSION` to `Backend/admin/admission.php`'s guard**

Change:
```php
require_role([ROLE_REGISTRAR_STAFF, ROLE_HEAD_REGISTRAR, ROLE_ADMIN], true);
```
to:
```php
require_role([ROLE_REGISTRAR_STAFF, ROLE_HEAD_REGISTRAR, ROLE_ADMISSION, ROLE_ADMIN], true);
```

- [ ] **Step 5: Create the new `Frontend/View/Admission/admission.php` (list view)**

```php
<?php
$pageTitle  = "ADMISSION";
$activePage = "admission";

require_once '../../../Backend/auth.php';
require_once '../../../Backend/roles.php';
require_once '../../../Backend/require_role.php';
require_role([ROLE_ADMISSION, ROLE_ADMIN]);
require_once __DIR__ . '/../../../Backend/admin/admission.php';

include '../Include/header.php';
?>

<div class="app-layout">

    <?php include '../Include/sidebar.php'; ?>

    <main class="page-content">

        <div class="panel">

            <div class="panel-header">
                <span class="panel-title">All Admission Records</span>
            </div>

            <div class="panel-body" style="padding:0;">
                <div class="table-responsive">
                <table class="data-table" id="admissionTable">
                    <thead>
                        <tr>
                            <th>Reference ID</th>
                            <th>Applicant Name</th>
                            <th>Program</th>
                            <th>Application Date</th>
                            <th>Status</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($admissions as $row): ?>
                            <tr>
                                <td><?= htmlspecialchars($row['reference_id']) ?></td>
                                <td><?= htmlspecialchars($row['applicant_name']) ?></td>
                                <td><?= htmlspecialchars($row['program']) ?></td>
                                <td><?= date('M d, Y', strtotime($row['created_at'])) ?></td>
                                <td><?= htmlspecialchars($row['status']) ?></td>
                                <td>
                                    <a href="admission_confirm.php?ref=<?= urlencode($row['reference_id']) ?>" class="btn btn-outline" style="padding:4px 10px;font-size:12px">
                                        Review
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                </div>
            </div>

        </div>

    </main>

</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
  initDataTable('#admissionTable', { order: [[3, 'desc']] });
});
</script>

<?php include '../Include/footer.php'; ?>
```

(This page is Task 10's job to move onto the shared layout — for now it references `Include/header.php` etc. relative to the Admission module's own folder, matching the file's temporary location; Task 10 updates the include paths when the module moves to the shared layout, same two-step pattern Foundation used for `settings.php`.)

- [ ] **Step 6: Verify**

Run `php -l` on both `admission.php` and `admission_confirm.php`. Grep the codebase for any other reference to the old path (`Admission/admission.php` used as a *walk-in tool* reference, e.g. in `login.php`'s redirect switch) — confirm `login.php`'s `case 'admission': $redirect = '.../Admission/admission.php';` now correctly points at the new list page (no code change needed there since the filename `admission.php` still exists, just repurposed — but confirm this is the desired landing page for the Admission role, matching the design's "Dashboard" being the first sidebar item; if the design intends Dashboard as the post-login landing page instead, note this as a follow-up for Task 10's dashboard work rather than changing `login.php` in this task).

- [ ] **Step 7: Commit**

```bash
git add -A SIAdrafts/Frontend/View/Admission/admission.php SIAdrafts/Frontend/View/Admission/admission_confirm.php SIAdrafts/Frontend/Js/Admission/admission-confirm.js SIAdrafts/Backend/admin/admission.php
git commit -m "Split Admission walk-in tool (admission_confirm.php) from new Admission list view (admission.php)"
```

---

## Task 10: Admission module shared-layout migration

**Files:**
- Modify: `SIAdrafts/Backend/nav_config.php`
- Modify: `SIAdrafts/Frontend/View/Admission/admission.php`
- Modify: `SIAdrafts/Frontend/View/Admission/admission_confirm.php`
- Modify: `SIAdrafts/Frontend/View/Admission/enrollment.php`
- Modify: `SIAdrafts/Frontend/View/Admission/enrollment_confirm.php`
- Modify: `SIAdrafts/Frontend/View/Admission/enrollment_profile.php`
- Modify: `SIAdrafts/Frontend/View/Admission/enrollment_subjects.php`

**Interfaces:**
- Consumes: `Frontend/View/Include/{header,sidebar,footer}.php` (Foundation), `Backend/nav_config.php`'s per-role array shape (Foundation).

- [ ] **Step 1: Add an `'admission'` entry to `Backend/nav_config.php`**

```php
    'admission' => [
        ['label' => 'Dashboard', 'page' => 'dashboard', 'url' => '/SIAdrafts/Frontend/View/Admission/dashboard.php', 'icon' => 'dashboard'],
        ['label' => 'Admission', 'page' => 'admission', 'url' => '/SIAdrafts/Frontend/View/Admission/admission.php', 'icon' => 'admission'],
        ['label' => 'Enrollment', 'page' => 'enrollment', 'url' => '/SIAdrafts/Frontend/View/Admission/enrollment.php', 'icon' => 'enrollment'],
        ['label' => 'Total Enrolees', 'page' => 'total_enrolees', 'url' => '/SIAdrafts/Frontend/View/Admission/total_enrolees.php', 'icon' => 'enrolees'],
    ],
```

(`dashboard.php` and `total_enrolees.php` don't exist yet — created in Tasks 11 and 12. Adding the nav entry now is safe; the links simply won't resolve until those tasks land, same sequencing Foundation used for `settings.php`.)

- [ ] **Step 2: Read each of the 6 listed files' current top/bottom include blocks**

Each currently does `include '../Admission/Include/header.php';` (or similar) at the top and `include '../Admission/Include/footer.php'` at the bottom, per the pattern already observed in `online_admission.php` and `admission.php`/`admission_confirm.php`. Confirm the exact current include lines in each of `enrollment.php`, `enrollment_confirm.php`, `enrollment_profile.php`, `enrollment_subjects.php` before editing (they may differ slightly file to file), since these weren't individually read during planning.

- [ ] **Step 3: Add `$activePage` to each of the 6 files if missing**

Each file needs `$activePage = "..."` set (matching the `page` values used in Step 1's nav entries: `"admission"` for `admission.php`/`admission_confirm.php`, `"enrollment"` for all four enrollment-flow files) near the top, alongside its existing `$pageTitle`/`$page_scripts` assignment, if not already present.

- [ ] **Step 4: Switch each file's includes to the shared layout**

For each of the 6 files: replace the header/sidebar/footer include paths with `'../Include/header.php'`, `'../Include/sidebar.php'`, `'../Include/footer.php'` (same pattern as every other module in Foundation's Task 12) — noting that `admission.php` and `admission_confirm.php` don't currently include a sidebar at all (the Admission module's old header.php renders a top-nav, not a sidebar+header.php/footer.php triplet like Admin/Registrar/HeadRegistrar) — so this step also means adding the `<div class="app-layout"><?php include '../Include/sidebar.php'; ?><main class="page-content">...</main></div>` wrapper structure around each file's existing body content, matching the pattern already used in the new `admission.php` list view created in Task 9.

- [ ] **Step 5: Verify**

Run `php -l` on all 6 modified files. Read each one fully to confirm the `app-layout`/`page-content` wrapper was added correctly around existing content without breaking any Vue `id="..."` mount points those files rely on (each of the 4 enrollment-flow files mounts its own Vue app the same way `admission_confirm.php` does — confirm the mount-point `<div id="...">` element stays inside `<main class="page-content">`, not accidentally left outside it).

- [ ] **Step 6: Commit**

```bash
git add SIAdrafts/Backend/nav_config.php SIAdrafts/Frontend/View/Admission/admission.php SIAdrafts/Frontend/View/Admission/admission_confirm.php SIAdrafts/Frontend/View/Admission/enrollment.php SIAdrafts/Frontend/View/Admission/enrollment_confirm.php SIAdrafts/Frontend/View/Admission/enrollment_profile.php SIAdrafts/Frontend/View/Admission/enrollment_subjects.php
git commit -m "Migrate Admission staff pages to the shared sidebar layout"
```

---

## Task 11: Admission Dashboard

**Files:**
- Create: `SIAdrafts/Backend/admin/admission_dashboard.php`
- Create: `SIAdrafts/Frontend/View/Admission/dashboard.php`

**Interfaces:**
- Produces: `$dashboard` array (keys: `pending_review`, `verified_today`, `possible_duplicates`), consumed by the view.

- [ ] **Step 1: Create `Backend/admin/admission_dashboard.php`**

```php
<?php

require_once __DIR__ . '/../db.php';
require_once __DIR__ . '/../roles.php';
require_once __DIR__ . '/../require_role.php';
require_role([ROLE_ADMISSION, ROLE_ADMIN], true);

$db   = new Database();
$conn = $db->connect();

$dashboard = [
    'pending_review'      => 0,
    'verified_today'      => 0,
    'possible_duplicates' => 0,
];

$res = $conn->query("SELECT COUNT(*) AS cnt FROM applicants WHERE admission_status = 'pending_verification'");
$dashboard['pending_review'] = (int)($res->fetch_assoc()['cnt'] ?? 0);

$res = $conn->query("SELECT COUNT(*) AS cnt FROM applicants WHERE admission_status = 'verified' AND DATE(verified_at) = CURDATE()");
$dashboard['verified_today'] = (int)($res->fetch_assoc()['cnt'] ?? 0);

$res = $conn->query("SELECT COUNT(*) AS cnt FROM applicants WHERE duplicate_match_status = 'pending_review'");
$dashboard['possible_duplicates'] = (int)($res->fetch_assoc()['cnt'] ?? 0);

$db->close();
```

- [ ] **Step 2: Create `Frontend/View/Admission/dashboard.php`**

```php
<?php
$pageTitle  = "ADMISSION DASHBOARD";
$activePage = "dashboard";

require_once '../../../Backend/auth.php';
require_once '../../../Backend/roles.php';
require_once '../../../Backend/require_role.php';
require_role([ROLE_ADMISSION, ROLE_ADMIN]);
require_once __DIR__ . '/../../../Backend/admin/admission_dashboard.php';

include '../Include/header.php';
?>

<div class="app-layout">

    <?php include '../Include/sidebar.php'; ?>

    <main class="page-content">

        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-icon gold"><i class="bi bi-hourglass-split"></i></div>
                <div>
                    <div class="stat-value"><?= $dashboard['pending_review'] ?></div>
                    <div class="stat-label">Pending Review</div>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon gold"><i class="bi bi-check-circle"></i></div>
                <div>
                    <div class="stat-value"><?= $dashboard['verified_today'] ?></div>
                    <div class="stat-label">Verified Today</div>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon gold"><i class="bi bi-people"></i></div>
                <div>
                    <div class="stat-value"><?= $dashboard['possible_duplicates'] ?></div>
                    <div class="stat-label">Possible Returning Students</div>
                </div>
            </div>
        </div>

    </main>

</div>

<?php include '../Include/footer.php'; ?>
```

- [ ] **Step 3: Verify**

Run `php -l` on both files.

- [ ] **Step 4: Commit**

```bash
git add SIAdrafts/Backend/admin/admission_dashboard.php SIAdrafts/Frontend/View/Admission/dashboard.php
git commit -m "Add Admission staff dashboard"
```

---

## Task 12: Admission Total Enrollees page

**Files:**
- Modify: `SIAdrafts/Backend/admin/total_enrolees.php` (add `ROLE_ADMISSION` to its guard)
- Create: `SIAdrafts/Frontend/View/Admission/total_enrolees.php`

**Interfaces:**
- Consumes: whatever variable `Backend/admin/total_enrolees.php` already produces for Registrar's `total_enrolees.php` to consume (read `Frontend/View/Registrar/total_enrolees.php` in full during this task — only its `<table>` structure was seen during planning, via Task 13 of the Foundation plan; read the complete file, including its top PHP block, before writing the Admission copy).

- [ ] **Step 1: Read `Frontend/View/Registrar/total_enrolees.php` in full**

Read the complete file (not just the table markup) to capture its exact top-of-file PHP block, full body structure, and bottom include/footer block, since the Admission version needs to mirror it exactly except for role gating and nav `$activePage`.

- [ ] **Step 2: Add `ROLE_ADMISSION` to `Backend/admin/total_enrolees.php`'s guard**

Find its existing `require_role([...], true)` call and add `ROLE_ADMISSION` to the allowed list, following the same pattern as Task 9 Step 4.

- [ ] **Step 3: Create `Frontend/View/Admission/total_enrolees.php`**

Copy `Frontend/View/Registrar/total_enrolees.php`'s full content (as read in Step 1) into the new file, with these changes: `$activePage = "total_enrolees";` stays the same value; the `require_role([...])` call becomes `require_role([ROLE_ADMISSION, ROLE_ADMIN]);`; include paths already point at `'../Include/...'` (shared layout, same as the Registrar version post-Foundation) so no path change is needed; remove the DataTables-init `<script>` block added in Foundation Task 13 only if it references an id that doesn't exist in this copy — otherwise keep it as-is, since Admission's table has the same `id="enroleesTable"` structure.

- [ ] **Step 4: Verify**

Run `php -l` on both files. Confirm the new file's role guard is `[ROLE_ADMISSION, ROLE_ADMIN]`, not `[ROLE_REGISTRAR_STAFF, ROLE_HEAD_REGISTRAR]` (a copy-paste risk given Step 3 starts from the Registrar file).

- [ ] **Step 5: Commit**

```bash
git add SIAdrafts/Backend/admin/total_enrolees.php SIAdrafts/Frontend/View/Admission/total_enrolees.php
git commit -m "Add Admission staff Total Enrollees page"
```

---

## Task 13: Landing page dynamic course showcase + Apply Now

**Files:**
- Modify: `SIAdrafts/Frontend/View/index.php`

**Interfaces:**
- Consumes: `course` table (`course_id`, `course_code`, `course_name`, `total_units`, `status`).
- Produces: `online_admission.php?course_id={id}` links (consumed by Task 3's lock logic).

- [ ] **Step 1: Add a DB query at the top of `index.php`**

Before the `<!DOCTYPE html>` line, add:

```php
<?php
require_once __DIR__ . '/../../Backend/db.php';
$db   = new Database();
$conn = $db->connect();
$courses = [];
$res = $conn->query("SELECT course_id, course_code, course_name, total_units FROM course WHERE status = 'Approved' ORDER BY course_name ASC");
if ($res) $courses = $res->fetch_all(MYSQLI_ASSOC);
$db->close();
?>
```

- [ ] **Step 2: Replace the static `.program-list` block**

Replace:
```html
      <div class="program-list">
        <div class="program-card">
          <iconify-icon icon="mdi:laptop"></iconify-icon>
          <h4>BS Information Technology</h4>
          <p>4-year program</p>
        </div>
        <div class="program-card">
          <iconify-icon icon="mdi:office-building-outline"></iconify-icon>
          <h4>BS Business Administration</h4>
          <p>4-year program</p>
        </div>
        <div class="program-card">
          <iconify-icon icon="mdi:heart-pulse"></iconify-icon>
          <h4>BS Nursing</h4>
          <p>4-year program</p>
        </div>
      </div>
```
with:
```html
      <div class="program-list">
        <?php if (empty($courses)): ?>
          <p style="color:rgba(250,247,240,0.6);">No programs currently open for enrollment.</p>
        <?php else: ?>
          <?php foreach ($courses as $c): ?>
            <div class="program-card">
              <iconify-icon icon="mdi:school-outline"></iconify-icon>
              <h4><?= htmlspecialchars($c['course_name']) ?></h4>
              <p><?= (int)$c['total_units'] ?> total units</p>
              <button type="button" class="btn btn-apply" style="margin-top:12px;" onclick="openApplyModal(<?= (int)$c['course_id'] ?>, '<?= htmlspecialchars(addslashes($c['course_name'])) ?>')">
                Apply Now
              </button>
            </div>
          <?php endforeach; ?>
        <?php endif; ?>
      </div>
```

- [ ] **Step 3: Add the Apply Now modal markup**

Immediately before `</body>`, add:

```html
  <div id="applyModal" style="display:none; position:fixed; inset:0; background:rgba(27,42,74,0.5); z-index:2000; align-items:center; justify-content:center;">
    <div style="background:var(--white); border-radius:18px; padding:28px; max-width:420px; width:90%;">
      <h3 id="applyModalTitle" style="margin:0 0 10px;">Apply for this program?</h3>
      <p style="color:var(--ink-soft); margin:0 0 20px;">You'll be taken to the application form with this program pre-selected.</p>
      <div style="display:flex; gap:10px; justify-content:flex-end;">
        <button type="button" class="btn btn-login" onclick="closeApplyModal()">Cancel</button>
        <a id="applyModalConfirm" href="#" class="btn btn-apply">Continue</a>
      </div>
    </div>
  </div>
  <script>
    function openApplyModal(courseId, courseName) {
      document.getElementById('applyModalTitle').textContent = 'Apply for ' + courseName + '?';
      document.getElementById('applyModalConfirm').href = '/SIAdrafts/Frontend/View/Admission/online_admission.php?course_id=' + courseId;
      document.getElementById('applyModal').style.display = 'flex';
    }
    function closeApplyModal() {
      document.getElementById('applyModal').style.display = 'none';
    }
  </script>
```

- [ ] **Step 4: Verify**

Run `php -l` on `index.php`. Confirm the `require_once __DIR__ . '/../../Backend/db.php';` path is correct relative to `Frontend/View/index.php`'s actual location (`Frontend/View/` → `../../` reaches `SIAdrafts/`, then `Backend/db.php` — count the directory levels against the file's real path before trusting this snippet, since `index.php` sits one level shallower than the Admission-subfolder files this plan mostly deals with).

- [ ] **Step 5: Commit**

```bash
git add SIAdrafts/Frontend/View/index.php
git commit -m "Add dynamic course showcase and Apply Now flow to the landing page"
```

---

## Self-Review Notes

- **Spec coverage:** Schema (Task 1), nationality/relationship validation (Task 2), form redesign (Task 3-4), automation — SY/semester + returning-student + applicant-type suggestion (Task 5-6), Form 138/PSA-NSO/Admission Authorization/duplicate review (Task 8), admin sidebar+table (Task 9-10), Dashboard (Task 11), Total Enrollees (Task 12), landing page courses (Task 13). All design sections have corresponding tasks.
- **Known gap flagged inline:** Task 8's two new endpoints (`update_admission_authorization.php`, `update_duplicate_match.php`) call `csrf_verify()` but the Vue-based calling JS doesn't yet supply a token — flagged explicitly in Task 8 as something the implementer should either resolve (preferred, small addition) or report as a concern, consistent with how Foundation handled its own CSRF-coverage gap (documented, not silently inconsistent).
- **Type consistency:** `REQUIREMENT_DEFINITIONS`/`missing_requirement_groups()` (Task 1) used identically in Task 5 (online processing) and Task 8 (walk-in confirm) — same function, same shape, no divergent reimplementation.
- **Read-before-edit flags:** Tasks 7, 9 (Step 2), 10 (Step 2), 12 (Step 1), and 13 (Step 4) explicitly instruct the implementer to read the current file/path depth before transcribing the given snippet, since several of these files' exact current contents (or exact relative path depth) weren't fully captured during planning — this is called out inline rather than guessed at, per the plan's own "ask rather than guess" standard from Foundation.
