const form = document.getElementById('loginForm');
const errBox = document.getElementById('loginError');
const errMsg = document.getElementById('loginErrorMsg');
const submitBtn = document.getElementById('loginSubmit');
const submitSpinner = submitBtn.querySelector('.sp-btn-spinner');
const submitLabel = submitBtn.querySelector('.sp-btn-label');

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

function showError(message) {
  errBox.classList.remove('is-visible');
  void errBox.offsetWidth; // restart the shake animation if the same error fires twice in a row
  errMsg.textContent = message;
  errBox.classList.add('is-visible');
}

function setLoading(loading) {
  submitBtn.disabled = loading;
  submitSpinner.hidden = !loading;
  submitLabel.textContent = loading ? 'Signing in…' : 'Log in';
}

function escapeHtml(str) {
  return String(str).replace(/[&<>"']/g, function (c) {
    return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c];
  });
}

// ===== Sign-in terminal =====
// Ported from the staff login (Frontend/Js/Admission/login.js): sequences
// the perceived steps around the real network call and reflects the actual
// result — it never fabricates success.
const STEP_MS = 460;

function runLoginTerminal(steps, run) {
  const terminal = document.getElementById('spLoginTerminal');
  const stage    = terminal.querySelector('.sp-login-terminal-stage');
  const stepEl   = document.getElementById('spLoginTerminalStep');
  const fillEl   = document.getElementById('spLoginTerminalFill');
  const resultEl = document.getElementById('spLoginTerminalResult');

  stage.classList.remove('is-success', 'is-error');
  resultEl.innerHTML = '';
  fillEl.style.transition = 'none';
  fillEl.style.width = '0%';
  stepEl.textContent = steps[0];
  terminal.classList.add('is-open');
  terminal.setAttribute('aria-hidden', 'false');

  requestAnimationFrame(function () {
    fillEl.style.transition = 'width ' + (steps.length * STEP_MS) + 'ms cubic-bezier(0.65, 0, 0.35, 1)';
    fillEl.style.width = '92%';
  });

  let i = 0;
  const stepTimer = setInterval(function () {
    i++;
    if (i < steps.length) stepEl.textContent = steps[i];
  }, STEP_MS);

  const minWait = new Promise(function (resolve) { setTimeout(resolve, steps.length * STEP_MS); });

  return Promise.all([
    run().catch(function () { return { success: false, error: 'Something went wrong. Please try again.' }; }),
    minWait,
  ]).then(function (results) {
    const result = results[0];
    clearInterval(stepTimer);
    fillEl.style.transition = 'width 200ms ease-out';
    fillEl.style.width = '100%';

    return new Promise(function (resolve) {
      setTimeout(function () {
        if (result && result.success) {
          stepEl.textContent = 'Welcome back';
          stage.classList.add('is-success');
        } else {
          stepEl.textContent = "Couldn't sign you in";
          stage.classList.add('is-error');
          resultEl.innerHTML = '<span>' + escapeHtml((result && result.error) || 'Unable to log in.') + '</span>' +
            '<button type="button" class="retry-btn" data-terminal-dismiss>Try again</button>';
        }
        resolve(result);
      }, 220);
    });
  });
}

function closeLoginTerminal() {
  const terminal = document.getElementById('spLoginTerminal');
  terminal.classList.remove('is-open');
  terminal.setAttribute('aria-hidden', 'true');
}

const spTerminalEl = document.getElementById('spLoginTerminal');
if (spTerminalEl) {
  spTerminalEl.addEventListener('click', function (e) {
    if (e.target.closest('[data-terminal-dismiss]')) closeLoginTerminal();
  });
}

form.addEventListener('submit', function (e) {
  e.preventDefault();
  setLoading(true);

  runLoginTerminal(
    ['Checking credentials…', 'Signing you in…'],
    function () {
      return fetch('/SIAdrafts/Backend/api/Auth/student_login.php', {
        method: 'POST',
        body: new FormData(form),
      }).then(function (res) { return res.json(); })
        .then(function (d) { return { success: !!d.success, redirect: d.redirect, tab_token: d.tab_token, error: d.error }; });
    }
  ).then(function (result) {
    setLoading(false);
    if (result && result.success) {
      try { sessionStorage.setItem('sia_tab_token', result.tab_token); } catch (err) {}
      window.setTimeout(function () { window.location.href = result.redirect; }, 500);
    } else {
      showError((result && result.error) || 'Unable to log in.');
    }
  });
});

// A genuinely live element (real ticking clock), not decorative fakery --
// this is the one screen in the app a returning user sees only once per
// session, so a little first-impression delight earns its keep here.
(function tickClock() {
  var timeEl = document.getElementById('authClock');
  var dateEl = document.getElementById('authDate');
  var progressEl = document.getElementById('authDayProgress');
  function render() {
    var now = new Date();
    timeEl.textContent = now.toLocaleTimeString([], { hour: 'numeric', minute: '2-digit' });
    dateEl.textContent = now.toLocaleDateString([], { weekday: 'long', month: 'long', day: 'numeric' });
    if (progressEl) {
      var secondsToday = now.getHours() * 3600 + now.getMinutes() * 60 + now.getSeconds();
      progressEl.style.width = ((secondsToday / 86400) * 100).toFixed(2) + '%';
    }
  }
  render();
  setInterval(render, 1000);
})();

// Cursor-follow parallax on the decorative orbs -- pure decoration, so it's
// gated to fine-pointer/hover devices and reduced-motion, and springs back
// to rest (lerp toward 0,0) on mouseleave rather than snapping.
(function orbParallax() {
  var visual = document.querySelector('.sp-auth-visual');
  if (!visual) return;
  if (!window.matchMedia('(hover: hover) and (pointer: fine)').matches) return;
  if (window.matchMedia('(prefers-reduced-motion: reduce)').matches) return;

  var orbs = visual.querySelectorAll('.sp-auth-orb');
  var target = { x: 0, y: 0 };
  var current = { x: 0, y: 0 };
  var raf = null;

  function tick() {
    current.x += (target.x - current.x) * 0.08;
    current.y += (target.y - current.y) * 0.08;
    orbs.forEach(function (orb, i) {
      var depth = i === 0 ? 22 : 14;
      orb.style.transform = 'translate3d(' + (current.x * depth).toFixed(1) + 'px,' + (current.y * depth).toFixed(1) + 'px,0)';
    });
    var settled = Math.abs(current.x - target.x) < 0.001 && Math.abs(current.y - target.y) < 0.001;
    if (!settled) {
      raf = requestAnimationFrame(tick);
    } else {
      raf = null;
      if (target.x === 0 && target.y === 0) {
        visual.classList.remove('is-tracking');
        orbs.forEach(function (orb) { orb.style.transform = ''; });
      }
    }
  }

  visual.addEventListener('mousemove', function (e) {
    var r = visual.getBoundingClientRect();
    target.x = ((e.clientX - r.left) / r.width - 0.5) * 2;
    target.y = ((e.clientY - r.top) / r.height - 0.5) * 2;
    visual.classList.add('is-tracking');
    if (!raf) raf = requestAnimationFrame(tick);
  });
  visual.addEventListener('mouseleave', function () {
    target.x = 0; target.y = 0;
    if (!raf) raf = requestAnimationFrame(tick);
  });
})();

// A subtle magnetic pull on the primary button -- decorative, small
// (max ~6px). Written as --mx/--my custom properties (not a direct
// `transform` set) so the CSS :active press-scale still composes with it
// instead of the two fighting over the same inline style.
(function magneticButton() {
  if (!window.matchMedia('(hover: hover) and (pointer: fine)').matches) return;
  if (window.matchMedia('(prefers-reduced-motion: reduce)').matches) return;
  var btn = submitBtn;
  btn.addEventListener('mousemove', function (e) {
    var r = btn.getBoundingClientRect();
    var x = (e.clientX - r.left - r.width / 2) * 0.15;
    var y = (e.clientY - r.top - r.height / 2) * 0.35;
    btn.style.setProperty('--mx', x.toFixed(1) + 'px');
    btn.style.setProperty('--my', y.toFixed(1) + 'px');
  });
  btn.addEventListener('mouseleave', function () {
    btn.style.removeProperty('--mx');
    btn.style.removeProperty('--my');
  });
})();
