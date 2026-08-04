# Subject Cards & Add Subject — Design

Date: 2026-07-17

## Problem

The Course & Section page (`courses.php`, duplicated under `Frontend/View/Registrar/` and
`Frontend/View/HeadRegistrar/`) currently shows Courses and Sections as data-table rows. Subjects
are only visible inside a course's "View" modal, also as a table.

Requirements:
1. Show subjects as cards on the Course & Section page, for both the Head Registrar and Registrar
   Staff roles.
2. Provide a way to add a subject (subject code, subject name, units) tied to an existing course,
   using the existing `subject` table (`subject_code`, `subject_name`, `units`, `course_id`,
   `category_id`) — no schema changes.
3. Add Course stays exactly as it is today (course_code, course_name, total_units only). Course
   creation and subject creation remain two separate steps — confirmed with the user.

## Scope

- Both `Frontend/View/Registrar/courses.php` and `Frontend/View/HeadRegistrar/courses.php` (files
  are byte-for-byte identical except the `require_role()` call — every markup change is applied
  identically to both).
- Shared `Frontend/Js/Registrar/courses.js` (already loaded by both role pages).
- `Frontend/Css/Admin/admin.css` — new `.subject-card` / `.subject-card-grid` rules.
- New backend endpoint `Backend/api/save_subject.php`.
- Existing `Backend/api/get_course_subjects.php` reused as-is (response shape unchanged); only its
  JS consumer's rendering changes from `<tr>` to cards.

Out of scope: category/year-level/semester inputs on the Add Subject form (left unset — cards show
"Uncategorized" when `category_id` is null), edit/delete subject, approval workflow for subjects
(courses have Pending/Approved status; subjects do not, per current schema).

## Design

### 1. New Subjects panel

A third `.panel`, full width, placed below the existing `.grid-2` row (Courses | Sections) in both
`courses.php` files:

- `.panel-header`: title "Subjects", button `+ Add Subject` (`data-open="addSubjectModal"`).
- A search input (`#subjectSearch`) filtering by code/name/course, matching the existing
  `wireListSearch` pattern used for Courses/Sections — but since subjects render as cards, not
  table rows, `wireListSearch` needs a card-aware variant (filter `.subject-card[data-search]`
  elements instead of `tr[data-search]`, toggling `display: none` the same way).
- Body: server-rendered PHP loop (same pattern as the existing Courses/Sections PHP query) over all
  subjects joined with `course` and `subject_category`:
  ```sql
  SELECT sub.subject_id, sub.subject_code, sub.subject_name, sub.units,
         sc.category_name, c.course_code
  FROM subject sub
  LEFT JOIN subject_category sc ON sc.category_id = sub.category_id
  LEFT JOIN course c ON c.course_id = sub.course_id
  ORDER BY c.course_code, sub.subject_code
  ```
- Rendered into a `.subject-card-grid` container of `.subject-card` divs (see markup below). No
  page-load JS fetch is needed — consistent with how Courses/Sections already render server-side.
- Empty state: "No subjects found." (hidden/shown by the search JS, same as
  `courseListEmptyState`/`sectionListEmptyState`).

**Card markup (per subject):**
```html
<div class="subject-card" data-search="<?= strtolower(code . ' ' . name . ' ' . course_code) ?>">
  <div class="subject-card-top">
    <span class="subject-card-code"><?= subject_code ?></span>
    <span class="subject-card-category"><?= category_name ?? 'Uncategorized' ?></span>
  </div>
  <div class="subject-card-name"><?= subject_name ?></div>
  <div class="subject-card-footer">
    <span><?= units ?> unit<?= units == 1 ? '' : 's' ?></span>
    <span><?= course_code ?? '—' ?></span>
  </div>
</div>
```

**CSS** (`admin.css`, new block near `.stats-grid`/`.stat-card`):
- `.subject-card-grid`: `display:grid; grid-template-columns: repeat(auto-fit, minmax(220px,1fr)); gap:16px;`
- `.subject-card`: white bg, `var(--radius)`, `var(--shadow)`, `padding:18px`, border
  `1px solid rgba(0,0,0,0.04)`, hover lift — same treatment as `.stat-card`.
- `.subject-card-category`: reuse `.status-pill` visual style (small rounded chip, muted color)
  rather than inventing a new chip style.
- `.subject-card-name`: bold, `var(--navy)`, `font-size:14px`.
- `.subject-card-footer`: `display:flex; justify-content:space-between; font-size:12px; color:var(--text-muted);`

### 2. Add Subject modal

New `#addSubjectModal`, structurally identical to `#addSectionModal`:

```html
<div id="addSubjectModal" class="modal-overlay">
  <div class="modal-box">
    <div class="modal-header">...icon, "Add Subject", "Create a new subject"...</div>
    <div class="modal-body">
      <div class="form-group">
        <label>Course*</label>
        <select id="newSubjectCourse" class="form-input form-select" required>
          <option value="">-- Select Course --</option>
          <?php foreach ($approvedCourses as $c): ?>
            <option value="<?= course_id ?>"><?= course_code ?> - <?= course_name ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="form-group">
        <label>Subject Code*</label>
        <input type="text" id="newSubjectCode" placeholder="e.g. CS101" required>
      </div>
      <div class="form-group">
        <label>Subject Name*</label>
        <input type="text" id="newSubjectName" placeholder="e.g. Introduction to Programming" required>
      </div>
      <div class="form-group">
        <label>Units*</label>
        <input type="number" id="newSubjectUnits" value="3" min="0" required>
      </div>
    </div>
    <div class="modal-footer">
      <button data-close="addSubjectModal">Cancel</button>
      <button id="confirmAddSubject">Save Subject</button>
    </div>
  </div>
</div>
```

Reuses the same `$approvedCourses` PHP variable Add Section already computes — only courses with
`status === 'Approved'` are selectable, so subjects can't be attached to a not-yet-approved course.

### 3. JS (`courses.js`)

Add a `confirmAddSubject` click handler, mirroring `confirmAddSection`:
- Read `newSubjectCourse`, `newSubjectCode`, `newSubjectName`, `newSubjectUnits`.
- Validate course_id, subject_code, subject_name are non-empty (SweetAlert warning if not).
- POST to `save_subject.php` via the existing `postJSON` helper.
- On success: SweetAlert success toast → `location.reload()` (same pattern as Add Course/Section).
- On error: SweetAlert error with `result.error`.

Add a card-aware search filter for `#subjectSearch` (small variant of `wireListSearch` that
targets `.subject-card[data-search]` instead of `tr[data-search]`).

Change the "View Course" handler's rendering: instead of building `<tr>` HTML from
`data.subjects`, build `.subject-card` HTML (reusing the same markup/CSS as the Subjects panel).
The `get_course_subjects.php` response shape (`subject_code`, `subject_name`, `units`,
`year_level`, `semester`, `category_name`) is unchanged; only the template string in
`courses.js` changes. Year/Sem, currently a table column, moves into the card (e.g. a second
footer line or appended to the category chip) — kept, not dropped, since it's still useful
context inside a single course's subject list.

### 4. `Backend/api/save_subject.php` (new)

Mirrors `save_course.php`'s structure:

```php
<?php
session_start();
require_once '../db.php';
require_once '../require_role.php';
header('Content-Type: application/json');

if (empty($_SESSION['user_id'])) { http_response_code(401); echo json_encode(['error'=>'Unauthorized.']); exit; }
require_role(['Head Registrar', 'Registrar Staff'], true);
if ($_SERVER['REQUEST_METHOD'] !== 'POST') { http_response_code(405); echo json_encode(['error'=>'Method not allowed.']); exit; }

$data = json_decode(file_get_contents('php://input'), true) ?? $_POST;
$course_id = (int)($data['course_id'] ?? 0);
$code = trim($data['subject_code'] ?? '');
$name = trim($data['subject_name'] ?? '');
$units = (float)($data['units'] ?? 0);

if (!$course_id || $code === '' || $name === '') {
    echo json_encode(['error' => 'Course, subject code, and subject name are required.']);
    exit;
}

$db = new Database(); $conn = $db->connect();
$stmt = $conn->prepare("INSERT INTO subject (subject_code, subject_name, units, course_id) VALUES (?, ?, ?, ?)");
$stmt->bind_param('ssdi', $code, $name, $units, $course_id);
try {
    $stmt->execute();
    echo json_encode(['success' => true, 'subject_id' => $conn->insert_id, 'message' => 'Subject added.']);
} catch (mysqli_sql_exception $e) {
    echo json_encode(['error' => ($e->getCode() === 1062)
        ? "Subject code \"$code\" already exists."
        : 'Could not save subject. Please try again.']);
}
$stmt->close(); $db->close();
```

No approval/status workflow — both roles insert directly, matching the fact that the `subject`
table has no `status` column (unlike `course`).

## Error handling

- Missing/invalid fields → inline SweetAlert warning, no request sent (matches Add Course/Section).
- DB duplicate `subject_code` (if a unique index exists) → friendly error message, same pattern as
  `save_course.php`'s 1062 handling. If no unique index exists on `subject_code`, this branch is
  simply unreachable — no schema change is being added to force uniqueness.
- Network failure on card-rendering fetch (View Course modal) → existing catch block's error
  message, unchanged.

## Testing / verification

Manual verification only (no automated test suite in this project):
1. Load Course & Section page as both Registrar Staff and Head Registrar — confirm Subjects panel
   renders cards for existing subjects, grouped/labeled correctly.
2. Click "+ Add Subject", submit with a valid approved course — confirm new card appears after
   reload, confirm row exists in DB.
3. Submit Add Subject with missing fields — confirm client-side validation blocks it.
4. Click "View" on a course — confirm its subject list now renders as cards, total units footer
   still correct.
5. Confirm Add Course modal/flow is untouched (still only course_code/course_name/total_units).
6. Confirm subject search box filters cards correctly, including the "no results" empty state.
