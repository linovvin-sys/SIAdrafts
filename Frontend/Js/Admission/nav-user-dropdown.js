const userWrap = document.querySelector('.nav-user-wrap');
const dropdown  = document.querySelector('.nav-user-dropdown');
if (userWrap && dropdown) {
  let hideTimer;

  function showDropdown() {
    clearTimeout(hideTimer);
    dropdown.classList.add('open');
  }

  function hideDropdown() {
    hideTimer = setTimeout(() => dropdown.classList.remove('open'), 120);
  }

  userWrap.addEventListener('mouseenter', showDropdown);
  userWrap.addEventListener('mouseleave', hideDropdown);
  dropdown.addEventListener('mouseenter', showDropdown);
  dropdown.addEventListener('mouseleave', hideDropdown);
}
