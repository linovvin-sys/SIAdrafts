const navWrap = document.getElementById('navWrap');
if (navWrap) {
  window.addEventListener('scroll', () => {
    navWrap.classList.toggle('scrolled', window.scrollY > 24);
  });
}
