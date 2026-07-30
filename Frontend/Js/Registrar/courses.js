document.addEventListener('DOMContentLoaded', function () {

  function openModal(modal) {
    modal.classList.add('active');
    document.body.style.overflow = 'hidden';
  }
  function closeModal(modal) {
    modal.classList.remove('active');
    document.body.style.overflow = '';
  }

  if (document.getElementById('courseTable')) initDataTable('#courseTable', { order: [] });
  if (document.getElementById('sectionTable')) initDataTable('#sectionTable', { order: [] });

  function wireCardSearch(searchId, gridId, emptyId) {
    const searchEl = document.getElementById(searchId);
    const grid      = document.getElementById(gridId);
    const emptyEl   = document.getElementById(emptyId);
    if (!searchEl || !grid) return;

    const cards = [...grid.querySelectorAll('.subject-card[data-search]')];
    if (!cards.length) return;

    searchEl.addEventListener('input', () => {
      const search = searchEl.value.trim().toLowerCase();
      let visible = 0;

      cards.forEach(card => {
        const show = !search || card.dataset.search.includes(search);
        card.style.display = show ? '' : 'none';
        if (show) visible++;
      });

      if (emptyEl) emptyEl.style.display = visible ? 'none' : 'block';
    });
  }

  wireCardSearch('subjectSearch', 'subjectCardGrid', 'subjectListEmptyState');

  document.querySelectorAll('[data-open]').forEach(btn => {
    btn.addEventListener('click', () => {
      const target = document.getElementById(btn.getAttribute('data-open'));
      if (target) openModal(target);
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

  async function postJSON(url, payload) {
    const res = await fetch(API + url, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify(payload),
    });
    return res.json();
  }

  // ----- Add Course -----
  const confirmAddCourse = document.getElementById('confirmAddCourse');
  if (confirmAddCourse) {
    confirmAddCourse.addEventListener('click', async () => {
      const course_code  = document.getElementById('newCourseCode').value.trim();
      const course_name  = document.getElementById('newCourseName').value.trim();
      const total_units  = document.getElementById('newCourseUnits').value;

      if (!course_code || !course_name) {
        Swal.fire({ icon: 'warning', title: 'Missing fields', text: 'Course code and name are required.' });
        return;
      }

      const result = await postJSON('save_course.php', { course_code, course_name, total_units });
      if (result.error) {
        Swal.fire({ icon: 'error', title: 'Could not add course', text: result.error });
        return;
      }
      Swal.fire({ icon: 'success', title: result.message || 'Course added', timer: 1500, showConfirmButton: false })
        .then(() => location.reload());
    });
  }

  // ----- Add Section -----
  const confirmAddSection = document.getElementById('confirmAddSection');
  if (confirmAddSection) {
    confirmAddSection.addEventListener('click', async () => {
      const section_name = document.getElementById('newSectionName').value.trim();
      const capacity      = document.getElementById('newSectionCapacity').value;
      const course_id     = document.getElementById('newSectionCourse').value;

      if (!section_name || !course_id) {
        Swal.fire({ icon: 'warning', title: 'Missing fields', text: 'Section name and course are required.' });
        return;
      }

      const result = await postJSON('save_section.php', { section_name, capacity, course_id });
      if (result.error) {
        Swal.fire({ icon: 'error', title: 'Could not add section', text: result.error });
        return;
      }
      Swal.fire({ icon: 'success', title: result.message || 'Section added', timer: 1500, showConfirmButton: false })
        .then(() => location.reload());
    });
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

      const result = await postJSON('save_subject.php', { course_id, subject_code, subject_name, units, category_id, year_level, semester });
      if (result.error) {
        Swal.fire({ icon: 'error', title: 'Could not add subject', text: result.error });
        return;
      }
      Swal.fire({ icon: 'success', title: result.message || 'Subject added', timer: 1500, showConfirmButton: false })
        .then(() => location.reload());
    });
  }

  // ----- Remove Course (Head Registrar only — button only renders for that role) -----
  document.querySelectorAll('[data-remove-course]').forEach(btn => {
    btn.addEventListener('click', async () => {
      const course_id = btn.getAttribute('data-remove-course');
      const confirm = await Swal.fire({
        icon: 'warning',
        title: 'Remove this course?',
        text: 'This cannot be undone.',
        showCancelButton: true,
        confirmButtonText: 'Remove',
        confirmButtonColor: '#dc2626',
      });
      if (!confirm.isConfirmed) return;

      const result = await postJSON('delete_course.php', { course_id });
      if (result.error) {
        Swal.fire({ icon: 'error', title: 'Could not remove course', text: result.error });
        return;
      }
      btn.closest('tr').remove();
    });
  });

  // ----- Remove Section (Head Registrar only) -----
  document.querySelectorAll('[data-remove-section]').forEach(btn => {
    btn.addEventListener('click', async () => {
      const section_id = btn.getAttribute('data-remove-section');
      const confirm = await Swal.fire({
        icon: 'warning',
        title: 'Remove this section?',
        text: 'This cannot be undone.',
        showCancelButton: true,
        confirmButtonText: 'Remove',
        confirmButtonColor: '#dc2626',
      });
      if (!confirm.isConfirmed) return;

      const result = await postJSON('delete_section.php', { section_id });
      if (result.error) {
        Swal.fire({ icon: 'error', title: 'Could not remove section', text: result.error });
        return;
      }
      btn.closest('tr').remove();
    });
  });

  function escHtml(s) {
    return String(s ?? '').replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;');
  }

  function ordinalYear(n) {
    const suffix = n === 1 ? 'st' : n === 2 ? 'nd' : n === 3 ? 'rd' : 'th';
    return n ? `${n}${suffix} Yr` : '—';
  }

  // ----- View Course (subjects + units) -----
  document.querySelectorAll('[data-view-course]').forEach(btn => {
    btn.addEventListener('click', async () => {
      const course_id = btn.getAttribute('data-view-course');
      document.getElementById('viewCourseLabel').textContent = btn.getAttribute('data-course-label') || '';

      const modal = document.getElementById('viewCourseModal');
      const body  = document.getElementById('viewCourseBody');
      const totalEl = document.getElementById('viewCourseTotalUnits');
      body.innerHTML = '<p style="text-align:center;">Loading…</p>';
      totalEl.textContent = '—';
      openModal(modal);

      try {
        const res = await fetch(`${API}get_course_subjects.php?course_id=${course_id}`);
        const data = await res.json();

        if (data.error) {
          body.innerHTML = `<p style="text-align:center;color:#b91c1c;">${escHtml(data.error)}</p>`;
          return;
        }

        if (!data.subjects.length) {
          body.innerHTML = '<p style="text-align:center;">No subjects assigned to this course yet.</p>';
          totalEl.textContent = '0';
          return;
        }

        body.innerHTML = data.subjects.map(s => `
          <div class="subject-card">
            <div class="subject-card-top">
              <span class="subject-card-code">${escHtml(s.subject_code)}</span>
              <span class="subject-card-category">${escHtml(s.category_name || 'Uncategorized')}</span>
            </div>
            <div class="subject-card-name">${escHtml(s.subject_name)}</div>
            <div class="subject-card-footer">
              <span>${escHtml(s.units)} unit${Number(s.units) === 1 ? '' : 's'}</span>
              <span>${ordinalYear(s.year_level)} · Sem ${escHtml(s.semester)}</span>
            </div>
          </div>`).join('');
        totalEl.textContent = data.total_units;
      } catch (_) {
        body.innerHTML = '<p style="text-align:center;color:#b91c1c;">Network error. Please try again.</p>';
      }
    });
  });

  // ----- View Section (enrolled students + their subjects) -----
  document.querySelectorAll('[data-view-section]').forEach(btn => {
    btn.addEventListener('click', async () => {
      const section_id = btn.getAttribute('data-view-section');
      document.getElementById('viewSectionLabel').textContent = btn.getAttribute('data-section-label') || '';

      const modal = document.getElementById('viewSectionModal');
      const body  = document.getElementById('viewSectionBody');
      body.innerHTML = '<tr><td colspan="4" style="text-align:center;">Loading…</td></tr>';
      openModal(modal);

      try {
        const res = await fetch(`${API}get_section_roster.php?section_id=${section_id}`);
        const data = await res.json();

        if (data.error) {
          body.innerHTML = `<tr><td colspan="4" style="text-align:center;color:#b91c1c;">${escHtml(data.error)}</td></tr>`;
          return;
        }

        if (!data.students.length) {
          body.innerHTML = '<tr><td colspan="4" style="text-align:center;">No students in this section yet.</td></tr>';
          return;
        }

        body.innerHTML = data.students.map(st => `
          <tr>
            <td>${escHtml(st.student_no)}</td>
            <td>${escHtml(st.last_name)}, ${escHtml(st.first_name)}${st.middle_name ? ' ' + escHtml(st.middle_name) : ''}</td>
            <td>${escHtml(st.email || '—')}</td>
            <td>${escHtml(st.contact_number || '—')}</td>
          </tr>`).join('');
      } catch (_) {
        body.innerHTML = '<tr><td colspan="4" style="text-align:center;color:#b91c1c;">Network error. Please try again.</td></tr>';
      }
    });
  });

});