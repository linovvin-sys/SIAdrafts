# SIA System Audit — Functional Review by Role

**Date:** 2026-08-06
**Scope:** Full inventory of what each role/portal can actually do in the current codebase — pages, features, gating, and data model. Complements `system_audit(partial).md` (which covers backend validation/error-handling/security findings) with a functionality-first view organized by role.
**Method:** Direct read of `SIAdrafts/SIAdrafts/` — page listings, gate functions, and table names reflect what the code does, not what filenames imply.

---

## Role overview

| Role | View folder | Session key | Gate | Status |
|---|---|---|---|---|
| Admin | `Frontend/View/Admin/` | `user_id` + `role_name` | `require_role([ADMIN])` | Working |
| Admission | `Frontend/View/Admission/*` | `user_id` + `role_name` | `require_role([...])` | Working |
| Treasury | `Frontend/View/Admission/{treasury,revenue_*}` | `user_id` + `role_name` | `require_role([TREASURY])` | Partial — no folder of its own, no messaging access |
| Registrar Staff / Head Registrar | `Frontend/View/Registrar/` | `user_id` + `role_name` | `require_role([...])` | Working, most feature-rich |
| Professor | `Frontend/View/Professor/` | `professor_id` | `require_professor()` | Working, recently redesigned |
| Student | `Frontend/View/Student/` | `student_id` | `require_student()` | Working, newest portal |

---

## Admin

System-wide oversight & user management. 3 pages.

- **Dashboard** — stat cards (total students, pending/approved admissions, active courses), admissions trend + status-breakdown charts, recent-admissions ledger, per-course fill-rate table.
- **Manage users** — full staff CRUD: add/edit modals, role and status filters, search, auto-generated staff IDs. The only place staff accounts are created.
- **Settings** — sets the global current school year / semester that every other portal reads. Shared access with Head Registrar.

Gate: `require_role([ROLE_ADMIN])`. Session: `$_SESSION['user_id']`, `role_name='Admin'`.

---

## Admission & Treasury

Applicant intake, enrollment processing, payments. 10 pages combined.

**Admission features**
- Admissions queue — dashboard and confirm/finalize flow for incoming applicants.
- Enrollment workflow — profile capture → subject/schedule selection → confirmation, across four linked pages.
- Public application form — unauthenticated online admission form; honeypot-only spam guard.
- Pending documents — verification queue for applicant-submitted documents.
- Total enrolees — headcount reporting.

**Treasury features**
- Payment processing — record and process payments; subject-change fees.
- Revenue ledger — paid-revenue listing.
- Unpaid students — tracking and transfer of unpaid accounts.

Gate: mixed `ROLE_ADMISSION` / `ROLE_STAFF` / `ROLE_ADMIN` / `ROLE_TREASURY` per page.

> **Gap** — the internal messaging feature (see Registrar below) hardcodes only Head Registrar ↔ Registrar Staff as an allowed pairing. Admission and Treasury staff have no access to it despite the UI existing. The public application form also carries two open `TODO`s from its own author questioning its include path and form action.

---

## Registrar Staff / Head Registrar

Curriculum, scheduling, approvals — the largest portal. 16 pages.

- **Dashboard** — Registrar landing view.
- **Courses / subjects / sections** — create and approve curriculum and offerings; bulk curriculum import with preview.
- **Schedule** — class scheduling, approve/reject, availability lookups.
- **Professors** — roster management; the only place professor records are created.
- **Add/drop subject** — mid-term add/drop workflow for enrolled students.
- **Pending approval** — Head Registrar-only sign-off queue for courses/sections/subjects/schedules.
- **Readmission requests** — intake and processing of readmission applications.
- **Student profile** — full chronological student record view.
- **Messages** — real-time internal messaging (Server-Sent Events) with file attachments — currently Head Registrar ↔ Registrar Staff only.

Gate: `require_role(['Registrar Staff','Head Registrar'])`.

---

## Professor / Instructor

Class rosters and personal schedule, self-service. 4 pages. Recently redesigned to match the Student portal's UI.

- **Dashboard** — "happening now / next class" live widget with countdown; stat cards for active offerings, student count, pending approvals.
- **Classes** — rosters grouped by section and term, per-class and per-section views, CSV export, print, search.
- **Schedule** — weekly grid view, multi-term history, `.ics` calendar export, print/PDF.
- **Profile** — view info; inline email and password update forms.

Gate: `require_professor()`. Session: `$_SESSION['professor_id']` only — cannot be reinterpreted as a staff session. Login: shared form with staff, falls back to `professor` table.

> **Gap** — Profile page's email/password-update buttons are wired to JS handlers whose backing file wasn't confirmed to exist. Worth a manual click-through before relying on it.

---

## Student

Self-service enrollment record, newest and most isolated portal. 6 pages.

- **Dashboard** — next-class widget, enrollment status, unit count, balance-due card, requirements progress, recent activity.
- **Registration** — printable Certificate of Registration: course, year, section, term, enrolled subjects, unit totals.
- **Schedule** — weekly grid, `.ics` export, professor/room listing.
- **Accountabilities** — balance due with overdue flag, payment breakdown, missing-requirements list (submit in person).
- **Change password** — forced on first login; blocks every other page until completed.

Gate: `require_student()`. Session: `$_SESSION['student_id']`, entirely separate from staff/professor. Data layer: one aggregation function per page in `Backend/Student/*_data.php`, no inline SQL in views.

---

## Data model, at a glance

~30 tables inferred from live queries — no `schema.sql` dump exists, only 4 incremental migrations.

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

---

## Gaps & risks

Ordered by what would surprise a user first.

1. **No grading system exists (Structural).** There is no `grade` table anywhere in the schema. Prerequisite-checking is approximated as "the student has an `enrollment_subject` record for that prerequisite that was never Dropped" — a heuristic standing in for a feature that was never built. (`Backend/prereq.php`)

2. **Public admission form has two unresolved author TODOs (User-facing).** The only unauthenticated, public-facing page in the system questions its own include path and form-action route in its own comments. Because it's unauthenticated, a broken path here is the most visible possible failure. (`Frontend/View/Admission/online_admission.php`)

3. **Messaging only works for one role pair (Scope gap).** The internal messaging system's access check hardcodes Head Registrar ↔ Registrar Staff as the only allowed pairing, with a comment noting Treasury/Admission aren't wired in yet. (`Backend/api/can_message.php`)

4. **Public landing page is explicitly marked as a mockup (Content).** Contains a literal placeholder comment for testimonials and a footer reading "This is a preview mockup — replace placeholder content before deploying." (`Frontend/View/index.php`)

5. **No consolidated schema file (Housekeeping).** Only 4 incremental migration files exist; the full schema must be reverse-engineered from query text. One migration's own comment already flags a superseded-but-not-dropped column still being read elsewhere — schema drift is already happening in practice. (`Backend/migrations/`)

6. **Confusing legacy column naming (Housekeeping).** `enrollment.student_id` actually stores `applicants.applicant_id`, not `student.student_id` — flagged in several places as a naming quirk every new query has to work around. This was also the root cause of finding #1 in `system_audit(partial).md` (broken section-capacity check), fixed 2026-08-06. (Registrar `student_profile.php`, Professor dashboard headcount query)

---

## Authentication model

Three parallel, intentionally isolated systems:

- **Staff and Professor** share one login screen. It checks the `users` table first; on failure it falls back to the `professor` table. A staff session carries `user_id` and `role_name`; a professor session carries only `professor_id` — built so a professor session can never be reinterpreted as a staff session.
- **Students** log in through an entirely separate screen against `student_portal_account`, a table distinct from both `student` and `users`. First login forces a password change before any other page is reachable.
- Both flows share a login rate limiter (10 attempts per 5 minutes per IP) and CSRF protection on state-changing forms.
