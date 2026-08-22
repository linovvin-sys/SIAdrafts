<?php
session_start();
require_once '../../db.php';
require_once '../../roles.php';
require_once '../../require_role.php';
require_once '../../csrf.php';
require_once '../../subject_course.php';
require_once '../../curriculum_import.php';
header('Content-Type: application/json');

if (empty($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized.']);
    exit;
}

require_registrar_tier(REGISTRAR_TIER_STAFF, true);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed.']);
    exit;
}

csrf_verify();

if (empty($_FILES['curriculum_csv']['tmp_name']) || $_FILES['curriculum_csv']['error'] !== UPLOAD_ERR_OK) {
    echo json_encode(['error' => 'Please choose a CSV file to upload.']);
    exit;
}

$db   = new Database();
$conn = $db->connect();

// Never trust whatever the client echoed back from the preview step —
// re-parse and re-validate the file from scratch, exactly as preview did.
$result = parse_and_validate_curriculum_csv($conn, $_FILES['curriculum_csv']['tmp_name']);

if (!empty($result['file_error'])) {
    $db->close();
    echo json_encode(['error' => $result['file_error']]);
    exit;
}

if ($result['has_errors']) {
    $db->close();
    echo json_encode(['error' => 'This file still has unresolved errors — fix them and re-upload before importing.']);
    exit;
}

$rows = $result['rows'];
if (empty($rows)) {
    $db->close();
    echo json_encode(['error' => 'The file has no subject rows to import.']);
    exit;
}

// Head Registrar submissions go live immediately; Registrar Staff
// submissions need approval first — same rule as the single Add Subject form.
$isHeadRegistrar = current_user_is(['Head Registrar']);
$status          = $isHeadRegistrar ? 'Approved' : 'Pending';
$requested_by    = (int)$_SESSION['user_id'];

$conn->begin_transaction();
try {
    $insertStmt = $conn->prepare(
        "INSERT INTO subject (subject_code, subject_name, units, course_id, category_id, year_level, semester, status, requested_by)
         VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)"
    );
    $crossListStmt = $conn->prepare(
        "INSERT INTO subject_course (subject_id, course_id) VALUES (?, ?)"
    );
    $prereqStmt = $conn->prepare("UPDATE subject SET prereq_id = ? WHERE subject_id = ?");

    $inserted = 0;
    $crossListed = 0;
    $newSubjectIdByCode = []; // subject_code (upper) => newly created subject_id

    foreach ($rows as $r) {
        if ($r['action'] !== 'insert') continue;
        $insertStmt->bind_param(
            'ssdiiiisi',
            $r['subject_code'], $r['subject_name'], $r['units'], $r['course_id'],
            $r['category_id'], $r['year_level'], $r['semester'], $status, $requested_by
        );
        $insertStmt->execute();
        $newSubjectIdByCode[strtoupper($r['subject_code'])] = $conn->insert_id;
        $inserted++;
    }
    $insertStmt->close();

    foreach ($rows as $r) {
        if ($r['action'] !== 'cross_list') continue;
        $crossListStmt->bind_param('ii', $r['existing_subject_id'], $r['course_id']);
        $crossListStmt->execute();
        $crossListed++;
    }
    $crossListStmt->close();

    // Second pass: resolve prerequisite_code -> prereq_id, now that every
    // subject created in this same batch has a real subject_id.
    $existingCodeToId = [];
    $codeRes = $conn->query("SELECT subject_id, subject_code FROM subject");
    while ($row = $codeRes->fetch_assoc()) $existingCodeToId[strtoupper($row['subject_code'])] = (int)$row['subject_id'];

    foreach ($rows as $r) {
        if ($r['action'] !== 'insert' || $r['prerequisite_code'] === '') continue;
        $prereqKey = strtoupper($r['prerequisite_code']);
        $prereqId = $existingCodeToId[$prereqKey] ?? null;
        if ($prereqId === null) continue; // already validated to exist; defensive only
        $newSubjectId = $newSubjectIdByCode[strtoupper($r['subject_code'])];
        $prereqStmt->bind_param('ii', $prereqId, $newSubjectId);
        $prereqStmt->execute();
    }
    $prereqStmt->close();

    $conn->commit();
    $db->close();

    echo json_encode([
        'success' => true,
        'inserted' => $inserted,
        'cross_listed' => $crossListed,
        'status' => $status,
        'message' => $status === 'Pending'
            ? "Imported {$inserted} new subject(s) and {$crossListed} cross-listing(s), submitted for Head Registrar approval."
            : "Imported {$inserted} new subject(s) and {$crossListed} cross-listing(s).",
    ]);
} catch (mysqli_sql_exception $e) {
    $conn->rollback();
    $db->close();
    http_response_code(500);
    echo json_encode(['error' => 'Import failed, nothing was saved: ' . $e->getMessage()]);
}
