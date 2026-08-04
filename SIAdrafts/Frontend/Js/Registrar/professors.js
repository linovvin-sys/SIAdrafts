document.addEventListener('DOMContentLoaded', function () {

  function openModal(modal) {
    modal.classList.add('active');
    document.body.style.overflow = 'hidden';
  }
  function closeModal(modal) {
    modal.classList.remove('active');
    document.body.style.overflow = '';
  }

  const professorTableEl = document.getElementById('professorTable');
  const professorTable = professorTableEl ? initDataTable('#professorTable', { order: [] }) : null;

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

  // ----- Search -----
  // Driven through DataTables' own search API, not manual row.style.display —
  // DataTables paginates client-side, so directly hiding <tr> elements gets
  // silently undone on redraw (sort, page change, its own search box) once
  // there are enough rows to paginate.
  const searchEl = document.getElementById('professorSearch');
  if (searchEl && professorTable) {
    searchEl.addEventListener('input', () => professorTable.search(searchEl.value).draw());
  }

  // ----- Add Professor -----
  const confirmAddProfessor = document.getElementById('confirmAddProfessor');
  if (confirmAddProfessor) {
    confirmAddProfessor.addEventListener('click', async () => {
      const first_name    = document.getElementById('newProfessorFirstName').value.trim();
      const middle_name   = document.getElementById('newProfessorMiddleName').value.trim();
      const last_name     = document.getElementById('newProfessorLastName').value.trim();
      const department_id = document.getElementById('newProfessorDepartment').value;

      if (!first_name || !last_name || !department_id) {
        Swal.fire({ icon: 'warning', title: 'Missing fields', text: 'First name, last name, and department are required.' });
        return;
      }

      const result = await postJSON('save_professor.php', { first_name, middle_name, last_name, department_id });
      if (result.error) {
        Swal.fire({ icon: 'error', title: 'Could not add professor', text: result.error });
        return;
      }
      Swal.fire({ icon: 'success', title: result.message || 'Professor added', timer: 1500, showConfirmButton: false })
        .then(() => location.reload());
    });
  }

  // ----- Activate / Deactivate -----
  document.querySelectorAll('[data-toggle-professor]').forEach(btn => {
    btn.addEventListener('click', async () => {
      const professor_id = btn.getAttribute('data-toggle-professor');
      const action = btn.getAttribute('data-toggle-action');

      const confirmResult = await Swal.fire({
        icon: 'question',
        title: action === 'activate' ? 'Activate this professor?' : 'Deactivate this professor?',
        text: action === 'deactivate' ? 'They will no longer be assignable to new schedules.' : '',
        showCancelButton: true,
        confirmButtonText: action === 'activate' ? 'Activate' : 'Deactivate',
        confirmButtonColor: action === 'activate' ? '#16a34a' : '#dc2626',
      });
      if (!confirmResult.isConfirmed) return;

      const result = await postJSON('update_professor_status.php', { professor_id, action });
      if (result.error) {
        Swal.fire({ icon: 'error', title: 'Could not update', text: result.error });
        return;
      }
      location.reload();
    });
  });

});
