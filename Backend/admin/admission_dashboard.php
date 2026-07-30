<?php

require_once __DIR__ . '/../db.php';
require_once __DIR__ . '/../roles.php';
require_once __DIR__ . '/../require_role.php';
require_role([ROLE_ADMISSION, ROLE_STAFF, ROLE_ADMIN], true);

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
