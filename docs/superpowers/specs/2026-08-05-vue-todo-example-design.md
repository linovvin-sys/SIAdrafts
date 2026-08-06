# Vue To-Do Example — Design

## Purpose

A new standalone teaching project, `vue-todo-example/`, that extends the existing plain-script Vue MVCR examples (`vue-mvcr-example`, `vue-student-portal-example`, `vue-calculator-example`) with a real backend: login/registration and a MySQL-backed to-do list.

## Coding style constraint

Beginner-level syntax only, matching the existing examples:
- `var`, not `let`/`const` (except the existing `var { createApp } = Vue;` destructuring pattern already used in these examples)
- Plain `function () {}` — no arrow functions
- No `async`/`await` — use `fetch(...).then().catch()` chains
- No classes, no modern destructuring/spread beyond what's already used
- PHP backend written in the same plain, procedural style as `SIAdrafts/Backend` (PDO, no framework)

## Project location

New sibling project: `vue-todo-example/`, independent of SIAdrafts (separate DB, separate codebase). Not added as a module of SIAdrafts.

## File structure

```
vue-todo-example/
  index.html
  js/
    controllers/
      AuthController.js      // register, login, logout, checkSession (fetch().then())
      TaskController.js      // getTasks, createTask, updateTask, deleteTask
    views/
      LoginView.js
      RegisterView.js
      DashboardView.js       // cards, search, filter buttons, task list, "+ Add Task"
    components/
      TaskModal.js           // shared add/edit form
    router/
      router.js              // guards: redirect to /login if no session, / if already logged in
  Backend/
    db.php                   // PDO connection
    require_auth.php         // session guard, included at top of protected endpoints
    api/
      register.php
      login.php
      logout.php
      session.php             // returns current logged-in user or null
      get_tasks.php
      create_task.php
      update_task.php
      delete_task.php
    migrations/
      xxxx_create_users_table.sql
      xxxx_create_tasks_table.sql
```

## Data model

**users**
- `id` (PK, auto increment)
- `name`
- `email` (unique)
- `password_hash`
- `created_at`

**tasks**
- `id` (PK, auto increment)
- `user_id` (FK -> users.id)
- `title`
- `notes`
- `category` (free text)
- `status` (enum: `pending`, `in_progress`, `completed`; default `pending`)
- `created_at`
- `updated_at`

Tasks are private per user: every task query is scoped to the logged-in user's `id` from the session.

## Auth

- PHP session-based auth (same pattern as `SIAdrafts/Backend/api/student_login.php` and `require_student.php`).
- `register.php`: creates a user with a hashed password (`password_hash`), then logs them in (starts session).
- `login.php`: verifies email/password, starts session on success.
- `logout.php`: destroys session.
- `session.php`: returns the current logged-in user (or null) — used by the router on app boot to decide the initial route.
- `require_auth.php`: included at the top of every task endpoint; returns a 401 JSON response and exits if there's no active session.

## Frontend flow

- **App boot**: router calls `AuthController.checkSession()` once before resolving the first route. If not logged in and the target route is protected, redirect to `/login`. If logged in and the target route is `/login` or `/register`, redirect to `/`.
- **LoginView / RegisterView**: plain HTML forms, submit via `fetch` POST, show a simple inline error message `<div>` on failure. RegisterView collects name, email, password, confirm-password (client-side check that they match before submitting).
- **DashboardView**:
  - On mount, loads all of the current user's tasks once via `TaskController.getTasks()`.
  - Four stat cards (Total, Pending, In Progress, Completed) computed client-side from the loaded task list — no extra API calls.
  - A search input filters the loaded list by title/notes text (client-side `.filter()`).
  - Status filter buttons (All / Pending / In Progress / Completed) also filter the loaded list client-side.
  - "+ Add Task" button opens `TaskModal` in create mode.
  - Each task row shows: title, notes, category, a status badge, and Edit/Delete actions.
  - Clicking the status badge cycles it: pending → in_progress → completed → pending, firing `update_task.php` and updating local state on success.
  - Edit opens `TaskModal` pre-filled with the task; Delete asks for confirmation, then calls `delete_task.php` and removes it from local state.
- **TaskModal**: one shared component for add and edit. Fields: title, notes, category, status. Emits `save` (parent decides create vs. update based on whether the task has an `id`) and `cancel`.

## Error handling

Inline, plain-text error messages (no toast/notification library), consistent with the simplicity of the existing example projects. Failed API calls show a short message near the relevant form or list.

## Testing / verification

No automated test suite (matches the rest of these example projects). Verification is manual: run the app via MAMP, walk through register → login → add/edit/delete/search/filter tasks → logout, using the `run` skill or a browser.

## Out of scope

- Password reset / forgot-password flow
- Email verification
- Task due dates, priorities, or attachments
- Multi-user sharing of tasks
- Pagination (task lists are assumed small for a teaching example)
