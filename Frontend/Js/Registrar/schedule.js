document.addEventListener('DOMContentLoaded', init);

const API_BASE = '/SIAdrafts/Backend/api/';
let rows = [];
let options = { sections: [], subjects: [], rooms: [], professors: [] };

const filterCourse = document.getElementById('filterCourse');
const filterDay = document.getElementById('filterDay');
const filterStatus = document.getElementById('filterStatus');
const searchInput = document.getElementById('searchSchedule');
const scheduleBody = document.getElementById('scheduleBody');
const emptyState = document.getElementById('emptyState');

async function init() {
  bindModalOpenClose();
  await Promise.all([loadOptions(), loadSchedules()]);
  populateCourseFilter();
  populateFormDropdowns();
  render();

  [filterCourse, filterDay, filterStatus].forEach(el => el.addEventListener('change', render));
  searchInput.addEventListener('input', render);

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

function populateFormDropdowns() {
  const sectionSel = document.getElementById('schedSection');
  const subjectSel = document.getElementById('schedSubject');
  const profSel = document.getElementById('schedProfessor');
  const roomSel = document.getElementById('schedRoom');

  sectionSel.innerHTML = options.sections
    .map(s => `<option value="${s.section_id}">${escHtml(s.course_code)} - ${escHtml(s.section_name)}</option>`).join('');
  subjectSel.innerHTML = options.subjects
    .map(s => `<option value="${s.subject_id}">${escHtml(s.subject_code)} - ${escHtml(s.subject_name)}</option>`).join('');
  profSel.innerHTML = '<option value="">-- None --</option>' + options.professors
    .map(p => `<option value="${p.professor_id}">${escHtml(p.professor_name)}</option>`).join('');
  roomSel.innerHTML = options.rooms
    .map(r => `<option value="${r.room_id}">${escHtml(r.room_name)}</option>`).join('');
}

function render() {
  const course = filterCourse.value;
  const day = filterDay.value;
  const status = filterStatus.value;
  const search = searchInput.value.toLowerCase();

  const filtered = rows.filter(r => {
    if (course && r.course_code !== course) return false;
    if (day && r.day !== day) return false;
    if (status && r.status !== status) return false;
    if (search && !(`${r.subject_name} ${r.room_name}`.toLowerCase().includes(search))) return false;
    return true;
  });

  scheduleBody.innerHTML = '';
  emptyState.style.display = filtered.length ? 'none' : 'block';

  filtered.forEach(r => scheduleBody.appendChild(buildRow(r)));
}

function buildRow(r) {
  const tr = document.createElement('tr');
  const statusClass = 'status-pill--' + (r.status || 'approved').toLowerCase();
  tr.innerHTML = `
    <td>${escHtml(r.course_code || '')} ${escHtml(r.section_name || '')}</td>
    <td>${escHtml(r.subject_code || '')} — ${escHtml(r.subject_name || '')}${r.professor_name ? '<br><span style="color:#888;font-size:12px">' + escHtml(r.professor_name) + '</span>' : ''}</td>
    <td>${escHtml(r.room_name || '')}</td>
    <td>${escHtml((r.day || '').slice(0, 3))}</td>
    <td>${formatTime(r.time_start)} – ${formatTime(r.time_end)}</td>
    <td><span class="status-pill ${statusClass}">${escHtml(r.status || 'Approved')}</span></td>`;
  return tr;
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
      headers: { 'Content-Type': 'application/json' },
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