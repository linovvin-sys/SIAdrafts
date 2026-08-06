# SIA — Full System Audit

**Date:** 2026-08-06
**Scope:** A single consolidated audit of the SIAdrafts Student Information System — what exists, what it does per role, its data model, and every known bug/error-handling/security gap with current fix status.
**Method:** Direct read of `SIAdrafts/SIAdrafts/` (Backend PHP/API, Frontend views/JS, DB schema-vs-code). This document merges and supersedes the two source audits for reading purposes; the originals remain as backing detail:
- [`role_functionality_audit.md`](role_functionality_audit.md) — per-role feature inventory (source for Parts 1–4 below)
- [`system_audit(partial).md`](system_audit(partial).md) — the 30-finding bug/security audit (source for Part 5 below)

---

## Part 1 — What the system is

Plain PHP, no framework, no router. Every page is a standalone file under `Frontend/View/<Role>/*.php`; every AJAX action is a standalone file under `Backend/api/*.php`. DB access is raw `mysqli` via `Backend/db.php`'s `Database` class — no ORM. App root is `SIAdrafts/SIAdrafts/` (nested folder).

**Six portals exist and work:** Admin, Admission, Treasury, Registrar (Staff/Head), Professor, Student. Student and Professor are the newest — added within the last two days of this project's history — and each has its own visually distinct, structurally isolated design system rather than reusing the staff Bootstrap shell.

---

## Part 2 — Role-by-role functionality

| Role | View folder | Session key | Gate | Status |
|---|---|---|---|---|
| Admin | `Frontend/View/Admin/` | `user_id` + `role_name` | `require_role([ADMIN])` | Working |
| Admission | `Frontend/View/Admission/*` | `user_id` + `role_name` | `require_role([...])` | Working |
| Treasury | `Frontend/View/Admission/{treasury,revenue_*}` | `user_id` + `role_name` | `require_role([TREASURY])` | Partial — no folder of its own, no messaging access |
| Registrar Staff / Head Registrar | `Frontend/View/Registrar/` | `user_id` + `role_name` | `require_role([...])` | Working, most feature-rich |
| Professor | `Frontend/View/Professor/` | `professor_id` | `require_professor()` | Working, recently redesigned |
| Student | `Frontend/View/Student/` | `student_id` | `require_student()` | Working, newest portal |

### Admin — system-wide oversight & user management (3 own pages + read-only monitoring)
- **Dashboard** — stat cards (total students, pending/approved admissions, active courses), admissions trend + status-breakdown charts, recent-admissions ledger, per-course fill-rate table.
- **Manage users** — full staff CRUD: add/edit modals, role/status filters, search, auto-generated staff IDs. The only place staff accounts are created.
- **Settings** — sets the global current school year / semester every other portal reads. Shared with Head Registrar.
- **Read-only monitoring (added 2026-08-06)** — Admin can now view (but never act on) Admission, Payment/Treasury, and Registrar/Head Registrar screens: admissions queue, pending documents, enrollment records, treasury revenue/payment queues, unpaid students, curriculum (courses/subjects/sections), schedules, professor roster, add/drop history, pending approvals, and a metadata-only messaging activity view (participant pairs, message counts, last activity — never message content). Enforced at both the UI (write controls hidden) and the API layer (mutating endpoints reject Admin's session even if called directly). See [`system_review_dev_and_user.md`](system_review_dev_and_user.md) for the design rationale, or the conversation history for the full page-by-page mapping.

### Admission & Treasury — intake, enrollment, payments (10 pages)
**Admission:** admissions queue + confirm/finalize; enrollment workflow (profile → subject/schedule selection → confirmation); public online application form (honeypot-only spam guard); pending-documents verification queue; total-enrolees reporting.
**Treasury:** payment processing + subject-change fees; paid-revenue ledger; unpaid-student tracking/transfer.

> Gap — internal messaging is scoped to Head Registrar ↔ Registrar Staff only; Admission/Treasury have the UI but no access. The public application form also carries two open author `TODO`s questioning its own include path and form action.

### Registrar Staff / Head Registrar — curriculum, scheduling, approvals (16 pages, largest portal)
Dashboard; courses/subjects/sections management with bulk curriculum import; class scheduling with approve/reject; professor roster management (only place professor records are created); mid-term add/drop workflow; Head-Registrar-only pending-approval queue; readmission request intake; full student profile view; real-time internal messaging (SSE) with attachments.

### Professor / Instructor — class rosters and personal schedule (4 pages)
Dashboard with a live "happening now / next class" widget; Classes (rosters by section/term, CSV export, print); Schedule (weekly grid, multi-term history, `.ics` export); Profile (info + inline email/password update).

> Gap — Profile page's email/password-update buttons are wired to JS handlers whose backing file wasn't confirmed to exist. Worth a manual click-through.

### Student — self-service enrollment record (6 pages, newest, most isolated)
Dashboard (next-class widget, enrollment status, balance-due, requirements progress); Registration (printable Certificate of Registration); Schedule (weekly grid + `.ics` export); Accountabilities (balance, payment breakdown, missing requirements); forced Change Password on first login.

---

## Part 3 — Data model

~30 tables inferred from live queries — **no `schema.sql` dump exists**, only 4 incremental migration files under `Backend/migrations/`.

| Domain | Tables |
|---|---|
| Staff auth | `users`, `roles`, `statuses` |
| Professor | `professor`, `department` |
| Student auth | `student_portal_account` |
| Admissions | `applicants`, `applicant_documents`, `applicant_school_history`, `applicant_subject_credit`, `credited_by`, `student_type` |
| Student record | `student` |
| Curriculum | `course`, `subject`, `subject_category`, `subject_course`, `pattern` |
| Scheduling | `schedule`, `schedule_deletion_log`, `section`, `room` |
| Enrollment | `enrollment`, `enrollment_subject`, `unpaid_students`, `readmission_request` |
| Finance | `payment`, `payment_breakdown`, `payment_transactions`, `fee_schedule`, `fee_schedule_item`, `subject_change_fee` |
| Messaging | `messages` |
| System config | `settings` |

**Known footgun:** `enrollment.student_id` actually stores `applicants.applicant_id`, not `student.student_id` — every new query has to work around this. It was the root cause of Critical finding #1 below (now fixed).

---

## Part 4 — Authentication model

Three parallel, intentionally isolated systems:
- **Staff + Professor** share one login screen (`login.php`): checks `users` first, falls back to `professor`. Staff session = `user_id` + `role_name`; professor session = `professor_id` only, built so it can never be reinterpreted as a staff session.
- **Student** logs in separately against `student_portal_account`, distinct from both `student` and `users`. Forced password change on first login.
- Both share a login rate limiter (10 attempts / 5 min / IP) and CSRF protection on state-changing forms.

---

## Part 5 — Bugs, errors & security findings

Full detail (file/line, description, fix approach) lives in [`system_audit(partial).md`](system_audit(partial).md). This is the status rollup.

| Severity | Total | Fixed | Open |
|---|---|---|---|
| Critical | 2 | 2 | 0 |
| High | 6 | 5 (+1 resolved by context) | 0 |
| Medium | 13 | 1 | 12 |
| Low | 9 | 0 | 9 |
| **Total** | **30** | **9** | **21** |

### ✅ Fixed 2026-08-06

| # | Finding | Fix |
|---|---|---|
| 1 | Section capacity check silently allowed unlimited over-enrollment (Critical) | Corrected the join key (`student.applicant_id = enrollment.student_id`) so the seat-count and "section full" guard now work. |
| 2 | `save_enrollment.php` had no CSRF protection (Critical) | Added `csrf_verify()` server-side and the `X-CSRF-Token` header client-side. |
| 3 | Professor rosters silently dropped irregular students (High) | Roster query now also matches via `enrollment_subject.schedule_id`, not just `enrollment.section_id`. |
| 4 | `add_subject_registrar.php` trusted a client-supplied `schedule_id` with no validation (High) | Added an existence/ownership/term check before insert. |
| 5 | Duplicate-enrollment warning was dead (a `??` URL typo) (High) | Fixed the typo. |
| 6 | Capacity/units validation was entirely decorative (High) | Added real server-side `> 0` checks in `save_section.php`/`save_subject.php`/`save_course.php`, plus matching client-side guards. |
| 7 | `student_portal_account` had a seeded schema with zero code (High) | Resolved by context, not this pass — the Student portal (built after the original audit) now fully reads/writes this table. |
| 8 | `get_creditable_subjects.php` used schedule existence instead of the real course mapping (High) | Rewrote to use `subject_course` (with `subject.course_id` fallback), matching the documented convention. |
| 20 | Zero-unit subjects were accepted client- and server-side (Medium) | Resolved alongside #6: server check, client check, and `min="0"` → `min="0.5"` correction. |

### 🔲 Still open — Medium (12)

| # | Finding |
|---|---|
| 9 | Raw MySQL error strings leaked to API clients across ~7 endpoints |
| 10 | `admin/edit_user.php` uses bare `die()` instead of a JSON/redirect convention |
| 11 | Duplicate-key catch-all mislabels unrelated collisions as "already enrolled" |
| 12 | `admin/add_user.php` missing duplicate-phone check; blank phone can collide |
| 13 | Inconsistent `requested_by`/`reviewed_by` scheme across approval tables (some FK-enforced, some not) |
| 14 | `require_role(..., true)` (JSON mode) used on full HTML admin pages — breaks the 403 page |
| 15 | `message_stream.php` has no per-user concurrency cap |
| 16 | `confirm_admission.php` doesn't validate credited `subject_id` values |
| 17 | Missing `.catch()`/try-catch across Registrar "Add X" JS flows |
| 18 | No disable-during-submit guard on Save buttons — duplicate submission risk |
| 19 | Document upload size limit enforced server-side only, not on file selection |
| 21 | Inconsistent post-save/delete feedback across near-identical flows |

### 🔲 Still open — Low (9)

| # | Finding |
|---|---|
| 22 | `payment.balance` vs `unpaid_students.balance` can drift |
| 23 | Implicit-null `middle_name` string concatenation (future PHP `TypeError` risk) |
| 24 | `readmission_request.new_course_id` captured but never displayed |
| 25 | Business-rule failures returned as HTTP 200 instead of an error status |
| 26 | `can_message.php` hardcodes role IDs disconnected from `roles.php` |
| 27 | Minor username-enumeration timing side-channel in `login.php` |
| 28 | One unescaped `innerHTML` line (latent XSS) in `classes.js` |
| 29 | `escHtml()` reimplemented independently in ~6 JS files instead of shared |
| 30 | "Remove Course"/"Remove Section" bypass the DataTable API, risking pagination desync |

---

## Part 6 — Other known gaps (not bugs, but real)

1. **No grading system exists.** No `grade` table anywhere; prerequisite completion is approximated from `enrollment_subject` status. (`Backend/prereq.php`)
2. **Public admission form has two unresolved author TODOs** about its own include path and form action — the one unauthenticated page in the system.
3. **Messaging is scoped to one role pair** (Head Registrar ↔ Registrar Staff) — Admission/Treasury can't use it despite the UI existing.
4. **Public landing page is explicitly marked a mockup** — placeholder testimonials and a footer stating it's a preview.
5. **No consolidated schema file** — schema must be reverse-engineered from query text; drift is already happening in practice per one migration's own comment.

---

## Suggested next steps

1. **Medium hardening pass** — #9–#19, #21: mostly small, mechanical fixes (generic error messages, HTTP status codes, missing try/catch, duplicate-phone check). Good candidates to batch together.
2. **Low pass** — #22–#30: lowest urgency; #28/#29 (shared `escHtml()`) is worth doing early since it closes a whole class of future XSS risk in one move.
3. **Product decision, not code** — whether/how to extend internal messaging to Admission/Treasury, and whether a grading system is in scope at all.
