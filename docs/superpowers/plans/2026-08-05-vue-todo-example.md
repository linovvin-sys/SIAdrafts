# Vue To-Do Example Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Build `vue-todo-example/`, a plain-script Vue MVCR to-do app with PHP+MySQL login/registration and a private-per-user task list (search, status filter, add/edit/delete via modal, click-to-cycle status).

**Architecture:** PHP session-based auth (mirrors `SIAdrafts/Backend`'s pattern, simplified). Every protected endpoint includes `require_auth.php`. The Vue Router checks session status once via `session.php` before resolving the first route, then guards `/` vs `/login` / `/register`. The dashboard loads the full task list once and does search/filter/counts client-side with plain `.filter()` — no extra round trips per keystroke or per filter click.

**Tech Stack:** Vue 3 + Vue Router 4 (via `unpkg`, plain `<script>` tags, no build step), PHP with `mysqli`, MySQL (via MAMP).

## Global Constraints

- Beginner-level syntax only, matching `vue-student-portal-example` / `vue-calculator-example`: `var` (not `let`/`const`), plain `function () {}` (no arrow functions), no `async`/`await` — use `fetch(...).then(...)` chains, no classes.
- Reactive view state uses `var { ref } = Vue;` + `ref()` inside `setup()`, exactly like the existing examples — this is the one Vue-specific pattern they already use, not a deviation from "beginner."
- PHP written procedurally (functions, not classes), matching a teaching-project style — simpler than `SIAdrafts/Backend/db.php`'s typed class, but same `mysqli` + `password_hash`/`password_verify` approach.
- DB credentials: host `localhost`, port `8889`, user `root`, password `root` (MAMP defaults), database `vue_todo_db`. If the implementer's local MAMP uses different credentials, update `Backend/db.php` accordingly.
- No automated test framework in this project (matches the other examples). Every task below is verified manually: backend tasks via `curl` against the running MAMP server, frontend tasks via a browser walkthrough.
- All new files live under `vue-todo-example/`, independent of `SIAdrafts/`.

---

### Task 1: Database schema + connection helper

**Files:**
- Create: `vue-todo-example/Backend/migrations/2026_08_05_create_users_table.sql`
- Create: `vue-todo-example/Backend/migrations/2026_08_05_create_tasks_table.sql`
- Create: `vue-todo-example/Backend/db.php`

**Interfaces:**
- Produces: `get_db_connection()` — PHP function, no args, returns a connected `mysqli` object with `utf8mb4` charset. Every later PHP file calls this.

- [ ] **Step 1: Write the users table migration**

```sql
-- vue-todo-example/Backend/migrations/2026_08_05_create_users_table.sql
CREATE DATABASE IF NOT EXISTS vue_todo_db;
USE vue_todo_db;

CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(150) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
);
```

- [ ] **Step 2: Write the tasks table migration**

```sql
-- vue-todo-example/Backend/migrations/2026_08_05_create_tasks_table.sql
USE vue_todo_db;

CREATE TABLE IF NOT EXISTS tasks (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    title VARCHAR(150) NOT NULL,
    notes TEXT,
    category VARCHAR(100),
    status ENUM('pending', 'in_progress', 'completed') NOT NULL DEFAULT 'pending',
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);
```

- [ ] **Step 3: Run both migrations against MAMP's MySQL**

Run (adjust socket/path if MAMP's `mysql` client isn't on PATH — MAMP ships one at `/Applications/MAMP/Library/bin/mysql80/bin/mysql` or similar):

```bash
/Applications/MAMP/Library/bin/mysql80/bin/mysql -h localhost -P 8889 -u root -proot < vue-todo-example/Backend/migrations/2026_08_05_create_users_table.sql
/Applications/MAMP/Library/bin/mysql80/bin/mysql -h localhost -P 8889 -u root -proot < vue-todo-example/Backend/migrations/2026_08_05_create_tasks_table.sql
```

Expected: no output (success). Verify with:

```bash
/Applications/MAMP/Library/bin/mysql80/bin/mysql -h localhost -P 8889 -u root -proot -e "USE vue_todo_db; SHOW TABLES;"
```

Expected: lists `users` and `tasks`.

- [ ] **Step 4: Write the connection helper**

```php
<?php
// vue-todo-example/Backend/db.php

function get_db_connection() {
    $host = 'localhost';
    $port = '8889';
    $username = 'root';
    $password = 'root';
    $database = 'vue_todo_db';

    $conn = new mysqli($host, $username, $password, $database, $port);

    if ($conn->connect_error) {
        die('Database Connection Failed: ' . $conn->connect_error);
    }

    $conn->set_charset('utf8mb4');

    return $conn;
}
```

- [ ] **Step 5: Verify the connection helper works**

Create a throwaway file `vue-todo-example/Backend/_check.php`:

```php
<?php
require_once __DIR__ . '/db.php';
$conn = get_db_connection();
echo 'connected ok';
$conn->close();
```

Run: `php vue-todo-example/Backend/_check.php`
Expected output: `connected ok`

Then delete `_check.php`.

- [ ] **Step 6: Commit**

```bash
git add vue-todo-example/Backend/migrations vue-todo-example/Backend/db.php
git commit -m "Add vue-todo-example DB schema and connection helper"
```

---

### Task 2: Auth session guard

**Files:**
- Create: `vue-todo-example/Backend/require_auth.php`

**Interfaces:**
- Consumes: PHP `$_SESSION` superglobal (populated by Task 3's `login.php`/`register.php`).
- Produces: `require_auth()` — PHP function, no args. If `$_SESSION['user_id']` is empty, sends a 401 JSON response and calls `exit`. Otherwise returns normally. Task 4's endpoints call this first.

- [ ] **Step 1: Write the guard**

```php
<?php
// vue-todo-example/Backend/require_auth.php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

function require_auth() {
    if (empty($_SESSION['user_id'])) {
        header('Content-Type: application/json');
        http_response_code(401);
        echo json_encode(['error' => 'Please log in.']);
        exit;
    }
}
```

- [ ] **Step 2: Verify it blocks unauthenticated requests**

Create a throwaway file `vue-todo-example/Backend/api/_check_guard.php`:

```php
<?php
require_once '../require_auth.php';
header('Content-Type: application/json');
require_auth();
echo json_encode(['ok' => true]);
```

With MAMP running (adjust the port below to match your MAMP Apache port — check MAMP's start page):

```bash
curl -i http://localhost:8888/vue-todo-example/Backend/api/_check_guard.php
```

Expected: `HTTP/1.1 401` and body `{"error":"Please log in."}`.

Then delete `_check_guard.php`.

- [ ] **Step 3: Commit**

```bash
git add vue-todo-example/Backend/require_auth.php
git commit -m "Add session auth guard for vue-todo-example API"
```

---

### Task 3: Auth endpoints (register, login, logout, session)

**Files:**
- Create: `vue-todo-example/Backend/api/register.php`
- Create: `vue-todo-example/Backend/api/login.php`
- Create: `vue-todo-example/Backend/api/logout.php`
- Create: `vue-todo-example/Backend/api/session.php`

**Interfaces:**
- Consumes: `get_db_connection()` from Task 1.
- Produces: four JSON endpoints under `Backend/api/`. `register.php` and `login.php` both return `{ "success": true, "user": { "id", "name", "email" } }` on success or `{ "error": "..." }` on failure, and both populate `$_SESSION['user_id' | 'user_name' | 'user_email']`. `session.php` returns `{ "user": {...} | null }`. `logout.php` returns `{ "success": true }`. Task 5's `AuthController.js` calls these by exact path.

- [ ] **Step 1: Write register.php**

```php
<?php
// vue-todo-example/Backend/api/register.php
session_start();
require_once '../db.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed.']);
    exit;
}

$name = trim($_POST['name'] ?? '');
$email = trim($_POST['email'] ?? '');
$password = $_POST['password'] ?? '';
$confirmPassword = $_POST['confirm_password'] ?? '';

if ($name === '' || $email === '' || $password === '') {
    echo json_encode(['error' => 'Name, email, and password are required.']);
    exit;
}

if ($password !== $confirmPassword) {
    echo json_encode(['error' => 'Passwords do not match.']);
    exit;
}

$conn = get_db_connection();

$checkStmt = $conn->prepare('SELECT id FROM users WHERE email = ?');
$checkStmt->bind_param('s', $email);
$checkStmt->execute();
$existing = $checkStmt->get_result()->fetch_assoc();
$checkStmt->close();

if ($existing) {
    echo json_encode(['error' => 'An account with that email already exists.']);
    $conn->close();
    exit;
}

$passwordHash = password_hash($password, PASSWORD_DEFAULT);

$insertStmt = $conn->prepare('INSERT INTO users (name, email, password_hash) VALUES (?, ?, ?)');
$insertStmt->bind_param('sss', $name, $email, $passwordHash);
$insertStmt->execute();
$userId = $insertStmt->insert_id;
$insertStmt->close();
$conn->close();

$_SESSION['user_id'] = $userId;
$_SESSION['user_name'] = $name;
$_SESSION['user_email'] = $email;

echo json_encode([
    'success' => true,
    'user' => ['id' => $userId, 'name' => $name, 'email' => $email],
]);
```

- [ ] **Step 2: Write login.php**

```php
<?php
// vue-todo-example/Backend/api/login.php
session_start();
require_once '../db.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed.']);
    exit;
}

$email = trim($_POST['email'] ?? '');
$password = $_POST['password'] ?? '';

if ($email === '' || $password === '') {
    echo json_encode(['error' => 'Email and password are required.']);
    exit;
}

$conn = get_db_connection();

$stmt = $conn->prepare('SELECT id, name, email, password_hash FROM users WHERE email = ?');
$stmt->bind_param('s', $email);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();
$stmt->close();
$conn->close();

if (!$user || !password_verify($password, $user['password_hash'])) {
    echo json_encode(['error' => 'Invalid email or password.']);
    exit;
}

session_regenerate_id(true);

$_SESSION['user_id'] = $user['id'];
$_SESSION['user_name'] = $user['name'];
$_SESSION['user_email'] = $user['email'];

echo json_encode([
    'success' => true,
    'user' => ['id' => $user['id'], 'name' => $user['name'], 'email' => $user['email']],
]);
```

- [ ] **Step 3: Write logout.php and session.php**

```php
<?php
// vue-todo-example/Backend/api/logout.php
session_start();
header('Content-Type: application/json');

$_SESSION = [];
session_destroy();

echo json_encode(['success' => true]);
```

```php
<?php
// vue-todo-example/Backend/api/session.php
session_start();
header('Content-Type: application/json');

if (empty($_SESSION['user_id'])) {
    echo json_encode(['user' => null]);
    exit;
}

echo json_encode([
    'user' => [
        'id' => $_SESSION['user_id'],
        'name' => $_SESSION['user_name'],
        'email' => $_SESSION['user_email'],
    ],
]);
```

- [ ] **Step 4: Verify the full auth cycle with curl**

With MAMP running (adjust port to match your setup):

```bash
curl -c /tmp/cookies.txt -i http://localhost:8888/vue-todo-example/Backend/api/register.php \
  -d "name=Test User" -d "email=test@example.com" -d "password=secret123" -d "confirm_password=secret123"
```
Expected: `{"success":true,"user":{"id":1,"name":"Test User","email":"test@example.com"}}`

```bash
curl -b /tmp/cookies.txt http://localhost:8888/vue-todo-example/Backend/api/session.php
```
Expected: `{"user":{"id":1,"name":"Test User","email":"test@example.com"}}`

```bash
curl -b /tmp/cookies.txt -X POST http://localhost:8888/vue-todo-example/Backend/api/logout.php
curl -b /tmp/cookies.txt http://localhost:8888/vue-todo-example/Backend/api/session.php
```
Expected final call: `{"user":null}`

```bash
curl -c /tmp/cookies2.txt -i http://localhost:8888/vue-todo-example/Backend/api/login.php \
  -d "email=test@example.com" -d "password=secret123"
```
Expected: `{"success":true,"user":{"id":1,"name":"Test User","email":"test@example.com"}}`

- [ ] **Step 5: Commit**

```bash
git add vue-todo-example/Backend/api/register.php vue-todo-example/Backend/api/login.php vue-todo-example/Backend/api/logout.php vue-todo-example/Backend/api/session.php
git commit -m "Add register/login/logout/session endpoints for vue-todo-example"
```

---

### Task 4: Task CRUD endpoints

**Files:**
- Create: `vue-todo-example/Backend/api/get_tasks.php`
- Create: `vue-todo-example/Backend/api/create_task.php`
- Create: `vue-todo-example/Backend/api/update_task.php`
- Create: `vue-todo-example/Backend/api/delete_task.php`

**Interfaces:**
- Consumes: `require_auth()` (Task 2), `get_db_connection()` (Task 1), `$_SESSION['user_id']` (Task 3).
- Produces: `get_tasks.php` returns `{ "tasks": [{ "id", "title", "notes", "category", "status", "created_at", "updated_at" }, ...] }`. `create_task.php` and `update_task.php` return `{ "success": true, "task": {...} }` or `{ "error": "..." }`. `delete_task.php` returns `{ "success": true }` or `{ "error": "..." }`. Task 7's `TaskController.js` calls these by exact path with these exact field names.

- [ ] **Step 1: Write get_tasks.php**

```php
<?php
// vue-todo-example/Backend/api/get_tasks.php
require_once '../require_auth.php';
require_once '../db.php';

header('Content-Type: application/json');
require_auth();

$conn = get_db_connection();

$stmt = $conn->prepare('SELECT id, title, notes, category, status, created_at, updated_at FROM tasks WHERE user_id = ? ORDER BY created_at DESC');
$stmt->bind_param('i', $_SESSION['user_id']);
$stmt->execute();
$result = $stmt->get_result();

$tasks = [];
while ($row = $result->fetch_assoc()) {
    $tasks[] = $row;
}

$stmt->close();
$conn->close();

echo json_encode(['tasks' => $tasks]);
```

- [ ] **Step 2: Write create_task.php**

```php
<?php
// vue-todo-example/Backend/api/create_task.php
require_once '../require_auth.php';
require_once '../db.php';

header('Content-Type: application/json');
require_auth();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed.']);
    exit;
}

$title = trim($_POST['title'] ?? '');
$notes = trim($_POST['notes'] ?? '');
$category = trim($_POST['category'] ?? '');
$status = $_POST['status'] ?? 'pending';

if ($title === '') {
    echo json_encode(['error' => 'Title is required.']);
    exit;
}

$allowedStatuses = ['pending', 'in_progress', 'completed'];
if (!in_array($status, $allowedStatuses)) {
    $status = 'pending';
}

$conn = get_db_connection();

$stmt = $conn->prepare('INSERT INTO tasks (user_id, title, notes, category, status) VALUES (?, ?, ?, ?, ?)');
$stmt->bind_param('issss', $_SESSION['user_id'], $title, $notes, $category, $status);
$stmt->execute();
$taskId = $stmt->insert_id;
$stmt->close();
$conn->close();

echo json_encode([
    'success' => true,
    'task' => [
        'id' => $taskId,
        'title' => $title,
        'notes' => $notes,
        'category' => $category,
        'status' => $status,
    ],
]);
```

- [ ] **Step 3: Write update_task.php**

```php
<?php
// vue-todo-example/Backend/api/update_task.php
require_once '../require_auth.php';
require_once '../db.php';

header('Content-Type: application/json');
require_auth();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed.']);
    exit;
}

$id = (int)($_POST['id'] ?? 0);
$title = trim($_POST['title'] ?? '');
$notes = trim($_POST['notes'] ?? '');
$category = trim($_POST['category'] ?? '');
$status = $_POST['status'] ?? 'pending';

if ($id <= 0 || $title === '') {
    echo json_encode(['error' => 'A valid task id and title are required.']);
    exit;
}

$allowedStatuses = ['pending', 'in_progress', 'completed'];
if (!in_array($status, $allowedStatuses)) {
    $status = 'pending';
}

$conn = get_db_connection();

$checkStmt = $conn->prepare('SELECT id FROM tasks WHERE id = ? AND user_id = ?');
$checkStmt->bind_param('ii', $id, $_SESSION['user_id']);
$checkStmt->execute();
$owned = $checkStmt->get_result()->fetch_assoc();
$checkStmt->close();

if (!$owned) {
    http_response_code(404);
    echo json_encode(['error' => 'Task not found.']);
    $conn->close();
    exit;
}

$stmt = $conn->prepare('UPDATE tasks SET title = ?, notes = ?, category = ?, status = ? WHERE id = ? AND user_id = ?');
$stmt->bind_param('ssssii', $title, $notes, $category, $status, $id, $_SESSION['user_id']);
$stmt->execute();
$stmt->close();
$conn->close();

echo json_encode([
    'success' => true,
    'task' => [
        'id' => $id,
        'title' => $title,
        'notes' => $notes,
        'category' => $category,
        'status' => $status,
    ],
]);
```

- [ ] **Step 4: Write delete_task.php**

```php
<?php
// vue-todo-example/Backend/api/delete_task.php
require_once '../require_auth.php';
require_once '../db.php';

header('Content-Type: application/json');
require_auth();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed.']);
    exit;
}

$id = (int)($_POST['id'] ?? 0);

if ($id <= 0) {
    echo json_encode(['error' => 'A valid task id is required.']);
    exit;
}

$conn = get_db_connection();

$stmt = $conn->prepare('DELETE FROM tasks WHERE id = ? AND user_id = ?');
$stmt->bind_param('ii', $id, $_SESSION['user_id']);
$stmt->execute();
$deleted = $stmt->affected_rows > 0;
$stmt->close();
$conn->close();

if (!$deleted) {
    http_response_code(404);
    echo json_encode(['error' => 'Task not found.']);
    exit;
}

echo json_encode(['success' => true]);
```

- [ ] **Step 5: Verify the full task CRUD cycle with curl**

Reuse the logged-in cookie jar from Task 3 (`/tmp/cookies2.txt`), adjust port as needed:

```bash
curl -b /tmp/cookies2.txt -i http://localhost:8888/vue-todo-example/Backend/api/create_task.php \
  -d "title=Buy groceries" -d "notes=milk, eggs" -d "category=Errands" -d "status=pending"
```
Expected: `{"success":true,"task":{"id":1,"title":"Buy groceries","notes":"milk, eggs","category":"Errands","status":"pending"}}`

```bash
curl -b /tmp/cookies2.txt http://localhost:8888/vue-todo-example/Backend/api/get_tasks.php
```
Expected: `{"tasks":[{"id":1,"title":"Buy groceries",...}]}`

```bash
curl -b /tmp/cookies2.txt -i http://localhost:8888/vue-todo-example/Backend/api/update_task.php \
  -d "id=1" -d "title=Buy groceries" -d "notes=milk, eggs, bread" -d "category=Errands" -d "status=in_progress"
```
Expected: `{"success":true,"task":{"id":1,...,"status":"in_progress"}}`

```bash
curl -b /tmp/cookies2.txt -i http://localhost:8888/vue-todo-example/Backend/api/delete_task.php -d "id=1"
```
Expected: `{"success":true}`

```bash
curl -b /tmp/cookies2.txt http://localhost:8888/vue-todo-example/Backend/api/get_tasks.php
```
Expected: `{"tasks":[]}`

- [ ] **Step 6: Commit**

```bash
git add vue-todo-example/Backend/api/get_tasks.php vue-todo-example/Backend/api/create_task.php vue-todo-example/Backend/api/update_task.php vue-todo-example/Backend/api/delete_task.php
git commit -m "Add task CRUD endpoints for vue-todo-example"
```

---

### Task 5: App shell, router with auth guard, AuthController

**Files:**
- Create: `vue-todo-example/index.html`
- Create: `vue-todo-example/js/controllers/AuthController.js`
- Create: `vue-todo-example/js/router/router.js`
- Create (stub, filled in Task 6): `vue-todo-example/js/views/LoginView.js`
- Create (stub, filled in Task 6): `vue-todo-example/js/views/RegisterView.js`
- Create (stub, filled in Task 9): `vue-todo-example/js/views/DashboardView.js`

**Interfaces:**
- Produces: `AuthController.register(name, email, password, confirmPassword)`, `AuthController.login(email, password)`, `AuthController.logout()`, `AuthController.checkSession()` — each returns a `fetch` promise that resolves to the parsed JSON body (same shapes as Task 3's endpoints). Task 6 and Task 9 call these.
- Produces: global `router` (Vue Router instance) mounted in `index.html`.

- [ ] **Step 1: Write minimal view stubs so the router/app boot without errors**

```js
// vue-todo-example/js/views/LoginView.js
var LoginView = {
  template: `<div class="page"><h1>Log In (stub)</h1></div>`,
};
```

```js
// vue-todo-example/js/views/RegisterView.js
var RegisterView = {
  template: `<div class="page"><h1>Register (stub)</h1></div>`,
};
```

```js
// vue-todo-example/js/views/DashboardView.js
var DashboardView = {
  template: `<div class="page"><h1>Dashboard (stub)</h1></div>`,
};
```

- [ ] **Step 2: Write AuthController.js**

```js
// vue-todo-example/js/controllers/AuthController.js
var AuthController = {
  register: function (name, email, password, confirmPassword) {
    var formData = new FormData();
    formData.append('name', name);
    formData.append('email', email);
    formData.append('password', password);
    formData.append('confirm_password', confirmPassword);

    return fetch('./Backend/api/register.php', {
      method: 'POST',
      body: formData,
    }).then(function (response) {
      return response.json();
    });
  },

  login: function (email, password) {
    var formData = new FormData();
    formData.append('email', email);
    formData.append('password', password);

    return fetch('./Backend/api/login.php', {
      method: 'POST',
      body: formData,
    }).then(function (response) {
      return response.json();
    });
  },

  logout: function () {
    return fetch('./Backend/api/logout.php', {
      method: 'POST',
    }).then(function (response) {
      return response.json();
    });
  },

  checkSession: function () {
    return fetch('./Backend/api/session.php').then(function (response) {
      return response.json();
    });
  },
};
```

- [ ] **Step 3: Write router.js with the auth guard**

```js
// vue-todo-example/js/router/router.js
var { createRouter, createWebHashHistory } = VueRouter;

var router = createRouter({
  history: createWebHashHistory(),
  routes: [
    { path: '/login', component: LoginView, meta: { guest: true } },
    { path: '/register', component: RegisterView, meta: { guest: true } },
    { path: '/', component: DashboardView, meta: { requiresAuth: true } },
  ],
});

router.beforeEach(function (to, from, next) {
  AuthController.checkSession().then(function (data) {
    var loggedIn = !!data.user;

    if (to.meta.requiresAuth && !loggedIn) {
      next('/login');
    } else if (to.meta.guest && loggedIn) {
      next('/');
    } else {
      next();
    }
  });
});
```

- [ ] **Step 4: Write index.html**

```html
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Vue To-Do Example</title>
<style>
  body { font-family: system-ui, sans-serif; margin: 0; color: #222; }
  .page { max-width: 720px; margin: 0 auto; padding: 96px 20px 40px; }
  h1 { font-size: 1.5rem; margin: 0; }
  nav a { margin-left: 12px; color: inherit; text-decoration: none; }
  nav a:hover { text-decoration: underline; }
  .topbar { position: fixed; top: 0; left: 0; right: 0; z-index: 10; background: #fff; padding: 16px 20px; border-bottom: 1px solid #eee; display: flex; align-items: center; justify-content: space-between; }
  form { display: flex; flex-direction: column; gap: 10px; max-width: 320px; }
  input, select, textarea { padding: 8px 10px; border: 1px solid #ccc; border-radius: 4px; font: inherit; }
  button { cursor: pointer; padding: 8px 14px; border: 1px solid #222; background: #222; color: #fff; border-radius: 4px; }
  button.secondary { background: #fff; color: #222; }
  .error { color: #b00020; margin-top: 8px; }
  .cards { display: grid; grid-template-columns: repeat(4, 1fr); gap: 12px; margin: 16px 0; }
  .stat-card { background: #f8f8f8; border: 1px solid #eee; border-radius: 6px; padding: 16px; text-align: center; }
  .stat-value { font-size: 1.6rem; font-weight: bold; }
  .stat-label { color: #666; font-size: 0.85rem; }
  .toolbar { display: flex; gap: 8px; margin: 16px 0; flex-wrap: wrap; }
  .filter-bar button { padding: 6px 14px; border: 1px solid #ccc; background: #fff; border-radius: 4px; }
  .filter-bar button.active { background: #222; color: #fff; border-color: #222; }
  .search-box { flex: 1; min-width: 200px; padding: 8px 10px; border: 1px solid #ccc; border-radius: 4px; }
  table { width: 100%; border-collapse: collapse; margin-top: 12px; }
  th, td { text-align: left; padding: 8px 10px; border-bottom: 1px solid #eee; }
  .status-badge { cursor: pointer; padding: 4px 10px; border-radius: 12px; font-size: 0.8rem; border: 1px solid #ccc; display: inline-block; }
  .status-pending { background: #fff3cd; }
  .status-in_progress { background: #cfe2ff; }
  .status-completed { background: #d1e7dd; }
  .modal-backdrop { position: fixed; inset: 0; background: rgba(0,0,0,0.4); display: flex; align-items: center; justify-content: center; z-index: 20; }
  .modal { background: #fff; border-radius: 8px; padding: 24px; width: 360px; }
  .modal-actions { display: flex; justify-content: flex-end; gap: 8px; margin-top: 12px; }
</style>
</head>
<body>
  <div id="app">
    <router-view></router-view>
  </div>

  <script src="https://unpkg.com/vue@3/dist/vue.global.js"></script>
  <script src="https://unpkg.com/vue-router@4/dist/vue-router.global.js"></script>

  <script src="./js/controllers/AuthController.js"></script>
  <script src="./js/controllers/TaskController.js"></script>
  <script src="./js/components/TaskModal.js"></script>
  <script src="./js/views/LoginView.js"></script>
  <script src="./js/views/RegisterView.js"></script>
  <script src="./js/views/DashboardView.js"></script>
  <script src="./js/router/router.js"></script>

  <script>
    var { createApp } = Vue;
    createApp({}).use(router).mount('#app');
  </script>
</body>
</html>
```

Note: `index.html` references `TaskController.js` and `TaskModal.js`, created in Task 7 and Task 8. Create empty placeholder files now so the page doesn't 404 on script load:

```js
// vue-todo-example/js/controllers/TaskController.js
var TaskController = {};
```

```js
// vue-todo-example/js/components/TaskModal.js
var TaskModal = { template: '<div></div>' };
```

- [ ] **Step 5: Verify the guard redirects to login**

Open `http://localhost:8888/vue-todo-example/index.html` in a browser (adjust port as needed) with no session cookie (private/incognito window). Expected: URL becomes `#/login` and the page shows "Log In (stub)".

- [ ] **Step 6: Commit**

```bash
git add vue-todo-example/index.html vue-todo-example/js/controllers/AuthController.js vue-todo-example/js/controllers/TaskController.js vue-todo-example/js/components/TaskModal.js vue-todo-example/js/router/router.js vue-todo-example/js/views/LoginView.js vue-todo-example/js/views/RegisterView.js vue-todo-example/js/views/DashboardView.js
git commit -m "Add vue-todo-example app shell, router guard, and AuthController"
```

---

### Task 6: LoginView and RegisterView

**Files:**
- Modify: `vue-todo-example/js/views/LoginView.js` (replace stub)
- Modify: `vue-todo-example/js/views/RegisterView.js` (replace stub)

**Interfaces:**
- Consumes: `AuthController.login(email, password)`, `AuthController.register(name, email, password, confirmPassword)` (Task 5).

- [ ] **Step 1: Write LoginView.js**

```js
// vue-todo-example/js/views/LoginView.js
var { ref } = Vue;

var LoginView = {
  setup() {
    var email = ref('');
    var password = ref('');
    var errorMsg = ref('');

    function submit() {
      errorMsg.value = '';
      AuthController.login(email.value, password.value).then(function (data) {
        if (data.error) {
          errorMsg.value = data.error;
        } else {
          window.location.hash = '#/';
        }
      });
    }

    return { email, password, errorMsg, submit };
  },
  template: `
    <div class="page">
      <h1>Log In</h1>
      <form @submit.prevent="submit">
        <input v-model="email" type="email" placeholder="Email" required />
        <input v-model="password" type="password" placeholder="Password" required />
        <button type="submit">Log In</button>
      </form>
      <p class="error" v-if="errorMsg">{{ errorMsg }}</p>
      <p><router-link to="/register">Need an account? Register</router-link></p>
    </div>
  `,
};
```

- [ ] **Step 2: Write RegisterView.js**

```js
// vue-todo-example/js/views/RegisterView.js
var { ref } = Vue;

var RegisterView = {
  setup() {
    var name = ref('');
    var email = ref('');
    var password = ref('');
    var confirmPassword = ref('');
    var errorMsg = ref('');

    function submit() {
      errorMsg.value = '';

      if (password.value !== confirmPassword.value) {
        errorMsg.value = 'Passwords do not match.';
        return;
      }

      AuthController.register(name.value, email.value, password.value, confirmPassword.value).then(function (data) {
        if (data.error) {
          errorMsg.value = data.error;
        } else {
          window.location.hash = '#/';
        }
      });
    }

    return { name, email, password, confirmPassword, errorMsg, submit };
  },
  template: `
    <div class="page">
      <h1>Register</h1>
      <form @submit.prevent="submit">
        <input v-model="name" placeholder="Name" required />
        <input v-model="email" type="email" placeholder="Email" required />
        <input v-model="password" type="password" placeholder="Password" required />
        <input v-model="confirmPassword" type="password" placeholder="Confirm password" required />
        <button type="submit">Register</button>
      </form>
      <p class="error" v-if="errorMsg">{{ errorMsg }}</p>
      <p><router-link to="/login">Already have an account? Log in</router-link></p>
    </div>
  `,
};
```

- [ ] **Step 3: Verify registration and login in the browser**

With MAMP running, open `http://localhost:8888/vue-todo-example/index.html#/register` in a fresh private window. Fill in name/email/password/confirm, submit. Expected: redirected to `#/` (shows "Dashboard (stub)"). Reload the page — expected: still on `#/` (session persists), not bounced to login.

Open a second private window, go to `#/login`, log in with the same credentials. Expected: redirected to `#/`.

- [ ] **Step 4: Commit**

```bash
git add vue-todo-example/js/views/LoginView.js vue-todo-example/js/views/RegisterView.js
git commit -m "Implement vue-todo-example LoginView and RegisterView"
```

---

### Task 7: TaskController

**Files:**
- Modify: `vue-todo-example/js/controllers/TaskController.js` (replace placeholder)

**Interfaces:**
- Produces: `TaskController.getTasks()`, `TaskController.createTask(task)`, `TaskController.updateTask(task)`, `TaskController.deleteTask(id)` — each returns a `fetch` promise resolving to parsed JSON (same shapes as Task 4's endpoints). `task` is `{ id, title, notes, category, status }` (`id` omitted for `createTask`). Task 9 calls these.

- [ ] **Step 1: Write TaskController.js**

```js
// vue-todo-example/js/controllers/TaskController.js
var TaskController = {
  getTasks: function () {
    return fetch('./Backend/api/get_tasks.php').then(function (response) {
      return response.json();
    });
  },

  createTask: function (task) {
    var formData = new FormData();
    formData.append('title', task.title);
    formData.append('notes', task.notes);
    formData.append('category', task.category);
    formData.append('status', task.status);

    return fetch('./Backend/api/create_task.php', {
      method: 'POST',
      body: formData,
    }).then(function (response) {
      return response.json();
    });
  },

  updateTask: function (task) {
    var formData = new FormData();
    formData.append('id', task.id);
    formData.append('title', task.title);
    formData.append('notes', task.notes);
    formData.append('category', task.category);
    formData.append('status', task.status);

    return fetch('./Backend/api/update_task.php', {
      method: 'POST',
      body: formData,
    }).then(function (response) {
      return response.json();
    });
  },

  deleteTask: function (id) {
    var formData = new FormData();
    formData.append('id', id);

    return fetch('./Backend/api/delete_task.php', {
      method: 'POST',
      body: formData,
    }).then(function (response) {
      return response.json();
    });
  },
};
```

- [ ] **Step 2: Verify from the browser console**

With a logged-in session open (from Task 6's verification), open devtools console on the app page and run:

```js
TaskController.createTask({ title: 'Test task', notes: 'n', category: 'General', status: 'pending' }).then(function (d) { console.log(d); });
```
Expected: logs `{success: true, task: {id: 1, ...}}`.

```js
TaskController.getTasks().then(function (d) { console.log(d); });
```
Expected: logs `{tasks: [{id: 1, title: "Test task", ...}]}`.

- [ ] **Step 3: Commit**

```bash
git add vue-todo-example/js/controllers/TaskController.js
git commit -m "Implement vue-todo-example TaskController"
```

---

### Task 8: TaskModal component

**Files:**
- Modify: `vue-todo-example/js/components/TaskModal.js` (replace placeholder)

**Interfaces:**
- Consumes: prop `task` — `null` for create mode, or `{ id, title, notes, category, status }` for edit mode.
- Produces: emits `save` with payload `{ id, title, notes, category, status }` (`id` is `undefined` in create mode), and emits `cancel` with no payload. Task 9's `DashboardView` renders this and listens for both events.

- [ ] **Step 1: Write TaskModal.js**

```js
// vue-todo-example/js/components/TaskModal.js
var { ref, watch } = Vue;

var TaskModal = {
  props: ['task'],
  emits: ['save', 'cancel'],
  setup(props, context) {
    var title = ref('');
    var notes = ref('');
    var category = ref('');
    var status = ref('pending');

    function loadFromProp() {
      if (props.task) {
        title.value = props.task.title;
        notes.value = props.task.notes;
        category.value = props.task.category;
        status.value = props.task.status;
      } else {
        title.value = '';
        notes.value = '';
        category.value = '';
        status.value = 'pending';
      }
    }

    loadFromProp();
    watch(function () { return props.task; }, loadFromProp);

    function submit() {
      context.emit('save', {
        id: props.task ? props.task.id : undefined,
        title: title.value,
        notes: notes.value,
        category: category.value,
        status: status.value,
      });
    }

    function cancel() {
      context.emit('cancel');
    }

    return { title, notes, category, status, submit, cancel };
  },
  template: `
    <div class="modal-backdrop" @click.self="cancel">
      <div class="modal">
        <h2>{{ task ? 'Edit Task' : 'Add Task' }}</h2>
        <form @submit.prevent="submit">
          <input v-model="title" placeholder="Title" required />
          <textarea v-model="notes" placeholder="Notes"></textarea>
          <input v-model="category" placeholder="Subject / Category" />
          <select v-model="status">
            <option value="pending">Pending</option>
            <option value="in_progress">In Progress</option>
            <option value="completed">Completed</option>
          </select>
          <div class="modal-actions">
            <button type="button" class="secondary" @click="cancel">Cancel</button>
            <button type="submit">Save</button>
          </div>
        </form>
      </div>
    </div>
  `,
};
```

- [ ] **Step 2: Verify in isolation (temporary harness)**

Temporarily change `DashboardView.js`'s stub template to `<task-modal :task="null" @save="console.log" @cancel="console.log"></task-modal>` and register it via `components: { TaskModal }` in the stub's options, reload `#/`. Expected: modal renders with empty fields; filling in a title and clicking Save logs the payload to console; clicking Cancel logs with no payload. Revert this temporary change afterward — Task 9 replaces `DashboardView.js` properly.

- [ ] **Step 3: Commit**

```bash
git add vue-todo-example/js/components/TaskModal.js
git commit -m "Implement vue-todo-example TaskModal component"
```

---

### Task 9: DashboardView — cards, search, filter, task list, modal wiring

**Files:**
- Modify: `vue-todo-example/js/views/DashboardView.js` (replace stub)

**Interfaces:**
- Consumes: `TaskController.getTasks/createTask/updateTask/deleteTask` (Task 7), `AuthController.logout` (Task 5), `TaskModal` component (Task 8).

- [ ] **Step 1: Write DashboardView.js**

```js
// vue-todo-example/js/views/DashboardView.js
var { ref, computed } = Vue;

var STATUS_LABELS = {
  pending: 'Pending',
  in_progress: 'In Progress',
  completed: 'Completed',
};

var STATUS_ORDER = ['pending', 'in_progress', 'completed'];

var DashboardView = {
  components: { TaskModal: TaskModal },
  setup() {
    var tasks = ref([]);
    var searchText = ref('');
    var statusFilter = ref('');
    var showModal = ref(false);
    var editingTask = ref(null);
    var errorMsg = ref('');

    function loadTasks() {
      TaskController.getTasks().then(function (data) {
        tasks.value = data.tasks || [];
      });
    }

    loadTasks();

    var filteredTasks = computed(function () {
      var lowerSearch = searchText.value.toLowerCase();
      return tasks.value.filter(function (t) {
        var matchesStatus = !statusFilter.value || t.status === statusFilter.value;
        var matchesSearch = !lowerSearch ||
          t.title.toLowerCase().indexOf(lowerSearch) !== -1 ||
          (t.notes || '').toLowerCase().indexOf(lowerSearch) !== -1;
        return matchesStatus && matchesSearch;
      });
    });

    var totalCount = computed(function () { return tasks.value.length; });
    var pendingCount = computed(function () {
      return tasks.value.filter(function (t) { return t.status === 'pending'; }).length;
    });
    var inProgressCount = computed(function () {
      return tasks.value.filter(function (t) { return t.status === 'in_progress'; }).length;
    });
    var completedCount = computed(function () {
      return tasks.value.filter(function (t) { return t.status === 'completed'; }).length;
    });

    function statusLabel(status) {
      return STATUS_LABELS[status];
    }

    function cycleStatus(task) {
      var currentIndex = STATUS_ORDER.indexOf(task.status);
      var nextStatus = STATUS_ORDER[(currentIndex + 1) % STATUS_ORDER.length];

      TaskController.updateTask({
        id: task.id,
        title: task.title,
        notes: task.notes,
        category: task.category,
        status: nextStatus,
      }).then(function (data) {
        if (data.success) {
          task.status = nextStatus;
          errorMsg.value = '';
        } else {
          errorMsg.value = data.error;
        }
      });
    }

    function openAddModal() {
      editingTask.value = null;
      showModal.value = true;
    }

    function openEditModal(task) {
      editingTask.value = task;
      showModal.value = true;
    }

    function closeModal() {
      showModal.value = false;
      editingTask.value = null;
    }

    function saveTask(payload) {
      var request = payload.id ? TaskController.updateTask(payload) : TaskController.createTask(payload);

      request.then(function (data) {
        if (data.success) {
          errorMsg.value = '';
          loadTasks();
          closeModal();
        } else {
          errorMsg.value = data.error;
        }
      });
    }

    function removeTask(task) {
      if (!window.confirm('Delete "' + task.title + '"?')) {
        return;
      }

      TaskController.deleteTask(task.id).then(function (data) {
        if (data.success) {
          errorMsg.value = '';
          tasks.value = tasks.value.filter(function (t) { return t.id !== task.id; });
        } else {
          errorMsg.value = data.error;
        }
      });
    }

    function logout() {
      AuthController.logout().then(function () {
        window.location.hash = '#/login';
      });
    }

    return {
      tasks,
      searchText,
      statusFilter,
      showModal,
      editingTask,
      errorMsg,
      filteredTasks,
      totalCount,
      pendingCount,
      inProgressCount,
      completedCount,
      statusLabel,
      cycleStatus,
      openAddModal,
      openEditModal,
      closeModal,
      saveTask,
      removeTask,
      logout,
    };
  },
  template: `
    <div class="page">
      <div class="topbar">
        <h1>My Tasks</h1>
        <nav>
          <a href="#" @click.prevent="logout">Log Out</a>
        </nav>
      </div>

      <div class="cards">
        <div class="stat-card">
          <div class="stat-value">{{ totalCount }}</div>
          <div class="stat-label">Total</div>
        </div>
        <div class="stat-card">
          <div class="stat-value">{{ pendingCount }}</div>
          <div class="stat-label">Pending</div>
        </div>
        <div class="stat-card">
          <div class="stat-value">{{ inProgressCount }}</div>
          <div class="stat-label">In Progress</div>
        </div>
        <div class="stat-card">
          <div class="stat-value">{{ completedCount }}</div>
          <div class="stat-label">Completed</div>
        </div>
      </div>

      <p class="error" v-if="errorMsg">{{ errorMsg }}</p>

      <div class="toolbar">
        <input v-model="searchText" class="search-box" placeholder="Search tasks..." />
        <button @click="openAddModal">+ Add Task</button>
      </div>

      <div class="filter-bar">
        <button @click="statusFilter = ''" :class="{ active: statusFilter === '' }">All</button>
        <button @click="statusFilter = 'pending'" :class="{ active: statusFilter === 'pending' }">Pending</button>
        <button @click="statusFilter = 'in_progress'" :class="{ active: statusFilter === 'in_progress' }">In Progress</button>
        <button @click="statusFilter = 'completed'" :class="{ active: statusFilter === 'completed' }">Completed</button>
      </div>

      <table>
        <thead>
          <tr>
            <th>Title</th>
            <th>Category</th>
            <th>Status</th>
            <th></th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="t in filteredTasks" :key="t.id">
            <td>
              <strong>{{ t.title }}</strong>
              <div v-if="t.notes">{{ t.notes }}</div>
            </td>
            <td>{{ t.category }}</td>
            <td>
              <span class="status-badge" :class="'status-' + t.status" @click="cycleStatus(t)">
                {{ statusLabel(t.status) }}
              </span>
            </td>
            <td>
              <button class="secondary" @click="openEditModal(t)">Edit</button>
              <button class="secondary" @click="removeTask(t)">Delete</button>
            </td>
          </tr>
        </tbody>
      </table>
      <p v-if="filteredTasks.length === 0">No tasks found.</p>

      <task-modal v-if="showModal" :task="editingTask" @save="saveTask" @cancel="closeModal"></task-modal>
    </div>
  `,
};
```

- [ ] **Step 2: Full browser walkthrough**

With MAMP running, open a fresh private window at `http://localhost:8888/vue-todo-example/index.html`:

1. Register a new account → expect redirect to `#/` showing 4 cards all at 0 and "No tasks found."
2. Click "+ Add Task", fill in title "Write report", category "Work", save → expect it appears in the table, Total = 1, Pending = 1.
3. Click its status badge once → expect it becomes "In Progress", In Progress card = 1, Pending = 0.
4. Click the status badge twice more → expect it cycles to "Completed" then back to "Pending".
5. Type part of the title into the search box → expect the table filters to matching rows only.
6. Clear search, click the "Completed" filter button while the task is Pending → expect the table shows "No tasks found."
7. Click "All", click "Edit" on the task, change its notes, save → expect the updated notes show in the table.
8. Click "Delete", confirm → expect the task disappears and all cards return to 0.
9. Click "Log Out" → expect redirect to `#/login`. Reload the page directly at `#/` → expect it bounces back to `#/login` (auth guard still enforced).

- [ ] **Step 3: Commit**

```bash
git add vue-todo-example/js/views/DashboardView.js
git commit -m "Implement vue-todo-example DashboardView with cards, search, filter, and modal wiring"
```

---

### Task 10: Final end-to-end pass

**Files:** none (verification only)

- [ ] **Step 1: Re-run the full walkthrough from Task 9 Step 2 top to bottom in one sitting**, plus:
  - Register two different accounts in two separate private windows, add a task under each, and confirm each account only ever sees its own task (private-per-user check).
  - Try registering with an email that's already taken → expect the inline error "An account with that email already exists."
  - Try logging in with a wrong password → expect "Invalid email or password."

- [ ] **Step 2: Fix any issues found**, committing each fix separately with a descriptive message.

- [ ] **Step 3: Final commit confirming the app works end-to-end**

```bash
git add -A
git commit -m "Verify vue-todo-example end-to-end" --allow-empty
```
