document.addEventListener('DOMContentLoaded', function () {

  function openModal(modal) {
    modal.classList.add('active');
    document.body.style.overflow = 'hidden';
  }
  function closeModal(modal) {
    modal.classList.remove('active');
    document.body.style.overflow = '';
  }

  const sectionTableEl = document.getElementById('sectionTable');
  const table = sectionTableEl ? initDataTable('#sectionTable', { order: [] }) : null;

  const searchEl = document.getElementById('sectionSearch');
  const courseFilterEl = document.getElementById('sectionCourseFilter');

  if (table) {
    // DataTables paginates rows client-side, so filtering by directly hiding
    // <tr> elements (the pattern professors.js/total_enrolees.js use) breaks
    // silently once there are enough rows to paginate — DataTables redraws
    // and un-hides them. Driving both the text search and the course filter
    // through DataTables' own search API keeps filtering, pagination, and
    // the "no matching records" empty state all in sync.
    $.fn.dataTable.ext.search.push(function (settings, searchData, index) {
      if (settings.nTable.id !== 'sectionTable') return true;
      const courseId = courseFilterEl?.value || '';
      if (!courseId) return true;
      const row = table.row(index).node();
      return !!row && row.dataset.courseId === courseId;
    });

    if (searchEl) searchEl.addEventListener('input', () => table.search(searchEl.value).draw());
    if (courseFilterEl) courseFilterEl.addEventListener('change', () => table.draw());
  }

  document.querySelectorAll('[data-open]').forEach(btn => {
    btn.addEventListener('click', () => {
      const target = document.getElementById(btn.getAttribute('data-open'));
      if (!target) return;
      // Pre-fill the Add Section modal's course with whatever's currently filtered.
      if (target.id === 'addSectionModal' && courseFilterEl && courseFilterEl.value) {
        const courseSel = document.getElementById('newSectionCourse');
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

      if (!(Number(capacity) > 0)) {
        Swal.fire({ icon: 'warning', title: 'Invalid capacity', text: 'Capacity must be a positive number.' });
        return;
      }

      const result = await postJSON('Sections/save_section.php', { section_name, capacity, course_id });
      if (result.error) {
        Swal.fire({ icon: 'error', title: 'Could not add section', text: result.error });
        return;
      }
      Swal.fire({ icon: 'success', title: result.message || 'Section added', timer: 1500, showConfirmButton: false })
        .then(() => location.reload());
    });
  }

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

      const result = await postJSON('Sections/delete_section.php', { section_id });
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

  // ----- View Section (enrolled students) -----
  document.querySelectorAll('[data-view-section]').forEach(btn => {
    btn.addEventListener('click', async () => {
      const section_id = btn.getAttribute('data-view-section');
      document.getElementById('viewSectionLabel').textContent = btn.getAttribute('data-section-label') || '';

      const modal = document.getElementById('viewSectionModal');
      const body  = document.getElementById('viewSectionBody');
      body.innerHTML = '<tr><td colspan="4" style="text-align:center;">Loading…</td></tr>';
      openModal(modal);

      try {
        const res = await fetch(`${API}Sections/get_section_roster.php?section_id=${section_id}`);
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
