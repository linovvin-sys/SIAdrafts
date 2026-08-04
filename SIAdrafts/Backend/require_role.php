<?php
/**
 * require_role.php
 * Include this AFTER auth.php in any page or API file that needs
 * to restrict access by role (Head Registrar / Registrar Staff).
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

function require_role(array $allowedRoles, bool $isApi = false): void
{
    $currentRole = $_SESSION['role_name'] ?? '';

    $normalizedAllowed = array_map('strtolower', $allowedRoles);
    $normalizedCurrent = strtolower(trim($currentRole));

    if (!in_array($normalizedCurrent, $normalizedAllowed, true)) {
        if ($isApi) {
            header('Content-Type: application/json');
            http_response_code(403);
            echo json_encode([
                'error' => 'You do not have permission to perform this action.'
            ]);
        } else {
            http_response_code(403);
            echo '<h2>403 — You do not have permission to view this page.</h2>';
        }
        exit;
    }
}

/**
 * Convenience check (no exit) used inside views to show/hide buttons,
 * e.g. only Head Registrar sees the "Delete" button on Courses/Sections.
 */
function current_user_is(array $roles): bool
{
    $currentRole = strtolower(trim($_SESSION['role_name'] ?? ''));
    $roles = array_map('strtolower', $roles);
    return in_array($currentRole, $roles, true);
}