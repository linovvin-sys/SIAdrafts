<?php

require_once __DIR__ . '/../db.php';
require_once __DIR__ . '/../roles.php';
require_once __DIR__ . '/../require_role.php';
require_role([ROLE_ADMIN, ROLE_HEAD_REGISTRAR], true);

// A Head Registrar only manages the staff branches below them (see
// ROLES_HEAD_REGISTRAR_MANAGEABLE) — Admin and other Head Registrar
// accounts never even appear in their list, let alone become editable.
$isHeadRegistrar = current_user_is([ROLE_HEAD_REGISTRAR]);

$db = new Database();
$conn = $db->connect();

$users = [];

$sql = "
SELECT
    u.user_id,
    u.staff_id,
    u.first_name,
    u.middle_name,
    u.last_name,

    CONCAT(
        u.first_name,
        ' ',
        IFNULL(CONCAT(u.middle_name, ' '), ''),
        u.last_name
    ) AS full_name,

    u.email,
    u.username,
    u.phone_number,
    r.role_name,
    s.status_name,
    u.last_login

FROM users u

INNER JOIN roles r
    ON u.role_id = r.role_id

INNER JOIN statuses s
    ON u.status_id = s.status_id
" . ($isHeadRegistrar
    ? "WHERE r.role_name IN ('" . implode("','", array_map(fn($r) => $conn->real_escape_string($r), ROLES_HEAD_REGISTRAR_MANAGEABLE)) . "')"
    : ""
) . "
ORDER BY u.first_name, u.last_name
";

$result = $conn->query($sql);

if ($result) {
    while ($row = $result->fetch_assoc()) {
        $users[] = $row;
    }
}

/* ==========================
   Summary stats for the header cards
   (aggregated from $users — no extra queries needed)
========================== */

$userStats = [
    'total'    => count($users),
    'active'   => 0,
    'inactive' => 0,
    'roles'    => [],
];

foreach ($users as $u) {
    if (strtolower($u['status_name']) === 'active') {
        $userStats['active']++;
    } else {
        $userStats['inactive']++;
    }
    $role = $u['role_name'];
    $userStats['roles'][$role] = ($userStats['roles'][$role] ?? 0) + 1;
}