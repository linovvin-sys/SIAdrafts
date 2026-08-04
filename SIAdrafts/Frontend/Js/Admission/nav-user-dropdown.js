const userWrap = document.querySelector('.nav-user-wrap');
const trigger   = document.querySelector('.nav-user-trigger');
const dropdown  = document.querySelector('.nav-user-dropdown');

if (userWrap && trigger && dropdown) {
  let hideTimer;

  function setOpen(open) {
    clearTimeout(hideTimer);
    dropdown.classList.toggle('open', open);
    trigger.setAttribute('aria-expanded', open ? 'true' : 'false');
  }

  function showDropdown() { setOpen(true); }
  function hideDropdown() { hideTimer = setTimeout(() => setOpen(false), 120); }

  // Mouse users: hover to open, as before.
  userWrap.addEventListener('mouseenter', showDropdown);
  userWrap.addEventListener('mouseleave', hideDropdown);
  dropdown.addEventListener('mouseenter', showDropdown);
  dropdown.addEventListener('mouseleave', hideDropdown);

  // Keyboard / click users: the trigger itself toggles the menu.
  trigger.addEventListener('click', () => {
    setOpen(!dropdown.classList.contains('open'));
  });
  trigger.addEventListener('keydown', (e) => {
    if (e.key === 'Enter' || e.key === ' ') {
      e.preventDefault();
      setOpen(!dropdown.classList.contains('open'));
    }
  });

  document.addEventListener('keydown', (e) => {
    if (e.key === 'Escape' && dropdown.classList.contains('open')) {
      setOpen(false);
      trigger.focus();
    }
  });

  document.addEventListener('click', (e) => {
    if (!userWrap.contains(e.target)) setOpen(false);
  });
}
