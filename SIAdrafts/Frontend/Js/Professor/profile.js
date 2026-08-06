document.addEventListener('DOMContentLoaded', function () {

  const API = '/SIAdrafts/Backend/api/';
  const csrfToken = document.body.dataset.csrf;

  document.querySelectorAll('.sp-input-toggle').forEach(function (btn) {
    btn.addEventListener('click', function () {
      var input = document.getElementById(btn.dataset.for);
      var showing = input.type === 'text';
      input.type = showing ? 'password' : 'text';
      btn.setAttribute('aria-pressed', String(!showing));
      btn.setAttribute('aria-label', showing ? 'Show password' : 'Hide password');
      btn.querySelector('iconify-icon').setAttribute('icon', showing ? 'mdi:eye-outline' : 'mdi:eye-off-outline');
    });
  });

  function showError(boxId, msgId, message) {
    const box = document.getElementById(boxId);
    const msg = document.getElementById(msgId);
    box.classList.remove('is-visible');
    void box.offsetWidth; // restart the shake animation if the same error fires twice in a row
    msg.textContent = message;
    box.classList.add('is-visible');
  }

  function setLoading(btn, loading, idleLabel) {
    btn.disabled = loading;
    btn.querySelector('.sp-btn-spinner').hidden = !loading;
    btn.querySelector('.sp-btn-label').textContent = loading ? 'Saving…' : idleLabel;
  }

  async function postJSON(url, payload) {
    const res = await fetch(API + url, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json', 'X-CSRF-Token': csrfToken },
      body: JSON.stringify(payload),
    });
    return res.json();
  }

  const saveEmailBtn = document.getElementById('saveEmailBtn');
  if (saveEmailBtn) {
    saveEmailBtn.addEventListener('click', async () => {
      const email = document.getElementById('profileEmail').value.trim();
      if (!email) {
        showError('emailError', 'emailErrorMsg', 'Email is required.');
        return;
      }
      setLoading(saveEmailBtn, true, 'Save Email');
      try {
        const result = await postJSON('update_professor_profile.php', { action: 'update_email', email });
        if (result.error) {
          showError('emailError', 'emailErrorMsg', result.error);
          return;
        }
        document.getElementById('emailError').classList.remove('is-visible');
        window.spToast('Email updated.', 'mdi:check-circle-outline');
      } catch (err) {
        showError('emailError', 'emailErrorMsg', 'Something went wrong. Please try again.');
      } finally {
        setLoading(saveEmailBtn, false, 'Save Email');
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
        showError('pwError', 'pwErrorMsg', 'All fields are required.');
        return;
      }
      if (new_password.length < 8) {
        showError('pwError', 'pwErrorMsg', 'New password must be at least 8 characters.');
        return;
      }
      if (new_password !== confirm_password) {
        showError('pwError', 'pwErrorMsg', 'Passwords do not match.');
        return;
      }

      setLoading(changePasswordBtn, true, 'Change Password');
      try {
        const result = await postJSON('update_professor_profile.php', { action: 'change_password', current_password, new_password });
        if (result.error) {
          showError('pwError', 'pwErrorMsg', result.error);
          return;
        }
        document.getElementById('pwError').classList.remove('is-visible');
        document.getElementById('currentPassword').value = '';
        document.getElementById('newPassword').value = '';
        document.getElementById('confirmPassword').value = '';
        window.spToast('Password updated.', 'mdi:check-circle-outline');
      } catch (err) {
        showError('pwError', 'pwErrorMsg', 'Something went wrong. Please try again.');
      } finally {
        setLoading(changePasswordBtn, false, 'Change Password');
      }
    });
  }

});
