document.querySelectorAll('[data-target]').forEach(function (btn) {
  btn.addEventListener('click', function () {
    const input   = document.getElementById(btn.dataset.target);
    const icon    = btn.querySelector('iconify-icon');
    const showing = input.type === 'password';
    input.type    = showing ? 'text' : 'password';
    icon.setAttribute('icon', showing ? 'mdi:eye-off-outline' : 'mdi:eye-outline');
    btn.setAttribute('aria-label', showing ? 'Hide password' : 'Show password');
  });
});

// ===== Access terminal =====
// Same processing-overlay pattern as Treasury's payment terminal
// (Frontend/Js/Admission/treasury.js, runPaymentTerminal): sequences the
// perceived steps around the real network call and reflects the actual
// result — it never fabricates success.
const STEP_MS = 480;

function runLoginTerminal(steps, run) {
  const terminal = document.getElementById('loginTerminal');
  const stage    = terminal.querySelector('.badge-terminal-stage');
  const stepEl   = document.getElementById('loginTerminalStep');
  const fillEl   = document.getElementById('loginTerminalFill');
  const resultEl = document.getElementById('loginTerminalResult');

  stage.classList.remove('is-success', 'is-error');
  resultEl.innerHTML = '';
  fillEl.style.transition = 'none';
  fillEl.style.width = '0%';
  stepEl.textContent = steps[0];
  terminal.classList.add('is-open');
  terminal.setAttribute('aria-hidden', 'false');
  document.body.style.overflow = 'hidden';

  requestAnimationFrame(function () {
    fillEl.style.transition = 'width ' + (steps.length * STEP_MS) + 'ms var(--ease-in-out)';
    fillEl.style.width = '92%';
  });

  let i = 0;
  const stepTimer = setInterval(function () {
    i++;
    if (i < steps.length) stepEl.textContent = steps[i];
  }, STEP_MS);

  const minWait = new Promise(function (resolve) { setTimeout(resolve, steps.length * STEP_MS); });

  return Promise.all([
    run().catch(function () { return { success: false, error: 'Could not reach the server. Please try again.' }; }),
    minWait,
  ]).then(function (results) {
    const result = results[0];
    clearInterval(stepTimer);
    fillEl.style.transition = 'width 200ms ease-out';
    fillEl.style.width = '100%';

    return new Promise(function (resolve) {
      setTimeout(function () {
        if (result && result.success) {
          stepEl.textContent = 'Access granted';
          stage.classList.add('is-success');
        } else {
          stepEl.textContent = 'Access denied';
          stage.classList.add('is-error');
          resultEl.innerHTML = '<span>' + escapeHtml((result && result.error) || 'Login failed.') + '</span>' +
            '<button type="button" class="retry-btn" data-terminal-dismiss>Try again</button>';
        }
        resolve(result);
      }, 220);
    });
  });
}

function closeLoginTerminal() {
  const terminal = document.getElementById('loginTerminal');
  terminal.classList.remove('is-open');
  terminal.setAttribute('aria-hidden', 'true');
  document.body.style.overflow = '';
}

function escapeHtml(str) {
  return String(str).replace(/[&<>"']/g, function (c) {
    return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c];
  });
}

const loginTerminalEl = document.getElementById('loginTerminal');
if (loginTerminalEl) {
  loginTerminalEl.addEventListener('click', function (e) {
    if (e.target.closest('[data-terminal-dismiss]')) closeLoginTerminal();
  });
}

// ===== Login form =====
const loginForm = document.getElementById('login-form');
const loginBtn  = document.getElementById('login-btn');

if (loginForm) {
  loginForm.addEventListener('submit', function (e) {
    e.preventDefault();
    if (loginBtn) loginBtn.disabled = true;

    runLoginTerminal(
      ['Checking credentials…', 'Preparing your workspace…'],
      function () {
        return fetch('/SIAdrafts/Backend/api/Auth/login.php', {
          method: 'POST',
          body:   new FormData(loginForm),
        }).then(function (res) { return res.json(); })
          .then(function (d) { return { success: !!d.success, redirect: d.redirect, error: d.error, tab_token: d.tab_token }; });
      }
    ).then(function (result) {
      if (loginBtn) loginBtn.disabled = false;
      if (result && result.success) {
        try { sessionStorage.setItem('sia_tab_token', result.tab_token); } catch (e) {}
        window.setTimeout(function () { window.location.href = result.redirect; }, 450);
      }
    });
  });
}
