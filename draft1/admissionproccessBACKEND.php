<?php
// ============================================================
// admission_process.php
// Handles the POST from admission.php (walk-in enrollment form).
//
// CURRENT STATE: frontend-only confirmation flow.
// Validates required fields and displays what was submitted.
// No database writes yet — see the commented INSERT block below
// for where that goes once db.php is wired in.
// ============================================================
 
// Uncomment when ready to save to MySQL:
// require 'db.php'; // expects $conn = new mysqli(...) already created in db.php
 
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: admission.php');
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
$school_names    = $_POST['school_name'] ?? [];
$school_address  = $_POST['school_address'] ?? [];
$school_year     = $_POST['school_year'] ?? [];
$school_strand   = $_POST['school_strand'] ?? [];
$school_gpa      = $_POST['school_gpa'] ?? [];
 
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
 
$required_fields = [
    'last_name' => 'Last name',
    'first_name' => 'First name',
    'birth_date' => 'Date of birth',
    'sex' => 'Sex',
    'contact_number' => "Student's contact number",
    'home_address' => 'Home address',
    'guardian_name' => 'Guardian full name',
    'guardian_relationship' => "Guardian's relationship to applicant",
    'guardian_contact' => "Guardian's contact number",
    'guardian_id_type' => 'Guardian ID type',
    'guardian_id_number' => 'Guardian ID number',
    'id_verified_by' => 'Staff ID verification initials',
    'year_level' => 'Year level',
    'start_term' => 'Intended start term',
];
 
foreach ($required_fields as $key => $label) {
    if ($fields[$key] === '') {
        $errors[] = $label . ' is required.';
    }
}
 
if (empty($history)) {
    $errors[] = 'At least one academic history entry is required.';
}
 
if (!empty($missing_required)) {
    $errors[] = 'Missing required documents: ' . implode(', ', $missing_required) . '.';
}
 
// ============================================================
// WHEN READY FOR MYSQL — uncomment and adapt this block.
// Runs only if $errors is empty, right before showing the
// confirmation screen.
// ============================================================
/*
if (empty($errors)) {
 
    $stmt = $conn->prepare("
        INSERT INTO applicants
            (last_name, first_name, middle_name, birth_date, sex, civil_status,
             contact_number, email, home_address,
             guardian_name, guardian_relationship, guardian_contact,
             guardian_id_type, guardian_id_number, id_verified_by,
             program, year_level, start_term, applicant_type, created_at)
        VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?, NOW())
    ");
    $stmt->bind_param(
        'sssssssssssssssssss',
        $fields['last_name'], $fields['first_name'], $fields['middle_name'],
        $fields['birth_date'], $fields['sex'], $fields['civil_status'],
        $fields['contact_number'], $fields['email'], $fields['home_address'],
        $fields['guardian_name'], $fields['guardian_relationship'], $fields['guardian_contact'],
        $fields['guardian_id_type'], $fields['guardian_id_number'], $fields['id_verified_by'],
        $fields['program'], $fields['year_level'], $fields['start_term'], $fields['applicant_type']
    );
    $stmt->execute();
    $applicant_id = $stmt->insert_id;
    $stmt->close();
 
    // Insert each academic history row, linked by applicant_id
    $histStmt = $conn->prepare("
        INSERT INTO applicant_school_history
            (applicant_id, school_name, school_address, school_year, school_strand, school_gpa)
        VALUES (?,?,?,?,?,?)
    ");
    foreach ($history as $row) {
        $histStmt->bind_param(
            'isssss', // applicant_id(int), school, address, year, strand, gpa — all strings except the int id
            $applicant_id, $row['school'], $row['address'], $row['year'], $row['strand'], $row['gpa']
        );
        $histStmt->execute();
    }
    $histStmt->close();
 
    // Insert submitted documents, linked by applicant_id
    $docStmt = $conn->prepare("
        INSERT INTO applicant_documents (applicant_id, document_name)
        VALUES (?, ?)
    ");
    foreach ($docs_submitted as $doc) {
        $docStmt->bind_param('is', $applicant_id, $doc);
        $docStmt->execute();
    }
    $docStmt->close();
 
    $conn->close();
}
*/
// ============================================================
 ?>