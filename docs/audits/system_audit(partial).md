# System Audit — Backend & Frontend (Functionality, Error Control, Validation)

**Date:** 2026-08-05
**Scope:** Full-stack review of `SIAdrafts/` — Backend PHP/API, Frontend views/JS, and DB schema-vs-code consistency.
**Method:** Three parallel audits (backend validation/auth/errors, frontend forms/JS error-handling/XSS, DB schema-vs-code integrity), findings deduplicated and ranked below.

Findings are numbered 1 (highest priority) → 30 (lowest). Each entry has what's wrong, why it matters, and how to fix it.

---

## 1. Section capacity check silently allows unlimited over-enrollment
**Severity:** Critical
**File:** `SIAdrafts/Backend/api/save_enrollment.php:238-253`

**Description:** The capacity-check subquery joins `student.student_id = enrollment.student_id`, but `enrollment.student_id` actually stores `applicants.applicant_id` (per the `fk_enroll_applicant` constraint). Since `student.student_id` is a separate auto-increment sequence, this join almost never matches real rows, so the "seats taken" count is chronically undercounted — often 0 regardless of true enrollment. The "section is full" guard right after it therefore essentially never fires.

**Approach to fix:** Change the join to `student.applicant_id = e.student_id`, matching the corrected pattern already used in `get_professor_roster.php` and `unpaid_transfer.php`. After fixing, backfill-test against a section that's already over its stated capacity in seed data to confirm the guard now trips correctly.

---

## 2. `save_enrollment.php` has no CSRF protection
**Severity:** Critical
**File:** `SIAdrafts/Backend/api/save_enrollment.php` (backend), `SIAdrafts/Frontend/Js/Admission/enrollment-finalize.js:25-38` (frontend caller)

**Description:** This endpoint creates `student`/`enrollment`/`enrollment_subject`/`payment`/`payment_breakdown` rows but never calls `csrf_verify()`, and its JS caller never sends a CSRF token. Every comparable mutating endpoint in the codebase does both. A forged cross-site request from an authenticated staff session could silently create a real enrollment and billing record.

**Approach to fix:** Add `require_once '../csrf.php'; csrf_verify();` at the top of the endpoint (same placement as `record_payment.php`), and update `enrollment-finalize.js` to send `'X-CSRF-Token': document.body.dataset.csrf` in the fetch headers, matching the pattern in `Frontend/Js/Registrar/professors.js`'s `postJSON()`.

---

## 3. Professor roster silently drops irregular students
**Severity:** High
**File:** `SIAdrafts/Backend/api/get_professor_roster.php:39-47`

**Description:** The roster query joins `enrollment.section_id = schedule.section_id`. Irregular students have `enrollment.section_id = NULL` by design — their actual class assignment lives in `enrollment_subject.schedule_id` instead. Since `schedule.section_id` is `NOT NULL`, `NULL = schedule.section_id` never matches, so irregular students enrolled in a professor's class never appear on their roster, with no error or empty-state distinction.

**Approach to fix:** Extend the query with an `OR` branch (or `UNION`) that also matches irregular enrollments directly via `enrollment_subject.schedule_id = ?`, independent of `enrollment.section_id`. Concretely: `WHERE (s.schedule_id = ? AND s.professor_id = ?) AND (e.section_id = s.section_id OR es.schedule_id = s.schedule_id)`, being careful the `enrollment_subject` join condition itself doesn't already require a non-null `section_id` upstream.

---

## 4. `add_subject_registrar.php` trusts a client-supplied `schedule_id` with no validation
**Severity:** High
**File:** `SIAdrafts/Backend/api/add_subject_registrar.php:33-35, 108-118`

**Description:** Unlike the irregular-enrollment path in `save_enrollment.php` (which re-validates that `schedule_id` matches the subject/course/year/semester and doesn't conflict with the student's existing schedule), this endpoint inserts whatever `schedule_id` the client sends into `enrollment_subject` with no existence or ownership check.

**Approach to fix:** Before the insert, run a `SELECT` confirming `schedule_id` exists, belongs to the given `subject_id`, and matches the student's current school_year/semester — reuse the validation block already written in `save_enrollment.php`'s irregular path rather than re-deriving it. Optionally also check for a timetable overlap against the student's other `enrollment_subject` rows.

---

## 5. Duplicate-enrollment warning is silently broken
**Severity:** High
**File:** `SIAdrafts/Frontend/Js/Admission/dup-enrollment-check.js:19`

**Description:** The request URL contains a typo — `check_enrollment.php??student_id=...` (double `?`) — so `$_GET['student_id']` never resolves server-side in `Backend/api/check_enrollment.php`. The "this student may already be enrolled" safety banner has been silently non-functional, and its `catch` block hides the warning on any error too, so there's no visible symptom.

**Approach to fix:** Remove the duplicate `?`. Then verify by testing against a student known to have an existing enrollment for the term and confirming the warning banner now appears.

---

## 6. Negative/zero capacity and units accepted; client-side validation is entirely decorative
**Severity:** High
**Files:** `SIAdrafts/Backend/api/save_section.php:31`, `save_subject.php:33-38`, `save_course.php:33`; `SIAdrafts/Frontend/View/Registrar/{sections,subjects,courses,professors}.php`

**Description:** Server-side, none of `capacity`, `units`, or `total_units` are checked to be positive before insert. Client-side, the `min="1"`, `type="email"`, `minlength="8"` attributes on these fields look like they enforce this, but none of the "Add X" modals across these four files are real `<form>` elements being submitted — buttons are `type="button"` with click handlers — so native HTML5 constraint validation never runs. The validation is decorative on both ends.

**Approach to fix:** Server-side: add explicit `if ($capacity <= 0)` / `if ($units <= 0)` checks with a clear error message in each `save_*.php` file. Client-side: either wrap each modal's fields in an actual `<form>` and call `form.reportValidity()` before submitting, or add equivalent manual checks in the JS `confirmAdd*` handlers so the existing `min`/`type` intent is actually enforced somewhere.

---

## 7. `student_portal_account` — fully seeded schema with zero corresponding code
**Severity:** High (architectural — needs a decision, not just a patch)
**File:** DB table `student_portal_account` (see `enrollment_db_sia_final.sql`)

**Description:** The table has real seeded rows (bcrypt password hashes, `must_change_password`, recent `last_login` timestamps as recent as the day of this audit), but no PHP file anywhere in the codebase reads or writes it. This is either a half-built student-login feature whose code was lost/removed, or stale fixture data masquerading as real accounts.

**Approach to fix:** This isn't a code bug to patch — flag to the team/product owner and decide: (a) build the student-login flow this table implies (mirrors the professor-login pattern already built: check `student_portal_account` in `login.php`, gate a Student Frontend/View folder), or (b) drop the table and seed data if it's abandoned, to stop it misleading future audits or onboarding devs into thinking student login already works.

---

## 8. `get_creditable_subjects.php` determines course membership by schedule existence, not the real mapping
**Severity:** High
**File:** `SIAdrafts/Backend/api/get_creditable_subjects.php:41-51`

**Description:** Every other part of the codebase (`get_schedule_options.php`, `save_schedule.php`, `import_curriculum.php`) uses `subject_course` as the authoritative subject↔course mapping, per `Backend/subject_course.php`'s own documented convention. This endpoint instead requires a `schedule` row to already exist for the subject before it's offered as creditable — a legitimately creditable subject that hasn't been scheduled yet is invisible here.

**Approach to fix:** Rewrite the query to join through `subject_course` (falling back to `subject.course_id` for subjects with zero `subject_course` rows, per the documented convention in `subject_course.php`), dropping the dependency on `schedule` existing.

---

## 9. Raw database error messages leaked to API clients
**Severity:** Medium
**Files:** `save_enrollment.php`, `record_payment.php`, `save_professor.php`, `update_professor_status.php`, `setup_payment.php`, `admin/add_user.php`, `admin/edit_user.php:228`, and others matching `'Database error: ' . $conn->error`

**Description:** On DB failure, many endpoints echo the raw MySQL error string (table/column/constraint names) straight into the JSON response, visible to any authenticated user regardless of role — useful reconnaissance for an attacker and inconsistent with the generic-error pattern used elsewhere in the app.

**Approach to fix:** Replace client-facing raw errors with a generic message (`'A database error occurred. Please try again.'`), and `error_log($conn->error)` server-side instead, so operators can still debug from logs without exposing internals to clients.

---

## 10. `admin/edit_user.php` has inconsistent, non-standard error handling
**Severity:** Medium
**File:** `SIAdrafts/Backend/admin/edit_user.php:49,59,70,81,92,106,117,120,151,160,228`

**Description:** Every validation failure calls bare `die("some string")` — HTTP 200, no JSON envelope, no `http_response_code()`. This differs from its sibling `add_user.php` (redirect with query-string error) and from the JSON-API convention used everywhere else. A caller checking `response.ok` or parsing JSON will treat every validation failure here as a success. Line 228 also leaks a raw DB error (see #9).

**Approach to fix:** Standardize on the same redirect-with-error-message pattern `add_user.php` uses (or convert this to a JSON API consistently, matching whichever pattern the calling frontend page expects), and apply the fix from #9 to the raw-error line.

---

## 11. `save_enrollment.php`'s catch-all for duplicate-key errors mislabels unrelated collisions
**Severity:** Medium
**File:** `SIAdrafts/Backend/api/save_enrollment.php`

**Description:** The transaction wraps four different unique constraints (`enrollment`, `student.student_no`, `student.(contact_number,email)`, `enrollment_subject`). Any MySQL error 1062 from any of them is collapsed into the single message "Student is already enrolled for this term" — so a genuine contact-info collision (e.g. siblings sharing a guardian's email/phone) is misdiagnosed as an enrollment duplicate, with no path to actually resolve it.

**Approach to fix:** Inspect `$conn->error` (or `$stmt->error`) for the specific constraint name (MySQL includes it in the error text, e.g. `for key 'contact_number'`) and branch the user-facing message accordingly, or catch each insert's duplicate-key case individually rather than one shared catch-all.

---

## 12. `admin/add_user.php` missing duplicate-phone check; blank phone can collide
**Severity:** Medium
**File:** `SIAdrafts/Backend/admin/add_user.php:86-108`

**Description:** `users.phone_number` has a UNIQUE constraint, and the form explicitly pre-checks duplicate `email` and `username` but not `phone_number`. Additionally, `clean()` turns a blank phone field into `''` rather than `NULL` — and MySQL's unique index treats two `''` values as equal (unlike `NULL`) — so two staff accounts both left with a blank phone number will collide on the second insert with a raw DB error (see #9).

**Approach to fix:** Add a duplicate-phone pre-check mirroring the existing email/username checks, and store an empty phone as `NULL` instead of `''` so multiple blank entries don't collide.

---

## 13. Inconsistent `requested_by`/`reviewed_by` identifier scheme across approval-workflow tables
**Severity:** Medium
**Files:** `course`, `section`, `schedule`, `subject` (`int`, unconstrained, no FK) vs. `applicants`, `readmission_request`, `applicant_subject_credit`, `subject_change_fee` (`varchar(9)` staff_id, FK-enforced)

**Description:** Half the approval-workflow tables store `requested_by`/`reviewed_by` as a `users.staff_id`-formatted string with an enforced FK; the other half store a raw `users.user_id` int with **no FK constraint at all**. Copy-pasting code from one convention into the other would either fail loudly (FK-constrained columns reject the wrong format) or silently insert a plausible-but-wrong number with zero DB-level defense on the four unconstrained columns.

**Approach to fix:** Not urgent to migrate existing data, but (a) add the missing FK constraints on `course.requested_by/reviewed_by`, `section.requested_by/reviewed_by`, `schedule.requested_by/reviewed_by`, `subject.requested_by/reviewed_by` referencing `users.user_id`, and (b) document the two conventions clearly (e.g. in a code comment near `roles.php`) so future additions pick one deliberately instead of by copy-paste.

---

## 14. `require_role(..., true)` (JSON mode) used on full HTML admin pages
**Severity:** Medium
**Files:** `admin/dashboard.php`, `admin/enrollment.php`, `admin/revenue.php`, `admin/admission.php`, `admin/admission_dashboard.php`, `admin/admission_view.php`, `admin/manage_user.php`, `admin/total_enrolees.php`, `admin/generate_staff_id.php`

**Description:** These render HTML dashboard pages but pass `isApi = true` to `require_role()`, so a role mismatch returns a bare JSON error blob instead of the app's styled 403 page.

**Approach to fix:** Change these calls to `require_role([...])` (default `isApi = false`) so unauthorized visitors get the proper HTML 403 page, matching how every other non-API view in the app handles this.

---

## 15. `message_stream.php` has no per-user concurrency cap
**Severity:** Medium
**File:** `SIAdrafts/Backend/api/message_stream.php`

**Description:** Each open connection holds a PHP process for up to 5 minutes, polling the DB every second. There's no limit on how many concurrent streams one session/IP can open, unlike `login.php`/`online_admission_process.php` which use `Backend/rate_limit.php`.

**Approach to fix:** Apply `rate_limit_check()` (already available) to cap concurrent/rapid stream-open requests per session or IP, or track open-stream count per user and reject beyond a small cap (e.g. 2-3 tabs).

---

## 16. `confirm_admission.php` doesn't validate credited `subject_id` values
**Severity:** Medium
**File:** `SIAdrafts/Backend/api/confirm_admission.php:213-231`

**Description:** `$_POST['credited_subjects']` is cast to ints and inserted into `applicant_subject_credit` with only an `ON DUPLICATE KEY UPDATE`, no check that each `subject_id` exists or belongs to the applicant's course. A bogus or stale subject_id could silently mismatch billable-subject counts later in `save_enrollment.php`.

**Approach to fix:** Before inserting, validate each `subject_id` exists in `subject` and is part of the applicant's course curriculum (via `subject_course`), rejecting the request with a clear error if not.

---

## 17. Missing `.catch()`/try-catch across Registrar "Add X" flows — unhandled rejections, stuck UI
**Severity:** Medium
**Files:** `Frontend/Js/Registrar/{sections,courses,subjects,professors,add_drop_subject,pending}.js`

**Description:** `confirmAddSection`/`confirmAddCourse`/`confirmAddSubject`/`confirmAddProfessor` and the shared `postJSON()`/`getJSON()` helpers have no try/catch or `.catch()`. A network failure or non-JSON error response mid-request leaves the modal open with no error shown, and in `add_drop_subject.js` can leave a modal stuck on "Loading…" indefinitely.

**Approach to fix:** Wrap each `postJSON`/`getJSON` call site in try/catch (or add a `.catch()` inside the shared helper itself, since it's duplicated per-file — see #29) that shows a `Swal.fire({icon:'error', ...})` and re-enables the UI on failure.

---

## 18. No disable-during-submit guard on Save buttons — duplicate submission risk
**Severity:** Medium
**Files:** `Frontend/Js/Registrar/{sections,courses,subjects,professors}.js`

**Description:** None of these Save buttons are disabled while their request is in flight. A double-click fires two POSTs; only accidentally prevented where a unique constraint happens to exist (e.g. section name), and not at all for professors (no name-uniqueness check).

**Approach to fix:** Set `btn.disabled = true` at the start of each `confirmAdd*` handler and re-enable it in a `finally` block (once #17's try/catch is added).

---

## 19. Document upload size limit is enforced server-side only
**Severity:** Medium
**Files:** `SIAdrafts/Frontend/View/Admission/online_admission.php:304`, `SIAdrafts/Backend/api/online_admission_process.php:245`

**Description:** The 5MB size limit is only checked after the full multipart upload completes — on a slow connection, an applicant attaching an oversized scan doesn't find out until after the entire (possibly multi-file) submission finishes uploading. Contrast with `Frontend/Js/Registrar/messages.js:218`, which checks file size client-side immediately on selection.

**Approach to fix:** Add the same `file.size > 5 * 1024 * 1024` client-side check on file selection in the admission upload flow, giving immediate feedback before any upload starts.

---

## 20. Zero-unit subjects allowed both client- and server-side
**Severity:** Medium
**Files:** `SIAdrafts/Frontend/View/Registrar/subjects.php:144`, `SIAdrafts/Backend/api/save_subject.php:38`

**Description:** The units field allows `min="0"` (and per #6, that constraint doesn't even fire), and the server never validates `units > 0` at all — it's missing from the required-fields check entirely. A 0-unit subject would silently zero out fee computations wherever units are multiplied (add/drop fees, treasury per-unit fees).

**Approach to fix:** Change `min="1"` (or `min="0.5"` matching the `step`) client-side, and add an explicit server-side `units > 0` check in `save_subject.php`.

---

## 21. Inconsistent post-save/delete feedback across near-identical flows
**Severity:** Medium
**Files:** `Frontend/Js/Registrar/{sections,courses,professors}.js`

**Description:** `sections.js`/`courses.js`/`subjects.js` show a success toast then reload; `professors.js` reloads with no success toast at all. "Remove Section"/"Remove Course" give no feedback either way (row just disappears from the DOM). This inconsistency makes it unclear to staff whether an action actually succeeded.

**Approach to fix:** Standardize: every mutating action shows a `Swal.fire({icon:'success', ...})` (or equivalent) confirmation before reloading/removing the row, matching the pattern already used for approve/reject flows in `pending.js`.

---

## 22. `payment.balance` vs `unpaid_students.balance` can drift
**Severity:** Low
**Files:** `payment` table (generated column), `unpaid_students` table (plain column), `SIAdrafts/Backend/unpaid_transfer.php`

**Description:** `payment.balance` is a MySQL `GENERATED ALWAYS AS` column, always correct. `unpaid_students.balance` is a one-time hand-copied snapshot taken at transfer time and never recalculated if the original `payment` row is later updated.

**Approach to fix:** When resolving/updating a payment that has a corresponding `unpaid_students` row, also update `unpaid_students.balance` in the same transaction, or recompute it on read instead of trusting the stored snapshot.

---

## 23. Implicit-null `middle_name` string concatenation
**Severity:** Low
**Files:** `get_applicant.php:79`, `confirm_admission.php:238`, `online_admission_process.php:384`

**Description:** These concatenate a possibly-`NULL` `middle_name` directly (`$a['first_name'] . ' ' . $a['middle_name'] . ' ' . $a['last_name']`) rather than guarding with `?? ''` like `get_student.php` does. Only a deprecation notice on PHP 8.3, but would break on a future PHP major version where this becomes a `TypeError`.

**Approach to fix:** Apply the same `$row['middle_name'] ?? ''` guard used in `get_student.php` to these three sites.

---

## 24. `readmission_request.new_course_id` is captured but never displayed
**Severity:** Low
**Files:** `SIAdrafts/Backend/api/submit_readmission.php`, `SIAdrafts/Frontend/View/Registrar/readmission_request.php`

**Description:** The column is correctly written on submit, but no query anywhere selects it back out — the registrar-facing review UI has no way to see which course a shifting student actually requested.

**Approach to fix:** Add `new_course_id` (joined to `course.course_name`) to whatever query backs the readmission review list/detail view, and display it when `is_shifting = 1`.

---

## 25. Business-rule failures returned as HTTP 200
**Severity:** Low
**Files:** `record_payment.php:229-237`, `add_subject_registrar.php:140-143`, `drop_subject_registrar.php:150-153`, `cancel_subject_fee.php:85-88`

**Description:** These endpoints return `{"error": "..."}` on legitimate business-rule failures (e.g. "already fully paid") without setting an HTTP error status, unlike `setup_payment.php`/`update_setting.php` which do. Frontend code that checks `response.ok` instead of parsing the body would treat these as successes.

**Approach to fix:** Add `http_response_code(422)` (or 400) alongside the existing JSON error response in each of these catch blocks.

---

## 26. `can_message.php` hardcodes role IDs disconnected from `roles.php`
**Severity:** Low
**File:** `SIAdrafts/Backend/api/can_message.php:12-13`

**Description:** `MSG_ROLE_HEAD_REGISTRAR = 5` / `MSG_ROLE_REGISTRAR_STAFF = 6` are hardcoded numeric `role_id` values rather than derived from `roles.role_name` at query time. If the `roles` table is ever reseeded with different IDs (as already happened once in this project's history — see the earlier `Professor` role experiment), this silently breaks messaging authorization with no error.

**Approach to fix:** Replace the hardcoded constants with a lookup against `roles.role_name` (matching the pattern already used in `login.php` and `require_role.php`), or at minimum add a startup assertion that fails loudly if the expected IDs don't match.

---

## 27. Minor username-enumeration timing side-channel in `login.php`
**Severity:** Low
**File:** `SIAdrafts/Backend/api/login.php:32-102`

**Description:** A failed lookup against `users` falls through to a second query against `professor`. A username that exists only in `users` fails after one query + `password_verify`; one that exists in neither table fails after two queries. This timing/behavior asymmetry is a very minor enumeration signal introduced by the new dual-auth-source design.

**Approach to fix:** Low priority; if ever addressed, always run both lookups (or a constant-time dummy `password_verify` call on the missing path) so timing doesn't vary based on which table (if any) contains the username.

---

## 28. One unescaped `innerHTML` line in an otherwise-fixed file
**Severity:** Low
**File:** `SIAdrafts/Frontend/Js/Professor/classes.js:55`

**Description:** `'<td ...>' + data.error + '</td>'` inserts `data.error` unescaped, while every other dynamic value in the same file correctly goes through `escHtml()`. Not currently exploitable — `get_professor_roster.php` never actually returns an `error` key in practice — but a latent XSS if that endpoint is ever extended to surface a user-influenced error message.

**Approach to fix:** Wrap `data.error` in `escHtml()` for consistency with the rest of the file, closing the gap before it can ever matter.

---

## 29. `escHtml()` reimplemented independently in ~6 different JS files
**Severity:** Low
**Files:** `sections.js`, `courses.js`, `pending.js`, `schedule.js`, `classes.js`, others

**Description:** Each file defines its own private copy of the same escaping function. Functionally equivalent today, but this duplication is exactly what let #28 happen — there's no single place to audit "did we forget to escape somewhere."

**Approach to fix:** Extract one shared `escHtml()` into a small shared JS file (e.g. `Frontend/Js/shared.js`, loaded before per-page scripts in `footer.php`), and have each page-specific file use the shared version instead of redefining it.

---

## 30. "Remove Course"/"Remove Section" bypass the DataTable API, risking pagination desync
**Severity:** Low
**Files:** `SIAdrafts/Frontend/Js/Registrar/courses.js:76-96`, `sections.js:107-127`

**Description:** These handlers call `btn.closest('tr').remove()` directly instead of going through the DataTables row-removal API, even though both files initialize the table via `initDataTable(...)`. Removing enough rows to cross a page boundary can leave a stale "page 2 of 2" control pointing at an empty page.

**Approach to fix:** Use the DataTables API's row-removal method (`table.row(tr).remove().draw()`) instead of raw DOM removal, consistent with how the same files already special-case DataTables' behavior for search/filtering.

---

## Summary

| Severity | Count |
|---|---|
| Critical | 2 |
| High | 6 |
| Medium | 13 |
| Low | 9 |

**Suggested fix order:** #1–#5 first (small diffs, outsized real-world impact: over-enrollment, forged enrollments, invisible students, a broken safety check, an unvalidated schedule assignment). #7 is a decision, not a patch — raise with the team before touching it. Everything else can be batched into a general hardening pass.
