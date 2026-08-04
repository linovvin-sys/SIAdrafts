<?php
// Single source of truth for who's allowed to message whom.
// Currently: Head Registrar (5) and Registrar Staff (6) can message
// across the two roles, and can also message peers within their own
// role (e.g. one Registrar Staff member to another).
// Add more allowed pairs here later if Treasury/Admission gets added.

// Named MSG_* (rather than ROLE_*) to avoid a fatal "constant already
// defined" collision with the ROLE_HEAD_REGISTRAR / ROLE_REGISTRAR_STAFF
// role-name string constants in ../roles.php (Task 4/9) — this file's
// constants are numeric users.role_id values, a different thing entirely.
const MSG_ROLE_HEAD_REGISTRAR  = 5;
const MSG_ROLE_REGISTRAR_STAFF = 6;

const ALLOWED_MESSAGE_ROLE_PAIRS = [
    [MSG_ROLE_HEAD_REGISTRAR, MSG_ROLE_REGISTRAR_STAFF],
    [MSG_ROLE_HEAD_REGISTRAR, MSG_ROLE_HEAD_REGISTRAR],
    [MSG_ROLE_REGISTRAR_STAFF, MSG_ROLE_REGISTRAR_STAFF],
];

function roles_can_message(int $roleA, int $roleB): bool {
    foreach (ALLOWED_MESSAGE_ROLE_PAIRS as [$r1, $r2]) {
        if (($roleA === $r1 && $roleB === $r2) || ($roleA === $r2 && $roleB === $r1)) {
            return true;
        }
    }
    return false;
}

// Given two user_ids, checks their actual current roles from the DB
// and returns true only if that pairing is allowed. Never trust a
// role passed in from the client — always re-derive from users.role_id.
function users_can_message(mysqli $conn, int $userA, int $userB): bool {
    if ($userA === $userB) return false;

    $stmt = $conn->prepare("SELECT user_id, role_id FROM users WHERE user_id IN (?, ?)");
    $stmt->bind_param('ii', $userA, $userB);
    $stmt->execute();
    $rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();

    if (count($rows) !== 2) return false;

    $roles = array_column($rows, 'role_id', 'user_id');
    return roles_can_message((int)$roles[$userA], (int)$roles[$userB]);
}

// Given one user_id, returns the list of user_ids they're allowed to
// message — i.e. everyone whose role forms an allowed pair with theirs.
function get_allowed_contacts(mysqli $conn, int $userId): array {
    $stmt = $conn->prepare("SELECT role_id FROM users WHERE user_id = ?");
    $stmt->bind_param('i', $userId);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    if (!$row) return [];

    $myRole = (int)$row['role_id'];
    $counterpartRoles = [];
    foreach (ALLOWED_MESSAGE_ROLE_PAIRS as [$r1, $r2]) {
        if ($myRole === $r1) $counterpartRoles[] = $r2;
        if ($myRole === $r2) $counterpartRoles[] = $r1;
    }
    $counterpartRoles = array_values(array_unique($counterpartRoles));
    if (empty($counterpartRoles)) return [];

    $ph = implode(',', array_fill(0, count($counterpartRoles), '?'));
    $types = str_repeat('i', count($counterpartRoles));
    $stmt = $conn->prepare(
        "SELECT user_id, staff_id, first_name, last_name, role_id
         FROM users WHERE role_id IN ($ph) AND user_id != ?
         ORDER BY last_name, first_name"
    );
    $params = [...$counterpartRoles, $userId];
    $stmt->bind_param($types . 'i', ...$params);
    $stmt->execute();
    $contacts = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
    return $contacts;
}

// Returns [sender_id => unread_count] for every unread message sent to
// $userId, so the contacts list can show a badge per conversation.
function get_unread_counts(mysqli $conn, int $userId): array {
    $stmt = $conn->prepare(
        "SELECT sender_id, COUNT(*) AS unread
         FROM messages
         WHERE recipient_id = ? AND read_at IS NULL
         GROUP BY sender_id"
    );
    $stmt->bind_param('i', $userId);
    $stmt->execute();
    $rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();

    $counts = [];
    foreach ($rows as $row) {
        $counts[(int)$row['sender_id']] = (int)$row['unread'];
    }
    return $counts;
}