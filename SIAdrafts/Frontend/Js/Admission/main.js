
    const navWrap = document.getElementById('navWrap');
    const toggle = document.getElementById('navToggle');
    const panel = document.getElementById('mobilePanel');

    window.addEventListener('scroll', () => {
      if (window.scrollY > 24) navWrap.classList.add('scrolled');
      else navWrap.classList.remove('scrolled');
    });

    toggle.addEventListener('click', () => {
      const isOpen = panel.classList.toggle('open');
      toggle.classList.toggle('open', isOpen);
      toggle.setAttribute('aria-expanded', isOpen);
    });

    


