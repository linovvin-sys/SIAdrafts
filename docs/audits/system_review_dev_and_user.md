# SIA — System Review (Developer Perspective + User Perspective)

**Date:** 2026-08-06
**Method:** Live smoke-test against the running XAMPP instance (`curl` against `http://localhost/SIAdrafts/...` — Apache + MySQL confirmed listening) plus direct source review. No headless-browser tooling was available in this environment, so this is not a full click-through UI test; screens behind auth (Admin/Admission/Treasury/Registrar/Professor dashboards) were verified to gate correctly (`302` redirect) but not visually inspected. Treat the User Perspective section as an informed walkthrough grounded in the actual markup/CSS/JS shipped, not a substitute for a real manual QA pass.
**Companion docs:** [`full_system_audit.md`](full_system_audit.md) (functionality + bug list), [`system_audit(partial).md`](system_audit(partial).md) (30 findings with fix status).

---

## Part A — Reviewed as a professional web developer

### Architecture

No framework, no router, no ORM — every page is a standalone `.php` file, every AJAX action a standalone endpoint under `Backend/api/`. For a team of this apparent size, that's a defensible choice: zero build step, zero framework version to track, anyone can trace a request by opening one file. The cost shows up exactly where you'd expect — in duplication. `escHtml()` is reimplemented independently in ~6 JS files (finding #29); CSRF-header boilerplate (`csrfToken()` + `postJSON()`) is copy-pasted per-page instead of shared; the Student and Professor portals duplicate the same "happening now / next class" widget and weekly-grid renderer instead of sharing one component, despite being built back-to-back by (evidently) the same author. None of this is broken, but every one of these copies is a place a fix can be applied to five of the six copies and missed in the sixth — which is precisely how finding #28 (one unescaped `innerHTML` line) happened.

**No schema.sql, no tests, no CI.** `composer.json` only pulls in PHPMailer and phpdotenv — there's no test runner dependency at all, and no `.github/` or equivalent CI config exists. The schema currently lives entirely as tribal knowledge encoded in query text and 4 incremental migration files. This is the single biggest structural risk in the codebase: a schema this large (~30 tables) with no dump and no tests means every future change is verified by hand, and the audit already caught one instance of schema drift in the wild (a superseded-but-not-dropped column still being read elsewhere, flagged in the migration's own comment).

### Code quality, where it's good

The codebase is unusually well-commented for a project this size, and not with noise — the comments explain *why*, not *what*. `subject_course.php`'s docblock on the course-membership fallback convention, `save_enrollment.php`'s comment on why `student.applicant_id` (not `student_id`) has to be the join key, `prereq.php`'s honest admission that there's no grading system so prerequisite-checking is a heuristic — these are the comments of someone leaving a trail for the next person, including future-them. The Student portal's data layer (`Backend/Student/*_data.php`) is also a genuinely good pattern: one aggregation function per page, no inline SQL in the view, easy to test in isolation even though nothing currently does.

### Code quality, where it's thin

- **Validation was decorative in several places until this session's fixes** — HTML5 `min`/`required` attributes on inputs that were never inside a real `<form>` being submitted, so `reportValidity()` never ran (findings #1, #6, #20, now fixed). This is worth a broader sweep: if it happened in three files, it's worth checking whether the same "modal that looks like a form but isn't" pattern exists elsewhere in Registrar/Admission.
- **Error handling is inconsistent across endpoints** — some return `{"error": ...}` with no HTTP status change (findings #10, #25), some leak raw MySQL errors to the client (#9), and `catch` blocks in the frontend routinely swallow failures silently rather than surfacing them (#17 — a network failure mid-request leaves a modal stuck on "Loading…" with no way out for the user). A codebase this size would benefit from one documented convention (status code + JSON envelope shape) and a lint/review checklist enforcing it, rather than each endpoint improvising.
- **Trust boundaries were inconsistently enforced** before this session — some endpoints re-validated client input server-side with real rigor (`save_enrollment.php`'s irregular-schedule re-validation is genuinely careful), while a structurally identical endpoint (`add_subject_registrar.php`, now fixed) trusted the same kind of input blindly. The good pattern already exists in the codebase; it just wasn't applied uniformly.
- **Security posture is mid-tier, trending up.** CSRF (`csrf.php`) and rate limiting (`rate_limit.php`) are real, applied consistently on most mutating staff/student endpoints, and correctly implemented (constant-time token comparison via `hash_equals`). Session cookies are hardened on logout (`httponly` set in `logout.php`/`student_logout.php`). What's missing: the public admission form's anti-bot is honeypot-only by the author's own admission (a real CAPTCHA is flagged as a follow-up), and a handful of endpoints still lack the CSRF check that's standard everywhere else (was true of `save_enrollment.php` until this session).

### Performance & responsiveness (technical, not visual)

Nothing alarming was found — no obvious N+1 patterns in the reviewed queries, and the Server-Sent Events messaging implementation (`message_stream.php`) is a reasonable choice for real-time staff messaging without a WebSocket dependency, though it has no per-connection concurrency cap (#15), which is the kind of thing that's fine at current staff headcount and a problem the day someone leaves 10 tabs open.

### Verdict, as a developer

This is a **competent, honest, un-oversold codebase** — its own comments admit its gaps rather than hiding them, which is rare and valuable. Its main risk isn't any single bug (the Critical/High ones are now fixed); it's the combination of **no schema source of truth + no tests + heavy copy-paste** meaning the codebase's correctness depends entirely on the current maintainer's memory. That's sustainable for one or two developers who wrote it; it becomes fragile the moment someone new has to touch it.

---

## Part B — Reviewed as a user

### As a prospective student (public, no login)

Landing at `index.php`, the page loads fast, has a working responsive layout (viewport meta present, confirmed by curl), and reads as a normal school marketing site — until you notice it says so itself: the footer literally reads *"This is a preview mockup — replace placeholder content before deploying,"* and the testimonials section is a code-visible placeholder. A real prospective student wouldn't see the comment, but they would see a testimonials section that's either empty or filled with obviously fake copy — a bad first impression on the one page that exists to make a good one.

Clicking through to **Start Your Application** (`online_admission.php`) is the strongest public-facing page in the system: clearly labeled sections (Personal Information → Guardian Information → Program → Academic History → Requirements), a real file-upload flow, no login required. I verified the form's submission endpoint actually resolves correctly server-side (`422` on empty POST — i.e., it's live and validating), despite the code's own author having left a `TODO` questioning whether the path was right. It is. That's a case where the audit trail undersold the feature.

### As a returning student (self-service portal)

This is the most polished portal in the system. The login screen has its own identity — distinct typography, a proper page title ("Student Portal Login — EduSchool" vs. the generic "EduSchool" title on the staff login), and — notably — it's the only login screen with a visible "forgot your password?" affordance (pointing to the Registrar's Office, since there's no self-service reset). The dashboard's "happening now / next class" widget, `.ics` calendar export, and printable Certificate of Registration are the kind of small conveniences that make a student portal feel like it was built by someone who thought about a real term's worth of usage, not just the CRUD minimum. The Student CSS also carries by far the most responsive breakpoints of any portal (26 `@media` queries vs. single digits or zero elsewhere) — this is the one part of the system that was clearly designed mobile-first, which matches how students actually use these portals (on their phones, between classes).

The forced password-change flow on first login is a reasonable security default, though the generated temp password scheme (lowercased last name + last 5 digits of student number) is guessable by anyone who knows a student's name and roughly when they enrolled — acceptable for a one-time credential the student is forced to change immediately, but worth knowing if that assumption is ever relaxed.

### As a professor

Functionally on par with the Student portal (deliberately redesigned to match it), but the underlying CSS (`Professor/professor.css`) has **zero `@media` queries** — meaning, unlike the Student portal, nothing about this portal was tuned for a smaller screen. A professor checking their schedule from a phone between classes — a very plausible real use case — is looking at a layout that was never adjusted for that. The weekly schedule grid in particular is a UI element that tends to break first on narrow viewports.

The Profile page's email/password-update buttons are wired up in the markup, but this review couldn't confirm the JS handler backing them actually exists (flagged in the earlier audit too) — worth a real click-through before telling professors this works.

### As registrar/admission/treasury/admin staff

Couldn't be visually verified in this pass (pages correctly 302-redirect unauthenticated requests, which is the correct behavior — I just couldn't get past login without credentials). Based on source review: the Registrar portal is the most feature-complete but also the least consistent in *feel* — save flows across `sections.js`/`courses.js`/`subjects.js`/`professors.js` give inconsistent feedback (#21: some show a success toast, one doesn't; delete actions give none), which means a staff member doing the same *kind* of task in four adjacent screens gets four subtly different experiences of "did that work?" That's a small thing individually and a real source of low-grade friction across a full day of data entry.

The internal messaging feature is real (SSE-based, with file attachments) but currently only usable between Head Registrar and Registrar Staff — an Admission or Treasury staff member sees no evidence it exists for them at all, which is fine as a scoping decision but should probably be communicated (e.g. hidden from nav for roles that can't use it, if it isn't already) rather than left to be discovered as "doesn't work."

### Verdict, as a user

The system's quality is **uneven by portal**, and it tracks build recency almost exactly: the newest portal (Student) is the most thoughtful and the most responsive; the oldest UI surfaces (public landing page, staff login) are the least finished. A student today gets a genuinely good experience. A prospective applicant gets a good application flow undercut by an obviously-placeholder homepage. Staff get a functional but inconsistent set of tools where the same action can feel different depending on which screen you're standing in.

---

## Summary table

| Lens | Strongest part | Weakest part |
|---|---|---|
| Developer | Honest, well-commented code; genuinely careful server-side re-validation where it exists; CSRF/rate-limiting done correctly | No schema source of truth, no tests, heavy JS duplication, inconsistent error-handling convention |
| User | Student portal (fast, mobile-tuned, thoughtful conveniences) and the public application form | Landing page (self-admits it's a mockup), Professor portal has no mobile styling, inconsistent staff-side save/delete feedback |

Both lenses point at the same underlying cause: **the parts of the system built together, recently, and by one clear hand (Student + Professor portals, the fee/enrollment engine) are consistent and considered. The parts stitched together earlier or across more history (staff shell, landing page, Registrar's per-page JS) show the seams.** That's a normal shape for a system mid-build, not a red flag — but it's the honest read of where effort should go next if the goal is a uniformly solid product rather than a system with one excellent wing.
