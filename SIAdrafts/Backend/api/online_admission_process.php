<?php
/**
 * Public online admission intake.
 *
 * Deliberately has NO session_start() / login check — this is meant to be
 * filled out by the applicant themselves before ever visiting campus.
 * It never sets id_verified_by and never touches documents: those only
 * happen in person, at the walk-in verification step (confirm_admission.php).
 */

require '../db.php';
require 'validation_rules.php';
require '../requirements.php';
require '../settings.php';
require '../mailer.php';
require '../rate_limit.php';

$db   = new Database();
$conn = $db->connect();

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'errors' => ['Invalid request method.']]);
    exit;
}

// This is a public, unauthenticated form — cap submissions per IP so it
// can't be used to spam-fill the applicants table or hammer the mailer.
if (!rate_limit_check('online_admission', 5, 3600)) {
    http_response_code(429);
    echo json_encode(['success' => false, 'errors' => ['Too many submissions from this connection. Please try again later.']]);
    exit;
}

// Basic bot deterrent: a hidden field that real applicants never fill in.
// Pair with a real CAPTCHA (e.g. reCAPTCHA) before going live publicly.
if (!empty($_POST['website'])) {
    http_response_code(422);
    echo json_encode(['success' => false, 'errors' => ['Submission rejected.']]);
    exit;
}

function clean($value) {
    return htmlspecialchars(trim($value ?? ''), ENT_QUOTES, 'UTF-8');
}

/* reference id generator — REF-NNNNN-NNN
   NNNNN = global running sequence, never resets, taken from the highest
           value seen across every reference_id so far.
   NNN   = per-day counter, resets to 001 at the start of each calendar day
           (scoped by applicants.created_at). */
function generate_reference_id(mysqli $conn): string {
    // Global running sequence — the middle group.
    $seqStmt = $conn->prepare(
        "SELECT reference_id FROM applicants
         WHERE reference_id LIKE 'REF-%'
         ORDER BY reference_id DESC
         LIMIT 1"
    );
    $seqStmt->execute();
    $seqRow = $seqStmt->get_result()->fetch_assoc();
    $seqStmt->close();

    $next_seq = 1;
    if ($seqRow && preg_match('/^REF-(\d{5})-(\d{3})$/', $seqRow['reference_id'], $m)) {
        $next_seq = (int)$m[1] + 1;
    }

    // Per-day counter — the trailing group, scoped to today's rows only.
    $dayStmt = $conn->prepare(
        "SELECT reference_id FROM applicants
         WHERE reference_id LIKE 'REF-%'
           AND DATE(created_at) = CURDATE()
         ORDER BY created_at DESC
         LIMIT 1"
    );
    $dayStmt->execute();
    $dayRow = $dayStmt->get_result()->fetch_assoc();
    $dayStmt->close();

    $next_daily = 1;
    if ($dayRow && preg_match('/^REF-(\d{5})-(\d{3})$/', $dayRow['reference_id'], $m)) {
        $next_daily = (int)$m[2] + 1;
    }

    $seq_padded   = str_pad((string)$next_seq, 5, '0', STR_PAD_LEFT);
    $daily_padded = str_pad((string)$next_daily, 3, '0', STR_PAD_LEFT);

    return "REF-{$seq_padded}-{$daily_padded}";
}

//  collect single-value fields (same shape as the old admission_process.php,
//  minus id_verified_by — nobody has verified anything yet)
$fields = [
    'last_name'        => clean($_POST['last_name'] ?? ''),
    'first_name'       => clean($_POST['first_name'] ?? ''),
    'middle_name'      => clean($_POST['middle_name'] ?? ''),
    'birth_date'       => clean($_POST['birth_date'] ?? ''),
    'sex'              => clean($_POST['sex'] ?? ''),
    'nationality'      => clean($_POST['nationality'] ?? ''),
    'civil_status'     => clean($_POST['civil_status'] ?? ''),
    'contact_number'   => clean($_POST['contact_number'] ?? ''),
    'email'            => clean($_POST['email'] ?? ''),
    'home_address'     => clean($_POST['home_address'] ?? ''),

    'guardian_name'         => clean($_POST['guardian_name'] ?? ''),
    'guardian_relationship' => clean($_POST['guardian_relationship'] ?? ''),
    'guardian_contact'      => clean($_POST['guardian_contact'] ?? ''),
    'guardian_id_type'      => clean($_POST['guardian_id_type'] ?? ''),
    'guardian_id_number'    => clean($_POST['guardian_id_number'] ?? ''),

    'course_id'      => (int)($_POST['course_id'] ?? 0),
    'year_level'     => clean($_POST['year_level'] ?? ''),
    'applicant_type' => clean($_POST['applicant_type'] ?? ''),
];

//  repeatable academic history rows
$school_names   = $_POST['school_name'] ?? [];
$school_address = $_POST['school_address'] ?? [];
$school_year    = $_POST['school_year'] ?? [];
$school_strand  = $_POST['school_strand'] ?? [];
$school_gpa     = $_POST['school_gpa'] ?? [];

$history = [];
for ($i = 0; $i < count($school_names); $i++) {
    if (trim($school_names[$i] ?? '') === '') continue;
    $history[] = [
        'school'  => clean($school_names[$i] ?? ''),
        'address' => clean($school_address[$i] ?? ''),
        'year'    => clean($school_year[$i] ?? ''),
        'strand'  => clean($school_strand[$i] ?? ''),
        'gpa'     => clean($school_gpa[$i] ?? ''),
    ];
}

//  validation — same rules as before, minus anything document/staff related
$errors = [];

$required_fields = required_field_labels();
unset($required_fields['id_verified_by']); // not applicable — no staff present
unset($required_fields['start_term']); // superseded by automatic school_year/semester (see below)

foreach ($required_fields as $key => $label) {
    if (($fields[$key] ?? '') === '') {
        $errors[] = $label . ' is required.';
    }
}

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
    validate_nationality($fields['nationality']),
    validate_relationship($fields['guardian_relationship']),
];

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

//  requirements: each is either an uploaded file or "submit at campus" —
//  never required, never blocks submission.
$requirementRows = []; // ['key' => ..., 'status' => 'submitted_online'|'will_submit_later', 'file_path' => ...|null]
$uploadDir = __DIR__ . '/../uploads/requirements/';

foreach (REQUIREMENT_DEFINITIONS as $req) {
    $key = $req['key'];
    $later = ($_POST['requirement_status'][$key] ?? '') === 'later';

    if ($later) {
        $requirementRows[] = ['key' => $key, 'status' => 'will_submit_later', 'file_path' => null];
        continue;
    }

    if (!empty($_FILES['requirement_file']['name'][$key])) {
        $tmpPath  = $_FILES['requirement_file']['tmp_name'][$key];
        $errCode  = $_FILES['requirement_file']['error'][$key];
        $origName = $_FILES['requirement_file']['name'][$key];
        $fileError = null;

        if ($errCode !== UPLOAD_ERR_OK) {
            $fileError = 'Upload failed for ' . $req['label'] . '.';
        } else {
            $mime = mime_content_type($tmpPath);
            // The stored extension is derived from the validated MIME type,
            // never from $origName — a client can name a file anything
            // (e.g. "photo.jpg.php") and mime_content_type() alone doesn't
            // stop that name from being trusted downstream.
            $allowedExtByMime = [
                'application/pdf' => 'pdf',
                'image/jpeg'      => 'jpg',
                'image/png'       => 'png',
            ];
            if (!isset($allowedExtByMime[$mime])) {
                $fileError = $req['label'] . ' must be a PDF, JPG, or PNG file.';
            } elseif (filesize($tmpPath) > 5 * 1024 * 1024) {
                $fileError = $req['label'] . ' file is too large (max 5MB).';
            } else {
                $ext = $allowedExtByMime[$mime];
                $storedName = $key . '_' . bin2hex(random_bytes(8)) . '.' . $ext;
                if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);
                if (move_uploaded_file($tmpPath, $uploadDir . $storedName)) {
                    $requirementRows[] = ['key' => $key, 'status' => 'submitted_online', 'file_path' => 'requirements/' . $storedName];
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

$fields['school_year'] = get_setting('current_school_year') ?? '';
$fields['semester']    = (int)(get_setting('current_semester') ?? 1);

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

if (!empty($errors)) {
    http_response_code(422);
    echo json_encode(['success' => false, 'errors' => $errors]);
    exit;
}

//  insert, with a small retry loop in case two applicants collide on the
//  same generated reference_id (rare, but the online form has no login to
//  naturally serialize requests the way the staff form does)
$applicant_id  = null;
$reference_id  = null;
$attempts_left = 5;

while ($attempts_left-- > 0) {
    $reference_id = generate_reference_id($conn);

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

    if (!$stmt) {
        http_response_code(500);
        echo json_encode(['success' => false, 'errors' => ['Database error: ' . $conn->error]]);
        exit;
    }

    $stmt->bind_param(
        'sssssssssssssssssissisis',
        $reference_id,
        $fields['last_name'], $fields['first_name'], $fields['middle_name'],
        $fields['birth_date'], $fields['sex'], $fields['nationality'], $fields['civil_status'],
        $fields['contact_number'], $fields['email'], $fields['home_address'],
        $fields['guardian_name'], $fields['guardian_relationship'], $fields['guardian_contact'],
        $fields['guardian_id_type'], $fields['guardian_id_number'],
        $fields['program'], $fields['course_id'], $fields['year_level'], $fields['school_year'], $fields['semester'],
        $fields['applicant_type'], $possible_duplicate_student_id, $duplicate_match_status
    );

    if ($stmt->execute()) {
        $applicant_id = $stmt->insert_id;
        $stmt->close();
        break;
    }

    $duplicate = ($conn->errno === 1062);
    $stmt->close();

    if (!$duplicate || $attempts_left === 0) {
        http_response_code(500);
        echo json_encode(['success' => false, 'errors' => ['Database error (applicants insert): ' . $conn->error]]);
        exit;
    }
    // else: reference_id collided, loop and generate the next one
}

$histStmt = $conn->prepare("
    INSERT INTO applicant_school_history
        (applicant_id, school_name, school_address, school_year, school_strand, school_gpa)
    VALUES (?,?,?,?,?,?)
");
foreach ($history as $row) {
    $histStmt->bind_param(
        'isssss',
        $applicant_id, $row['school'], $row['address'], $row['year'], $row['strand'], $row['gpa']
    );
    $histStmt->execute();
}
$histStmt->close();

if (!empty($requirementRows)) {
    $reqStmt = $conn->prepare("
        INSERT INTO applicant_documents (applicant_id, document_name, file_path, status, source)
        VALUES (?, ?, ?, ?, 'applicant')
    ");
    foreach ($requirementRows as $row) {
        $label = '';
        foreach (REQUIREMENT_DEFINITIONS as $def) {
            if ($def['key'] === $row['key']) { $label = $def['label']; break; }
        }
        $reqStmt->bind_param('isss', $applicant_id, $label, $row['file_path'], $row['status']);
        $reqStmt->execute();
    }
    $reqStmt->close();
}

$conn->close();

// Email the applicant a printable admission slip with their reference ID.
// A mail failure must never fail the application itself -- the applicant
// already has the reference number on-screen; the email is a keepsake copy.
$applicantName = trim($fields['first_name'] . ' ' . $fields['middle_name'] . ' ' . $fields['last_name']);
$emailSent = false;
if (!empty($fields['email'])) {
    $e = fn($v) => htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8');
    $slipHtml = '
    <div style="margin:0;padding:24px;background:#F7F4EC;font-family:Helvetica,Arial,sans-serif;color:#1F2E28;">
      <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="max-width:560px;margin:0 auto;">
        <tr><td style="padding-bottom:18px;text-align:center;">
          <div style="font-family:Georgia,serif;font-size:22px;font-weight:600;">Edu<span style="color:#A47B3F;">School</span></div>
          <div style="font-size:12px;color:rgba(31,46,40,0.8);letter-spacing:1px;text-transform:uppercase;margin-top:4px;">Admission Slip</div>
        </td></tr>
        <tr><td style="background:#FFFFFF;border:1px solid rgba(31,46,40,0.14);border-radius:14px;padding:28px 30px;">
          <p style="margin:0 0 16px;font-size:15px;">Hi ' . $e($fields['first_name']) . ', we received your application. Your reference number is:</p>
          <div style="text-align:center;background:#F3ECDD;border:1px dashed #A47B3F;border-radius:10px;padding:16px;margin-bottom:22px;">
            <span style="font-family:Courier,monospace;font-size:24px;font-weight:bold;letter-spacing:2px;">' . $e($reference_id) . '</span>
          </div>
          <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="font-size:14px;border-top:1px solid rgba(31,46,40,0.14);">
            <tr><td style="padding:10px 0;color:rgba(31,46,40,0.8);border-bottom:1px solid rgba(31,46,40,0.08);">Applicant</td><td style="padding:10px 0;text-align:right;font-weight:bold;border-bottom:1px solid rgba(31,46,40,0.08);">' . $e($applicantName) . '</td></tr>
            <tr><td style="padding:10px 0;color:rgba(31,46,40,0.8);border-bottom:1px solid rgba(31,46,40,0.08);">Program</td><td style="padding:10px 0;text-align:right;font-weight:bold;border-bottom:1px solid rgba(31,46,40,0.08);">' . $e($fields['program'] . ' — ' . $fields['year_level']) . '</td></tr>
            <tr><td style="padding:10px 0;color:rgba(31,46,40,0.8);border-bottom:1px solid rgba(31,46,40,0.08);">Term</td><td style="padding:10px 0;text-align:right;font-weight:bold;border-bottom:1px solid rgba(31,46,40,0.08);">' . $e($fields['school_year'] . ' — Semester ' . $fields['semester']) . '</td></tr>
            <tr><td style="padding:10px 0;color:rgba(31,46,40,0.8);">Date filed</td><td style="padding:10px 0;text-align:right;font-weight:bold;">' . $e(date('F j, Y')) . '</td></tr>
          </table>
          <p style="margin:22px 0 0;font-size:13px;color:rgba(31,46,40,0.8);line-height:1.6;">
            Print this slip or show it on your phone when you visit campus, together with your
            Certificate of Good Moral, PSA birth certificate, Form 138, and 2x2 photos,
            to complete document verification and enrollment.
          </p>
        </td></tr>
        <tr><td style="padding-top:16px;text-align:center;font-size:11px;color:rgba(31,46,40,0.6);">
          This is an automated message from the EduSchool admissions office — replies are not monitored.
        </td></tr>
      </table>
    </div>';
    $emailSent = send_email(
        $fields['email'],
        'Your EduSchool Admission Slip — ' . $reference_id,
        $slipHtml
    );
}

echo json_encode([
    'success'      => true,
    'reference_id' => $reference_id,
    'email_sent'   => $emailSent,
    'summary'      => [
        'name'        => $applicantName,
        'program'     => $fields['program'] . ' — ' . $fields['year_level'],
        'school_year' => $fields['school_year'] . ' — Semester ' . $fields['semester'],
    ],
]);