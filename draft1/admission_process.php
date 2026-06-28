<?php
// ============================================================
// admission_process.php
// Handles the POST from admission.php (walk-in enrollment form).
// Validates required fields, then saves the applicant, their
// academic history, and their submitted documents to MySQL.
// ============================================================

require 'db.php'; // expects $conn = new mysqli(...) already created in db.php
require 'validation_rules.php'; // shared rules — also used by validate_field.php

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'errors' => ['Invalid request method.']]);
    exit;
}

// ---------- helper ----------
function clean($value) {
    return htmlspecialchars(trim($value ?? ''), ENT_QUOTES, 'UTF-8');
}

// ---------- collect single-value fields ----------
$fields = [
    'last_name'        => clean($_POST['last_name'] ?? ''),
    'first_name'       => clean($_POST['first_name'] ?? ''),
    'middle_name'      => clean($_POST['middle_name'] ?? ''),
    'birth_date'       => clean($_POST['birth_date'] ?? ''),
    'sex'              => clean($_POST['sex'] ?? ''),
    'civil_status'     => clean($_POST['civil_status'] ?? ''),
    'contact_number'   => clean($_POST['contact_number'] ?? ''),
    'email'            => clean($_POST['email'] ?? ''),
    'home_address'     => clean($_POST['home_address'] ?? ''),

    'guardian_name'         => clean($_POST['guardian_name'] ?? ''),
    'guardian_relationship' => clean($_POST['guardian_relationship'] ?? ''),
    'guardian_contact'      => clean($_POST['guardian_contact'] ?? ''),
    'guardian_id_type'      => clean($_POST['guardian_id_type'] ?? ''),
    'guardian_id_number'    => clean($_POST['guardian_id_number'] ?? ''),
    'id_verified_by'        => clean($_POST['id_verified_by'] ?? ''),

    'program'        => clean($_POST['program'] ?? ''),
    'year_level'     => clean($_POST['year_level'] ?? ''),
    'start_term'     => clean($_POST['start_term'] ?? ''),
    'applicant_type' => clean($_POST['applicant_type'] ?? ''),
];

// ---------- collect repeatable academic history rows ----------
$school_names   = $_POST['school_name'] ?? [];
$school_address = $_POST['school_address'] ?? [];
$school_year    = $_POST['school_year'] ?? [];
$school_strand  = $_POST['school_strand'] ?? [];
$school_gpa     = $_POST['school_gpa'] ?? [];

$history = [];
for ($i = 0; $i < count($school_names); $i++) {
    // Skip fully-empty rows (e.g. an "Add another school" row left blank)
    if (trim($school_names[$i] ?? '') === '') continue;
    $history[] = [
        'school'  => clean($school_names[$i] ?? ''),
        'address' => clean($school_address[$i] ?? ''),
        'year'    => clean($school_year[$i] ?? ''),
        'strand'  => clean($school_strand[$i] ?? ''),
        'gpa'     => clean($school_gpa[$i] ?? ''),
    ];
}

// ---------- collect document checklist ----------
$docs_submitted = array_map('clean', $_POST['docs'] ?? []);

$required_docs = [
    'Form 137 / SHS Card',
    'Certificate of Good Moral',
    'Birth Certificate (PSA)',
    '2x2 ID Photos',
];

$missing_required = array_diff($required_docs, $docs_submitted);

// ---------- validation ----------
$errors = [];

$required_fields = required_field_labels();

foreach ($required_fields as $key => $label) {
    if ($fields[$key] === '') {
        $errors[] = $label . ' is required.';
    }
}

// ---------- format validation ----------
// Every check below calls the shared functions in validation_rules.php —
// the exact same functions validate_field.php uses for per-field AJAX
// checks in admission.php. One set of rules, two callers, no duplication.

$check = [
    validate_name($fields['last_name'], 'Last name'),
    validate_name($fields['first_name'], 'First name'),
    validate_name($fields['middle_name'], 'Middle name'),
    validate_name($fields['guardian_name'], 'Guardian name'),
    validate_ph_mobile($fields['contact_number'], "Student's contact number"),
    validate_ph_mobile($fields['guardian_contact'], "Guardian's contact number"),
    validate_email_field($fields['email']),
    validate_birth_date($fields['birth_date']),
    validate_guardian_id($fields['guardian_id_number']),
    validate_address($fields['home_address']),
];

foreach ($check as $error) {
    if ($error !== null) $errors[] = $error;
}

// Academic history rows aren't single fields, so they're checked per-row
// using the same validate_school_name() / validate_gpa() functions.
foreach ($history as $i => $h) {
    $row_num = $i + 1;
    $school_error = validate_school_name($h['school']);
    if ($school_error !== null) $errors[] = "Row $row_num: $school_error";

    $gpa_error = validate_gpa($h['gpa']);
    if ($gpa_error !== null) $errors[] = "Row $row_num: $gpa_error";
}

if (empty($history)) {
    $errors[] = 'At least one academic history entry is required.';
}

if (!empty($missing_required)) {
    $errors[] = 'Missing required documents: ' . implode(', ', $missing_required) . '.';
}

// ============================================================
// SAVE TO DATABASE — only runs if validation passed.
// Tables expected:
//   applicants(applicant_id, last_name, first_name, middle_name,
//     birth_date, sex, civil_status, contact_number, email,
//     home_address, guardian_name, guardian_relationship,
//     guardian_contact, guardian_id_type, guardian_id_number,
//     id_verified_by, program, year_level, start_term,
//     applicant_type, created_at)
//   applicant_school_history(history_id, applicant_id, school_name,
//     school_address, school_year, school_strand, school_gpa)
//   applicant_documents(document_id, applicant_id, document_name)
// ============================================================

$applicant_id = null;

if (empty($errors)) {

    // ---- 1. insert the main applicant record ----
    $stmt = $conn->prepare("
        INSERT INTO applicants
            (last_name, first_name, middle_name, birth_date, sex, civil_status,
             contact_number, email, home_address,
             guardian_name, guardian_relationship, guardian_contact,
             guardian_id_type, guardian_id_number, id_verified_by,
             program, year_level, start_term, applicant_type, created_at)
        VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?, NOW())
    ");

    if (!$stmt) {
        $errors[] = 'Database error (applicants insert): ' . $conn->error;
    } else {
        $stmt->bind_param(
            'sssssssssssssssssss',
            $fields['last_name'], $fields['first_name'], $fields['middle_name'],
            $fields['birth_date'], $fields['sex'], $fields['civil_status'],
            $fields['contact_number'], $fields['email'], $fields['home_address'],
            $fields['guardian_name'], $fields['guardian_relationship'], $fields['guardian_contact'],
            $fields['guardian_id_type'], $fields['guardian_id_number'], $fields['id_verified_by'],
            $fields['program'], $fields['year_level'], $fields['start_term'], $fields['applicant_type']
        );

        if (!$stmt->execute()) {
            $errors[] = 'Database error (applicants insert): ' . $stmt->error;
        } else {
            $applicant_id = $stmt->insert_id;
        }
        $stmt->close();
    }

    // ---- 2. insert academic history rows, linked by applicant_id ----
    if ($applicant_id && empty($errors)) {
        $histStmt = $conn->prepare("
            INSERT INTO applicant_school_history
                (applicant_id, school_name, school_address, school_year, school_strand, school_gpa)
            VALUES (?,?,?,?,?,?)
        ");

        if (!$histStmt) {
            $errors[] = 'Database error (school history insert): ' . $conn->error;
        } else {
            foreach ($history as $row) {
                $histStmt->bind_param(
                    'isssss',
                    $applicant_id, $row['school'], $row['address'], $row['year'], $row['strand'], $row['gpa']
                );
                if (!$histStmt->execute()) {
                    $errors[] = 'Database error (school history insert): ' . $histStmt->error;
                    break;
                }
            }
            $histStmt->close();
        }
    }

    // ---- 3. insert submitted documents, linked by applicant_id ----
    if ($applicant_id && empty($errors) && !empty($docs_submitted)) {
        $docStmt = $conn->prepare("
            INSERT INTO applicant_documents (applicant_id, document_name)
            VALUES (?, ?)
        ");

        if (!$docStmt) {
            $errors[] = 'Database error (documents insert): ' . $conn->error;
        } else {
            foreach ($docs_submitted as $doc) {
                $docStmt->bind_param('is', $applicant_id, $doc);
                if (!$docStmt->execute()) {
                    $errors[] = 'Database error (documents insert): ' . $docStmt->error;
                    break;
                }
            }
            $docStmt->close();
        }
    }

    $conn->close();
}

// ============================================================
// JSON RESPONSE — sent back to the fetch() call in admission.php.
// success:false includes the same $errors array used above, so
// the frontend can show each one inline without a page reload.
// ============================================================

if (!empty($errors)) {
    http_response_code(422); // Unprocessable Entity — validation/db failure
    echo json_encode([
        'success' => false,
        'errors'  => $errors,
    ]);
    exit;
}

echo json_encode([
    'success'       => true,
    'applicant_id'  => $applicant_id,
    'summary'       => [
        'name'        => trim($fields['first_name'] . ' ' . $fields['middle_name'] . ' ' . $fields['last_name']),
        'program'     => $fields['program'] . ' — ' . $fields['year_level'],
        'contact'     => $fields['contact_number'],
        'start_term'  => $fields['start_term'],
        'guardian'    => $fields['guardian_name'] . ' (' . $fields['guardian_relationship'] . ')',
        'verified_by' => $fields['id_verified_by'],
        'history'     => $history,
        'documents'   => $docs_submitted,
    ],
]);  

include 'header.php';
?>

<style>
  :root{
    --ink:#1B2A4A;
    --paper:#FAF7F0;
    --amber:#E8A33D;
    --sage:#7C9885;
    --ink-soft: rgba(27,42,74,0.65);
    --ink-line: rgba(27,42,74,0.12);
  }

  .confirm-page{
    padding-top: 130px;
    padding-bottom: 70px;
    min-height: 100vh;
    background:
      radial-gradient(900px 420px at 50% 0%, rgba(232,163,61,0.08), transparent 60%),
      var(--paper);
  }

  .confirm-card{
    max-width: 760px;
    margin: 0 auto;
    padding: 0 16px;
  }

  .confirm-box{
    background: rgba(255,255,255,0.92);
    border: 1px solid var(--ink-line);
    border-radius: 24px;
    box-shadow: 0 1px 1px rgba(27,42,74,0.03), 0 24px 48px -22px rgba(27,42,74,0.22);
    padding: 32px;
  }

  .confirm-icon{
    width: 56px; height:56px;
    border-radius: 16px;
    display:flex; align-items:center; justify-content:center;
    margin-bottom: 18px;
    font-size: 26px;
  }
  .confirm-icon.success{ background: rgba(124,152,133,0.15); color: var(--sage); }
  .confirm-icon.error{ background: rgba(232,163,61,0.18); color: #b8741f; }

  .confirm-box h1{
    font-family: Georgia, "Times New Roman", serif;
    color: var(--ink);
    font-size: 1.5rem;
    margin: 0 0 6px;
  }
  .confirm-box .sub{ color: var(--ink-soft); margin: 0 0 22px; font-size: 0.94rem; }

  .error-list{
    background: rgba(232,163,61,0.08);
    border: 1px solid rgba(232,163,61,0.3);
    border-radius: 14px;
    padding: 16px 18px;
    margin-bottom: 20px;
  }
  .error-list ul{ margin: 0; padding-left: 18px; color: var(--ink); font-size: 0.9rem; }
  .error-list li{ margin-bottom: 4px; }

  .summary-grid{
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 14px 20px;
    margin-bottom: 22px;
  }
  @media (max-width: 560px){ .summary-grid{ grid-template-columns: 1fr; } }

  .summary-item .k{
    font-size: 0.72rem;
    font-weight: 700;
    letter-spacing: 0.03em;
    text-transform: uppercase;
    color: var(--ink-soft);
    margin-bottom: 2px;
  }
  .summary-item .v{
    font-size: 0.94rem;
    color: var(--ink);
  }

  .history-block{
    border-top: 1px solid var(--ink-line);
    padding-top: 18px;
    margin-top: 6px;
  }
  .history-block h3{
    font-size: 0.82rem;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.03em;
    color: var(--ink-soft);
    margin-bottom: 10px;
  }
  .history-entry{
    background: rgba(27,42,74,0.03);
    border-radius: 10px;
    padding: 10px 14px;
    margin-bottom: 8px;
    font-size: 0.88rem;
    color: var(--ink);
  }

  .doc-pill{
    display:inline-flex;
    align-items:center;
    gap:6px;
    background: rgba(124,152,133,0.12);
    color: var(--ink);
    font-size: 0.82rem;
    padding: 5px 12px;
    border-radius: 999px;
    margin: 3px 4px 0 0;
  }
  .doc-pill iconify-icon{ color: var(--sage); font-size: 14px; }

  .confirm-actions{
    display:flex;
    gap: 10px;
    margin-top: 26px;
    flex-wrap: wrap;
  }
  .btn-back{
    background: var(--ink);
    border-color: var(--ink);
    color: #fff;
    font-weight: 600;
    border-radius: 0.8rem;
    padding: 11px 22px;
  }
  .btn-back:hover{ background:#2c3e63; border-color:#2c3e63; color:#fff; }
  .btn-edit{
    background: #fff;
    border: 1.5px solid var(--ink-line);
    color: var(--ink);
    font-weight: 600;
    border-radius: 0.8rem;
    padding: 11px 22px;
  }
  .btn-edit:hover{ border-color: var(--ink); background: var(--paper); }
</style>

<div class="confirm-page">
  <div class="confirm-card">
    <div class="confirm-box">

      <?php if (!empty($errors)): ?>

        <div class="confirm-icon error">
          <iconify-icon icon="mdi:alert-circle-outline"></iconify-icon>
        </div>
        <h1>Application incomplete</h1>
        <p class="sub">Fix the items below, then go back and resubmit.</p>

        <div class="error-list">
          <ul>
            <?php foreach ($errors as $err): ?>
              <li><?= $err ?></li>
            <?php endforeach; ?>
          </ul>
        </div>

        <div class="confirm-actions">
          <a href="admission.php" class="btn btn-back">Back to form</a>
        </div>

      <?php else: ?>

        <div class="confirm-icon success">
          <iconify-icon icon="mdi:check-circle-outline"></iconify-icon>
        </div>
        <h1>Application saved</h1>
        <p class="sub">Applicant record #<?= $applicant_id ?> has been saved to the database.</p>

        <div class="summary-grid">
          <div class="summary-item">
            <div class="k">Applicant</div>
            <div class="v"><?= $fields['first_name'] ?> <?= $fields['middle_name'] ?> <?= $fields['last_name'] ?></div>
          </div>
          <div class="summary-item">
            <div class="k">Program</div>
            <div class="v"><?= $fields['program'] ?> — <?= $fields['year_level'] ?></div>
          </div>
          <div class="summary-item">
            <div class="k">Contact number</div>
            <div class="v"><?= $fields['contact_number'] ?></div>
          </div>
          <div class="summary-item">
            <div class="k">Start term</div>
            <div class="v"><?= $fields['start_term'] ?></div>
          </div>
          <div class="summary-item">
            <div class="k">Guardian</div>
            <div class="v"><?= $fields['guardian_name'] ?> (<?= $fields['guardian_relationship'] ?>)</div>
          </div>
          <div class="summary-item">
            <div class="k">Guardian ID verified by</div>
            <div class="v"><?= $fields['id_verified_by'] ?></div>
          </div>
        </div>

        <div class="history-block">
          <h3>Academic history</h3>
          <?php foreach ($history as $h): ?>
            <div class="history-entry">
              <strong><?= $h['school'] ?></strong>
              <?php if ($h['year']): ?> — <?= $h['year'] ?><?php endif; ?>
              <?php if ($h['gpa']): ?> · Avg: <?= $h['gpa'] ?><?php endif; ?>
            </div>
          <?php endforeach; ?>
        </div>

        <div class="history-block">
          <h3>Documents submitted</h3>
          <?php if (empty($docs_submitted)): ?>
            <p class="sub" style="margin:0;">None checked.</p>
          <?php else: ?>
            <?php foreach ($docs_submitted as $doc): ?>
              <span class="doc-pill"><iconify-icon icon="mdi:check"></iconify-icon><?= $doc ?></span>
            <?php endforeach; ?>
          <?php endif; ?>
        </div>

        <div class="confirm-actions">
          <a href="admission.php" class="btn btn-edit">Enroll another student</a>
        </div>

      <?php endif; ?>

    </div>
  </div>
</div>

<?php include 'footer.php' ?>