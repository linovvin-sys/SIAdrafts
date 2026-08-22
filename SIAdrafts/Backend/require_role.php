<?php
/**
 * require_role.php
 * Include this AFTER auth.php in any page or API file that needs
 * to restrict access by role (Head Registrar / Registrar Staff).
 */

require_once __DIR__ . '/roles.php';
require_once __DIR__ . '/session_security.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Idle-timeout check runs once per request, as soon as a session exists,
// regardless of which of the three gate functions below actually gets
// called — an expired session just clears $_SESSION, so the role check
// that follows fails the same way an anonymous request would.
if (!empty($_SESSION['user_id'])) {
    session_touch_or_expire();
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

/**
 * Rejects unless the current session's role has org rank >= $minLevel
 * (see ROLE_LEVELS in roles.php). Provisioning-style ranking only — do
 * NOT use this for Head-Registrar-only academic approval actions, since
 * Admin's higher level would wrongly grant it access. Use
 * require_registrar_tier() for those instead.
 */
function require_min_role(int $minLevel, bool $isApi = false): void
{
    $currentRole = $_SESSION['role_name'] ?? '';
    $currentLevel = role_level(trim($currentRole));

    if ($currentLevel < $minLevel) {
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
 * Rejects unless the current session's role is Registrar Staff or Head
 * Registrar AND its registrar tier >= $minTier. Scoped to just those two
 * roles (see REGISTRAR_TIERS in roles.php) so Admin/Treasury/etc. can
 * never satisfy this check no matter their ROLE_LEVELS rank.
 */
function require_registrar_tier(int $minTier, bool $isApi = false): void
{
    $currentRole = trim($_SESSION['role_name'] ?? '');
    $currentTier = REGISTRAR_TIERS[$currentRole] ?? 0;

    if ($currentTier < $minTier) {
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