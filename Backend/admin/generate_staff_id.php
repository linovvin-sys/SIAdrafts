<?php
/**
 * Generates the next sequential StaffID for the current year.
 * Format: YYYY-NNNN (e.g. 2026-0001, 2026-0002, ...)
 *
 * Call this right before inserting a new row into `users`.
 * Drop this function into wherever your "add user" backend lives
 * (or require this file from there).
 */
function generate_staff_id(mysqli $conn): string {
    $year = date('Y');

    $stmt = $conn->prepare(
        "SELECT staff_id FROM users
         WHERE staff_id LIKE ?
         ORDER BY staff_id DESC
         LIMIT 1"
    );
    $like = $year . '-%';
    $stmt->bind_param('s', $like);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if ($row && preg_match('/^(\d{4})-(\d{4})$/', $row['staff_id'], $m)) {
        $next_number = (int)$m[2] + 1;
    } else {
        $next_number = 1;
    }

    $padded = str_pad((string)$next_number, 4, '0', STR_PAD_LEFT);

    return "{$year}-{$padded}";
}

/**
 * Example usage inside your add-user insert flow:
 *
 *   $staff_id = generate_staff_id($conn);
 *
 *   $stmt = $conn->prepare(
 *       "INSERT INTO users
 *           (staff_id, first_name, middle_name, last_name, email,
 *            phone_number, username, password, role_id, status_id, created_at)
 *        VALUES (?,?,?,?,?,?,?,?,?,?, NOW())"
 *   );
 *   $stmt->bind_param(
 *       'sssssssiii',
 *       $staff_id, $first_name, $middle_name, $last_name, $email,
 *       $phone_number, $username, $hashed_password, $role_id, $status_id
 *   );
 *   $stmt->execute();
 */