# Foundation / Cross-Cutting Infrastructure Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Lay the foundation the Admission/Treasury/Registrar/Head Registrar sub-projects depend on: config/secrets isolation, a current-term settings concept, consistent RBAC, CSRF protection, PHPMailer wired to a dummy SMTP, and a shared Bootstrap+DataTables UI baseline.

**Architecture:** All new shared logic lives in `Backend/` as small single-purpose include files (`config.php`, `settings.php`, `roles.php`, `csrf.php`, `mailer.php`, `nav_config.php`), loaded via Composer autoload + `require_once`. UI unification adds one shared `Frontend/View/Include/{header,sidebar,footer}.php` set that the Admin, Registrar, and Head Registrar modules switch to (Admission's top-nav-to-sidebar conversion is explicitly in-scope for the later Admission sub-project, not here). Existing page bodies do not change — only include paths at the top/bottom of each view swap to the shared files, because the new shared partials reproduce the exact same HTML structural contract (`.app-layout` / `.main-content` / `.page-content`, left open the same way the current `Admin/Include/sidebar.php` and `footer.php` already do).

**Tech Stack:** PHP 8.3 (MAMP: `/Applications/MAMP/bin/php/php8.3.30/bin/php`), MySQL (MAMP: `/Applications/MAMP/Library/bin/mysql80/bin/mysql`, port 8889, db `enrollment_db_sia_final`), Composer (`/Applications/MAMP/bin/php/composer`), `vlucas/phpdotenv`, `phpmailer/phpmailer`, Bootstrap 5.3.8 (already vendored), Bootstrap Icons (CDN), DataTables 1.13.8 (CDN).

## Global Constraints

- DB credentials, SMTP credentials, and `CSRF_SECRET` must live only in `.env` (git-ignored); no plaintext secrets committed. `.env.example` is committed with placeholder values.
- CSRF protection (`csrf.php`) is applied only to endpoints created or modified in this plan — do not retrofit untouched legacy endpoints.
- RBAC (`require_role()`) must be added to every file listed in the RBAC audit tasks below, using the canonical role strings from `Backend/roles.php`.
- The current school year/semester is a manual toggle stored in the `settings` table — no date-range auto-computation.
- Existing page body markup between the sidebar include and the footer include must not change during the UI-baseline migration — only include paths change.
- SMTP is configured for a Mailtrap sandbox inbox via `.env`; `send_email()` must not require code changes to move to production SMTP later.

---

## Task 1: Composer scaffold + dependencies

**Files:**
- Create: `SIAdrafts/composer.json`
- Create: `SIAdrafts/.gitignore` additions (root `.gitignore` already ignores `Backend/config.php`; add `vendor/` and `.env`)

**Interfaces:**
- Produces: `vendor/autoload.php`, `vlucas/phpdotenv` and `phpmailer/phpmailer` available to later tasks.

- [ ] **Step 1: Create `composer.json`**

```json
{
    "name": "sia/enrollment-system",
    "description": "Enrollment Management System",
    "require": {
        "php": ">=8.1",
        "phpmailer/phpmailer": "^6.9",
        "vlucas/phpdotenv": "^5.6"
    },
    "config": {
        "optimize-autoloader": true
    }
}
```

- [ ] **Step 2: Install dependencies**

Run: `cd /Applications/MAMP/htdocs/SIAdrafts && /Applications/MAMP/bin/php/composer install`
Expected: `vendor/` directory created, `composer.lock` generated, no errors.

- [ ] **Step 3: Add `vendor/` and `.env` to gitignore**

Edit `/Applications/MAMP/htdocs/.gitignore`, add two lines at the end:

```
SIAdrafts/vendor/
SIAdrafts/.env
```

- [ ] **Step 4: Verify autoload works**

Run: `/Applications/MAMP/bin/php/php8.3.30/bin/php -r "require '/Applications/MAMP/htdocs/SIAdrafts/vendor/autoload.php'; echo class_exists('PHPMailer\\PHPMailer\\PHPMailer') ? 'OK' : 'FAIL';"`
Expected: `OK`

- [ ] **Step 5: Commit**

```bash
git add SIAdrafts/composer.json SIAdrafts/composer.lock .gitignore
git commit -m "Add Composer with PHPMailer and phpdotenv dependencies"
```

---

## Task 2: `.env` config + `db.php` rewrite

**Files:**
- Create: `SIAdrafts/.env` (git-ignored, real local values)
- Create: `SIAdrafts/.env.example` (committed, placeholder values)
- Create: `SIAdrafts/Backend/config.php`
- Modify: `SIAdrafts/Backend/db.php` (full rewrite)

**Interfaces:**
- Produces: `config('KEY')` helper function returning a string env value or `null`; `Database` class in `db.php` unchanged in public shape (`connect()`, `close()`, `$conn`) so no caller elsewhere needs to change.

- [ ] **Step 1: Create `.env.example`**

```
DB_HOST=localhost
DB_PORT=8889
DB_USERNAME=root
DB_PASSWORD=root
DB_DATABASE=enrollment_db_sia_final

SMTP_HOST=sandbox.smtp.mailtrap.io
SMTP_PORT=2525
SMTP_USERNAME=your_mailtrap_username
SMTP_PASSWORD=your_mailtrap_password
SMTP_FROM_EMAIL=no-reply@eduschool.test
SMTP_FROM_NAME=EduSchool

CSRF_SECRET=change_me_to_a_random_32_char_string
```

- [ ] **Step 2: Create `.env`** (same as `.env.example` but with real Mailtrap sandbox credentials the user supplies, and `DB_PASSWORD=root` matching the current MAMP setup)

Copy `.env.example` to `.env` and fill in real Mailtrap credentials before Task 7's send test.

- [ ] **Step 3: Create `Backend/config.php`**

```php
<?php

require_once __DIR__ . '/../vendor/autoload.php';

$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/..');
$dotenv->load();

function config(string $key): ?string
{
    $value = $_ENV[$key] ?? null;
    return $value === null ? null : (string)$value;
}
```

- [ ] **Step 4: Rewrite `Backend/db.php`**

```php
<?php

require_once __DIR__ . '/config.php';

class Database
{
    private string $host;
    private string $username;
    private string $password;
    private string $database;
    private string $port;

    public $conn;

    public function __construct()
    {
        $this->host     = config('DB_HOST') ?? 'localhost';
        $this->username = config('DB_USERNAME') ?? 'root';
        $this->password = config('DB_PASSWORD') ?? '';
        $this->database = config('DB_DATABASE') ?? '';
        $this->port     = config('DB_PORT') ?? '3306';
    }

    public function connect()
    {
        $this->conn = new mysqli(
            $this->host,
            $this->username,
            $this->password,
            $this->database,
            $this->port
        );

        if ($this->conn->connect_error) {
            die("Database Connection Failed: " . $this->conn->connect_error);
        }

        $this->conn->set_charset("utf8mb4");

        return $this->conn;
    }

    public function close()
    {
        if ($this->conn) {
            $this->conn->close();
        }
    }
}
```

- [ ] **Step 5: Verify DB connection still works**

Run: `/Applications/MAMP/bin/php/php8.3.30/bin/php -r "require '/Applications/MAMP/htdocs/SIAdrafts/Backend/db.php'; \$d = new Database(); \$c = \$d->connect(); echo \$c->ping() ? 'CONNECTED' : 'FAIL'; \$d->close();"`
Expected: `CONNECTED` (requires MAMP MySQL running on port 8889)

- [ ] **Step 6: Commit**

```bash
git add SIAdrafts/.env.example SIAdrafts/Backend/config.php SIAdrafts/Backend/db.php
git commit -m "Move DB credentials from hardcoded values to .env-backed config"
```

(`.env` itself is git-ignored and not committed.)

---

## Task 3: `settings` table + `settings.php` helper

**Files:**
- Create: `SIAdrafts/Backend/migrations/2026_07_20_create_settings_table.sql`
- Create: `SIAdrafts/Backend/settings.php`

**Interfaces:**
- Produces: `get_setting(string $key): ?string`, `set_setting(string $key, string $value, int $userId): void`

- [ ] **Step 1: Create migration SQL**

```sql
CREATE TABLE IF NOT EXISTS settings (
  setting_key   VARCHAR(64) PRIMARY KEY,
  setting_value VARCHAR(255) NOT NULL,
  updated_by    INT NULL,
  updated_at    TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (updated_by) REFERENCES users(user_id)
);

INSERT INTO settings (setting_key, setting_value)
VALUES ('current_school_year', '2026-2027'), ('current_semester', '1')
ON DUPLICATE KEY UPDATE setting_value = setting_value;
```

- [ ] **Step 2: Apply migration**

Run: `/Applications/MAMP/Library/bin/mysql80/bin/mysql -h 127.0.0.1 -P 8889 -u root -proot enrollment_db_sia_final < /Applications/MAMP/htdocs/SIAdrafts/Backend/migrations/2026_07_20_create_settings_table.sql`
Expected: no errors; verify with `... -e "SELECT * FROM settings;"` showing the two seeded rows.

- [ ] **Step 3: Create `Backend/settings.php`**

```php
<?php

require_once __DIR__ . '/db.php';

function get_setting(string $key): ?string
{
    static $cache = [];
    if (array_key_exists($key, $cache)) {
        return $cache[$key];
    }

    $db   = new Database();
    $conn = $db->connect();
    $stmt = $conn->prepare("SELECT setting_value FROM settings WHERE setting_key = ?");
    $stmt->bind_param('s', $key);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    $db->close();

    return $cache[$key] = $row['setting_value'] ?? null;
}

function set_setting(string $key, string $value, int $userId): void
{
    $db   = new Database();
    $conn = $db->connect();
    $stmt = $conn->prepare(
        "INSERT INTO settings (setting_key, setting_value, updated_by)
         VALUES (?, ?, ?)
         ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value), updated_by = VALUES(updated_by)"
    );
    $stmt->bind_param('ssi', $key, $value, $userId);
    $stmt->execute();
    $stmt->close();
    $db->close();
}
```

- [ ] **Step 4: Verify helper**

Run: `/Applications/MAMP/bin/php/php8.3.30/bin/php -r "require '/Applications/MAMP/htdocs/SIAdrafts/Backend/settings.php'; echo get_setting('current_school_year');"`
Expected: `2026-2027`

- [ ] **Step 5: Commit**

```bash
git add SIAdrafts/Backend/migrations/2026_07_20_create_settings_table.sql SIAdrafts/Backend/settings.php
git commit -m "Add settings table and get_setting/set_setting helpers for current term"
```

---

## Task 4: Canonical role constants (`roles.php`)

**Files:**
- Create: `SIAdrafts/Backend/roles.php`

**Interfaces:**
- Produces: `ROLE_ADMIN`, `ROLE_STAFF`, `ROLE_TREASURY`, `ROLE_ADMISSION`, `ROLE_REGISTRAR_STAFF`, `ROLE_HEAD_REGISTRAR` string constants, and `ROLES_ALL_STAFF` array (used by messaging endpoints in Task 9).

- [ ] **Step 1: Create `Backend/roles.php`**

```php
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
```

- [ ] **Step 2: Verify**

Run: `/Applications/MAMP/bin/php/php8.3.30/bin/php -r "require '/Applications/MAMP/htdocs/SIAdrafts/Backend/roles.php'; echo ROLE_HEAD_REGISTRAR; echo ' '; echo count(ROLES_ALL_STAFF);"`
Expected: `Head Registrar 6`

- [ ] **Step 3: Commit**

```bash
git add SIAdrafts/Backend/roles.php
git commit -m "Add canonical role constants for require_role() call sites"
```

---

## Task 5: CSRF helper

**Files:**
- Create: `SIAdrafts/Backend/csrf.php`

**Interfaces:**
- Produces: `csrf_token(): string`, `csrf_verify(): void` (exits with 419 JSON on failure)

- [ ] **Step 1: Create `Backend/csrf.php`**

```php
<?php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

function csrf_token(): string
{
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function csrf_verify(): void
{
    $submitted = $_POST['csrf_token']
        ?? $_SERVER['HTTP_X_CSRF_TOKEN']
        ?? '';

    $expected = $_SESSION['csrf_token'] ?? '';

    if ($expected === '' || !hash_equals($expected, $submitted)) {
        http_response_code(419);
        header('Content-Type: application/json');
        echo json_encode(['error' => 'Invalid or missing CSRF token.']);
        exit;
    }
}
```

- [ ] **Step 2: Verify token generation is stable within a session**

Run: `/Applications/MAMP/bin/php/php8.3.30/bin/php -r "session_id('testsess'); require '/Applications/MAMP/htdocs/SIAdrafts/Backend/csrf.php'; \$a = csrf_token(); \$b = csrf_token(); echo (\$a === \$b) ? 'STABLE' : 'UNSTABLE';"`
Expected: `STABLE`

- [ ] **Step 3: Commit**

```bash
git add SIAdrafts/Backend/csrf.php
git commit -m "Add CSRF token generation and verification helpers"
```

---

## Task 6: Settings UI + `update_setting.php` API

**Files:**
- Create: `SIAdrafts/Backend/api/update_setting.php`
- Create: `SIAdrafts/Frontend/View/Admin/settings.php`

**Interfaces:**
- Consumes: `require_role()` (Task-existing `Backend/require_role.php`), `ROLE_ADMIN`/`ROLE_HEAD_REGISTRAR` (Task 4), `csrf_token()`/`csrf_verify()` (Task 5), `get_setting()`/`set_setting()` (Task 3).
- Produces: a working end-to-end example of the RBAC + CSRF + settings pattern that later tasks/sub-projects copy.

- [ ] **Step 1: Create `Backend/api/update_setting.php`**

```php
<?php
session_start();
header('Content-Type: application/json');

require_once '../db.php';
require_once '../roles.php';
require_once '../require_role.php';
require_once '../csrf.php';
require_once '../settings.php';

if (empty($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized.']);
    exit;
}

require_role([ROLE_ADMIN, ROLE_HEAD_REGISTRAR], true);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed.']);
    exit;
}

csrf_verify();

$key   = trim($_POST['setting_key'] ?? '');
$value = trim($_POST['setting_value'] ?? '');

$allowedKeys = ['current_school_year', 'current_semester'];
if (!in_array($key, $allowedKeys, true) || $value === '') {
    http_response_code(422);
    echo json_encode(['error' => 'Invalid setting key or value.']);
    exit;
}

if ($key === 'current_school_year' && !preg_match('/^\d{4}-\d{4}$/', $value)) {
    http_response_code(422);
    echo json_encode(['error' => 'current_school_year must be in YYYY-YYYY format.']);
    exit;
}

if ($key === 'current_semester' && !in_array($value, ['1', '2', '3'], true)) {
    http_response_code(422);
    echo json_encode(['error' => 'current_semester must be 1, 2, or 3.']);
    exit;
}

set_setting($key, $value, (int)$_SESSION['user_id']);

echo json_encode(['success' => true]);
```

- [ ] **Step 2: Create `Frontend/View/Admin/settings.php`**

```php
<?php
$pageTitle  = "SETTINGS";
$activePage = "settings";

require_once '../../../Backend/auth.php';
require_once '../../../Backend/roles.php';
require_once '../../../Backend/require_role.php';
require_role([ROLE_ADMIN, ROLE_HEAD_REGISTRAR]);
require_once '../../../Backend/settings.php';
require_once '../../../Backend/csrf.php';

$currentSchoolYear = get_setting('current_school_year');
$currentSemester   = get_setting('current_semester');
$token             = csrf_token();

include 'Include/header.php';
?>

<div class="app-layout">
    <?php include 'Include/sidebar.php'; ?>

    <main class="page-content">
      <div class="card p-4" style="max-width: 480px;">
        <h4 class="mb-3">Current Term</h4>
        <form id="settingsForm">
          <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($token, ENT_QUOTES) ?>">
          <div class="mb-3">
            <label class="form-label">Current School Year (YYYY-YYYY)</label>
            <input type="text" class="form-control" name="current_school_year"
                   value="<?= htmlspecialchars($currentSchoolYear ?? '', ENT_QUOTES) ?>" pattern="\d{4}-\d{4}" required>
          </div>
          <div class="mb-3">
            <label class="form-label">Current Semester</label>
            <select class="form-select" name="current_semester" required>
              <?php foreach (['1' => '1st Semester', '2' => '2nd Semester', '3' => 'Summer'] as $val => $label): ?>
                <option value="<?= $val ?>" <?= $currentSemester === $val ? 'selected' : '' ?>><?= $label ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <button type="submit" class="btn btn-primary">Save</button>
          <div id="settingsMsg" class="mt-2"></div>
        </form>
      </div>
    </main>
</div>

<script>
document.getElementById('settingsForm').addEventListener('submit', async function (e) {
  e.preventDefault();
  const form = e.target;
  const csrfToken = form.csrf_token.value;
  const fields = [
    ['current_school_year', form.current_school_year.value],
    ['current_semester', form.current_semester.value],
  ];
  const msgEl = document.getElementById('settingsMsg');
  msgEl.textContent = 'Saving...';

  for (const [key, value] of fields) {
    const body = new URLSearchParams({ csrf_token: csrfToken, setting_key: key, setting_value: value });
    const res = await fetch('/SIAdrafts/Backend/api/update_setting.php', { method: 'POST', body });
    const data = await res.json();
    if (!data.success) {
      msgEl.textContent = data.error || 'Failed to save ' + key;
      return;
    }
  }
  msgEl.textContent = 'Saved.';
});
</script>

<?php include 'Include/footer.php'; ?>
```

- [ ] **Step 3: Manual verification**

Start MAMP, log in as an Admin user, visit `http://localhost:8888/SIAdrafts/Frontend/View/Admin/settings.php`, change the school year, submit, and confirm the success message and that `SELECT * FROM settings;` reflects the new value and `updated_by`. Then log in as a Registrar Staff user and confirm visiting the same URL returns a 403 page.

- [ ] **Step 4: Commit**

```bash
git add SIAdrafts/Backend/api/update_setting.php SIAdrafts/Frontend/View/Admin/settings.php
git commit -m "Add current-term settings UI with RBAC and CSRF protection"
```

---

## Task 7: PHPMailer wrapper

**Files:**
- Create: `SIAdrafts/Backend/mailer.php`
- Create: `SIAdrafts/Backend/scripts/test_mail.php`

**Interfaces:**
- Produces: `send_email(string $to, string $subject, string $bodyHtml): bool`

- [ ] **Step 1: Create `Backend/mailer.php`**

```php
<?php

require_once __DIR__ . '/config.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

function send_email(string $to, string $subject, string $bodyHtml): bool
{
    $mail = new PHPMailer(true);

    try {
        $mail->isSMTP();
        $mail->Host       = config('SMTP_HOST');
        $mail->SMTPAuth   = true;
        $mail->Username   = config('SMTP_USERNAME');
        $mail->Password   = config('SMTP_PASSWORD');
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = (int)config('SMTP_PORT');

        $mail->setFrom(config('SMTP_FROM_EMAIL'), config('SMTP_FROM_NAME'));
        $mail->addAddress($to);
        $mail->isHTML(true);
        $mail->Subject = $subject;
        $mail->Body    = $bodyHtml;

        $mail->send();
        return true;
    } catch (Exception $e) {
        error_log('send_email failed: ' . $mail->ErrorInfo);
        return false;
    }
}
```

- [ ] **Step 2: Create `Backend/scripts/test_mail.php`**

```php
<?php
require_once __DIR__ . '/../mailer.php';

$ok = send_email('test@example.com', 'Foundation phase test email', '<p>If you can read this in Mailtrap, send_email() works.</p>');
echo $ok ? "SENT\n" : "FAILED — check error_log\n";
```

- [ ] **Step 3: Run test (requires real Mailtrap credentials in `.env` from Task 2)**

Run: `/Applications/MAMP/bin/php/php8.3.30/bin/php /Applications/MAMP/htdocs/SIAdrafts/Backend/scripts/test_mail.php`
Expected: `SENT`, and the email visible in the Mailtrap sandbox inbox.

- [ ] **Step 4: Commit**

```bash
git add SIAdrafts/Backend/mailer.php SIAdrafts/Backend/scripts/test_mail.php
git commit -m "Add PHPMailer wrapper configured from .env SMTP settings"
```

---

## Task 8: RBAC audit — Admission-domain API endpoints

**Files (all in `SIAdrafts/Backend/api/`):**
Modify: `confirm_admission.php`, `get_applicant.php`, `save_enrollment.php`, `get_student.php`, `check_enrollment.php`, `get_available_schedules.php`, `get_sections.php`, `get_creditable_subjects.php`, `get_subjects.php`, `record_payment.php`, `record_subject_fee_payment.php`, `setup_payment.php`, `get_payment_info.php`, `export_revenue_csv.php`

**Interfaces:**
- Consumes: `Backend/roles.php` constants (Task 4), existing `Backend/require_role.php`.

The edit is identical in shape for every file in this task: after the existing `require '../db.php';` / `require_once '../db.php';` line, add two requires, and after the existing `if (empty($_SESSION['user_id']))` unauthorized block, add one `require_role([...], true);` call using the role list from the table below.

- [ ] **Step 1: Add requires to each file**

Immediately after each file's `db.php` require line, add:

```php
require_once '../roles.php';
require_once '../require_role.php';
```

- [ ] **Step 2: Add `require_role()` call after the existing session/unauthorized check, per file**

| File | Role list |
|---|---|
| `confirm_admission.php` | `[ROLE_ADMISSION, ROLE_ADMIN]` |
| `get_applicant.php` | `[ROLE_ADMISSION, ROLE_STAFF, ROLE_ADMIN]` |
| `save_enrollment.php` | `[ROLE_STAFF, ROLE_ADMIN]` |
| `get_student.php` | `[ROLE_STAFF, ROLE_ADMISSION, ROLE_ADMIN]` |
| `check_enrollment.php` | `[ROLE_STAFF, ROLE_ADMISSION, ROLE_ADMIN]` |
| `get_available_schedules.php` | `[ROLE_STAFF, ROLE_ADMIN]` |
| `get_sections.php` | `[ROLE_STAFF, ROLE_ADMIN]` |
| `get_creditable_subjects.php` | `[ROLE_STAFF, ROLE_ADMIN]` |
| `get_subjects.php` | `[ROLE_STAFF, ROLE_ADMIN]` |
| `record_payment.php` | `[ROLE_TREASURY, ROLE_ADMIN]` |
| `record_subject_fee_payment.php` | `[ROLE_TREASURY, ROLE_ADMIN]` |
| `setup_payment.php` | `[ROLE_ADMIN]` |
| `get_payment_info.php` | `[ROLE_TREASURY, ROLE_STAFF, ROLE_ADMIN]` |
| `export_revenue_csv.php` | `[ROLE_TREASURY, ROLE_ADMIN]` |

Example (`confirm_admission.php`), showing the exact placement relative to the existing check:

```php
if (empty($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'errors' => ['Unauthorized.']]);
    exit;
}

require_role([ROLE_ADMISSION, ROLE_ADMIN], true);
```

Apply the same placement (right after the existing 401 block, before any other logic) to all 14 files, substituting the role list from the table.

- [ ] **Step 3: Manual verification**

For each of the 6 roles, log in and confirm: allowed endpoints in this batch return normal responses, disallowed ones return HTTP 403 with `{"error": "..."}`. Spot-check at minimum `record_payment.php` (Treasury allowed, Registrar Staff denied) and `save_enrollment.php` (Staff allowed, Treasury denied).

- [ ] **Step 4: Commit**

```bash
git add SIAdrafts/Backend/api/confirm_admission.php SIAdrafts/Backend/api/get_applicant.php SIAdrafts/Backend/api/save_enrollment.php SIAdrafts/Backend/api/get_student.php SIAdrafts/Backend/api/check_enrollment.php SIAdrafts/Backend/api/get_available_schedules.php SIAdrafts/Backend/api/get_sections.php SIAdrafts/Backend/api/get_creditable_subjects.php SIAdrafts/Backend/api/get_subjects.php SIAdrafts/Backend/api/record_payment.php SIAdrafts/Backend/api/record_subject_fee_payment.php SIAdrafts/Backend/api/setup_payment.php SIAdrafts/Backend/api/get_payment_info.php SIAdrafts/Backend/api/export_revenue_csv.php
git commit -m "Add require_role() checks to Admission/Treasury-domain API endpoints"
```

---

## Task 9: RBAC audit — Registrar-domain API endpoints + messaging

**Files (all in `SIAdrafts/Backend/api/`):**
Modify: `lookup_student.php`, `get_student_for_addrop.php`, `submit_readmission.php`, `export_enrolees_csv.php`, `get_schedule_options.php`, `get_schedules.php`, `get_course_subjects.php`, `get_section_roster.php`, `delete_schedule.php`, `save_schedule.php`, `download_attachment.php`, `can_message.php`, `get_conversations.php`, `message_attachments.php`, `message_stream.php`, `send_message.php`

**Interfaces:**
- Consumes: `Backend/roles.php` constants and `ROLES_ALL_STAFF` (Task 4).

- [ ] **Step 1: Add requires to each file** (same two lines as Task 8 Step 1, after each file's `db.php` require)

```php
require_once '../roles.php';
require_once '../require_role.php';
```

- [ ] **Step 2: Add `require_role()` call after each file's existing session/unauthorized check**

| File | Role list |
|---|---|
| `lookup_student.php` | `[ROLE_REGISTRAR_STAFF, ROLE_HEAD_REGISTRAR]` |
| `get_student_for_addrop.php` | `[ROLE_REGISTRAR_STAFF, ROLE_HEAD_REGISTRAR]` |
| `submit_readmission.php` | `[ROLE_REGISTRAR_STAFF, ROLE_HEAD_REGISTRAR]` |
| `export_enrolees_csv.php` | `[ROLE_REGISTRAR_STAFF, ROLE_HEAD_REGISTRAR, ROLE_ADMIN]` |
| `get_schedule_options.php` | `[ROLE_REGISTRAR_STAFF, ROLE_HEAD_REGISTRAR]` |
| `get_schedules.php` | `[ROLE_REGISTRAR_STAFF, ROLE_HEAD_REGISTRAR]` |
| `get_course_subjects.php` | `[ROLE_REGISTRAR_STAFF, ROLE_HEAD_REGISTRAR]` |
| `get_section_roster.php` | `[ROLE_REGISTRAR_STAFF, ROLE_HEAD_REGISTRAR]` |
| `delete_schedule.php` | `[ROLE_REGISTRAR_STAFF, ROLE_HEAD_REGISTRAR]` |
| `save_schedule.php` | `[ROLE_HEAD_REGISTRAR, ROLE_REGISTRAR_STAFF]` *(no current frontend caller found — flag as possibly-unused during review; guarded defensively rather than left open)* |
| `download_attachment.php` | `[ROLE_REGISTRAR_STAFF, ROLE_HEAD_REGISTRAR, ROLE_ADMIN]` |
| `can_message.php` | `ROLES_ALL_STAFF` |
| `get_conversations.php` | `ROLES_ALL_STAFF` |
| `message_attachments.php` | `ROLES_ALL_STAFF` |
| `message_stream.php` | `ROLES_ALL_STAFF` |
| `send_message.php` | `ROLES_ALL_STAFF` |

Placement matches Task 8 Step 2's example — immediately after the existing 401/unauthorized block.

- [ ] **Step 3: Manual verification**

Log in as Registrar Staff and Head Registrar; confirm both can reach their shared endpoints. Log in as Treasury and confirm `lookup_student.php` returns 403. Confirm messaging endpoints work for at least two different roles (e.g. Registrar Staff and Head Registrar exchanging a message).

- [ ] **Step 4: Commit**

```bash
git add SIAdrafts/Backend/api/lookup_student.php SIAdrafts/Backend/api/get_student_for_addrop.php SIAdrafts/Backend/api/submit_readmission.php SIAdrafts/Backend/api/export_enrolees_csv.php SIAdrafts/Backend/api/get_schedule_options.php SIAdrafts/Backend/api/get_schedules.php SIAdrafts/Backend/api/get_course_subjects.php SIAdrafts/Backend/api/get_section_roster.php SIAdrafts/Backend/api/delete_schedule.php SIAdrafts/Backend/api/save_schedule.php SIAdrafts/Backend/api/download_attachment.php SIAdrafts/Backend/api/can_message.php SIAdrafts/Backend/api/get_conversations.php SIAdrafts/Backend/api/message_attachments.php SIAdrafts/Backend/api/message_stream.php SIAdrafts/Backend/api/send_message.php
git commit -m "Add require_role() checks to Registrar-domain and messaging API endpoints"
```

---

## Task 10: RBAC audit — `Backend/admin/*.php` and view files

**Files:**
Modify: `SIAdrafts/Backend/admin/dashboard.php`, `admission.php`, `admission_view.php`, `enrollment.php`, `revenue.php`, `total_enrolees.php`, `manage_user.php`, `add_user.php`, `edit_user.php`, `generate_staff_id.php`
Modify: all protected view files under `Frontend/View/Admin/`, `Frontend/View/Registrar/`, `Frontend/View/HeadRegistrar/` (list in Step 3)

**Interfaces:**
- Consumes: `Backend/roles.php` constants (Task 4).

- [ ] **Step 1: Add `require_role()` to `Backend/admin/*.php` files**

Each file already sits behind `auth.php` (included by its parent view) but is also directly web-reachable. Add near the top of each file (after any existing `session_start()`/require lines, before query logic):

```php
require_once __DIR__ . '/../roles.php';
require_once __DIR__ . '/../require_role.php';
require_role([ROLE_ADMIN], true);
```

for `dashboard.php`, `manage_user.php`, `add_user.php`, `edit_user.php`, `generate_staff_id.php`.

```php
require_once __DIR__ . '/../roles.php';
require_once __DIR__ . '/../require_role.php';
require_role([ROLE_REGISTRAR_STAFF, ROLE_HEAD_REGISTRAR, ROLE_ADMIN], true);
```

for `admission.php`, `admission_view.php`, `enrollment.php`, `total_enrolees.php` (these are included by Registrar, Head Registrar, and Admin views per the codebase exploration).

```php
require_once __DIR__ . '/../roles.php';
require_once __DIR__ . '/../require_role.php';
require_role([ROLE_TREASURY, ROLE_ADMIN], true);
```

for `revenue.php` (included by `Frontend/View/Admission/treasury.php`, the Treasury role's page).

- [ ] **Step 2: Verify view files already calling `require_role()` are consistent**

`Frontend/View/Registrar/enrollment.php` already calls `require_role(['Registrar Staff'])` and `Backend/api/approve_section.php` already calls `require_role(['Head Registrar'], true)` — leave these working calls as-is; do not duplicate.

- [ ] **Step 3: Add `require_role()` to view files currently missing it**

For each file below, confirm whether it already calls `require_role()` (several already do per the codebase's existing pattern). For any that only call `auth.php` (session check) without a role check, add immediately after the `auth.php` require:

```php
require_once __DIR__ . '/../../../Backend/roles.php';
require_once __DIR__ . '/../../../Backend/require_role.php';
require_role([<appropriate roles>]);
```

Role assignment per view directory:
- `Frontend/View/Admin/admin_dashboard.php`, `manage_user.php`, `settings.php` → `[ROLE_ADMIN]` (settings.php already handled in Task 6 with `[ROLE_ADMIN, ROLE_HEAD_REGISTRAR]`)
- `Frontend/View/Registrar/*.php` (all 10 files) → `[ROLE_REGISTRAR_STAFF, ROLE_HEAD_REGISTRAR]` unless the file is Head-Registrar-only in intent
- `Frontend/View/HeadRegistrar/*.php` (all 11 files) → `[ROLE_HEAD_REGISTRAR]`, except `pending_approval.php` which already matches this role by design

Since Registrar and Head Registrar currently run near-duplicate file sets with overlapping access (Head Registrar can view everything Registrar can, plus approvals), use `[ROLE_REGISTRAR_STAFF, ROLE_HEAD_REGISTRAR]` for the Registrar module's files and `[ROLE_HEAD_REGISTRAR]` for the HeadRegistrar module's files — this preserves today's file-per-role-tree structure (a Registrar Staff user physically cannot reach `Frontend/View/HeadRegistrar/*` URLs even if they guess them, because those files require Head Registrar specifically).

- [ ] **Step 4: Manual verification**

As a Registrar Staff user, attempt to load a `Frontend/View/HeadRegistrar/*.php` URL directly — expect 403. As a Treasury user, attempt to load `Frontend/View/Admin/manage_user.php` — expect 403. Confirm normal role-appropriate navigation still works for all 6 roles.

- [ ] **Step 5: Commit**

```bash
git add SIAdrafts/Backend/admin/ SIAdrafts/Frontend/View/Admin/ SIAdrafts/Frontend/View/Registrar/ SIAdrafts/Frontend/View/HeadRegistrar/
git commit -m "Add require_role() checks to Backend/admin includes and protected view files"
```

---

## Task 11: Shared layout partials (header/sidebar/footer + nav config)

**Files:**
- Create: `SIAdrafts/Backend/nav_config.php`
- Create: `SIAdrafts/Frontend/View/Include/header.php`
- Create: `SIAdrafts/Frontend/View/Include/sidebar.php`
- Create: `SIAdrafts/Frontend/View/Include/footer.php`

**Interfaces:**
- Consumes: `$_SESSION['role_name']`, `$_SESSION['full_name']`, `$activePage`, `$pageTitle` (all already set by every existing view file), optional `$extraCss` / `$extraScripts` arrays a page can set before including header/footer.
- Produces: identical HTML structural contract to the current `Frontend/View/Admin/Include/{sidebar,footer}.php` (`<aside class="sidebar">…</aside><div class="main-content"><header class="top-header">…` left open, closed implicitly the same way the current pages already do) — so no page body needs to change in Task 12.

- [ ] **Step 1: Create `Backend/nav_config.php`**

```php
<?php
/**
 * Sidebar nav items per role. Keys are lowercase role_name values.
 * 'page' matches each view file's existing $activePage convention.
 */
return [
    'admin' => [
        ['label' => 'Dashboard', 'page' => 'dashboard', 'url' => '/SIAdrafts/Frontend/View/Admin/admin_dashboard.php', 'icon' => 'dashboard'],
        ['label' => 'Manage User', 'page' => 'manage_user', 'url' => '/SIAdrafts/Frontend/View/Admin/manage_user.php', 'icon' => 'users'],
        ['label' => 'Settings', 'page' => 'settings', 'url' => '/SIAdrafts/Frontend/View/Admin/settings.php', 'icon' => 'settings'],
    ],
    'registrar staff' => [
        ['label' => 'Dashboard', 'page' => 'dashboard', 'url' => '/SIAdrafts/Frontend/View/Registrar/registrar_dashboard.php', 'icon' => 'dashboard'],
        ['label' => 'Admission', 'page' => 'admission', 'url' => '/SIAdrafts/Frontend/View/Registrar/admission.php', 'icon' => 'admission'],
        ['label' => 'Enrollment', 'page' => 'enrollment', 'url' => '/SIAdrafts/Frontend/View/Registrar/enrollment.php', 'icon' => 'enrollment'],
        ['label' => 'Total Enrolees', 'page' => 'total_enrolees', 'url' => '/SIAdrafts/Frontend/View/Registrar/total_enrolees.php', 'icon' => 'enrolees'],
        ['label' => 'Course & Section', 'page' => 'courses', 'url' => '/SIAdrafts/Frontend/View/Registrar/courses.php', 'icon' => 'course'],
        ['label' => 'Schedule', 'page' => 'schedule', 'url' => '/SIAdrafts/Frontend/View/Registrar/schedule.php', 'icon' => 'schedule'],
        ['label' => 'Add/Drop Subject', 'page' => 'addDrop', 'url' => '/SIAdrafts/Frontend/View/Registrar/add_drop_subject.php', 'icon' => 'addDrop'],
        ['label' => 'Readmission Request', 'page' => 'readmission', 'url' => '/SIAdrafts/Frontend/View/Registrar/readmission_request.php', 'icon' => 'readmission'],
        ['label' => 'Messages', 'page' => 'messages', 'url' => '/SIAdrafts/Frontend/View/Registrar/messages.php', 'icon' => 'messages', 'badge' => 'unread'],
    ],
    'head registrar' => [
        ['label' => 'Dashboard', 'page' => 'dashboard', 'url' => '/SIAdrafts/Frontend/View/HeadRegistrar/registrar_dashboard.php', 'icon' => 'dashboard'],
        ['label' => 'Admission', 'page' => 'admission', 'url' => '/SIAdrafts/Frontend/View/HeadRegistrar/admission.php', 'icon' => 'admission'],
        ['label' => 'Enrollment', 'page' => 'enrollment', 'url' => '/SIAdrafts/Frontend/View/HeadRegistrar/enrollment.php', 'icon' => 'enrollment'],
        ['label' => 'Total Enrolees', 'page' => 'total_enrolees', 'url' => '/SIAdrafts/Frontend/View/HeadRegistrar/total_enrolees.php', 'icon' => 'enrolees'],
        ['label' => 'Course & Section', 'page' => 'courses', 'url' => '/SIAdrafts/Frontend/View/HeadRegistrar/courses.php', 'icon' => 'course'],
        ['label' => 'Schedule', 'page' => 'schedule', 'url' => '/SIAdrafts/Frontend/View/HeadRegistrar/schedule.php', 'icon' => 'schedule'],
        ['label' => 'Add/Drop Subject', 'page' => 'addDrop', 'url' => '/SIAdrafts/Frontend/View/HeadRegistrar/add_drop_subject.php', 'icon' => 'addDrop'],
        ['label' => 'Pending Approval', 'page' => 'pending_approval', 'url' => '/SIAdrafts/Frontend/View/HeadRegistrar/pending_approval.php', 'icon' => 'approval', 'badge' => 'pending'],
        ['label' => 'Readmission Request', 'page' => 'readmission', 'url' => '/SIAdrafts/Frontend/View/HeadRegistrar/readmission_request.php', 'icon' => 'readmission'],
        ['label' => 'Messages', 'page' => 'messages', 'url' => '/SIAdrafts/Frontend/View/HeadRegistrar/messages.php', 'icon' => 'messages', 'badge' => 'unread'],
    ],
];
```

- [ ] **Step 2: Create `Frontend/View/Include/header.php`**

```php
<?php
if (session_status() === PHP_SESSION_NONE) session_start();
$extraCss = $extraCss ?? [];
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>EduSchool — <?= htmlspecialchars($pageTitle ?? '', ENT_QUOTES) ?></title>
  <link rel="stylesheet" href="/SIAdrafts/bootstrap-5.3.8-dist/css/bootstrap.min.css" />
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" />
  <link rel="stylesheet" href="https://cdn.datatables.net/1.13.8/css/dataTables.bootstrap5.min.css" />
  <link rel="stylesheet" href="/SIAdrafts/Frontend/Css/Admin/admin.css" />
  <link rel="stylesheet" href="/SIAdrafts/Frontend/Css/Admin/schedule.css" />
  <link rel="stylesheet" href="/SIAdrafts/Frontend/Css/Admin/modal.css" />
  <?php foreach ($extraCss as $href): ?>
    <link rel="stylesheet" href="<?= htmlspecialchars($href, ENT_QUOTES) ?>" />
  <?php endforeach; ?>
  <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
  <script src="https://cdn.jsdelivr.net/npm/chart.js@4"></script>
</head>
<body>
```

- [ ] **Step 3: Create `Frontend/View/Include/sidebar.php`**

```php
<?php
require_once __DIR__ . '/../../../Backend/require_role.php';
require_once __DIR__ . '/../../../Backend/db.php';

$navConfig   = require __DIR__ . '/../../../Backend/nav_config.php';
$currentRole = strtolower(trim($_SESSION['role_name'] ?? ''));
$navItems    = $navConfig[$currentRole] ?? [];

$nameParts = preg_split('/\s+/', trim($_SESSION['full_name'] ?? ''));
$initials  = '';
foreach ($nameParts as $part) {
    if ($part !== '') $initials .= strtoupper($part[0]);
}
$initials  = substr($initials, 0, 2) ?: 'US';
$fullName  = htmlspecialchars($_SESSION['full_name'] ?? '', ENT_QUOTES);
$roleLabel = ucwords($currentRole);

$pendingCount = 0;
if ($currentRole === 'head registrar') {
    $db   = new Database();
    $conn = $db->connect();
    $res  = $conn->query("SELECT COUNT(*) AS cnt FROM schedule WHERE status = 'Pending'");
    $pendingCount = (int)($res->fetch_assoc()['cnt'] ?? 0);
    $db->close();
}

$unreadCount = 0;
if (!empty($_SESSION['user_id']) && in_array($currentRole, ['registrar staff', 'head registrar'], true)) {
    $db      = new Database();
    $conn    = $db->connect();
    $msgStmt = $conn->prepare("SELECT COUNT(*) AS cnt FROM messages WHERE recipient_id = ? AND read_at IS NULL");
    $msgStmt->bind_param('i', $_SESSION['user_id']);
    $msgStmt->execute();
    $unreadCount = (int)($msgStmt->get_result()->fetch_assoc()['cnt'] ?? 0);
    $msgStmt->close();
    $db->close();
}

$iconMap = [
    'dashboard'   => 'bi-grid-1x2-fill',
    'users'       => 'bi-people-fill',
    'settings'    => 'bi-gear-fill',
    'admission'   => 'bi-person-check-fill',
    'enrollment'  => 'bi-journal-check',
    'enrolees'    => 'bi-mortarboard-fill',
    'course'      => 'bi-book-half',
    'schedule'    => 'bi-calendar3',
    'addDrop'     => 'bi-arrow-left-right',
    'approval'    => 'bi-check2-square',
    'readmission' => 'bi-arrow-repeat',
    'messages'    => 'bi-chat-dots-fill',
];
?>
<!-- ===== SIDEBAR ===== -->
<aside class="sidebar">

  <div class="sidebar-brand" id="sidebar-toggle" title="Toggle sidebar">
    <div class="brand-icon">🎓</div>
    <div class="brand-name">Edu<span>School</span></div>
  </div>

  <nav class="nav-section">
    <div class="nav-label">Menu</div>
    <?php foreach ($navItems as $item): ?>
      <a href="<?= htmlspecialchars($item['url'], ENT_QUOTES) ?>"
         class="nav-item<?= ($activePage ?? '') === $item['page'] ? ' active' : '' ?>"
         data-page="<?= htmlspecialchars($item['page'], ENT_QUOTES) ?>">
        <span class="nav-icon"><i class="bi <?= $iconMap[$item['icon']] ?? 'bi-dot' ?>"></i></span>
        <span class="nav-text"><?= htmlspecialchars($item['label'], ENT_QUOTES) ?></span>
        <?php if (($item['badge'] ?? null) === 'pending' && $pendingCount > 0): ?>
          <span class="nav-badge"><?= $pendingCount ?></span>
        <?php elseif (($item['badge'] ?? null) === 'unread' && $unreadCount > 0): ?>
          <span class="nav-badge"><?= $unreadCount ?></span>
        <?php endif; ?>
      </a>
    <?php endforeach; ?>
  </nav>

  <div class="sidebar-user">
    <div class="user-avatar"><?= $initials ?></div>
    <div class="user-info">
      <div class="user-name"><?= $fullName ?: $roleLabel ?></div>
      <div class="user-role"><?= $roleLabel ?></div>
    </div>
  </div>

</aside>

<div class="main-content">
  <header class="top-header">
    <h1 class="header-title" id="page-title"><?= htmlspecialchars($pageTitle ?? '', ENT_QUOTES) ?></h1>
    <div class="header-actions">
      <button class="btn-notif">🔔<?php if ($pendingCount > 0): ?><span class="notif-dot"></span><?php endif; ?></button>
      <div class="avatar-wrapper" id="avatarWrapper">
        <div class="header-avatar" id="avatarBtn"><?= $initials ?></div>
        <div class="avatar-dropdown" id="avatarDropdown">
          <a href="/SIAdrafts/Frontend/View/profile.php" class="dropdown-item">
            <i class="bi bi-person-circle"></i> My profile
          </a>
          <a href="/SIAdrafts/Backend/api/logout.php" class="dropdown-item dropdown-item--danger">
            <i class="bi bi-box-arrow-right"></i> Log out
          </a>
        </div>
      </div>
    </div>
  </header>
```

- [ ] **Step 4: Create `Frontend/View/Include/footer.php`**

```php
<?php $extraScripts = $extraScripts ?? []; ?>
<script src="/SIAdrafts/Frontend/Js/Admin/confirm.js"></script>
<script src="/SIAdrafts/Frontend/Js/Admin/admin.js"></script>
<script src="https://cdn.datatables.net/1.13.8/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.8/js/dataTables.bootstrap5.min.js"></script>
<script src="/SIAdrafts/Frontend/Js/datatable-init.js"></script>
<?php foreach ($extraScripts as $src): ?>
  <script src="<?= htmlspecialchars($src, ENT_QUOTES) ?>"></script>
<?php endforeach; ?>
</body>
</html>
```

Note: `jquery.dataTables.min.js` requires jQuery, which the codebase doesn't currently load anywhere — add jQuery before it:

```php
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="https://cdn.datatables.net/1.13.8/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.8/js/dataTables.bootstrap5.min.js"></script>
```

(Update the footer.php code above to include this jQuery line before the DataTables `<script>` tags.)

- [ ] **Step 5: Create `Frontend/Js/datatable-init.js`**

```javascript
function initDataTable(selector, options) {
  return $(selector).DataTable(Object.assign({
    pageLength: 25,
    lengthChange: false,
    language: { search: '', searchPlaceholder: 'Search...' },
    dom: '<"dt-toolbar"f>rt<"dt-footer"ip>',
  }, options || {}));
}
```

- [ ] **Step 6: Commit**

```bash
git add SIAdrafts/Backend/nav_config.php SIAdrafts/Frontend/View/Include/ SIAdrafts/Frontend/Js/datatable-init.js
git commit -m "Add shared role-aware layout partials and DataTables init helper"
```

---

## Task 12: Migrate Admin, Registrar, Head Registrar views to shared layout

**Files (modify include paths only, 3 lines each):**
- `Frontend/View/Admin/admin_dashboard.php`, `manage_user.php`, `settings.php`
- `Frontend/View/Registrar/add_drop_subject.php`, `admission.php`, `admission_view.php`, `courses.php`, `enrollment.php`, `messages.php`, `readmission_request.php`, `registrar_dashboard.php`, `schedule.php`, `total_enrolees.php`
- `Frontend/View/HeadRegistrar/add_drop_subject.php`, `admission.php`, `admission_view.php`, `courses.php`, `enrollment.php`, `messages.php`, `pending_approval.php`, `readmission_request.php`, `registrar_dashboard.php`, `schedule.php`, `total_enrolees.php`

**Interfaces:**
- Consumes: `Frontend/View/Include/{header,sidebar,footer}.php` (Task 11) — same `$pageTitle`/`$activePage` contract every file already sets.

The edit is identical in shape for all 23 files: replace the three module-local include paths with the shared path, and (for Registrar/HeadRegistrar files that set `$pageScript`) set `$extraScripts` before the footer include so the page's own JS still loads.

- [ ] **Step 1: Update Admin module files (2 files, no `$pageScript`)**

In `admin_dashboard.php` and `manage_user.php`, change:

```php
include 'Include/header.php';
```
to
```php
include '../Include/header.php';
```

and change:
```php
<?php include 'Include/sidebar.php'; ?>
```
to
```php
<?php include '../Include/sidebar.php'; ?>
```

and change:
```php
<?php include 'Include/footer.php'; ?>
```
to
```php
<?php include '../Include/footer.php'; ?>
```

(`settings.php`, created in Task 6, is written directly against the shared paths already — no change needed there, but move it from `Include/header.php` style paths to `../Include/header.php` style to match, since Task 6 used the old per-module-style path as a placeholder pending this task.)

- [ ] **Step 2: Update Registrar and Head Registrar module files (21 files, most have `$pageScript`)**

For each of the 21 files, apply the same three include-path swaps as Step 1 (`Include/header.php` → `../Include/header.php`, same for `sidebar.php`), and additionally, immediately before the existing `<?php include 'Include/footer.php'; ?>` line, insert:

```php
<?php
$extraScripts = [
    'https://cdn.jsdelivr.net/npm/vue@3/dist/vue.global.prod.js',
    '/SIAdrafts/Frontend/Js/Registrar/' . ($pageScript ?? 'registrar') . '.js',
];
include '../Include/footer.php';
?>
```
replacing the old `<?php include 'Include/footer.php'; ?>` line entirely.

- [ ] **Step 3: Manual verification**

Log in as each of Admin, Registrar Staff, and Head Registrar and click through every page in their sidebar (all 23 migrated pages). Confirm: sidebar renders with the correct nav items and active-page highlighting, Bootstrap styling applies without visibly breaking existing layout, all existing page-specific JS still runs (e.g. SweetAlert confirmations, Vue-powered widgets, Chart.js dashboard charts), and the badge counts (pending approvals, unread messages) still show correctly for Head Registrar / Registrar Staff.

- [ ] **Step 4: Commit**

```bash
git add SIAdrafts/Frontend/View/Admin/ SIAdrafts/Frontend/View/Registrar/ SIAdrafts/Frontend/View/HeadRegistrar/
git commit -m "Migrate Admin, Registrar, and Head Registrar views to shared layout partials"
```

---

## Task 13: Example DataTables migration (proof of baseline)

**Files:**
- Modify: `SIAdrafts/Frontend/View/Registrar/total_enrolees.php`
- Modify: `SIAdrafts/Frontend/Js/Registrar/total_enrolees.js` (or wherever its table-rendering JS lives — check the file first)

**Interfaces:**
- Consumes: `initDataTable()` (Task 11 Step 5).

This task exists to prove the Bootstrap+DataTables baseline actually works end-to-end on a real table, since the Registrar/Treasury/Registrar sub-projects will each apply the same pattern to their own new tables.

- [ ] **Step 1: Read the current table markup and JS**

Read `Frontend/View/Registrar/total_enrolees.php` and its associated JS file to find the `<table>` element's `id` and how rows are currently populated (static PHP loop vs AJAX-populated).

- [ ] **Step 2: Ensure the table has a stable `id` and wrap it appropriately**

If the table lacks an `id`, add `id="enroleesTable"` and wrap it in `<div class="table-responsive">` if not already wrapped (Bootstrap convention).

- [ ] **Step 3: Initialize DataTables on it**

At the bottom of the page (or in its JS file, after the table is populated), add:

```javascript
document.addEventListener('DOMContentLoaded', function () {
  initDataTable('#enroleesTable', {
    order: [[0, 'asc']],
  });
});
```

- [ ] **Step 4: Manual verification**

Load the Total Enrolees page as Registrar Staff; confirm the DataTables search box, sorting, and pagination controls appear and function against the existing data, and that no existing functionality on the page (export CSV button, etc.) broke.

- [ ] **Step 5: Commit**

```bash
git add SIAdrafts/Frontend/View/Registrar/total_enrolees.php SIAdrafts/Frontend/Js/Registrar/
git commit -m "Apply DataTables baseline to Total Enrolees table as a working example"
```

---

## Self-Review Notes

- **Spec coverage:** Config/secrets (Task 2), settings/current term (Task 3, 6), RBAC audit (Tasks 8-10), CSRF (Task 5, applied in Task 6), Composer/PHPMailer (Tasks 1, 7), shared UI + DataTables baseline (Tasks 11-13). All six design sections have corresponding tasks.
- **Ambiguous RBAC assignments flagged inline:** `save_schedule.php` (Task 9) has no found frontend caller — guarded defensively, flagged for confirmation rather than left unprotected or guessed silently.
- **Non-goal boundary respected:** Admission module's top-nav-to-sidebar conversion and its own DataTables tables are explicitly left to the Admission sub-project (Task 11/12 only touch Admin/Registrar/HeadRegistrar, which already use the sidebar pattern).
- **Type/interface consistency:** `get_setting()`/`set_setting()` signatures (Task 3) match their usage in Task 6; `csrf_token()`/`csrf_verify()` (Task 5) match usage in Task 6; role constants (Task 4) are used identically across Tasks 6, 8, 9, 10; `initDataTable()` (Task 11) matches its call in Task 13.
