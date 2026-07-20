# Admission Module — Design Spec

**Date:** 2026-07-20
**Status:** Approved (pending user review of this document)
**Sub-project 2 of 5** in the Enrollment Management System update program (Foundation → Admission → Treasury → Registrar → Head Registrar).

## Context

Foundation (sub-project 1) shipped: `.env`-backed config, a `settings` table with `current_school_year`/`current_semester`, a hardened RBAC audit, a CSRF helper, PHPMailer wiring, and a shared Bootstrap+DataTables layout deployed to Admin/Registrar/Head Registrar (Admission was explicitly left on its old top-nav layout, deferred to this sub-project).

This sub-project covers the original spec's section 2 (Admission Module Updates) in full — student-facing form changes, automation, and admin-side UI — plus one item surfaced during implementation: turning the landing page's static "Programs" section into a DB-driven course showcase with a direct-to-apply flow.

Investigation findings that shape this design:
- `online_admission.php` (the public application form) has **no document upload section today** — documents are only physically checked off later, in person, via a hardcoded 4-item list in `admission-confirm.js`.
- There is **no `nationality` field** anywhere; `guardian_relationship` is free text.
- There is **no "Admission Authorization" concept** anywhere in the schema or forms.
- `Frontend/View/index.php` (the landing page) already has a "Programs" section, but it's static markup with a code comment: *"swap in the real list from the course table"*.
- The Admission module's own admin-side pages (`admission.php`, `enrollment.php`, `treasury.php`, etc.) still use the pre-Foundation top-nav layout (`Frontend/View/Admission/Include/`), not the shared sidebar system.

## Goals

1. Add document-requirement flexibility to the public application form: students indicate which requirements they'll submit (with optional file upload), submission is never blocked by missing documents.
2. Add `nationality` (dropdown) and convert `guardian_relationship` to a dropdown.
3. Add Form 138 as a requirement option, and a PSA/NSO alternative pairing for the birth certificate requirement.
4. Add an "Admission Authorization" staff-only note field (write/clear), used at walk-in verification.
5. Automate: applicant-type suggestion (New vs. Transferee) from submitted academic history; returning-student detection (flagged for staff confirmation, not auto-applied); semester/school-year sourced from Foundation's `settings` table instead of free-text entry.
6. Give the Admission module a sidebar (Dashboard / Admission with table+search / Enrollment / Total Enrollees), matching the shared layout system from Foundation, plus a DataTables-powered Admission list.
7. Turn the landing page's course showcase into real data, with an "Apply Now" flow that pre-fills and locks the application form's Program field.

## Non-Goals

- Matching the Canva mockup ("Ann's design") pixel-for-pixel — restyled using the existing design system instead, since the mockup wasn't available during design. Can be revisited once compared against the mockup directly.
- A full country list for Nationality — scoped to Filipino (default) + a short common list + Other.
- Editing/deleting subjects, or any Course & Section admin work — that's the separate, already-specced "Subject Cards & Add Subject" work (`docs/superpowers/specs/2026-07-17-subject-cards-and-add-subject-design.md`), unrelated to this sub-project.
- Treasury, Registrar, or Head Registrar feature work — later sub-projects.
- A CAPTCHA for the public form (existing honeypot field stays as-is; a TODO comment already flags this for later).

## Design

### 1. Data Model Changes

```sql
ALTER TABLE applicants
  ADD COLUMN nationality VARCHAR(50) NOT NULL DEFAULT 'Filipino' AFTER sex,
  ADD COLUMN possible_duplicate_student_id INT NULL AFTER admission_status,
  ADD COLUMN duplicate_match_status ENUM('none','pending_review','confirmed','dismissed') NOT NULL DEFAULT 'none' AFTER possible_duplicate_student_id,
  ADD COLUMN authorization_note TEXT NULL AFTER duplicate_match_status,
  ADD COLUMN authorized_by INT NULL AFTER authorization_note,
  ADD COLUMN authorized_at TIMESTAMP NULL AFTER authorized_by,
  ADD COLUMN cleared_by INT NULL AFTER authorized_at,
  ADD COLUMN cleared_at TIMESTAMP NULL AFTER cleared_by,
  ADD FOREIGN KEY (possible_duplicate_student_id) REFERENCES student(student_id),
  ADD FOREIGN KEY (authorized_by) REFERENCES users(user_id),
  ADD FOREIGN KEY (cleared_by) REFERENCES users(user_id);

ALTER TABLE applicant_documents
  ADD COLUMN file_path VARCHAR(255) NULL AFTER document_name,
  ADD COLUMN source ENUM('applicant','staff') NOT NULL DEFAULT 'staff' AFTER file_path;
-- status column (existing) gains new values by convention, not a schema change:
-- 'Will Submit Later', 'Submitted Online', in addition to existing 'submitted'.
```

- `guardian_relationship` (existing column, already free text) is not schema-changed — the dropdown constrains it at the application layer to a fixed value set (Parent, Mother, Father, Guardian, Sibling, Grandparent, Other), validated server-side.
- New upload directory `Backend/uploads/requirements/`, git-ignored, mirroring the existing `Backend/uploads/messages/` pattern (`.gitkeep` committed, contents ignored).

### 2. Public Application Form (`online_admission.php`)

- Restyled using the existing `admission-card`/`form-section` design system already used elsewhere in the Admission module — not an exact match to the unavailable Canva mockup.
- **Personal Information** section: add **Nationality** dropdown (`Filipino` pre-selected/first, then American, Chinese, Korean, Japanese, Indian, Other).
- **Guardian Information** section: `guardian_relationship` becomes a `<select>` (Parent, Mother, Father, Guardian, Sibling, Grandparent, Other) instead of free text.
- **Program** section: `start_term` free-text field is removed (superseded by automatic semester/SY, see Automation below). Program dropdown supports a locked/pre-filled state when arriving from the landing page's Apply Now flow (see section 5): rendered as a disabled-looking read-only display with a "Not your program? Change" link that swaps it back to an editable `<select>`.
- New **Requirements** section (replaces nothing — this is new): one row per requirement:
  - Form 137 / SHS Card
  - Certificate of Good Moral Character
  - Birth Certificate — radio choice of **PSA** or **NSO** (same requirement, different source document)
  - 2x2 ID Photos
  - **Form 138** (new)
  - Each row: an optional file input (`accept="application/pdf,image/*"`) **or** an "I'll submit this at campus" checkbox — selecting one disables the other for that row. Neither is required; submission is never blocked.
- **Admission Authorization** is intentionally **not** on this public form — it's staff-only, added to `admission.php` (walk-in verification), described in section 4.
- **Applicant Type** dropdown stays visible, pre-selected per the Automation rules below, still overridable by the student.

### 3. Automation

- **Semester / School Year**: `online_admission_process.php` calls `get_setting('current_school_year')` and `get_setting('current_semester')` (from Foundation) instead of accepting a free-text `start_term` value from the client. The `start_term` form field is removed.
- **New vs. Transferee suggestion**: client-side JS pre-selects "Transferee" in the Applicant Type dropdown the moment any Academic History row has a non-empty school name, else leaves "New" selected; the student can still change it. `online_admission_process.php` does not trust this value for anything security-sensitive — it's a UX convenience, not an authorization signal.
- **Returning-student detection**: server-side, on submit, `online_admission_process.php` queries `student` for a `last_name + first_name + birth_date` match. On a hit: sets `applicants.possible_duplicate_student_id` and `duplicate_match_status = 'pending_review'` — no automatic reclassification. `admission.php` (walk-in verification) surfaces a "Possible returning student — review" banner showing the matched record; staff clicks Confirm (sets `duplicate_match_status = 'confirmed'`, links the record) or Dismiss (`'dismissed'`).

### 4. Admin-Side UI (Admission Staff)

- `Frontend/View/Admission/Include/{header,sidebar,footer}.php` (the module's own top-nav) is replaced with the Foundation shared layout (`Frontend/View/Include/*`), same as Admin/Registrar/Head Registrar. `Backend/nav_config.php` gains an `'admission'` role entry: Dashboard, Admission, Enrollment, Total Enrollees.
- New **Admission Dashboard** (`dashboard.php`, currently missing — the module has no dashboard file today): reuses the stat-card pattern from Admin's dashboard, scoped to admission-relevant counts (pending review, verified today, possible-duplicate flags awaiting confirmation).
- **Admission table** (`admission.php`'s list view — currently this file is the single-applicant lookup tool; the *list* view is new): DataTables-powered, columns: Reference ID, Name, Program, Type, School Year, Status, Date Applied. Filters: search box (name/reference ID), dropdown filters for Status/Type/S.Y. Row action "Review" opens the existing lookup-and-verify flow (pre-filled with that row's reference ID, skipping manual entry).
- `Backend/roles.php`'s `ROLE_ADMISSION` constant (already exists from Foundation) gates these new pages via `require_role([ROLE_ADMISSION, ROLE_ADMIN])`, following the RBAC pattern established in Foundation.

### 5. Landing Page Dynamic Courses (`index.php`)

- The static `.programs` section's three hardcoded `.program-card` divs are replaced with a server-side PHP loop over `course WHERE status = 'Approved'`, rendering one `.program-card` per row (course name, a generic icon, "4-year program" or similar derived text if available — no new schema needed here since `course` already has what's used today).
- Each card gets an **"Apply Now"** button opening a modal (plain CSS/JS, matching the page's existing self-contained style — no new framework dependency) showing the course name and a confirm button.
- Confirming navigates to `online_admission.php?course_id={id}`, which pre-fills and locks the Program field per section 2.
- The hero section's existing generic "Apply Now" button is unchanged — it still links straight to the form with Program unlocked, for visitors who didn't pick a course from the showcase.

## Data Flow

1. Guest browses `index.php` → clicks a course's "Apply Now" → confirms in modal → redirected to `online_admission.php?course_id=X`.
2. Guest fills out the form (Program locked to X, Nationality/Relationship dropdowns, Requirements section optional, Applicant Type pre-suggested) → submits.
3. `online_admission_process.php`: validates input server-side (including re-deriving/validating `course_id`, same defensive pattern already used in `save_enrollment.php`), reads current SY/semester from `get_setting()`, runs the returning-student match query, stores any uploaded requirement files under `Backend/uploads/requirements/`, inserts the `applicants` row (+ `applicant_documents` rows for whatever was indicated), generates `reference_id`.
4. Guest receives reference ID, visits campus.
5. Admission staff: `admission.php` list view (DataTables) → clicks "Review" on the applicant's row → existing lookup-and-verify flow, now also showing the Admission Authorization note field (staff can write and later clear it) and the "possible returning student" banner if flagged.

## Error Handling

- File upload failures (wrong type, too large) → inline client-side validation message per requirement row, matching the existing form-validation pattern (`formBanner` alert box); the row simply falls back to no-file / not blocking submission.
- Server-side, `online_admission_process.php` re-validates file MIME type and size before storing (never trusts the client `accept` attribute alone) — consistent with the codebase's existing "server re-derives, never trusts client" pattern seen in `save_enrollment.php`.
- Returning-student match query failing/erroring does not block submission — `duplicate_match_status` simply stays `'none'` and the error is logged, not surfaced to the applicant.
- Admission Authorization clear action requires the same role as writing it (`ROLE_ADMISSION`/`ROLE_ADMIN`), and both actions are logged via `authorized_by`/`authorized_at`/`cleared_by`/`cleared_at` — no silent overwrite.

## Testing

Manual verification only (no automated test suite in this codebase), per the same approach used in Foundation:
- Submit the public form with: no requirement files/all "submit at campus" (should succeed), a mix of uploaded files and campus-later checkboxes, and a birth-certificate PSA vs. NSO choice — confirm all succeed and `applicant_documents` rows reflect the right `source`/`status`/`file_path`.
- Submit with academic history filled in → confirm Applicant Type auto-suggests Transferee; submit with none → confirms New.
- Submit with a name+birthdate matching an existing `student` row → confirm `duplicate_match_status` becomes `pending_review` and the walk-in verification page surfaces the banner; confirm and dismiss both paths.
- Arrive at the form via a landing-page course's Apply Now → confirm Program is locked to that course, and the "Change" link correctly unlocks it.
- Load `index.php` → confirm the Programs section reflects real `course` table rows (add/approve a test course, confirm it appears).
- Log in as Admission role → confirm the new sidebar (Dashboard/Admission/Enrollment/Total Enrollees) renders via the shared layout, the Admission table's DataTables search/filter/sort work, and "Review" correctly opens the matching applicant.
- Confirm Treasury role (which also currently lives under `Frontend/View/Admission/`) is unaffected — `treasury.php`, `revenue_paid.php`, `revenue_process.php` are out of scope for this sub-project and keep their current top-nav layout for now (Treasury's own sidebar conversion is sub-project 3).
