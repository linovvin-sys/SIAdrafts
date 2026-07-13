(function () {
  const API_BASE = '/SIAdrafts/Backend/api/';

  /* ─── state ─── */
  let flatRows = [];   // raw rows from get_schedules.php (one per day)
  let blocks   = [];   // grouped: one entry per subject/section/room/time combo
  let options  = { sections: [], subjects: [], rooms: [], professors: [] };

  /* ─── refs (view-only page: no modal/form fields anymore) ─── */
  const filterCourse = document.getElementById('filterCourse');
  const filterYear   = document.getElementById('filterYear');
  const filterDay    = document.getElementById('filterDay');
  const searchInput  = document.getElementById('searchSchedule');
  const scheduleBody = document.getElementById('scheduleBody');
  const emptyState   = document.getElementById('emptyState');

  document.addEventListener('DOMContentLoaded', init);

  async function init() {
    await Promise.all([loadOptions(), loadSchedules()]);
    populateCourseFilter();
    render();
  }

  /* ─── data loading ─── */
  async function loadSchedules() {
    try {
      const r = await fetch(API_BASE + 'get_schedules.php');
      flatRows = await r.json();
      if (!Array.isArray(flatRows)) flatRows = [];
    } catch (_) {
      flatRows = [];
    }
    blocks = groupIntoBlocks(flatRows);
  }

  async function loadOptions() {
    try {
      const r = await fetch(API_BASE + 'get_schedule_options.php');
      const d = await r.json();
      options = {
        sections:   d.sections   || [],
        subjects:   d.subjects   || [],
        rooms:      d.rooms      || [],
        professors: d.professors || [],
      };
    } catch (_) { /* leave defaults */ }
  }

  /* Group flat day-rows into one block per subject+section+room+professor+time. */
  function groupIntoBlocks(rows) {
    const map = new Map();
    rows.forEach(row => {
      const key = [row.subject_id, row.section_id, row.room_id, row.professor_id,
                   row.time_start, row.time_end, row.school_year, row.semester].join('|');
      if (!map.has(key)) {
        map.set(key, {
          ids: [],
          days: [],
          subject_id: +row.subject_id,
          section_id: +row.section_id,
          room_id: +row.room_id,
          professor_id: row.professor_id ? +row.professor_id : null,
          time_start: row.time_start.slice(0, 5),
          time_end: row.time_end.slice(0, 5),
          school_year: row.school_year,
          semester: +row.semester,
          subject_code: row.subject_code,
          subject_name: row.subject_name,
          year_level: +row.year_level,
          section_name: row.section_name,
          course_code: row.course_code,
          room_name: row.room_name,
          professor_name: row.professor_name || '',
        });
      }
      const b = map.get(key);
      b.ids.push(+row.schedule_id);
      b.days.push(row.day);
    });
    return [...map.values()];
  }

  function populateCourseFilter() {
    const codes = [...new Set(options.sections.map(s => s.course_code))].sort();
    filterCourse.innerHTML = '<option value="">All Courses</option>' +
      codes.map(c => `<option value="${escHtml(c)}">${escHtml(c)}</option>`).join('');
  }

  /* ─── filters ─── */
  filterCourse.addEventListener('change', render);
  filterYear.addEventListener('change', render);
  filterDay.addEventListener('change', render);
  searchInput.addEventListener('input', render);

  /* ─── global toggle opener (expand/collapse group rows) ─── */
  window.toggleGroup = function (groupId) {
    const rows = document.querySelectorAll(`.subject-row[data-parent="${cssEscape(groupId)}"]`);
    const arrow = document.getElementById(`arrow-${groupId}`);
    if (!arrow) return;
    const isOpen = arrow.classList.contains('open');
    rows.forEach(r => r.style.display = isOpen ? 'none' : '');
    arrow.classList.toggle('open', !isOpen);
    arrow.textContent = isOpen ? '▶' : '▼';
  };

  /* ─── render (grouped by course + year level) ─── */
  function render() {
    const course = filterCourse.value;
    const year   = filterYear.value;
    const day    = filterDay.value;
    const search = searchInput.value.toLowerCase();

    const filtered = blocks.filter(b => {
      if (course && b.course_code !== course) return false;
      if (year && b.year_level !== +year) return false;
      if (day && !b.days.includes(day)) return false;
      if (search && !b.subject_name.toLowerCase().includes(search) && !b.room_name.toLowerCase().includes(search)) return false;
      return true;
    });

    scheduleBody.innerHTML = '';
    emptyState.style.display = filtered.length ? 'none' : 'block';

    // group by course_code + year_level, preserving a stable order
    const groups = new Map();
    filtered.forEach(b => {
      const gid = `${b.course_code}-Y${b.year_level}`;
      if (!groups.has(gid)) groups.set(gid, { course: b.course_code, year: b.year_level, items: [] });
      groups.get(gid).items.push(b);
    });

    groups.forEach((g, gid) => {
      scheduleBody.appendChild(buildGroupRow(gid, g));
      g.items.forEach(b => scheduleBody.appendChild(buildBlockRow(gid, b)));
    });
  }

  function buildGroupRow(gid, g) {
    const tr = document.createElement('tr');
    tr.className = 'section-group-row';
    tr.dataset.group = gid;
    tr.addEventListener('click', () => window.toggleGroup(gid));

    const sectionCount = new Set(g.items.map(b => b.section_id)).size;
    const daySet = [...new Set(g.items.flatMap(b => b.days))];
    const dayOrder = ['Monday','Tuesday','Wednesday','Thursday','Friday','Saturday','Sunday'];
    daySet.sort((a, b) => dayOrder.indexOf(a) - dayOrder.indexOf(b));

    tr.innerHTML = `
      <td class="expand-cell"><span class="expand-arrow open" id="arrow-${gid}">▼</span></td>
      <td><div class="group-label"><span class="course-badge blue">${escHtml(g.course)}</span><span class="year-label">${ordinal(g.year)} Year</span></div></td>
      <td class="group-meta">${g.items.length} subject${g.items.length !== 1 ? 's' : ''} &middot; ${sectionCount} section${sectionCount !== 1 ? 's' : ''}</td>
      <td></td>
      <td>—</td>
      <td>${daySet.map(d => `<span class="day-badge">${shortDay(d)}</span>`).join(' ')}</td>
      <td class="group-meta"></td>
      <td><span class="badge badge-success">Active</span></td>`;
    return tr;
  }

  function buildBlockRow(gid, b) {
    const tr = document.createElement('tr');
    tr.className = 'subject-row';
    tr.dataset.parent = gid;

    const typeBadge = b.room_name.toLowerCase().includes('lab')
      ? '<span class="type-badge type-lab">LAB</span>'
      : '<span class="type-badge type-lec">LEC</span>';

    tr.innerHTML = `
      <td class="child-indent-cell"><span class="child-arrow">↳</span></td>
      <td><span class="sec-tag">${escHtml(b.section_name)}</span></td>
      <td class="subject-name">${escHtml(b.subject_name)}${b.professor_name ? '<br><span style="color:#888;font-size:12px">' + escHtml(b.professor_name) + '</span>' : ''}</td>
      <td>${typeBadge}</td>
      <td class="room-cell">${escHtml(b.room_name)}</td>
      <td>${b.days.map(d => `<span class="day-badge">${shortDay(d)}</span>`).join(' ')}</td>
      <td class="time-cell">${formatTime(b.time_start)} – ${formatTime(b.time_end)}<br><span>${duration(b.time_start, b.time_end)}</span></td>
      <td><span class="badge badge-success">Active</span></td>`;
    return tr;
  }

  function ordinal(n) { return n === 1 ? '1st' : n === 2 ? '2nd' : n === 3 ? '3rd' : n + 'th'; }
  function shortDay(d) { return d.slice(0, 3); }

  function formatTime(t) {
    if (!t) return '—';
    const [h, m] = t.split(':').map(Number);
    const ampm = h < 12 ? 'AM' : 'PM';
    const h12  = h % 12 || 12;
    return h12 + ':' + String(m).padStart(2, '0') + ' ' + ampm;
  }

  function duration(s, e) {
    if (!s || !e) return '';
    const [sh, sm] = s.split(':').map(Number);
    const [eh, em] = e.split(':').map(Number);
    const total = (eh * 60 + em) - (sh * 60 + sm);
    if (total <= 0) return '';
    const h = Math.floor(total / 60);
    const m = total % 60;
    return (h ? h + ' hr' + (h > 1 ? 's' : '') : '') + (m ? (h ? ' ' : '') + m + ' min' : '');
  }

  function escHtml(s) {
    return String(s).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
  }

  function cssEscape(s) {
    return String(s).replace(/["\\]/g, '\\$&');
  }
})();