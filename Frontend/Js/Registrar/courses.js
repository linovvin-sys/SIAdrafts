document.addEventListener('DOMContentLoaded', function () {

  function openModal(modal) {
    modal.classList.add('active');
    document.body.style.overflow = 'hidden';
  }
  function closeModal(modal) {
    modal.classList.remove('active');
    document.body.style.overflow = '';
  }

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

});