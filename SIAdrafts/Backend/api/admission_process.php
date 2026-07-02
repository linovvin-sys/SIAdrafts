<?php
session_start();
require '../db.php'; 
require 'validation_rules.php'; 

$db   = new Database();
$conn = $db->connect();

header('Content-Type: application/json');

if (empty($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'errors' => ['Unauthorized.']]);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'errors' => ['Invalid request method.']]);
    exit;
}   

// look up the logged-in staff member's StaffID (format YYYY-NNNN)
// so we can record "verified by" using the StaffID, not the raw user_id
$verifying_staff_id = null;
$staffStmt = $conn->prepare("SELECT staff_id FROM users WHERE user_id = ? LIMIT 1");
$staffStmt->bind_param('i', $_SESSION['user_id']);
$staffStmt->execute();
$staffRow = $staffStmt->get_result()->fetch_assoc();
$staffStmt->close();

if (!$staffRow || empty($staffRow['staff_id'])) {
    http_response_code(500);
    echo json_encode(['success' => false, 'errors' => ['Your account is missing a StaffID. Contact an administrator.']]);
    exit;
}
$verifying_staff_id = $staffRow['staff_id'];

// helper
function clean($value) {
    return htmlspecialchars(trim($value ?? ''), ENT_QUOTES, 'UTF-8');
}
/* id generator */
function generate_student_id(mysqli $conn): string {
    $year = date('Y');

    $stmt = $conn->prepare(
        "SELECT student_id FROM applicants
         WHERE student_id LIKE ?
         ORDER BY student_id DESC
         LIMIT 1"
    );
    $like = $year . '-%';
    $stmt->bind_param('s', $like);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if ($row && preg_match('/^(\d{4})-(\d{5})$/', $row['student_id'], $m)) {
        $next_number = (int)$m[2] + 1;
    } else {
        $next_number = 1;
    }

    $padded = str_pad((string)$next_number, 5, '0', STR_PAD_LEFT);

    return "{$year}-{$padded}";
}

//  collect single-value fields 
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
    'id_verified_by' => $verifying_staff_id,

    'course_id'      => (int)($_POST['course_id'] ?? 0),
    'year_level'     => clean($_POST['year_level'] ?? ''),
    'start_term'     => clean($_POST['start_term'] ?? ''),
    'applicant_type' => clean($_POST['applicant_type'] ?? ''),
];

//  collect repeatable academic history rows 
$school_names   = $_POST['school_name'] ?? [];
$school_address = $_POST['school_address'] ?? [];
$school_year    = $_POST['school_year'] ?? [];
$school_strand  = $_POST['school_strand'] ?? [];
$school_gpa     = $_POST['school_gpa'] ?? [];

$history = [];
for ($i = 0; $i < count($school_names); $i++) {
    // Skip fully-empty rows 
    if (trim($school_names[$i] ?? '') === '') continue;
    $history[] = [
        'school'  => clean($school_names[$i] ?? ''),
        'address' => clean($school_address[$i] ?? ''),
        'year'    => clean($school_year[$i] ?? ''),
        'strand'  => clean($school_strand[$i] ?? ''),
        'gpa'     => clean($school_gpa[$i] ?? ''),
    ];
}

//  collect document checklist 
$docs_submitted = array_map('clean', $_POST['docs'] ?? []);

$required_docs = [
    'Form 137 / SHS Card',
    'Certificate of Good Moral',
    'Birth Certificate (PSA)',
    '2x2 ID Photos',
];

$missing_required = array_diff($required_docs, $docs_submitted);

//  validation 
$errors = [];

$required_fields = required_field_labels();

foreach ($required_fields as $key => $label) {
    if ($fields[$key] === '') {
        $errors[] = $label . ' is required.';
    }
}

//  format validation 

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

// Resolve course_id → course_name (this becomes applicants.program)
$fields['program'] = '';
if ($fields['course_id'] > 0) {
    $courseStmt = $conn->prepare("SELECT course_name FROM course WHERE course_id = ?");
    $courseStmt->bind_param('i', $fields['course_id']);
    $courseStmt->execute();
    $courseRow = $courseStmt->get_result()->fetch_assoc();
    $courseStmt->close();

    if ($courseRow) {
        $fields['program'] = $courseRow['course_name'];
    }
}

if ($fields['course_id'] <= 0 || $fields['program'] === '') {
    $errors[] = 'Please select a valid program.';
}

// Program must match an actual course — dropdown can be tampered with client-side
$courseStmt = $conn->prepare("SELECT COUNT(*) FROM course WHERE course_name = ?");
$courseStmt->bind_param('s', $fields['program']);
$courseStmt->execute();
$validCourse = (int)$courseStmt->get_result()->fetch_row()[0];
$courseStmt->close();

if ($fields['program'] !== '' && $validCourse === 0) {
    $errors[] = 'Selected program is not a valid course.';
}

foreach ($check as $error) {
    if ($error !== null) $errors[] = $error;
}

// Academic history rows  

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


$applicant_id = null;
$student_id_value = null;

if (empty($errors)) {

    // idgenerator
    $student_id_value = generate_student_id($conn);

    
    $stmt = $conn->prepare("
        INSERT INTO applicants
            (student_id, last_name, first_name, middle_name, birth_date, sex, civil_status,
            contact_number, email, home_address,
            guardian_name, guardian_relationship, guardian_contact,
            guardian_id_type, guardian_id_number, id_verified_by,
            program, course_id, year_level, start_term, applicant_type, created_at)
        VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?, NOW())
    ");

    if (!$stmt) {
        $errors[] = 'Database error (applicants insert): ' . $conn->error;
    } else {
        $stmt->bind_param(
            'sssssssssssssssssisss',
            $student_id_value,
            $fields['last_name'], $fields['first_name'], $fields['middle_name'],
            $fields['birth_date'], $fields['sex'], $fields['civil_status'],
            $fields['contact_number'], $fields['email'], $fields['home_address'],
            $fields['guardian_name'], $fields['guardian_relationship'], $fields['guardian_contact'],
            $fields['guardian_id_type'], $fields['guardian_id_number'], $fields['id_verified_by'],
            $fields['program'], $fields['course_id'], $fields['year_level'], $fields['start_term'], $fields['applicant_type']
        );

        if (!$stmt->execute()) {
            $errors[] = 'Database error (applicants insert): ' . $stmt->error;
        } else {
            $applicant_id = $stmt->insert_id;
        }
        $stmt->close();
    }

    // acad history insert
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

    // document insert
    if ($applicant_id && empty($errors) && !empty($docs_submitted)) {
        $docStmt = $conn->prepare("
            INSERT INTO applicant_documents (applicant_id, document_name, status)
            VALUES (?, ?, 'submitted')
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



if (!empty($errors)) {
    http_response_code(422); 
    echo json_encode([
        'success' => false,
        'errors'  => $errors,
    ]);
    exit;
}

echo json_encode([
    'success'       => true,
    'applicant_id'  => $applicant_id,
    'student_id'    => $student_id_value,
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