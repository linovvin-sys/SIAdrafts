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

// look up the logged-in staff member's StaffID (format YYYY-NNNN), same as before
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

$staff_id_error = validate_staff_id($staffRow['staff_id']);
if ($staff_id_error !== null) {
    http_response_code(500);
    echo json_encode(['success' => false, 'errors' => ['Your account\'s StaffID is malformed (' . $staffRow['staff_id'] . '). Contact an administrator.']]);
    exit;
}
$verifying_staff_id = $staffRow['staff_id'];

$reference_id = trim($_POST['reference_id'] ?? '');
$ref_error = validate_reference_id($reference_id);
if ($reference_id === '' || $ref_error !== null) {
    http_response_code(422);
    echo json_encode(['success' => false, 'errors' => [$ref_error ?? 'Reference ID is required.']]);
    exit;
}

$docs_submitted = array_map('htmlspecialchars', $_POST['docs'] ?? []);

$required_docs = [
    'Form 137 / SHS Card',
    'Certificate of Good Moral',
    'Birth Certificate (PSA)',
    '2x2 ID Photos',
];
$missing_required = array_diff($required_docs, $docs_submitted);

$errors = [];
if (!empty($missing_required)) {
    $errors[] = 'Missing required documents: ' . implode(', ', $missing_required) . '.';
}

// look up the applicant, and lock the row for the duration of this
// transaction so two staff members can't confirm the same reference ID twice
$conn->begin_transaction();

$stmt = $conn->prepare("
    SELECT applicant_id, first_name, middle_name, last_name, program, year_level,
           guardian_name, guardian_relationship, admission_status
    FROM applicants
    WHERE reference_id = ?
    LIMIT 1
    FOR UPDATE
");
$stmt->bind_param('s', $reference_id);
$stmt->execute();
$applicant = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$applicant) {
    $conn->rollback();
    http_response_code(404);
    echo json_encode(['success' => false, 'errors' => ['No application found with that reference ID.']]);
    exit;
}

if ($applicant['admission_status'] === 'verified') {
    $conn->rollback();
    http_response_code(422);
    echo json_encode(['success' => false, 'errors' => ['This reference ID has already been verified.']]);
    exit;
}

if (!empty($errors)) {
    $conn->rollback();
    http_response_code(422);
    echo json_encode(['success' => false, 'errors' => $errors]);
    exit;
}

$applicant_id = (int)$applicant['applicant_id'];

$upd = $conn->prepare("
    UPDATE applicants
    SET id_verified_by = ?, admission_status = 'verified', verified_at = NOW()
    WHERE applicant_id = ?
");
$upd->bind_param('si', $verifying_staff_id, $applicant_id);

if (!$upd->execute()) {
    $conn->rollback();
    http_response_code(500);
    echo json_encode(['success' => false, 'errors' => ['Database error (verification): ' . $upd->error]]);
    exit;
}
$upd->close();

$docStmt = $conn->prepare("
    INSERT INTO applicant_documents (applicant_id, document_name, status, verified_by)
    VALUES (?, ?, 'submitted', ?)
");
foreach ($docs_submitted as $doc) {
    $docStmt->bind_param('isi', $applicant_id, $doc, $_SESSION['user_id']);
    if (!$docStmt->execute()) {
        $conn->rollback();
        http_response_code(500);
        echo json_encode(['success' => false, 'errors' => ['Database error (documents): ' . $docStmt->error]]);
        exit;
    }
}
$docStmt->close();

$conn->commit();
$conn->close();

echo json_encode([
    'success' => true,
    'summary' => [
        'name'        => trim($applicant['first_name'] . ' ' . $applicant['middle_name'] . ' ' . $applicant['last_name']),
        'reference_id' => $reference_id,
        'program'     => $applicant['program'] . ' — Year ' . $applicant['year_level'],
        'guardian'    => $applicant['guardian_name'] . ' (' . $applicant['guardian_relationship'] . ')',
        'verified_by' => $verifying_staff_id,
        'documents'   => $docs_submitted,
    ],
]);