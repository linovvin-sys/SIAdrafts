document.addEventListener('DOMContentLoaded', function () {

  function openModal(modal) {
    modal.classList.add('active');
    document.body.style.overflow = 'hidden';
  }
  function closeModal(modal) {
    modal.classList.remove('active');
    document.body.style.overflow = '';
  }
  document.querySelectorAll('[data-close]').forEach(btn => {
    btn.addEventListener('click', () => {
      const target = document.getElementById(btn.getAttribute('data-close'));
      if (target) closeModal(target);
    });
  });
  document.querySelectorAll('.modal-overlay').forEach(overlay => {
    overlay.addEventListener('click', (e) => {
      if (e.target === overlay) closeModal(overlay);
    });
  });
  document.addEventListener('keydown', (e) => {
    if (e.key === 'Escape') {
      document.querySelectorAll('.modal-overlay.active').forEach(closeModal);
    }
  });

  const API = '/SIAdrafts/Backend/api/';

  function csrfToken() {
    return document.body.dataset.csrf || '';
  }

  async function getJSON(url) {
    const res = await fetch(API + url);
    return res.json();
  }
  async function postJSON(url, payload) {
    const res = await fetch(API + url, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json', 'X-CSRF-Token': csrfToken() },
      body: JSON.stringify(payload),
    });
    return res.json();
  }

  const searchInput    = document.getElementById('studentSearchInput');
  const searchBtn      = document.getElementById('searchStudentBtn');
  const searchEmpty    = document.getElementById('searchEmptyState');
  const resultPanel    = document.getElementById('studentResultPanel');
  const infoGrid       = document.getElementById('studentInfoGrid');
  const subjectsPanel  = document.getElementById('subjectsPanel');
  const subjectsBody   = document.getElementById('enrolledSubjectsBody');
  const openAddBtn     = document.getElementById('openAddSubjectBtn');
  const addSubjectSelect = document.getElementById('addSubjectSelect');
  const addScheduleInfo  = document.getElementById('addSubjectScheduleInfo');
  const addScheduleText  = document.getElementById('addSubjectScheduleText');
  const addSubjectModal  = document.getElementById('addSubjectModal');

  let currentEnrollmentId = null;

  function esc(str) {
    return String(str ?? '').replace(/[&<>"']/g, (c) => ({
      '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;'
    }[c]));
  }

  function formatTime(t) {
    const [h, m] = t.split(':');
    const hour = ((+h + 11) % 12) + 1;
    const ampm = +h < 12 ? 'AM' : 'PM';
    return `${hour}:${m} ${ampm}`;
  }

  function scheduleLabel(row) {
    if (!row.day || !row.time_start) return 'No schedule set';
    let label = `${row.day} ${formatTime(row.time_start)}–${formatTime(row.time_end)}`;
    if (row.professor_name) label += ` — ${row.professor_name}`;
    return label;
  }

  function renderStudent(data) {
    const s = data.student;
    const e = data.enrollment;
    const fullName = [s.last_name, s.first_name, s.middle_name].filter(Boolean).join(', ') || s.student_name;

    infoGrid.innerHTML = `
      <div class="info-item"><div class="info-label">Student ID</div><div class="info-value">${esc(s.student_no)}</div></div>
      <div class="info-item"><div class="info-label">Name</div><div class="info-value">${esc(fullName)}</div></div>
      <div class="info-item"><div class="info-label">Course</div><div class="info-value">${esc(e?.course_code) || '—'}</div></div>
      <div class="info-item"><div class="info-label">Section</div><div class="info-value">${esc(e?.section_name) || '—'}</div></div>
      <div class="info-item"><div class="info-label">Year Level</div><div class="info-value">${e?.year_level ?? '—'}</div></div>
      <div class="info-item"><div class="info-label">School Year / Semester</div><div class="info-value">${e ? esc(e.school_year) + ' — Sem ' + e.semester : '—'}</div></div>
      <div class="info-item"><div class="info-label">Enrollment Status</div><div class="info-value">${esc(e?.enrollment_status) || '—'}</div></div>
    `;

    searchEmpty.style.display = 'none';
    resultPanel.style.display = '';

    if (!e) {
      subjectsPanel.style.display = 'none';
      currentEnrollmentId = null;
      Swal.fire({ icon: 'info', title: 'No enrollment record', text: 'This student has no enrollment on file yet.' });
      return;
    }

    currentEnrollmentId = e.enrollment_id;
    subjectsPanel.style.display = '';
    renderSubjects(data.subjects || []);
  }

  const FEE_PER_UNIT = 50;

  function feeLabel(units) {
    return '₱' + (Number(units) * FEE_PER_UNIT).toLocaleString('en-PH', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
  }

  function statusCell(sub) {
    if (sub.status === 'Pending Payment') {
      return `<span class="status-pill status-pill--pending">Pending add fee (${feeLabel(sub.units)})</span>`;
    }
    if (sub.status === 'Pending Drop') {
      return `<span class="status-pill status-pill--pending">Pending drop fee (${feeLabel(sub.units)})</span>`;
    }
    return `<span class="status-pill status-pill--approved">Enrolled</span>`;
  }

  function actionCell(sub) {
    if (sub.status === 'Pending Payment' || sub.status === 'Pending Drop') {
      return `<button type="button" class="btn-outline" data-cancel-fee="${sub.enrollment_subject_id}">Cancel</button>`;
    }
    return `<button type="button" class="btn-remove" data-drop-subject="${sub.enrollment_subject_id}">Drop (${feeLabel(sub.units)})</button>`;
  }

  function renderSubjects(subjects) {
    if (!subjects.length) {
      subjectsBody.innerHTML = `<tr><td colspan="7" style="text-align:center;">No subjects currently enrolled.</td></tr>`;
      return;
    }
    subjectsBody.innerHTML = subjects.map(sub => `
      <tr data-enrollment-subject-id="${sub.enrollment_subject_id}">
        <td>${esc(sub.subject_code)}</td>
        <td>${esc(sub.subject_name)}</td>
        <td>${esc(sub.units)}</td>
        <td>${esc(scheduleLabel(sub))}</td>
        <td>${esc(sub.professor_name) || '—'}</td>
        <td>${statusCell(sub)}</td>
        <td>${actionCell(sub)}</td>
      </tr>
    `).join('');
  }

  async function doSearch() {
    const q = searchInput.value.trim();
    if (!q) {
      Swal.fire({ icon: 'warning', title: 'Enter a student ID' });
      return;
    }
    const data = await getJSON(`get_student_for_addrop.php?student_no=${encodeURIComponent(q)}`);
    if (data.error) {
      resultPanel.style.display = 'none';
      subjectsPanel.style.display = 'none';
      searchEmpty.style.display = '';
      Swal.fire({ icon: 'error', title: 'Not found', text: data.error });
      return;
    }
    renderStudent(data);
  }

  searchBtn.addEventListener('click', doSearch);
  searchInput.addEventListener('keydown', (e) => { if (e.key === 'Enter') doSearch(); });

  // ----- All Enrolled Students list -----
  const allStudentsTable = document.getElementById('allStudentsTable');
  if (allStudentsTable) {
    initDataTable('#allStudentsTable', { order: [] });

    allStudentsTable.addEventListener('click', (e) => {
      const btn = e.target.closest('[data-manage-student]');
      if (!btn) return;
      searchInput.value = btn.getAttribute('data-manage-student');
      doSearch();
      resultPanel.scrollIntoView({ behavior: 'smooth', block: 'start' });
    });
  }

  // ----- Drop subject -----
  subjectsBody.addEventListener('click', async (e) => {
    const dropBtn = e.target.closest('[data-drop-subject]');
    if (dropBtn) {
      const enrollment_subject_id = dropBtn.getAttribute('data-drop-subject');
      const row = dropBtn.closest('tr');
      const units = row.children[2].textContent;

      const confirmResult = await Swal.fire({
        icon: 'warning',
        title: 'Drop this subject?',
        html: `A drop fee of <strong>${feeLabel(units)}</strong> (${units} units &times; ₱50) must be paid at Treasury before the drop is finalized.`,
        showCancelButton: true,
        confirmButtonText: 'Request drop',
        confirmButtonColor: '#dc2626',
      });
      if (!confirmResult.isConfirmed) return;

      const result = await postJSON('drop_subject_registrar.php', { enrollment_subject_id });
      if (result.error) {
        Swal.fire({ icon: 'error', title: 'Could not drop subject', text: result.error });
        return;
      }
      Swal.fire({ icon: 'success', title: result.message || 'Drop requested', timer: 1600, showConfirmButton: false })
        .then(() => doSearch());
      return;
    }

    const cancelBtn = e.target.closest('[data-cancel-fee]');
    if (cancelBtn) {
      const enrollment_subject_id = cancelBtn.getAttribute('data-cancel-fee');

      const confirmResult = await Swal.fire({
        icon: 'warning',
        title: 'Cancel this pending request?',
        text: 'The pending fee will be cancelled.',
        showCancelButton: true,
        confirmButtonText: 'Cancel request',
        confirmButtonColor: '#dc2626',
      });
      if (!confirmResult.isConfirmed) return;

      const result = await postJSON('cancel_subject_fee.php', { enrollment_subject_id });
      if (result.error) {
        Swal.fire({ icon: 'error', title: 'Could not cancel request', text: result.error });
        return;
      }
      Swal.fire({ icon: 'success', title: result.message || 'Request cancelled', timer: 1200, showConfirmButton: false })
        .then(() => doSearch());
    }
  });

  // ----- Add subject -----
  openAddBtn.addEventListener('click', async () => {
    if (!currentEnrollmentId) return;
    addSubjectSelect.innerHTML = '<option value="">Loading…</option>';
    addScheduleInfo.style.display = 'none';
    openModal(addSubjectModal);

    const data = await getJSON(`get_available_subjects_addrop.php?enrollment_id=${currentEnrollmentId}`);
    if (data.error) {
      addSubjectSelect.innerHTML = '<option value="">' + esc(data.error) + '</option>';
      return;
    }
    if (!data.subjects || !data.subjects.length) {
      addSubjectSelect.innerHTML = '<option value="">No subjects available to add</option>';
      return;
    }
    addSubjectSelect.innerHTML = '<option value="">-- Select Subject --</option>' +
      data.subjects.map(s => {
        const scheduleId = s.schedule_id ?? '';
        const scheduleText = scheduleLabel(s);
        return `<option value="${s.subject_id}" data-schedule-id="${scheduleId}" data-schedule-text="${esc(scheduleText)}" data-units="${esc(s.units)}">${esc(s.subject_code)} - ${esc(s.subject_name)} (${esc(s.units)} units)</option>`;
      }).join('');
  });

  addSubjectSelect.addEventListener('change', () => {
    const opt = addSubjectSelect.selectedOptions[0];
    if (!opt || !opt.value) {
      addScheduleInfo.style.display = 'none';
      return;
    }
    const units = opt.getAttribute('data-units');
    addScheduleText.textContent = opt.getAttribute('data-schedule-text') + ` — Add fee: ${feeLabel(units)}`;
    addScheduleInfo.style.display = '';
  });

  document.getElementById('confirmAddSubject').addEventListener('click', async () => {
    const opt = addSubjectSelect.selectedOptions[0];
    const subject_id = addSubjectSelect.value;
    if (!subject_id) {
      Swal.fire({ icon: 'warning', title: 'Select a subject' });
      return;
    }
    const scheduleIdRaw = opt.getAttribute('data-schedule-id');
    const schedule_id = scheduleIdRaw ? scheduleIdRaw : null;

    const result = await postJSON('add_subject_registrar.php', {
      enrollment_id: currentEnrollmentId,
      subject_id,
      schedule_id,
    });
    if (result.error) {
      Swal.fire({ icon: 'error', title: 'Could not add subject', text: result.error });
      return;
    }
    closeModal(addSubjectModal);
    Swal.fire({ icon: 'success', title: result.message || 'Subject added', timer: 1200, showConfirmButton: false })
      .then(() => doSearch());
  });

});