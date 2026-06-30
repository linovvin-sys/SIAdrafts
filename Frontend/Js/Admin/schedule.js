(function () {
  /* ─── state ─── */
  let schedules = [];
  let editId = null;
  let deleteId = null;

  /* ─── seed from existing DOM rows ─── */
  document.querySelectorAll('#scheduleBody tr').forEach(tr => {
    if (!tr.dataset.id) return;
    schedules.push({
      id:      +tr.dataset.id,
      section: tr.dataset.section,
      subject: tr.dataset.subject,
      type:    tr.dataset.type || 'lec',
      room:    tr.dataset.room,
      days:    JSON.parse(tr.dataset.days),
      start:   tr.dataset.start,
      end:     tr.dataset.end,
      status:  tr.dataset.status,
    });
  });

  /* ─── refs ─── */
  const scheduleModal = document.getElementById('scheduleModal');
  const deleteModal   = document.getElementById('deleteModal');
  const modalTitle    = document.getElementById('modalTitle');
  const modalSubtitle = document.getElementById('modalSubtitle');
  const submitBtn     = document.getElementById('submitSchedule');
  const filterSection = document.getElementById('filterSection');
  const filterDay     = document.getElementById('filterDay');
  const searchInput   = document.getElementById('searchSchedule');
  const recordCount   = document.getElementById('recordCount');

  /* ─── open add modal ─── */
  document.getElementById('openAddModal').addEventListener('click', () => {
    editId = null;
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

  /* ─── overlay click to close ─── */
  scheduleModal.addEventListener('click', e => { if (e.target === scheduleModal) closeModal(scheduleModal); });
  deleteModal.addEventListener('click',   e => { if (e.target === deleteModal)   closeModal(deleteModal); });

  /* ─── submit ─── */
  submitBtn.addEventListener('click', () => {
    if (!validate()) return;
    const entry = readForm();
    if (editId !== null) {
      const idx = schedules.findIndex(s => s.id === editId);
      if (idx !== -1) schedules[idx] = { ...schedules[idx], ...entry };
    } else {
      const newId = schedules.length ? Math.max(...schedules.map(s => s.id)) + 1 : 1;
      schedules.push({ id: newId, ...entry });
    }
    renderTable();
    closeModal(scheduleModal);
  });

  /* ─── delete confirm ─── */
  document.getElementById('confirmDelete').addEventListener('click', () => {
    schedules = schedules.filter(s => s.id !== deleteId);
    renderTable();
    closeModal(deleteModal);
  });

  /* ─── filters ─── */
  filterSection.addEventListener('change', renderTable);
  filterDay.addEventListener('change', renderTable);
  searchInput.addEventListener('input', renderTable);

  /* ─── global edit/delete openers ─── */
  window.openEdit = function (id) {
    const s = schedules.find(s => s.id === id);
    if (!s) return;
    editId = id;
    modalTitle.textContent    = 'Edit Schedule';
    modalSubtitle.textContent = 'Update the schedule details';
    submitBtn.textContent     = 'Save Changes';
    fillForm(s);
    openModal(scheduleModal);
  };

  window.openDelete = function (id) {
    const s = schedules.find(s => s.id === id);
    if (!s) return;
    deleteId = id;
    document.getElementById('deleteLabel').textContent =
      s.subject + ' (' + formatSection(s.section) + ')';
    openModal(deleteModal);
  };

  /* ─── helpers ─── */
  function openModal(el)  { el.classList.add('active'); }
  function closeModal(el) { el.classList.remove('active'); }

  function clearForm() {
    document.getElementById('fSection').value = '';
    document.getElementById('fSubject').value = '';
    document.getElementById('fRoom').value    = '';
    document.getElementById('fStart').value   = '';
    document.getElementById('fEnd').value     = '';
    document.getElementById('fStatus').checked = true;
    document.querySelectorAll('.day-cb').forEach(cb => cb.checked = false);
    document.querySelectorAll('input[name="fType"]').forEach(r => r.checked = false);
    clearErrors();
  }

  function fillForm(s) {
    document.getElementById('fSection').value  = s.section;
    document.getElementById('fSubject').value  = s.subject;
    document.getElementById('fRoom').value     = s.room;
    document.getElementById('fStart').value    = s.start;
    document.getElementById('fEnd').value      = s.end;
    document.getElementById('fStatus').checked = s.status === 'active';
    document.querySelectorAll('.day-cb').forEach(cb => {
      cb.checked = s.days.includes(cb.value);
    });
    document.querySelectorAll('input[name="fType"]').forEach(r => {
      r.checked = r.value === s.type;
    });
    clearErrors();
  }

  function readForm() {
    const days = [...document.querySelectorAll('.day-cb:checked')].map(cb => cb.value);
    const typeRadio = document.querySelector('input[name="fType"]:checked');
    return {
      section: document.getElementById('fSection').value,
      subject: document.getElementById('fSubject').value.trim(),
      type:    typeRadio ? typeRadio.value : '',
      room:    document.getElementById('fRoom').value.trim(),
      days,
      start:   document.getElementById('fStart').value,
      end:     document.getElementById('fEnd').value,
      status:  document.getElementById('fStatus').checked ? 'active' : 'inactive',
    };
  }

  function validate() {
    clearErrors();
    let ok = true;
    const v = readForm();
    if (!v.section) { show('errSection', 'Please select a section.'); ok = false; }
    if (!v.subject) { show('errSubject', 'Subject is required.'); ok = false; }
    if (!v.type)    { show('errType', 'Please select Lab or Lec.'); ok = false; }
    if (!v.room)    { show('errRoom', 'Room is required.'); ok = false; }
    if (!v.days.length) { show('errDays', 'Select at least one day.'); ok = false; }
    if (!v.start || !v.end) { show('errTime', 'Both start and end time are required.'); ok = false; }
    else if (v.start >= v.end) { show('errTime', 'End time must be after start time.'); ok = false; }
    return ok;
  }

  function show(id, msg) { document.getElementById(id).textContent = msg; }
  function clearErrors() {
    ['errSection','errSubject','errType','errRoom','errDays','errTime'].forEach(id => {
      document.getElementById(id).textContent = '';
    });
  }

  /* ─── render ─── */
  function renderTable() {
    const sec    = filterSection.value;
    const day    = filterDay.value;
    const search = searchInput.value.toLowerCase();

    const filtered = schedules.filter(s => {
      if (sec && s.section !== sec) return false;
      if (day && !s.days.includes(day)) return false;
      if (search && !s.subject.toLowerCase().includes(search) && !s.room.toLowerCase().includes(search)) return false;
      return true;
    });

    const tbody = document.getElementById('scheduleBody');
    tbody.innerHTML = '';

    document.getElementById('emptyState').style.display = filtered.length ? 'none' : 'block';
    recordCount.textContent = filtered.length
      ? 'Showing ' + filtered.length + ' record' + (filtered.length !== 1 ? 's' : '')
      : 'No records';

    filtered.forEach(s => {
      const tr = document.createElement('tr');
      tr.dataset.id      = s.id;
      tr.dataset.section = s.section;
      tr.dataset.subject = s.subject;
      tr.dataset.type    = s.type;
      tr.dataset.room    = s.room;
      tr.dataset.days    = JSON.stringify(s.days);
      tr.dataset.start   = s.start;
      tr.dataset.end     = s.end;
      tr.dataset.status  = s.status;

      const dayBadges = s.days.map(d => `<span class="day-badge">${d}</span>`).join(' ');
      const badge = s.status === 'active'
        ? '<span class="badge badge-success">Active</span>'
        : '<span class="badge badge-pending">Inactive</span>';
      const typeBadge = s.type === 'lab'
        ? '<span class="type-badge type-lab">LAB</span>'
        : '<span class="type-badge type-lec">LEC</span>';

      tr.innerHTML = `
        <td><strong>${formatSection(s.section)}</strong></td>
        <td>${escHtml(s.subject)}</td>
        <td>${typeBadge}</td>
        <td>${escHtml(s.room)}</td>
        <td>${dayBadges}</td>
        <td class="time-cell">${formatTime(s.start)} – ${formatTime(s.end)}<br><span>${duration(s.start, s.end)}</span></td>
        <td>${badge}</td>
        <td>
          <button class="btn btn-outline action-btn" onclick="openEdit(${s.id})">Edit</button>
          <button class="btn btn-danger action-btn" onclick="openDelete(${s.id})" style="margin-left:4px">Delete</button>
        </td>`;
      tbody.appendChild(tr);
    });
  }

  function formatSection(code) { return code.replace('-', ' ').replace(/(\d)([AB])/, '$1-$2'); }

  function formatTime(t) {
    if (!t) return '—';
    const [h, m] = t.split(':').map(Number);
    const ampm = h < 12 ? 'AM' : 'PM';
    const h12  = h % 12 || 12;
    return h12 + ':' + String(m).padStart(2,'0') + ' ' + ampm;
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
    return s.replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
  }
})();
