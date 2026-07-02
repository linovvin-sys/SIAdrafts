/* ============================================================
   confirm.js — shared SweetAlert2 confirmation layer for Admin
   ============================================================ */

function confirmAction(opts) {
  opts = opts || {};
  if (typeof Swal === 'undefined') {
    return Promise.resolve(window.confirm(opts.title || 'Are you sure?'));
  }
  return Swal.fire({
    title: opts.title || 'Are you sure?',
    text: opts.text || '',
    icon: opts.icon || 'question',
    showCancelButton: true,
    confirmButtonText: opts.confirmText || 'Yes, proceed',
    cancelButtonText: 'Cancel',
    confirmButtonColor: opts.danger ? '#e03a3a' : '#1c2b4a',
    cancelButtonColor: '#6b7280',
    reverseButtons: true,
    focusCancel: true,
  }).then(function (result) {
    return result.isConfirmed;
  });
}
window.confirmAction = confirmAction;

document.addEventListener('DOMContentLoaded', function () {

  function guardForm(formEl, opts) {
    if (!formEl) return;
    formEl.addEventListener('submit', function (e) {
      if (formEl.dataset.confirmed === 'true') return;
      e.preventDefault();
      if (typeof formEl.reportValidity === 'function' && !formEl.reportValidity()) return;
      confirmAction(opts).then(function (ok) {
        if (ok) {
          formEl.dataset.confirmed = 'true';
          formEl.submit();
        }
      });
    });
  }

  /* ===== Manage Users ===== */
  guardForm(document.querySelector('#addUserModal form'), {
    title: 'Add this user?',
    text: 'A new account will be created. If marked active, they can log in right away.',
    confirmText: 'Yes, add user',
  });

  guardForm(document.getElementById('editUserForm'), {
    title: 'Save changes to this user?',
    text: 'This will update the account\u2019s details.',
    confirmText: 'Yes, save changes',
  });

  /* ===== Courses / Sections / Subjects ===== */
  guardForm(document.querySelector('#addCourseModal form'), {
    title: 'Add this course?',
    confirmText: 'Yes, add course',
  });

  guardForm(document.querySelector('#addSectionModal form'), {
    title: 'Add this section?',
    confirmText: 'Yes, add section',
  });

  guardForm(document.querySelector('#addSubjectModal form'), {
    title: 'Add this subject?',
    confirmText: 'Yes, add subject',
  });

  /* ===== Logout ===== */
  document.querySelectorAll('a[href*="logout.php"]').forEach(function (link) {
    link.addEventListener('click', function (e) {
      e.preventDefault();
      const href = link.getAttribute('href');
      confirmAction({
        title: 'Log out?',
        text: 'You will need to sign in again to access the admin panel.',
        icon: 'warning',
        confirmText: 'Yes, log out',
        danger: true,
      }).then(function (ok) {
        if (ok) window.location.href = href;
      });
    });
  });

});