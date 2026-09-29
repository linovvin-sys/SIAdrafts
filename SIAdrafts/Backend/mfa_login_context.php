<?php
/**
 * Shared "who's actually signed in" resolver for the MFA setup/confirm/
 * disable endpoints. Staff and professor accounts are two separate,
 * otherwise-isolated auth systems (see staff_mfa's migration), but MFA
 * setup is identical for both, so each endpoint needs to resolve which
 * one is active rather than merging the two session types together.
 * Previously this exact if/elseif/else block was copy-pasted into
 * mfa_disable.php, mfa_setup_confirm.php, and mfa_setup_start.php —
 * folded here so the three can't silently drift out of sync.
 */

require_once __DIR__ . '/session_security.php';

/**
 * @return array{loginType:string, accountId:int, table:string, idCol:string, label:string}
 *         Exits with a 401 JSON error (matching the original duplicated
 *         behavior) if neither a staff nor a professor session is active.
 */
function resolve_mfa_login_context(): array
{
    if (!empty($_SESSION['user_id']) && !empty($_SESSION['role_name'])) {
        session_touch_or_expire();
        return [
            'loginType' => 'staff',
            'accountId' => (int)$_SESSION['user_id'],
            'table'     => 'users',
            'idCol'     => 'user_id',
            'label'     => $_SESSION['username'] ?? 'staff',
        ];
    }

    if (!empty($_SESSION['professor_id'])) {
        session_touch_or_expire();
        return [
            'loginType' => 'professor',
            'accountId' => (int)$_SESSION['professor_id'],
            'table'     => 'professor',
            'idCol'     => 'professor_id',
            'label'     => $_SESSION['username'] ?? 'professor',
        ];
    }

    header('Content-Type: application/json');
    http_response_code(401);
    echo json_encode(['error' => 'Please log in.']);
    exit;
}
