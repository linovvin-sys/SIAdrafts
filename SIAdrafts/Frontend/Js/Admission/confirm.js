/* ============================================================
   confirm.js — shared SweetAlert2 confirmation layer for Admission
   (loaded on every page via Include/footer.php, so it covers
    Staff / Admission / Treasury roles alike)
   ============================================================ */

function confirmAction(opts) {
  opts = opts || {};
  if (typeof Swal === 'undefined') {
    return Promise.resolve(window.confirm(opts.title || 'Are you sure?'));
  }
  return Swal.fire({
    title: opts.title || 'Are you sure?',
    text: opts.text || '',
    html: opts.html || undefined,
    icon: opts.icon || 'question',
    showCancelButton: true,
    confirmButtonText: opts.confirmText || 'Yes, proceed',
    cancelButtonText: opts.cancelText || 'Cancel',
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

  /* ===== Logout ===== */
  document.querySelectorAll('a[href*="Auth/logout.php"]').forEach(function (link) {
    link.addEventListener('click', function (e) {
      e.preventDefault();
      const href = link.getAttribute('href');
      confirmAction({
        title: 'Log out?',
        text: 'You will need to sign in again to continue.',
        icon: 'warning',
        confirmText: 'Yes, log out',
        danger: true,
      }).then(function (ok) {
        if (ok) window.location.href = href;
      });
    });
  });

});