<?php

require_once __DIR__ . '/../db.php';

$db = new Database();
$conn = $db->connect();

$users = [];

$sql = "
SELECT
    u.user_id,
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
    u.updated_at AS last_login

FROM users u

INNER JOIN roles r
    ON u.role_id = r.role_id

INNER JOIN statuses s
    ON u.status_id = s.status_id

ORDER BY u.first_name, u.last_name
";

$result = $conn->query($sql);

if ($result) {
    while ($row = $result->fetch_assoc()) {
        $users[] = $row;
    }
}