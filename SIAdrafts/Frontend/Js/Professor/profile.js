document.addEventListener('DOMContentLoaded', function () {

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

  const saveEmailBtn = document.getElementById('saveEmailBtn');
  if (saveEmailBtn) {
    saveEmailBtn.addEventListener('click', async () => {
      const email = document.getElementById('profileEmail').value.trim();
      if (!email) {
        Swal.fire({ icon: 'warning', title: 'Email required' });
        return;
      }
      try {
        const result = await postJSON('update_professor_profile.php', { action: 'update_email', email });
        if (result.error) {
          Swal.fire({ icon: 'error', title: 'Could not update email', text: result.error });
          return;
        }
        Swal.fire({ icon: 'success', title: 'Email updated', timer: 1500, showConfirmButton: false });
      } catch (err) {
        Swal.fire({ icon: 'error', title: 'Something went wrong', text: 'Please try again.' });
      }
    });
  }

  const changePasswordBtn = document.getElementById('changePasswordBtn');
  if (changePasswordBtn) {
    changePasswordBtn.addEventListener('click', async () => {
      const current_password = document.getElementById('currentPassword').value;
      const new_password      = document.getElementById('newPassword').value;
      const confirm_password  = document.getElementById('confirmPassword').value;

      if (!current_password || !new_password || !confirm_password) {
        Swal.fire({ icon: 'warning', title: 'All fields are required' });
        return;
      }
      if (new_password.length < 8) {
        Swal.fire({ icon: 'warning', title: 'Password too short', text: 'Must be at least 8 characters.' });
        return;
      }
      if (new_password !== confirm_password) {
        Swal.fire({ icon: 'warning', title: 'Passwords do not match' });
        return;
      }

      try {
        const result = await postJSON('update_professor_profile.php', { action: 'change_password', current_password, new_password });
        if (result.error) {
          Swal.fire({ icon: 'error', title: 'Could not change password', text: result.error });
          return;
        }
        document.getElementById('currentPassword').value = '';
        document.getElementById('newPassword').value = '';
        document.getElementById('confirmPassword').value = '';
        Swal.fire({ icon: 'success', title: 'Password updated', timer: 1500, showConfirmButton: false });
      } catch (err) {
        Swal.fire({ icon: 'error', title: 'Something went wrong', text: 'Please try again.' });
      }
    });
  }

});
