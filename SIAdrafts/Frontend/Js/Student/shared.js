// Shared across every student page (loaded via Include/footer.php) so any
// page can call spToast(...)/spConfirm(...) from a click handler without
// its own copy. Only ever invoked from event listeners, which run after
// this parses, so load order relative to page-specific scripts doesn't
// matter.
window.spToast = function (message, icon) {
  var host = document.getElementById('spToastHost');
  if (!host) return;
  var el = document.createElement('div');
  el.className = 'sp-toast';
  var iconEl = document.createElement('iconify-icon');
  iconEl.setAttribute('icon', icon || 'mdi:check-circle-outline');
  var textEl = document.createElement('span');
  textEl.textContent = message;
  el.appendChild(iconEl);
  el.appendChild(textEl);
  host.appendChild(el);
  requestAnimationFrame(function () { el.classList.add('is-visible'); });
  setTimeout(function () {
    el.classList.remove('is-visible');
    setTimeout(function () { el.remove(); }, 300);
  }, 3200);
};

// Delegated so any current or future "download a file" link just needs
// this class -- no per-page wiring required.
document.addEventListener('click', function (e) {
  var t = e.target.closest('.js-ics-download');
  if (t) window.spToast('Calendar file downloading…', 'mdi:calendar-check');
});

// A native <dialog>-based confirm, styled to match the rest of the app,
// instead of a themed alert library (SweetAlert etc.) that ships its own
// visual language you'd then have to fight to make match. Returns a
// Promise<boolean> so callers can `await` the user's choice.
window.spConfirm = function (message, opts) {
  opts = opts || {};
  var dialog = document.getElementById('spConfirmDialog');
  if (!dialog || typeof dialog.showModal !== 'function') {
    return Promise.resolve(window.confirm(message)); // very old browser fallback
  }
  document.getElementById('spConfirmTitle').textContent = opts.title || 'Are you sure?';
  document.getElementById('spConfirmMessage').textContent = message;
  var okBtn = document.getElementById('spConfirmOk');
  var cancelBtn = document.getElementById('spConfirmCancel');
  okBtn.textContent = opts.confirmLabel || 'Confirm';

  return new Promise(function (resolve) {
    function cleanup(result) {
      dialog.close();
      okBtn.removeEventListener('click', onOk);
      cancelBtn.removeEventListener('click', onCancel);
      dialog.removeEventListener('cancel', onCancel);
      dialog.removeEventListener('click', onBackdrop);
      resolve(result);
    }
    function onOk() { cleanup(true); }
    function onCancel() { cleanup(false); }
    function onBackdrop(e) { if (e.target === dialog) cleanup(false); }

    okBtn.addEventListener('click', onOk);
    cancelBtn.addEventListener('click', onCancel);
    dialog.addEventListener('cancel', onCancel); // Esc key
    dialog.addEventListener('click', onBackdrop);
    dialog.showModal();
  });
};

// Confirm before logging out -- previously an instant GET with no chance
// to back out of an accidental click.
document.addEventListener('click', function (e) {
  var logoutLink = e.target.closest('.sp-logout');
  if (!logoutLink) return;
  e.preventDefault();
  var href = logoutLink.getAttribute('href');
  window.spConfirm("You'll need to sign in again to get back into your portal.", {
    title: 'Log out?',
    confirmLabel: 'Log out'
  }).then(function (confirmed) {
    if (confirmed) window.location.href = href;
  });
});
