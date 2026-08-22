document.addEventListener('DOMContentLoaded', function () {

  const tableEl = document.getElementById('studentAccountTable');
  const table = tableEl ? initDataTable('#studentAccountTable', { order: [] }) : null;

  const searchEl = document.getElementById('studentAccountSearch');
  if (searchEl && table) {
    searchEl.addEventListener('input', () => table.search(searchEl.value).draw());
  }

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

  document.querySelectorAll('[data-reset-student]').forEach(btn => {
    btn.addEventListener('click', async () => {
      const student_portal_account_id = btn.getAttribute('data-reset-student');
      const name = btn.getAttribute('data-student-name');

      const confirmResult = await Swal.fire({
        icon: 'question',
        title: `Reset password for ${name}?`,
        text: 'They will be issued a new temporary password and required to change it on next login.',
        showCancelButton: true,
        confirmButtonText: 'Reset Password',
        confirmButtonColor: '#dc2626',
      });
      if (!confirmResult.isConfirmed) return;

      const result = await postJSON('Accounts/reset_student_password.php', { student_portal_account_id });
      if (result.error) {
        Swal.fire({ icon: 'error', title: 'Could not reset password', text: result.error });
        return;
      }

      Swal.fire({
        icon: 'success',
        title: 'Password reset',
        html: `New temporary password: <strong>${result.temp_password}</strong><br>They must change it on next login.`,
        confirmButtonColor: '#1c2b4a',
      }).then(() => location.reload());
    });
  });

});
