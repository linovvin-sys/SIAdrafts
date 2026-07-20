# Foundation / Cross-Cutting Infrastructure — Design Spec

**Date:** 2026-07-20
**Status:** Approved (pending user review of this document)
**Sub-project 1 of 5** in the Enrollment Management System update program (Foundation → Admission → Treasury → Registrar → Head Registrar).

## Context

The existing system (`/Applications/MAMP/htdocs/SIAdrafts`) is a working PHP/MySQL enrollment system covering Admin, Admission, Registrar, and Head Registrar. A large set of updates has been requested across all four modules plus a new Treasury module. Several of the requested features depend on infrastructure that doesn't exist yet:

- Auto-detection of current semester/school year (nothing centralizes "current term" today — it's manually entered per form).
- A "payment gates registrar enrollment" conditioned workflow (requires trustworthy role boundaries; RBAC is currently inconsistent — many API endpoints only check "is logged in," not role).
- Email notifications (PHPMailer isn't wired up at all; no Composer, no vendor directory).
- Unique/updated table styling (DataTables isn't used anywhere; Bootstrap is linked in only one of four modules).
- Config isolation for credentials (DB credentials are hardcoded in plaintext in `Backend/db.php`; no `.env`, no CSRF protection anywhere).

This spec covers the foundational work that the four module-specific sub-projects (Admission, Treasury, Registrar, Head Registrar) will build on. It intentionally does not implement any module-specific feature — it makes the ground solid for them.

## Goals

1. Centralize config/secrets (DB, SMTP) behind `.env`, removing hardcoded credentials.
2. Introduce a `settings` table with a "current school year / current semester" concept, manually toggled by Admin/Head Registrar, that other modules can read from.
3. Audit and correct role-based access control across all existing `Backend/api/*.php` and `Backend/admin/*.php` endpoints and all protected views, using a normalized role list.
4. Add CSRF protection (shared helper) applied to all endpoints touched by this project going forward (not retrofitted onto untouched legacy endpoints).
5. Introduce Composer, PHPMailer (configured for a Mailtrap sandbox SMTP), and a `send_email()` wrapper that requires no code changes to go to production later.
6. Replace the four duplicated per-module header/sidebar/footer partials with one shared, role-aware, data-driven layout, and establish Bootstrap 5 + DataTables as the baseline UI stack for all modules.

## Non-Goals

- Retrofitting CSRF onto legacy endpoints not touched by this project (tracked as follow-up debt).
- A full visual redesign of existing module-specific CSS — it stays as supplemental override layers on top of the new Bootstrap baseline.
- Any Admission/Treasury/Registrar/Head Registrar feature work itself — covered in later sub-project specs.
- Date-range-based automatic term rollover — the current term is a manual toggle (explicit decision; see Design section).

## Design

### 1. Config & Secrets

- New `Backend/config.php` loads a git-ignored `.env` via `vlucas/phpdotenv` (Composer), exposing DB host/user/pass/db/port, SMTP host/port/user/pass/from-address (Mailtrap sandbox credentials), and an app `CSRF_SECRET`.
- `Backend/db.php` is rewritten to read from `config.php` instead of hardcoded values. The dead, commented-out `Database` class pointing at `enrollment_db_secured` is removed.
- `.env.example` is committed (no real secrets) documenting required keys for onboarding/deployment.

### 2. Settings / Current Term

- New `settings` table: key-value (`setting_key` PK, `setting_value`, `updated_by`, `updated_at`), seeded with `current_school_year` (format `YYYY-YYYY`) and `current_semester` (int).
- `Backend/settings.php` provides `get_setting(string $key): ?string` and `set_setting(string $key, string $value, int $userId): void`, using prepared statements with a small in-request cache.
- A settings UI accessible to Admin and Head Registrar to view/update these two values (plain Bootstrap form), writing via a new `Backend/api/update_setting.php` guarded by `require_role(['admin','head registrar'], true)` + CSRF verification.
- The current term is a manual toggle, not date-computed — chosen for explicitness and to avoid surprise rollovers mid-term.
- Existing forms that let staff manually type school year/semester are unaffected; new auto-detect flows (built in later sub-projects) read via `get_setting()`.

### 3. RBAC Audit

- `Backend/roles.php` defines a single canonical, normalized role list (`ROLE_ADMIN`, `ROLE_ADMISSION`, `ROLE_TREASURY`, `ROLE_REGISTRAR`, `ROLE_HEAD_REGISTRAR`, `ROLE_STAFF`), resolving the current casing/spacing mismatch between `login.php`'s redirect switch and `require_role()`'s comparisons.
- Every file under `Backend/api/*.php` and `Backend/admin/*.php` is audited and given a `require_role([...], true)` call matching its intended actor:
  - Payment endpoints (`record_payment.php`, `record_subject_fee_payment.php`, etc.) → Treasury/Admin.
  - Approval endpoints (`approve_section.php`, `approve_course.php`, `approve_schedule.php`, reject counterparts) → Head Registrar.
  - Enrollment/admission endpoints (`save_enrollment.php`, `confirm_admission.php`, etc.) → Admission/Registrar per existing flow intent.
  - Endpoints that are intentionally public (`online_admission_process.php`, `login.php`) are explicitly documented as such in a code comment, not left ambiguous.
- Protected view files under `Frontend/View/**/*.php` are similarly audited to call `require_role()` (beyond today's `auth.php` "logged in" check), so a Treasury user can't load a Head Registrar page by direct URL.
- Files whose intended actor is ambiguous from context will be flagged for a quick confirmation during implementation rather than guessed.

### 4. CSRF Protection

- New `Backend/csrf.php`: `csrf_token(): string` (generates/returns a per-session token) and `csrf_verify(): void` (checks the submitted token against session, sends 419/403 JSON and exits on mismatch).
- Applied to every endpoint created or modified across this entire program of work (Foundation and all four module sub-projects going forward). Forms/AJAX calls in touched views include a hidden `csrf_token` field or `X-CSRF-Token` header sourced from `csrf_token()`.
- Untouched legacy endpoints are unaffected by this phase — explicitly tracked as follow-up debt, not silently inconsistent.

### 5. Composer, PHPMailer & Mail

- `composer.json` at the repo root pulling in `phpmailer/phpmailer` and `vlucas/phpdotenv`; `vendor/` is git-ignored.
- `Backend/mailer.php` exposes `send_email(string $to, string $subject, string $bodyHtml): bool`, wrapping PHPMailer and configured entirely from `.env` SMTP values (Mailtrap sandbox for now). Callers never touch SMTP details directly — swapping to production SMTP is a `.env`-only change, no code changes.

### 6. Shared UI Layout & DataTables Baseline

- One shared, role-aware layout include set — `Frontend/View/Include/header.php`, `sidebar.php`, `footer.php` — replaces the four separate near-duplicate per-module partials (`Admin`, `Admission`, `Registrar`, `HeadRegistrar`).
- Sidebar nav is data-driven: an array of `{label, url, icon, roles[]}` entries, so adding/adjusting nav items (e.g. Admission's or Registrar's new sidebar sections, requested in later sub-projects) doesn't require hand-editing HTML per module.
- Bootstrap 5.3.8 (already vendored at repo root) is linked from the shared header for all modules. DataTables (CSS+JS) is linked alongside, with one shared JS init helper (`assets/js/datatable-init.js`) providing consistent default options that per-table code can extend for module-specific needs.
- Existing module-specific CSS files (`admin.css`, `schedule.css`, `modal.css`, etc.) remain as supplemental override layers rather than being replaced wholesale — avoids a full visual rewrite of pages out of scope for this project.

## Data Model Changes

```sql
CREATE TABLE settings (
  setting_key   VARCHAR(64) PRIMARY KEY,
  setting_value VARCHAR(255) NOT NULL,
  updated_by    INT NULL,
  updated_at    TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (updated_by) REFERENCES users(user_id)
);
-- seeded rows: current_school_year, current_semester
```

No other schema changes in this sub-project.

## Error Handling

- `require_role()` failures: 403 JSON (`{"error": "forbidden"}`) for API calls, redirect-to-login-or-dashboard for views, consistent with existing `require_role.php` behavior.
- `csrf_verify()` failures: 419 JSON (`{"error": "csrf_token_invalid"}`), logged (not silently dropped) so legitimate session-expiry cases are distinguishable from tampering during testing.
- `send_email()` failures (SMTP unreachable, auth failure): caught, logged to a local log file, return `false` — callers must not let a failed email block the underlying business transaction (e.g. a payment or enrollment record) from completing.
- `get_setting()` for a missing key returns `null`; callers needing a default handle it explicitly rather than the helper guessing.

## Testing

- Manual verification (no existing automated test suite in this codebase):
  - Fresh `.env` from `.env.example` + `composer install` boots the app with no hardcoded-credential fallback remaining.
  - Login as each role; confirm each can only reach endpoints/views appropriate to that role (spot-check at least one endpoint per module).
  - Settings UI: update current school year/semester as Admin and as Head Registrar; confirm `get_setting()` reflects the change immediately; confirm a Registrar/Treasury/Admission user cannot access the settings UI.
  - Submit a form on a touched endpoint with a missing/incorrect CSRF token → expect rejection; with a valid token → expect success.
  - Trigger `send_email()` against the Mailtrap sandbox → confirm the email arrives in the sandbox inbox.
  - Load each module's pages after the shared layout swap → confirm sidebar/header/footer render correctly per role, Bootstrap styling applies, and no existing functionality regresses (spot-check the pages listed in the codebase exploration: admission.php, treasury.php, enrollment.php, pending_approval.php, etc.).
