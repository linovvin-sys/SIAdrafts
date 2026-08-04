document.addEventListener('DOMContentLoaded', init);

const API_BASE = '/SIAdrafts/Backend/api/';

// Small "3 pending" label next to each panel title, matching the count
// badge convention used on registrar_dashboard.php's panels.
function setCount(countId, n) {
  const el = document.getElementById(countId);
  if (el) el.textContent = n === 1 ? '1 pending' : n + ' pending';
}

// Shimmer placeholder rows shown while a queue is being fetched, so the
// panel never sits as a bare empty table during load.
function showSkeleton(bodyId, cols) {
  const body = document.getElementById(bodyId);
  if (!body) return;
  body.innerHTML = '';
  for (let i = 0; i < 3; i++) {
    const tr = document.createElement('tr');
    tr.className = 'skeleton-row';
    tr.setAttribute('aria-hidden', 'true');
    tr.innerHTML = `<td colspan="${cols}"><div class="skeleton-line" style="width:${85 - i * 18}%"></div></td>`;
    body.appendChild(tr);
  }
}

async function init() {
  await Promise.all([
    loadPendingCourses(),
    loadPendingSections(),
    loadPendingSubjects(),
    loadPendingSchedules(),
  ]);
}

/* ===================== COURSES ===================== */

async function loadPendingCourses() {
  const body = document.getElementById('pendingCourseBody');
  const empty = document.getElementById('emptyCourseState');
  showSkeleton('pendingCourseBody', 5);
  try {
    const r = await fetch(API_BASE + 'get_pending_courses.php');
    const d = await r.json();
    const list = d.pending || [];
    body.innerHTML = '';
    empty.style.display = list.length ? 'none' : 'block';
    setCount('pendingCourseCount', list.length);
    list.forEach(item => body.appendChild(buildCourseRow(item)));
  } catch (_) {
    body.innerHTML = '';
    empty.style.display = 'block';
  }
}

function buildCourseRow(item) {
  const tr = document.createElement('tr');
  tr.innerHTML = `
    <td>${escHtml(item.requested_by_name || '—')}</td>
    <td>${escHtml(item.course_code || '')}</td>
    <td>${escHtml(item.course_name || '')}</td>
    <td>${escHtml(item.total_units ?? '')}</td>
    <td>
      <button type="button" class="btn-approve" data-approve="${item.course_id}">Approve</button>
      <button type="button" class="btn-reject" data-reject="${item.course_id}">Reject</button>
    </td>`;

  tr.querySelector('[data-approve]').addEventListener('click', () =>
    approveItem('approve_course.php', 'course_id', item.course_id, tr, 'emptyCourseState', 'pendingCourseBody'));
  tr.querySelector('[data-reject]').addEventListener('click', () =>
    rejectItem('reject_course.php', 'course_id', item.course_id, tr, 'emptyCourseState', 'pendingCourseBody'));

  return tr;
}

/* ===================== SECTIONS ===================== */

async function loadPendingSections() {
  const body = document.getElementById('pendingSectionBody');
  const empty = document.getElementById('emptySectionState');
  showSkeleton('pendingSectionBody', 5);
  try {
    const r = await fetch(API_BASE + 'get_pending_sections.php');
    const d = await r.json();
    const list = d.pending || [];
    body.innerHTML = '';
    empty.style.display = list.length ? 'none' : 'block';
    setCount('pendingSectionCount', list.length);
    list.forEach(item => body.appendChild(buildSectionRow(item)));
  } catch (_) {
    body.innerHTML = '';
    empty.style.display = 'block';
  }
}

function buildSectionRow(item) {
  const tr = document.createElement('tr');
  tr.innerHTML = `
    <td>${escHtml(item.requested_by_name || '—')}</td>
    <td>${escHtml(item.section_name || '')}</td>
    <td>${escHtml(item.course_code || '—')}</td>
    <td>${escHtml(item.capacity ?? '')}</td>
    <td>
      <button type="button" class="btn-approve" data-approve="${item.section_id}">Approve</button>
      <button type="button" class="btn-reject" data-reject="${item.section_id}">Reject</button>
    </td>`;

  tr.querySelector('[data-approve]').addEventListener('click', () =>
    approveItem('approve_section.php', 'section_id', item.section_id, tr, 'emptySectionState', 'pendingSectionBody'));
  tr.querySelector('[data-reject]').addEventListener('click', () =>
    rejectItem('reject_section.php', 'section_id', item.section_id, tr, 'emptySectionState', 'pendingSectionBody'));

  return tr;
}

/* ===================== SUBJECTS ===================== */

async function loadPendingSubjects() {
  const body = document.getElementById('pendingSubjectBody');
  const empty = document.getElementById('emptySubjectState');
  showSkeleton('pendingSubjectBody', 6);
  try {
    const r = await fetch(API_BASE + 'get_pending_subjects.php');
    const d = await r.json();
    const list = d.pending || [];
    body.innerHTML = '';
    empty.style.display = list.length ? 'none' : 'block';
    setCount('pendingSubjectCount', list.length);
    list.forEach(item => body.appendChild(buildSubjectRow(item)));
  } catch (_) {
    body.innerHTML = '';
    empty.style.display = 'block';
  }
}

function buildSubjectRow(item) {
  const tr = document.createElement('tr');
  tr.innerHTML = `
    <td>${escHtml(item.requested_by_name || '—')}</td>
    <td>${escHtml(item.subject_code || '')}</td>
    <td>${escHtml(item.subject_name || '')}</td>
    <td>${escHtml(item.units ?? '')}</td>
    <td>${escHtml(item.course_code || '—')}</td>
    <td>
      <button type="button" class="btn-approve" data-approve="${item.subject_id}">Approve</button>
      <button type="button" class="btn-reject" data-reject="${item.subject_id}">Reject</button>
    </td>`;

  tr.querySelector('[data-approve]').addEventListener('click', () =>
    approveItem('approve_subject.php', 'subject_id', item.subject_id, tr, 'emptySubjectState', 'pendingSubjectBody'));
  tr.querySelector('[data-reject]').addEventListener('click', () =>
    rejectItem('reject_subject.php', 'subject_id', item.subject_id, tr, 'emptySubjectState', 'pendingSubjectBody'));

  return tr;
}

/* ===================== SCHEDULES ===================== */

async function loadPendingSchedules() {
  const body = document.getElementById('pendingBody');
  const empty = document.getElementById('emptyState');
  showSkeleton('pendingBody', 7);
  try {
    const r = await fetch(API_BASE + 'get_pending_schedules.php');
    const d = await r.json();
    const list = d.pending || [];
    body.innerHTML = '';
    empty.style.display = list.length ? 'none' : 'block';
    setCount('pendingScheduleCount', list.length);
    list.forEach(item => body.appendChild(buildScheduleRow(item)));
  } catch (_) {
    body.innerHTML = '';
    empty.style.display = 'block';
  }
}

function buildScheduleRow(item) {
  const tr = document.createElement('tr');
  tr.innerHTML = `
    <td>${escHtml(item.requested_by_name || '—')}</td>
    <td>${escHtml(item.course_code || '')} ${escHtml(item.section_name || '')}</td>
    <td>${escHtml(item.subject_code || '')} — ${escHtml(item.subject_name || '')}${item.professor_name ? '<br><span style="color:#888;font-size:12px">' + escHtml(item.professor_name) + '</span>' : ''}</td>
    <td>${escHtml(item.room_name || '')}</td>
    <td>${escHtml((item.day || '').slice(0, 3))}</td>
    <td>${formatTime(item.time_start)} – ${formatTime(item.time_end)}</td>
    <td>
      <button type="button" class="btn-approve" data-approve="${item.schedule_id}">Approve</button>
      <button type="button" class="btn-reject" data-reject="${item.schedule_id}">Reject</button>
    </td>`;

  tr.querySelector('[data-approve]').addEventListener('click', () =>
    approveItem('approve_schedule.php', 'schedule_id', item.schedule_id, tr, 'emptyState', 'pendingBody'));
  tr.querySelector('[data-reject]').addEventListener('click', () =>
    rejectItem('reject_schedule.php', 'schedule_id', item.schedule_id, tr, 'emptyState', 'pendingBody'));

  return tr;
}

/* ===================== SHARED APPROVE / REJECT ===================== */

async function approveItem(endpoint, idField, idValue, tr, emptyStateId, bodyId) {
  const confirm = await Swal.fire({
    icon: 'question',
    title: 'Approve this?',
    showCancelButton: true,
    confirmButtonText: 'Approve',
    confirmButtonColor: '#16a34a',
  });
  if (!confirm.isConfirmed) return;

  const res = await postJSON(endpoint, { [idField]: idValue });
  if (res.error) {
    Swal.fire({ icon: 'error', title: 'Could not approve', text: res.error });
    return;
  }
  tr.remove();
  toggleEmptyIfNoRows(emptyStateId, bodyId);
  Swal.fire({ icon: 'success', title: 'Approved', timer: 1200, showConfirmButton: false });
}

async function rejectItem(endpoint, idField, idValue, tr, emptyStateId, bodyId) {
  const { value: reason, isConfirmed } = await Swal.fire({
    icon: 'warning',
    title: 'Reject this?',
    input: 'text',
    inputLabel: 'Reason (optional)',
    inputPlaceholder: 'e.g. Duplicate entry',
    showCancelButton: true,
    confirmButtonText: 'Reject',
    confirmButtonColor: '#dc2626',
  });
  if (!isConfirmed) return;

  const res = await postJSON(endpoint, { [idField]: idValue, reason: reason || '' });
  if (res.error) {
    Swal.fire({ icon: 'error', title: 'Could not reject', text: res.error });
    return;
  }
  tr.remove();
  toggleEmptyIfNoRows(emptyStateId, bodyId);
  Swal.fire({ icon: 'success', title: 'Rejected', timer: 1200, showConfirmButton: false });
}

const COUNT_ID_BY_BODY_ID = {
  pendingCourseBody:  'pendingCourseCount',
  pendingSectionBody: 'pendingSectionCount',
  pendingSubjectBody: 'pendingSubjectCount',
  pendingBody:        'pendingScheduleCount',
};

function toggleEmptyIfNoRows(emptyStateId, bodyId) {
  const empty = document.getElementById(emptyStateId);
  const body = document.getElementById(bodyId);
  empty.style.display = body.children.length ? 'none' : 'block';
  const countId = COUNT_ID_BY_BODY_ID[bodyId];
  if (countId) setCount(countId, body.children.length);
}

function csrfToken() {
  return document.body.dataset.csrf || '';
}

async function postJSON(url, payload) {
  const res = await fetch(API_BASE + url, {
    method: 'POST',
    headers: { 'Content-Type': 'application/json', 'X-CSRF-Token': csrfToken() },
    body: JSON.stringify(payload),
  });
  return res.json();
}

function formatTime(t) {
  if (!t) return '—';
  const [h, m] = t.split(':').map(Number);
  const ampm = h < 12 ? 'AM' : 'PM';
  const h12 = h % 12 || 12;
  return h12 + ':' + String(m).padStart(2, '0') + ' ' + ampm;
}

function escHtml(s) {
  return String(s ?? '').replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;');
}