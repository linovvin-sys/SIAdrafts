document.addEventListener('DOMContentLoaded', function () {
  const API = '/SIAdrafts/Backend/api/';
  const csrfToken = document.body.dataset.csrf;

  document.querySelectorAll('.sp-assignment-submit-form').forEach(form => {
    form.addEventListener('submit', async (e) => {
      e.preventDefault();

      const assignmentId = form.getAttribute('data-assignment-id');
      const fileInput = form.querySelector('[data-submission-file]');
      const errEl = form.querySelector('[data-submit-error]');
      const btn = form.querySelector('button[type="submit"]');

      errEl.style.display = 'none';

      if (!fileInput.files || fileInput.files.length === 0) {
        errEl.textContent = 'Please choose a file to submit.';
        errEl.style.display = 'inline';
        return;
      }

      btn.disabled = true;
      btn.querySelector('.sp-btn-spinner').hidden = false;
      const label = btn.querySelector('.sp-btn-label');
      const originalLabel = label.textContent;
      label.textContent = 'Submitting…';

      const body = new FormData();
      body.append('assignment_id', assignmentId);
      body.append('submission_file', fileInput.files[0]);
      body.append('csrf_token', csrfToken);

      try {
        const res = await fetch(API + 'Assignments/submit_assignment.php', { method: 'POST', body });
        const data = await res.json();
        if (data.error) {
          errEl.textContent = data.error;
          errEl.style.display = 'inline';
          return;
        }
        if (window.spToast) window.spToast('Assignment submitted.', 'mdi:file-check-outline');
        window.location.reload();
      } catch (err) {
        errEl.textContent = 'Something went wrong. Please try again.';
        errEl.style.display = 'inline';
      } finally {
        btn.disabled = false;
        btn.querySelector('.sp-btn-spinner').hidden = true;
        label.textContent = originalLabel;
      }
    });
  });
});
