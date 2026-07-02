(function () {
  const API_BASE = '/SIAdrafts/Backend/api/';

  /* ─── state ─── */
  let flatRows   = [];   // raw rows from get_schedules.php (one per day)
  let blocks      = [];   // grouped: one entry per subject/section/room/time combo
  let options     = { sections: [], subjects: [], rooms: [], professors: [] };
  let editBlock   = null; // block object currently being edited, or null when adding
  let deleteIds   = [];   // schedule_id list for the block pending deletion

  /* ─── refs ─── */
  const scheduleModal = document.getElementById('scheduleModal');
  const deleteModal   = document.getElementById('deleteModal');
  const modalTitle     = document.getElementById('modalTitle');
  const modalSubtitle  = document.getElementById('modalSubtitle');
  const submitBtn      = document.getElementById('submitSchedule');
  const filterCourse   = document.getElementById('filterCourse');
  const filterYear     = document.getElementById('filterYear');
  const filterDay      = document.getElementById('filterDay');
  const searchInput    = document.getElementById('searchSchedule');
  const scheduleBody   = document.getElementById('scheduleBody');
  const emptyState     = document.getElementById('emptyState');

  document.addEventListener('DOMContentLoaded', init);

  async function init() {
    await Promise.all([loadOptions(), loadSchedules()]);
    populateOptionDropdowns();
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

  /* ─── dropdown population ─── */
  function populateOptionDropdowns() {
    const fSection = document.getElementById('fSection');
    fSection.innerHTML = '<option value="" disabled selected>Select section</option>' +
      options.sections.map(s => `<option value="${s.section_id}">${escHtml(s.course_code + ' ' + s.section_name)}</option>`).join('');

    const fSubject = document.getElementById('fSubject');
    fSubject.innerHTML = '<option value="" disabled selected>Select subject</option>' +
      options.subjects.map(s => `<option value="${s.subject_id}">${escHtml(s.subject_code + ' — ' + s.subject_name)}</option>`).join('');

    const fRoom = document.getElementById('fRoom');
    fRoom.innerHTML = '<option value="" disabled selected>Select room</option>' +
      options.rooms.map(r => `<option value="${r.room_id}">${escHtml(r.room_name)}</option>`).join('');

    const fProfessor = document.getElementById('fProfessor');
    fProfessor.innerHTML = '<option value="">Unassigned</option>' +
      options.professors.map(p => `<option value="${p.professor_id}">${escHtml(p.professor_name)}</option>`).join('');
  }

  function populateCourseFilter() {
    const codes = [...new Set(options.sections.map(s => s.course_code))].sort();
    filterCourse.innerHTML = '<option value="">All Courses</option>' +
      codes.map(c => `<option value="${escHtml(c)}">${escHtml(c)}</option>`).join('');
  }

  /* ─── open add modal ─── */
  document.getElementById('openAddModal').addEventListener('click', () => {
    editBlock = null;
    modalTitle.textContent    = 'Add Schedule';
    modalSubtitle.textContent = 'Fill in the schedule details below';
    submitBtn.textContent     = '+ Add Schedule';
    clearForm();
    openModal(scheduleModal);
  });

  /* ─── close buttons ─── */
  document.getElementById('closeModal').addEventListener('click',  () => closeModal(scheduleModal));
  document.getElementById('cancelModal').addEventListener('click', () => closeModal(scheduleModal));
  document.getElementById('closeDeleteModal').addEventListener('click',  () => closeModal(deleteModal));
  document.getElementById('cancelDelete').addEventListener('click', () => closeModal(deleteModal));
  scheduleModal.addEventListener('click', e => { if (e.target === scheduleModal) closeModal(scheduleModal); });
  deleteModal.addEventListener('click',   e => { if (e.target === deleteModal)   closeModal(deleteModal); });

  /* ─── submit (add or edit) ─── */
  submitBtn.addEventListener('click', async () => {
    if (!validate()) return;
    const payload = readForm();
    if (editBlock) payload.ids = editBlock.ids;

    submitBtn.disabled = true;
    try {
      const r = await fetch(API_BASE + 'save_schedule.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(payload),
      });
      const d = await r.json();
      if (d.error) {
        show('errSection', d.error); // surface server-side conflict/validation errors
        return;
      }
      await loadSchedules();
      render();
      closeModal(scheduleModal);
    } catch (_) {
      show('errSection', 'Network error. Please try again.');
    } finally {
      submitBtn.disabled = false;
    }
  });

  /* ─── delete confirm ─── */
  document.getElementById('confirmDelete').addEventListener('click', async () => {
    if (!deleteIds.length) return;
    try {
      await fetch(API_BASE + 'delete_schedule.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ ids: deleteIds }),
      });
    } catch (_) { /* ignore, still refresh */ }
    await loadSchedules();
    render();
    closeModal(deleteModal);
  });

  /* ─── filters ─── */
  filterCourse.addEventListener('change', render);
  filterYear.addEventListener('change', render);
  filterDay.addEventListener('change', render);
  searchInput.addEventListener('input', render);

  /* ─── global edit/delete/toggle openers ─── */
  window.openEdit = function (idsKey) {
    const b = blocks.find(b => b.ids.join(',') === String(idsKey));
    if (!b) return;
    editBlock = b;
    modalTitle.textContent    = 'Edit Schedule';
    modalSubtitle.textContent = 'Update the schedule details';
    submitBtn.textContent     = 'Save Changes';
    fillForm(b);
    openModal(scheduleModal);
  };

  window.openDelete = function (idsKey) {
    const b = blocks.find(b => b.ids.join(',') === String(idsKey));
    if (!b) return;
    deleteIds = b.ids;
    document.getElementById('deleteLabel').textContent =
      b.subject_name + ' (' + b.course_code + ' ' + b.section_name + ')';
    openModal(deleteModal);
  };

  window.toggleGroup = function (groupId) {
    const rows = document.querySelectorAll(`.subject-row[data-parent="${cssEscape(groupId)}"]`);
    const arrow = document.getElementById(`arrow-${groupId}`);
    if (!arrow) return;
    const isOpen = arrow.classList.contains('open');
    rows.forEach(r => r.style.display = isOpen ? 'none' : '');
    arrow.classList.toggle('open', !isOpen);
    arrow.textContent = isOpen ? '▶' : '▼';
  };

  /* ─── helpers ─── */
  function openModal(el)  { el.classList.add('active'); }
  function closeModal(el) { el.classList.remove('active'); }

  function clearForm() {
    document.getElementById('fSection').value = '';
    document.getElementById('fSection').selectedIndex = 0;
    document.getElementById('fSubject').selectedIndex = 0;
    document.getElementById('fProfessor').value = '';
    document.getElementById('fRoom').selectedIndex = 0;
    document.getElementById('fStart').value   = '';
    document.getElementById('fEnd').value     = '';
    document.getElementById('fSchoolYear').value = '';
    document.getElementById('fSemester').value = '1';
    document.getElementById('fStatus').checked = true;
    document.querySelectorAll('.day-cb').forEach(cb => cb.checked = false);
    document.querySelectorAll('input[name="fType"]').forEach(r => r.checked = false);
    clearErrors();
  }

  function fillForm(b) {
    document.getElementById('fSection').value    = b.section_id;
    document.getElementById('fSubject').value    = b.subject_id;
    document.getElementById('fProfessor').value  = b.professor_id || '';
    document.getElementById('fRoom').value       = b.room_id;
    document.getElementById('fStart').value      = b.time_start;
    document.getElementById('fEnd').value        = b.time_end;
    document.getElementById('fSchoolYear').value = b.school_year;
    document.getElementById('fSemester').value   = String(b.semester);
    document.getElementById('fStatus').checked   = true; // no DB column yet — see note below
    document.querySelectorAll('.day-cb').forEach(cb => {
      cb.checked = b.days.includes(cb.value);
    });
    clearErrors();
  }

  function readForm() {
    const days = [...document.querySelectorAll('.day-cb:checked')].map(cb => cb.value);
    const typeRadio = document.querySelector('input[name="fType"]:checked');
    return {
      section_id:   +document.getElementById('fSection').value || 0,
      subject_id:   +document.getElementById('fSubject').value || 0,
      professor_id: +document.getElementById('fProfessor').value || 0,
      room_id:      +document.getElementById('fRoom').value || 0,
      type:         typeRadio ? typeRadio.value : '',
      days,
      time_start:   document.getElementById('fStart').value,
      time_end:     document.getElementById('fEnd').value,
      school_year:  document.getElementById('fSchoolYear').value.trim(),
      semester:     +document.getElementById('fSemester').value,
    };
  }

  function validate() {
    clearErrors();
    let ok = true;
    const v = readForm();
    if (!v.section_id)  { show('errSection', 'Please select a section.'); ok = false; }
    if (!v.subject_id)  { show('errSubject', 'Please select a subject.'); ok = false; }
    if (!v.type)        { show('errType', 'Please select Lab or Lec.'); ok = false; }
    if (!v.room_id)      { show('errRoom', 'Please select a room.'); ok = false; }
    if (!v.days.length) { show('errDays', 'Select at least one day.'); ok = false; }
    if (!v.time_start || !v.time_end) { show('errTime', 'Both start and end time are required.'); ok = false; }
    else if (v.time_start >= v.time_end) { show('errTime', 'End time must be after start time.'); ok = false; }
    if (!/^\d{4}-\d{4}$/.test(v.school_year)) { show('errSchoolYear', 'Use YYYY-YYYY format.'); ok = false; }
    return ok;
  }

  function show(id, msg) { const el = document.getElementById(id); if (el) el.textContent = msg; }
  function clearErrors() {
    ['errSection','errSubject','errType','errRoom','errDays','errTime','errSchoolYear'].forEach(id => show(id, ''));
  }

  /* ─── render (grouped by course + year level, like the original mock) ─── */
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
      <td><span class="badge badge-success">Active</span></td>
      <td></td>`;
    return tr;
  }

  function buildBlockRow(gid, b) {
    const tr = document.createElement('tr');
    tr.className = 'subject-row';
    tr.dataset.parent = gid;
    const idsKey = b.ids.join(',');

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
      <td><span class="badge badge-success">Active</span></td>
      <td class="actions-cell">
        <button class="btn btn-outline action-btn" onclick="openEdit('${idsKey}');event.stopPropagation()">Edit</button>
        <button class="btn btn-danger action-btn" onclick="openDelete('${idsKey}');event.stopPropagation()">Delete</button>
      </td>`;
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