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
