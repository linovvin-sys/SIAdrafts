const loginForm = document.getElementById('login-form');
const loginBtn  = document.getElementById('login-btn');
const loginErr  = document.getElementById('login-error');

if (loginForm) {
  loginForm.addEventListener('submit', async function (e) {
    e.preventDefault();
    loginBtn.disabled  = true;
    loginBtn.innerHTML = 'Logging in… <iconify-icon icon="mdi:loading" class="spin"></iconify-icon>';
    loginErr.hidden    = true;
    try {
      const r = await fetch('/SIAdrafts/Backend/api/login.php', {
        method: 'POST',
        body:   new FormData(loginForm),
      });
      const d = await r.json();
      if (d.success) {
        window.location.href = d.redirect;
      } else {
        loginErr.textContent = d.error || 'Login failed.';
        loginErr.hidden      = false;
        loginBtn.disabled    = false;
        loginBtn.innerHTML   = 'Log in <iconify-icon icon="mdi:arrow-right"></iconify-icon>';
      }
    } catch (_) {
      loginErr.textContent = 'Connection error. Please try again.';
      loginErr.hidden      = false;
      loginBtn.disabled    = false;
      loginBtn.innerHTML   = 'Log in <iconify-icon icon="mdi:arrow-right"></iconify-icon>';
    }
  });
}
