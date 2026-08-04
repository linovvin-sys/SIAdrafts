<?php
session_start();
header('Content-Type: application/json');
require_once '../db.php';
require_once '../roles.php';
require_once '../require_role.php';
require_once 'validation_rules.php';

if (empty($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized.']);
    exit;
}

require_role([ROLE_ADMISSION, ROLE_ADMIN], true);

$db   = new Database();
$conn = $db->connect();

$ref = trim($_GET['reference_id'] ?? '');
$ref_error = validate_reference_id($ref);

if ($ref === '' || $ref_error !== null) {
    http_response_code(422);
    echo json_encode(['error' => $ref_error ?? 'Reference ID is required.']);
    exit;
}

$stmt = $conn->prepare("
    SELECT applicant_id, reference_id, last_name, first_name, middle_name,
           birth_date, sex, nationality, civil_status, contact_number, email, home_address,
           guardian_name, guardian_relationship, guardian_contact,
           guardian_id_type, guardian_id_number,
           program, year_level, school_year, semester, applicant_type,
           admission_status, verified_at,
           possible_duplicate_student_id, duplicate_match_status,
           authorization_note, authorized_by, authorized_at, cleared_by, cleared_at
    FROM applicants
    WHERE reference_id = ?
    LIMIT 1
");
$stmt->bind_param('s', $ref);
$stmt->execute();
$applicant = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$applicant) {
    http_response_code(404);
    echo json_encode(['error' => 'No application found with that reference ID.']);
    exit;
}

$histStmt = $conn->prepare("
    SELECT school_name, school_address, school_year, school_strand, school_gpa
    FROM applicant_school_history
    WHERE applicant_id = ?
    ORDER BY history_id
");
$histStmt->bind_param('i', $applicant['applicant_id']);
$histStmt->execute();
$history = $histStmt->get_result()->fetch_all(MYSQLI_ASSOC);
$histStmt->close();

// Full detail (not just the name) so the walk-in checklist can show what
// was actually submitted online — a soft copy to view, or a note that the
// applicant already said they'd bring it in person.
$docStmt = $conn->prepare("
    SELECT document_id, document_name, status, file_path, uploaded_at
    FROM applicant_documents
    WHERE applicant_id = ? AND source = 'applicant'
");
$docStmt->bind_param('i', $applicant['applicant_id']);
$docStmt->execute();
$online_requirements = $docStmt->get_result()->fetch_all(MYSQLI_ASSOC);
$docStmt->close();

$conn->close();

$applicant['full_name'] = trim($applicant['first_name'] . ' ' . $applicant['middle_name'] . ' ' . $applicant['last_name']);
$applicant['history'] = $history;
$applicant['online_requirements'] = $online_requirements;

echo json_encode(['success' => true, 'applicant' => $applicant]);