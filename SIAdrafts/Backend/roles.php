<?php
/**
 * Canonical role name strings, matching roles.role_name values.
 * require_role() lowercases both sides, so exact casing here only
 * matters for readability at call sites.
 */

const ROLE_ADMIN          = 'Admin';
const ROLE_STAFF           = 'Staff';
const ROLE_TREASURY        = 'Treasury';
const ROLE_ADMISSION       = 'Admission';
const ROLE_REGISTRAR_STAFF = 'Registrar Staff';
const ROLE_HEAD_REGISTRAR  = 'Head Registrar';

const ROLES_ALL_STAFF = [
    ROLE_ADMIN,
    ROLE_STAFF,
    ROLE_TREASURY,
    ROLE_ADMISSION,
    ROLE_REGISTRAR_STAFF,
    ROLE_HEAD_REGISTRAR,
];

/**
 * Org rank, mirrored from the `roles.level` column (see
 * Backend/migrations/2026_08_22_add_role_level.sql). Higher = outranks.
 *
 * This is for ACCOUNT-PROVISIONING purposes (e.g. "can role A manage role
 * B's account?", last-admin guards, future user-management UI) — it is
 * NOT a general-purpose permission cascade. Admin sits at the top because
 * it administers accounts and settings, not because it should inherit
 * every other role's domain permissions. In particular, Admin does NOT
 * gain Head Registrar's academic-approval authority just because it has
 * a higher level — that separation of duties is enforced separately by
 * require_registrar_tier() below, which only ever compares Head Registrar
 * against Registrar Staff.
 */
const ROLE_LEVELS = [
    ROLE_ADMIN          => 100,
    ROLE_HEAD_REGISTRAR => 80,
    ROLE_REGISTRAR_STAFF => 60,
    ROLE_ADMISSION       => 50,
    ROLE_TREASURY        => 50,
    ROLE_STAFF           => 50,
];

function role_level(string $roleName): int
{
    return ROLE_LEVELS[$roleName] ?? 0;
}

/**
 * Registrar office's own two-tier chain: Head Registrar outranks
 * Registrar Staff for approval actions (approve/reject/delete), while
 * both share day-to-day registrar operations (create/edit). Scoped to
 * just these two roles so it can never be satisfied by Admin or any
 * other role — see require_registrar_tier() in require_role.php.
 */
const REGISTRAR_TIER_STAFF = 1;
const REGISTRAR_TIER_HEAD  = 2;

const REGISTRAR_TIERS = [
    ROLE_REGISTRAR_STAFF => REGISTRAR_TIER_STAFF,
    ROLE_HEAD_REGISTRAR  => REGISTRAR_TIER_HEAD,
];

/**
 * The only roles a Head Registrar may create/edit accounts for via
 * Backend/admin/{add_user,edit_user,manage_user}.php. Deliberately excludes
 * Admin and Head Registrar itself — a Head Registrar can manage the staff
 * branches below them, not their own tier or above. Admin remains the only
 * role that can manage Admin/Head Registrar accounts.
 */
const ROLES_HEAD_REGISTRAR_MANAGEABLE = [
    ROLE_ADMISSION,
    ROLE_REGISTRAR_STAFF,
    ROLE_TREASURY,
    ROLE_STAFF,
];

/**
 * Where a staff/admin/professor account lands after logging in — the one
 * place this mapping lives, used both by the actual login endpoint
 * (Backend/api/Auth/login.php) and by login.php's "already signed in,
 * bounce onward" check, so the two can't quietly drift out of sync the
 * way they did before (login.php used to hardcode a relative
 * 'dashboard.php' that never pointed anywhere real). Case-insensitive,
 * same convention as require_role()/current_user_is().
 */
function staff_dashboard_url(string $roleName): string
{
    switch (strtolower(trim($roleName))) {
        case 'admin':
            return '/SIAdrafts/Frontend/View/Admin/admin_dashboard.php';
        case 'staff':
            return '/SIAdrafts/Frontend/View/Admission/enrollment.php';
        case 'treasury':
            return '/SIAdrafts/Frontend/View/Admission/treasury.php';
        case 'admission':
            return '/SIAdrafts/Frontend/View/Admission/admission.php';
        case 'head registrar':
        case 'registrar staff':
            return '/SIAdrafts/Frontend/View/Registrar/registrar_dashboard.php';
        case 'professor':
            return '/SIAdrafts/Frontend/View/Professor/professor_dashboard.php';
        default:
            return '/SIAdrafts/Frontend/View/login.php';
    }
}
