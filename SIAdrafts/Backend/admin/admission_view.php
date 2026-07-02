<?php

require_once __DIR__ . '/../db.php';

$db = new Database();
$conn = $db->connect();

$applicant      = null;
$schoolHistory  = [];
$documents      = [];

$studentId = $_GET['id'] ?? null;

if ($studentId) {

    $stmt = $conn->prepare("
        SELECT
            a.*,
            c.course_name,
            c.course_code
        FROM applicants a
        LEFT JOIN course c ON a.course_id = c.course_id
        WHERE a.student_id = ?
        LIMIT 1
    ");
    $stmt->bind_param('s', $studentId);
    $stmt->execute();
    $applicant = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if ($applicant) {

        $applicantId = $applicant['applicant_id'];

        // Educational background
        $stmt = $conn->prepare("
            SELECT *
            FROM applicant_school_history
            WHERE applicant_id = ?
            ORDER BY history_id DESC
        ");
        $stmt->bind_param('i', $applicantId);
        $stmt->execute();
        $res = $stmt->get_result();
        while ($row = $res->fetch_assoc()) {
            $schoolHistory[] = $row;
        }
        $stmt->close();

        // Submitted documents
        $stmt = $conn->prepare("
            SELECT *
            FROM applicant_documents
            WHERE applicant_id = ?
            ORDER BY document_id ASC
        ");
        $stmt->bind_param('i', $applicantId);
        $stmt->execute();
        $res = $stmt->get_result();
        while ($row = $res->fetch_assoc()) {
            $documents[] = $row;
        }
        $stmt->close();
    }
}