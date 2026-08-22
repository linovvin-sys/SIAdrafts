/**
 * Automatically appends a red "*" to the label of every required form field.
 * Runs on every page that includes this script; no per-page markup needed.
 */
(function () {
  function markLabel(label) {
    if (!label) return;
    if (label.querySelector('.required-mark, .required')) return;
    if (/\*\s*$/.test(label.textContent.trim())) return;
    var mark = document.createElement('span');
    mark.className = 'required-mark';
    mark.setAttribute('aria-hidden', 'true');
    mark.textContent = ' *';
    label.appendChild(mark);
  }

  function findLabelFor(field) {
    if (field.id) {
      var byFor = document.querySelector('label[for="' + CSS.escape(field.id) + '"]');
      if (byFor) return byFor;
    }
    return field.closest('label');
  }

  function isLoginField(field) {
    var form = field.closest('form');
    return !!(form && form.id && /login/i.test(form.id));
  }

  function run() {
    document.querySelectorAll('input[required], select[required], textarea[required]').forEach(function (field) {
      if (isLoginField(field)) return;
      markLabel(findLabelFor(field));
    });
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', run);
  } else {
    run();
  }

  // Re-scan when forms are injected dynamically (e.g. modals loaded via AJAX/JS).
  var scheduled = false;
  var observer = new MutationObserver(function () {
    if (scheduled) return;
    scheduled = true;
    requestAnimationFrame(function () {
      scheduled = false;
      run();
    });
  });
  document.addEventListener('DOMContentLoaded', function () {
    observer.observe(document.body, { childList: true, subtree: true });
  });
  if (document.body) {
    observer.observe(document.body, { childList: true, subtree: true });
  }
})();
