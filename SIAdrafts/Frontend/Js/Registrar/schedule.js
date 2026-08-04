document.addEventListener('DOMContentLoaded', init);

const API_BASE = '/SIAdrafts/Backend/api/';
let rows = [];
let options = { sections: [], subjects: [], rooms: [], professors: [] };
const collapsedGroups = new Set();
const COURSE_COLORS = ['blue', 'purple', 'green', 'orange', 'red', 'teal'];

const filterCourse = document.getElementById('filterCourse');
const filterYear = document.getElementById('filterYear');
const filterDay = document.getElementById('filterDay');
const filterStatus = document.getElementById('filterStatus');
const searchInput = document.getElementById('searchSchedule');
const scheduleBody = document.getElementById('scheduleBody');
const emptyState = document.getElementById('emptyState');
const scheduleTable = document.getElementById('scheduleTable');
const isHead = scheduleTable.dataset.isHead === '1';

function csrfToken() {
  return document.body.dataset.csrf || '';
}

async function init() {
  bindModalOpenClose();
  await Promise.all([loadOptions(), loadSchedules()]);
  populateCourseFilter();
  populateFormDropdowns();
  populateTimeDropdowns();
  render();

  [filterCourse, filterYear, filterDay, filterStatus].forEach(el => el.addEventListener('change', render));
  searchInput.addEventListener('input', render);

  scheduleBody.addEventListener('click', onBodyClick);

  const confirmBtn = document.getElementById('confirmAddSchedule');
  if (confirmBtn) confirmBtn.addEventListener('click', submitSchedule);
}

function bindModalOpenClose() {
  function openModal(m) { m.classList.add('active'); document.body.style.overflow = 'hidden'; }
  function closeModal(m) { m.classList.remove('active'); document.body.style.overflow = ''; }

  document.querySelectorAll('[data-open]').forEach(btn => {
    btn.addEventListener('click', () => {
      const t = document.getElementById(btn.getAttribute('data-open'));
      if (t) openModal(t);
    });
  });
  document.querySelectorAll('[data-close]').forEach(btn => {
    btn.addEventListener('click', () => {
      const t = document.getElementById(btn.getAttribute('data-close'));
      if (t) closeModal(t);
    });
  });
  document.querySelectorAll('.modal-overlay').forEach(overlay => {
    overlay.addEventListener('click', (e) => { if (e.target === overlay) closeModal(overlay); });
  });
  document.addEventListener('keydown', (e) => {
    if (e.key === 'Escape') document.querySelectorAll('.modal-overlay.active').forEach(closeModal);
  });
}

async function loadSchedules() {
  try {
    const r = await fetch(API_BASE + 'get_schedules.php?status=all');
    rows = await r.json();
    if (!Array.isArray(rows)) rows = [];
  } catch (_) { rows = []; }
}

async function loadOptions() {
  try {
    const r = await fetch(API_BASE + 'get_schedule_options.php');
    const d = await r.json();
    options = {
      sections: d.sections || [],
      subjects: d.subjects || [],
      rooms: d.rooms || [],
      professors: d.professors || [],
    };
  } catch (_) { /* keep defaults */ }
}

function populateCourseFilter() {
  const codes = [...new Set(rows.map(r => r.course_code).filter(Boolean))].sort();
  filterCourse.innerHTML = '<option value="">All Courses</option>' +
    codes.map(c => `<option value="${escHtml(c)}">${escHtml(c)}</option>`).join('');
}

// Preset time-slot options (7:00 AM – 9:00 PM, every 30 minutes) instead of
// a free-typed/native time picker, so Head Registrar just picks from a
// list rather than typing or scrubbing a time widget for every schedule.
const TIME_SLOTS = (() => {
  const slots = [];
  for (let mins = 7 * 60; mins <= 21 * 60; mins += 30) {
    const h24 = Math.floor(mins / 60);
    const m = mins % 60;
    const value = String(h24).padStart(2, '0') + ':' + String(m).padStart(2, '0');
    const h12 = h24 % 12 || 12;
    const ampm = h24 < 12 ? 'AM' : 'PM';
    const label = h12 + ':' + String(m).padStart(2, '0') + ' ' + ampm;
    slots.push({ value, label });
  }
  return slots;
})();

function populateTimeDropdowns() {
  const startSel = document.getElementById('schedStart');
  const endSel = document.getElementById('schedEnd');

  startSel.innerHTML = TIME_SLOTS
    .map(t => `<option value="${t.value}">${t.label}</option>`).join('');

  // End time only ever offers slots after whichever start time is picked —
  // an invalid (end <= start) combination can't even be selected.
  function refreshEndOptions() {
    const startValue = startSel.value;
    const previousEnd = endSel.value;
    const validEnds = TIME_SLOTS.filter(t => t.value > startValue);
    endSel.innerHTML = validEnds
      .map(t => `<option value="${t.value}">${t.label}</option>`).join('');
    if (validEnds.some(t => t.value === previousEnd)) {
      endSel.value = previousEnd;
    }
  }

  startSel.addEventListener('change', refreshEndOptions);
  refreshEndOptions();
}

function populateFormDropdowns() {
  const sectionSel  = document.getElementById('schedSection');
  const subjectSel  = document.getElementById('schedSubject');
  const yearSel     = document.getElementById('schedYear');
  const semesterSel = document.getElementById('schedSemester');

  sectionSel.innerHTML = options.sections
    .map(s => `<option value="${s.section_id}" data-course-id="${s.course_id}">${escHtml(s.course_code)} - ${escHtml(s.section_name)}</option>`).join('');

  // Subject list depends on which section (i.e. which course) is picked,
  // AND on year level + semester — a course's subject list spans all 4
  // year levels, so without this a 1st-year section could otherwise be
  // offered a 3rd-year subject. Professor/room lists then depend on which
  // subject is picked.
  sectionSel.addEventListener('change', refreshSubjects);
  yearSel.addEventListener('change', refreshSubjects);
  semesterSel.addEventListener('change', refreshSubjects);
  subjectSel.addEventListener('change', () => populateProfessorsAndRoomsForSubject(subjectSel));
  refreshSubjects();
}

function refreshSubjects() {
  const sectionSel = document.getElementById('schedSection');
  populateSubjectsForSection(sectionSel);
}

function populateSubjectsForSection(sectionSel) {
  const subjectSel = document.getElementById('schedSubject');
  const selectedOption = sectionSel.options[sectionSel.selectedIndex];
  const courseId = selectedOption ? Number(selectedOption.dataset.courseId) : null;
  const yearLevel = Number(document.getElementById('schedYear').value);
  const semester  = Number(document.getElementById('schedSemester').value);

  const matching = courseId
    ? options.subjects.filter(s =>
        s.course_ids.includes(courseId)
        && Number(s.year_level) === yearLevel
        && Number(s.semester) === semester
      )
    : [];

  subjectSel.innerHTML = matching.length
    ? matching.map(s =>
        `<option value="${s.subject_id}" data-department-id="${s.department_id ?? ''}" data-category="${escHtml(s.category_name || '')}" data-subject-name="${escHtml(s.subject_code + ' ' + s.subject_name)}">${escHtml(s.subject_code)} - ${escHtml(s.subject_name)}</option>`
      ).join('')
    : '<option value="">-- No subjects for this year level / semester --</option>';

  populateProfessorsAndRoomsForSubject(subjectSel);
}

// A subject's own department (via its category, e.g. PSYCH subjects ->
// Psychology department) narrows the professor list, and whether it's a
// lecture, lab, or PE subject narrows the room list — instead of showing
// every professor in the school and every room regardless of fit.
function populateProfessorsAndRoomsForSubject(subjectSel) {
  const profSel = document.getElementById('schedProfessor');
  const roomSel = document.getElementById('schedRoom');
  const selectedOption = subjectSel.options[subjectSel.selectedIndex];

  const departmentId = selectedOption?.dataset.departmentId ? Number(selectedOption.dataset.departmentId) : null;
  const category = selectedOption?.dataset.category || '';
  const subjectLabel = selectedOption?.dataset.subjectName || '';

  const matchingProfs = departmentId
    ? options.professors.filter(p => Number(p.department_id) === departmentId)
    : [];
  const profList = matchingProfs.length ? matchingProfs : options.professors;
  profSel.innerHTML = '<option value="">-- None --</option>' + profList
    .map(p => `<option value="${p.professor_id}">${escHtml(p.professor_name)}</option>`).join('');

  // Same lab-detection heuristic used for the Lec/Lab badge in the
  // schedule table below (no dedicated column exists for this distinction).
  const wantsGym = category === 'PATHFIT';
  const wantsLab = /lab/i.test(subjectLabel);
  const wantedType = wantsGym ? 'Gymnasium' : (wantsLab ? 'Laboratory' : 'Lecture');

  const matchingRooms = options.rooms.filter(r => r.room_type === wantedType);
  const roomList = matchingRooms.length ? matchingRooms : options.rooms;
  roomSel.innerHTML = roomList
    .map(r => `<option value="${r.room_id}">${escHtml(r.room_name)}</option>`).join('');
}

function render() {
  const course = filterCourse.value;
  const year = filterYear.value;
  const day = filterDay.value;
  const status = filterStatus.value;
  const search = searchInput.value.toLowerCase();

  const filtered = rows.filter(r => {
    if (course && r.course_code !== course) return false;
    if (year && String(r.year_level) !== year) return false;
    if (day && r.day !== day) return false;
    if (status && r.status !== status) return false;
    if (search && !(`${r.subject_name} ${r.room_name}`.toLowerCase().includes(search))) return false;
    return true;
  });

  scheduleBody.innerHTML = '';
  emptyState.style.display = filtered.length ? 'none' : 'block';

  buildGroups(filtered).forEach(group => {
    scheduleBody.appendChild(buildGroupRow(group));
    const collapsed = collapsedGroups.has(group.key);
    group.children.forEach(child => scheduleBody.appendChild(buildChildRow(group, child, collapsed)));
  });
}

// Groups rows by course + year level, then by subject + section (merging
// same subject/section across multiple days into one row with day badges).
function buildGroups(filtered) {
  const groups = new Map();

  filtered.forEach(r => {
    const gKey = `${r.course_code || '—'}|${r.year_level || ''}`;
    if (!groups.has(gKey)) {
      groups.set(gKey, {
        key: gKey,
        course_code: r.course_code || '—',
        year_level: r.year_level,
        children: new Map(),
      });
    }
    const group = groups.get(gKey);

    const cKey = `${r.subject_id}|${r.section_id}`;
    if (!group.children.has(cKey)) {
      group.children.set(cKey, {
        subject_code: r.subject_code,
        subject_name: r.subject_name,
        section_name: r.section_name,
        room_name: r.room_name,
        professor_name: r.professor_name,
        status: r.status,
        time_start: r.time_start,
        time_end: r.time_end,
        days: new Set(),
        schedule_ids: [],
      });
    }
    const child = group.children.get(cKey);
    child.days.add(r.day);
    child.schedule_ids.push(r.schedule_id);
  });

  const sorted = [...groups.values()].sort((a, b) =>
    a.course_code.localeCompare(b.course_code) || (a.year_level || 0) - (b.year_level || 0));
  sorted.forEach(g => {
    g.children = [...g.children.values()].sort((a, b) => (a.subject_code || '').localeCompare(b.subject_code || ''));
  });
  return sorted;
}

function courseColor(code) {
  let h = 0;
  for (let i = 0; i < code.length; i++) h = (h * 31 + code.charCodeAt(i)) >>> 0;
  return COURSE_COLORS[h % COURSE_COLORS.length];
}

function yearLabel(year) {
  const n = parseInt(year, 10);
  if (!n) return 'Year —';
  const suffix = n === 1 ? 'st' : n === 2 ? 'nd' : n === 3 ? 'rd' : 'th';
  return `${n}${suffix} Year`;
}

// No lecture/lab column exists in the schema — inferred from naming
// convention (subject code/name containing "lab"), same heuristic used
// wherever this distinction shows up in the curriculum.
function subjectType(code, name) {
  return /lab/i.test(code || '') || /lab/i.test(name || '') ? 'Lab' : 'Lec';
}

function buildGroupRow(group) {
  const tr = document.createElement('tr');
  tr.className = 'section-group-row';
  tr.dataset.groupToggle = group.key;
  const collapsed = collapsedGroups.has(group.key);
  const colspan = isHead ? 8 : 7;

  tr.innerHTML = `
    <td class="expand-cell"><span class="expand-arrow">${collapsed ? '▸' : '▾'}</span></td>
    <td colspan="${colspan}">
      <div class="group-label">
        <span class="course-badge ${courseColor(group.course_code)}">${escHtml(group.course_code)}</span>
        <span class="year-label">${escHtml(yearLabel(group.year_level))}</span>
        <span class="group-meta">${group.children.length} subject${group.children.length === 1 ? '' : 's'}</span>
      </div>
    </td>`;
  return tr;
}

function buildChildRow(group, child, collapsed) {
  const tr = document.createElement('tr');
  tr.className = 'subject-row';
  tr.dataset.group = group.key;
  tr.style.display = collapsed ? 'none' : '';

  const statusClass = 'status-pill--' + (child.status || 'approved').toLowerCase();
  const type = subjectType(child.subject_code, child.subject_name);
  const typeClass = type === 'Lab' ? 'type-lab' : 'type-lec';
  const dayBadges = [...child.days]
    .sort((a, b) => DAY_ORDER.indexOf(a) - DAY_ORDER.indexOf(b))
    .map(d => `<span class="day-badge">${escHtml(d.slice(0, 3))}</span>`)
    .join('');

  let cells = `
    <td class="child-indent-cell"><span class="child-arrow">↳</span></td>
    <td></td>
    <td>
      <div class="subject-name">${escHtml(child.subject_code || '')} — ${escHtml(child.subject_name || '')}</div>
      <span class="sec-tag">${escHtml(child.section_name || '')}</span>
      ${child.professor_name ? `<div class="group-meta">${escHtml(child.professor_name)}</div>` : ''}
    </td>
    <td><span class="type-badge ${typeClass}">${type}</span></td>
    <td class="room-cell">${escHtml(child.room_name || '—')}</td>
    <td>${dayBadges}</td>
    <td class="time-cell">${formatTime(child.time_start)} –<br><span>${formatTime(child.time_end)}</span></td>
    <td><span class="status-pill ${statusClass}">${escHtml(child.status || 'Approved')}</span></td>`;

  if (isHead) {
    cells += `
    <td class="actions-cell">
      <button type="button" class="action-btn btn-danger" data-delete-ids="${child.schedule_ids.join(',')}">Delete</button>
    </td>`;
  }

  tr.innerHTML = cells;
  return tr;
}

const DAY_ORDER = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'];

function onBodyClick(e) {
  const toggleRow = e.target.closest('[data-group-toggle]');
  if (toggleRow) {
    const key = toggleRow.dataset.groupToggle;
    if (collapsedGroups.has(key)) collapsedGroups.delete(key);
    else collapsedGroups.add(key);
    render();
    return;
  }

  const deleteBtn = e.target.closest('[data-delete-ids]');
  if (deleteBtn) {
    deleteSchedules(deleteBtn.dataset.deleteIds.split(',').map(Number));
  }
}

async function deleteSchedules(ids) {
  // A reason is required — this is a hard delete with no undo, and (unlike
  // every other reject/deactivate flow in this app) schedules had no audit
  // trail at all, so there was no record of who removed a class or why.
  const confirm = await Swal.fire({
    icon: 'warning',
    title: 'Delete this schedule?',
    html: 'This removes it for every day it meets. This cannot be undone.',
    input: 'textarea',
    inputLabel: 'Reason for deletion',
    inputPlaceholder: 'e.g. Duplicate entry, professor reassigned, class cancelled…',
    inputValidator: (value) => !value || !value.trim() ? 'Please provide a reason.' : undefined,
    showCancelButton: true,
    confirmButtonText: 'Delete',
    confirmButtonColor: '#dc2626',
  });
  if (!confirm.isConfirmed) return;
  const reason = confirm.value.trim();

  try {
    const res = await fetch(API_BASE + 'delete_schedule.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json', 'X-CSRF-Token': csrfToken() },
      body: JSON.stringify({ ids, reason }),
    });
    const result = await res.json();

    if (result.error) {
      Swal.fire({ icon: 'error', title: 'Could not delete schedule', text: result.error });
      return;
    }

    rows = rows.filter(r => !ids.includes(r.schedule_id));
    render();
    Swal.fire({ icon: 'success', title: 'Schedule deleted', timer: 1200, showConfirmButton: false });
  } catch (_) {
    Swal.fire({ icon: 'error', title: 'Network error', text: 'Please try again.' });
  }
}

async function submitSchedule() {
  const payload = {
    section_id: document.getElementById('schedSection').value,
    subject_id: document.getElementById('schedSubject').value,
    professor_id: document.getElementById('schedProfessor').value || null,
    room_id: document.getElementById('schedRoom').value,
    day: document.getElementById('schedDay').value,
    time_start: document.getElementById('schedStart').value,
    time_end: document.getElementById('schedEnd').value,
    school_year: document.getElementById('schedSchoolYear').value.trim(),
    semester: document.getElementById('schedSemester').value,
  };

  if (!payload.section_id || !payload.subject_id || !payload.room_id || !payload.day
      || !payload.time_start || !payload.time_end || !payload.school_year || !payload.semester) {
    Swal.fire({ icon: 'warning', title: 'Missing fields', text: 'Please fill in all required fields.' });
    return;
  }

  try {
    const res = await fetch(API_BASE + 'save_schedule_registrar.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json', 'X-CSRF-Token': csrfToken() },
      body: JSON.stringify(payload),
    });
    const result = await res.json();

    if (result.error) {
      Swal.fire({ icon: 'error', title: 'Could not save schedule', text: result.error });
      return;
    }

    Swal.fire({ icon: 'success', title: result.message || 'Saved', timer: 1500, showConfirmButton: false })
      .then(() => location.reload());
  } catch (_) {
    Swal.fire({ icon: 'error', title: 'Network error', text: 'Please try again.' });
  }
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
