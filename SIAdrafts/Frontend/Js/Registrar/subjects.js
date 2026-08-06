document.addEventListener('DOMContentLoaded', function () {

  function openModal(modal) {
    modal.classList.add('active');
    document.body.style.overflow = 'hidden';
  }
  function closeModal(modal) {
    modal.classList.remove('active');
    document.body.style.overflow = '';
  }

  // ----- Search + course filter, combined -----
  const searchEl = document.getElementById('subjectSearch');
  const courseFilterEl = document.getElementById('subjectCourseFilter');
  const grid = document.getElementById('subjectCardGrid');
  const emptyEl = document.getElementById('subjectListEmptyState');
  const cards = grid ? [...grid.querySelectorAll('.subject-card[data-search]')] : [];

  function applySubjectFilter() {
    const search = (searchEl?.value || '').trim().toLowerCase();
    const courseId = courseFilterEl?.value || '';
    let visible = 0;

    cards.forEach(card => {
      const matchesSearch = !search || card.dataset.search.includes(search);
      const matchesCourse = !courseId || card.dataset.courseId === courseId;
      const show = matchesSearch && matchesCourse;
      card.style.display = show ? '' : 'none';
      if (show) visible++;
    });

    if (emptyEl) emptyEl.style.display = visible ? 'none' : 'block';
  }

  if (searchEl) searchEl.addEventListener('input', applySubjectFilter);
  if (courseFilterEl) courseFilterEl.addEventListener('change', applySubjectFilter);
  // Apply once on load — the course filter may already be pre-selected via
  // a "Manage subjects" link carrying ?course_id=, rendered server-side.
  if (cards.length) applySubjectFilter();

  document.querySelectorAll('[data-open]').forEach(btn => {
    btn.addEventListener('click', () => {
      const target = document.getElementById(btn.getAttribute('data-open'));
      if (!target) return;
      // Pre-fill the Add Subject modal's course with whatever's currently filtered.
      if (target.id === 'addSubjectModal' && courseFilterEl && courseFilterEl.value) {
        const courseSel = document.getElementById('newSubjectCourse');
        if (courseSel && [...courseSel.options].some(o => o.value === courseFilterEl.value)) {
          courseSel.value = courseFilterEl.value;
        }
      }
      openModal(target);
    });
  });
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

  async function postJSON(url, payload) {
    const res = await fetch(API + url, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json', 'X-CSRF-Token': csrfToken() },
      body: JSON.stringify(payload),
    });
    return res.json();
  }

  // ----- Add Subject -----
  const confirmAddSubject = document.getElementById('confirmAddSubject');
  if (confirmAddSubject) {
    confirmAddSubject.addEventListener('click', async () => {
      const course_id    = document.getElementById('newSubjectCourse').value;
      const subject_code = document.getElementById('newSubjectCode').value.trim();
      const subject_name = document.getElementById('newSubjectName').value.trim();
      const units         = document.getElementById('newSubjectUnits').value;
      const category_id  = document.getElementById('newSubjectCategory').value;
      const year_level    = document.getElementById('newSubjectYearLevel').value;
      const semester       = document.getElementById('newSubjectSemester').value;

      if (!course_id || !subject_code || !subject_name || !category_id || !year_level || !semester) {
        Swal.fire({ icon: 'warning', title: 'Missing fields', text: 'Course, subject code, subject name, category, year level, and semester are required.' });
        return;
      }

      if (!(Number(units) > 0)) {
        Swal.fire({ icon: 'warning', title: 'Invalid units', text: 'Units must be greater than zero.' });
        return;
      }

      const result = await postJSON('save_subject.php', { course_id, subject_code, subject_name, units, category_id, year_level, semester });
      if (result.error) {
        Swal.fire({ icon: 'error', title: 'Could not add subject', text: result.error });
        return;
      }
      Swal.fire({ icon: 'success', title: result.message || 'Subject added', timer: 1500, showConfirmButton: false })
        .then(() => location.reload());
    });
  }

  function escHtml(s) {
    return String(s ?? '').replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;');
  }

  // ----- Import Curriculum (CSV) -----
  const csvFileInput = document.getElementById('curriculumCsvFile');
  const previewBtn = document.getElementById('previewCurriculumBtn');
  const confirmImportBtn = document.getElementById('confirmCurriculumImport');
  const statusEl = document.getElementById('curriculumImportStatus');
  const previewWrap = document.getElementById('curriculumPreviewWrap');
  const previewSummaryEl = document.getElementById('curriculumPreviewSummary');
  const previewBodyEl = document.getElementById('curriculumPreviewBody');

  const ACTION_LABELS = {
    insert: '<span class="status-pill status-pill--approved">New</span>',
    cross_list: '<span class="status-pill status-pill--pending">Cross-list</span>',
    no_action: '<span class="status-pill status-pill--rejected">Already added</span>',
    error: '<span class="status-pill status-pill--rejected">Error</span>',
  };

  function showImportStatus(kind, text) {
    if (!statusEl) return;
    statusEl.style.display = 'block';
    statusEl.className = kind === 'error' ? 'alert-box alert-error' : 'alert-box alert-success';
    statusEl.textContent = text;
  }

  function resetImportUI() {
    if (statusEl) statusEl.style.display = 'none';
    if (previewWrap) previewWrap.style.display = 'none';
    if (confirmImportBtn) confirmImportBtn.disabled = true;
  }

  if (csvFileInput) csvFileInput.addEventListener('change', resetImportUI);

  async function uploadCsv(endpoint) {
    const file = csvFileInput?.files?.[0];
    if (!file) {
      Swal.fire({ icon: 'warning', title: 'No file chosen', text: 'Please choose a CSV file first.' });
      return null;
    }
    const formData = new FormData();
    formData.append('curriculum_csv', file);

    const res = await fetch(API + endpoint, {
      method: 'POST',
      headers: { 'X-CSRF-Token': csrfToken() },
      body: formData,
    });
    return res.json();
  }

  if (previewBtn) {
    previewBtn.addEventListener('click', async () => {
      resetImportUI();
      previewBtn.disabled = true;
      previewBtn.textContent = 'Checking…';
      try {
        const result = await uploadCsv('preview_curriculum_import.php');
        if (!result) return;
        if (result.error) {
          showImportStatus('error', result.error);
          return;
        }

        const c = result.counts;
        previewSummaryEl.textContent =
          `${c.insert} new, ${c.cross_list} to cross-list, ${c.no_action} already added, ${c.error} error(s).`;
        previewBodyEl.innerHTML = result.rows.map(r => `
          <tr>
            <td>${r.line}</td>
            <td>${escHtml(r.subject_code)} — ${escHtml(r.subject_name)}</td>
            <td>${escHtml(r.course_code)}</td>
            <td>${ACTION_LABELS[r.action] || ''}<div class="text-muted" style="font-size:11px;">${escHtml(r.message)}</div></td>
          </tr>`).join('');
        previewWrap.style.display = 'block';

        if (result.has_errors) {
          showImportStatus('error', 'This file has errors — fix them and re-upload before importing. Nothing has been saved yet.');
          confirmImportBtn.disabled = true;
        } else {
          confirmImportBtn.disabled = false;
        }
      } catch (_) {
        showImportStatus('error', 'Network error. Please try again.');
      } finally {
        previewBtn.disabled = false;
        previewBtn.textContent = 'Preview';
      }
    });
  }

  if (confirmImportBtn) {
    confirmImportBtn.addEventListener('click', async () => {
      confirmImportBtn.disabled = true;
      confirmImportBtn.textContent = 'Importing…';
      try {
        const result = await uploadCsv('import_curriculum.php');
        if (!result) { confirmImportBtn.disabled = false; confirmImportBtn.textContent = 'Confirm Import'; return; }
        if (result.error) {
          showImportStatus('error', result.error);
          confirmImportBtn.disabled = false;
          confirmImportBtn.textContent = 'Confirm Import';
          return;
        }
        Swal.fire({ icon: 'success', title: result.message || 'Curriculum imported', timer: 2000, showConfirmButton: false })
          .then(() => location.reload());
      } catch (_) {
        showImportStatus('error', 'Network error. Please try again.');
        confirmImportBtn.disabled = false;
        confirmImportBtn.textContent = 'Confirm Import';
      }
    });
  }

});
