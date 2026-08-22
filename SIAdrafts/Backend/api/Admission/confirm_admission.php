<?php
session_start();
require '../../db.php';
require_once '../../roles.php';
require_once '../../require_role.php';
require_once '../../csrf.php';
require 'validation_rules.php';

$db   = new Database();
$conn = $db->connect();

header('Content-Type: application/json');

if (empty($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'errors' => ['Unauthorized.']]);
    exit;
}

// Admin is deliberately excluded — read-only monitoring only (see admission_confirm.php).
require_role([ROLE_ADMISSION], true);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'errors' => ['Invalid request method.']]);
    exit;
}

csrf_verify();

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

require '../../requirements.php';

$docs_submitted = array_map('htmlspecialchars', $_POST['docs'] ?? []);
$docs_later      = array_map('htmlspecialchars', $_POST['docs_later'] ?? []);

$labelToKey  = [];
$keyToGroup  = [];
foreach (REQUIREMENT_DEFINITIONS as $def) {
    $labelToKey[$def['label']] = $def['key'];
    $keyToGroup[$def['key']]   = $def['group'];
}
$submittedKeys = [];
foreach ($docs_submitted as $label) {
    if (isset($labelToKey[$label])) $submittedKeys[] = $labelToKey[$label];
}

// Critical groups (PSA/NSO, Good Moral) can only ever be satisfied by an
// actual submission — never trust "later" for these, even if a client
// sends one anyway (the UI never offers that option, but the server must
// not rely on that alone). Silently drop any critical-group entries here
// rather than accepting them as a deferred document.
$criticalGroups = critical_requirement_groups();
$laterKeys = [];
$validLaterLabels = [];
foreach ($docs_later as $label) {
    $key = $labelToKey[$label] ?? null;
    if ($key !== null && !in_array($keyToGroup[$key], $criticalGroups, true)) {
        $laterKeys[] = $key;
        $validLaterLabels[] = $label;
    }
}
// Everything downstream (the INSERT loop and the response summary) reads
// $docs_later — swap in the filtered list so a critical doc can never slip
// through as "will_submit_later" regardless of what the client sent.
$docs_later = $validLaterLabels;

// Critical groups are judged on submitted docs only; deferring one never
// satisfies it. Non-critical groups can be satisfied by either.
$blockingGroups = array_intersect(missing_requirement_groups($submittedKeys), $criticalGroups);

$errors = [];
if (!empty($blockingGroups)) {
    $missingLabels = [];
    foreach ($blockingGroups as $groupKey) {
        foreach (REQUIREMENT_DEFINITIONS as $def) {
            if ($def['group'] === $groupKey) { $missingLabels[] = $def['label']; break; }
        }
    }
    $errors[] = 'Missing required documents: ' . implode(', ', $missingLabels) . '.';
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

// A row for this exact document may already exist — most commonly the
// applicant declared it "will submit later" on the online form, and now
// walk-in is recording what actually happened. Update that row instead of
// inserting a second one, or Pending Documents ends up showing stale
// "still owed" entries for documents that were in fact already collected,
// or the same document listed twice.
function upsert_applicant_document(mysqli $conn, int $applicant_id, string $doc, string $status, ?int $verified_by): void
{
    $find = $conn->prepare("
        SELECT document_id FROM applicant_documents
        WHERE applicant_id = ? AND document_name = ?
        ORDER BY document_id DESC LIMIT 1
    ");
    $find->bind_param('is', $applicant_id, $doc);
    $find->execute();
    $existing = $find->get_result()->fetch_assoc();
    $find->close();

    if ($existing) {
        $upd = $conn->prepare("
            UPDATE applicant_documents
            SET status = ?, verified_by = ?, uploaded_at = NOW()
            WHERE document_id = ?
        ");
        $upd->bind_param('sii', $status, $verified_by, $existing['document_id']);
        $upd->execute();
        $upd->close();
    } else {
        $ins = $conn->prepare("
            INSERT INTO applicant_documents (applicant_id, document_name, status, verified_by)
            VALUES (?, ?, ?, ?)
        ");
        $ins->bind_param('issi', $applicant_id, $doc, $status, $verified_by);
        $ins->execute();
        $ins->close();
    }
}

foreach ($docs_submitted as $doc) {
    upsert_applicant_document($conn, $applicant_id, $doc, 'submitted', (int)$_SESSION['user_id']);
}

// Deferred docs get no verified_by — nobody's actually checked them yet,
// that only happens when mark_document_received.php later flips them over.
foreach ($docs_later as $doc) {
    upsert_applicant_document($conn, $applicant_id, $doc, 'will_submit_later', null);
}

$credited_subject_ids = array_map('intval', $_POST['credited_subjects'] ?? []);

if (!empty($credited_subject_ids)) {
    $creditStmt = $conn->prepare("
        INSERT INTO applicant_subject_credit (applicant_id, subject_id, credited_by)
        VALUES (?, ?, ?)
        ON DUPLICATE KEY UPDATE credited_by = VALUES(credited_by)
    ");
    foreach ($credited_subject_ids as $sid) {
        $creditStmt->bind_param('iis', $applicant_id, $sid, $verifying_staff_id);
        if (!$creditStmt->execute()) {
            $conn->rollback();
            http_response_code(500);
            echo json_encode(['success' => false, 'errors' => ['Database error (subject credits): ' . $creditStmt->error]]);
            exit;
        }
    }
    $creditStmt->close();
}
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
        'documents_later' => $docs_later,
        'credited_subjects' => count($credited_subject_ids),
    ],
]);