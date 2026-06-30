document.addEventListener('DOMContentLoaded', () => {

  /* ===== SIDEBAR TOGGLE (click brand logo) ===== */
  const sidebar     = document.querySelector('.sidebar');
  const mainContent = document.querySelector('.main-content');
  const brand       = document.querySelector('.sidebar-brand');

  if (brand && sidebar && mainContent) {
    brand.addEventListener('click', () => {
      sidebar.classList.toggle('collapsed');
      mainContent.style.marginLeft = sidebar.classList.contains('collapsed')
        ? '72px'
        : 'var(--sidebar-width)';
    });
  }

  /* ===== NAV ACTIVE STATE ===== */
  document.querySelectorAll('.nav-item').forEach(item => {
    item.addEventListener('click', () => {
      document.querySelectorAll('.nav-item').forEach(i => i.classList.remove('active'));
      item.classList.add('active');
    });
  });

  /* ===== AVATAR DROPDOWN ===== */
  const avatarBtn      = document.getElementById('avatarBtn');
  const avatarDropdown = document.getElementById('avatarDropdown');
  const avatarWrapper  = document.getElementById('avatarWrapper');

  if (avatarBtn && avatarDropdown) {
    avatarBtn.addEventListener('click', (e) => {
      e.stopPropagation();
      avatarDropdown.classList.toggle('open');
    });

    document.addEventListener('click', (e) => {
      if (!avatarWrapper.contains(e.target)) {
        avatarDropdown.classList.remove('open');
      }
    });
  }

  /* ===== ADD USER MODAL ===== */
  const modal           = document.getElementById('addUserModal');
  const openBtn         = document.getElementById('openAddUserModal');
  const closeBtn        = document.getElementById('closeAddUserModal');
  const cancelBtn       = document.getElementById('cancelAddUser');
  const submitBtn       = document.getElementById('submitAddUser');
  const pwToggleBtn     = document.getElementById('togglePassword');
  const pwField         = document.getElementById('userPassword');

  if (!modal) return; // modal not on this page, bail early

  /* -- Open -- */
  if (openBtn) {
    openBtn.addEventListener('click', () => {
      modal.classList.add('active');
      document.body.style.overflow = 'hidden';
    });
  }

  /* -- Close -- */
  function closeModal() {
    modal.classList.remove('active');
    document.body.style.overflow = '';
    resetForm();
  }

  if (closeBtn)  closeBtn.addEventListener('click', closeModal);
  if (cancelBtn) cancelBtn.addEventListener('click', closeModal);

  // Click outside modal box
  modal.addEventListener('click', (e) => {
    if (e.target === modal) closeModal();
  });

  // ESC key
  document.addEventListener('keydown', (e) => {
    if (e.key === 'Escape' && modal.classList.contains('active')) closeModal();
  });

  /* -- Password toggle -- */
  if (pwToggleBtn && pwField) {
    pwToggleBtn.addEventListener('click', () => {
      const show = pwField.type === 'password';
      pwField.type = show ? 'text' : 'password';
      pwToggleBtn.textContent = show ? '🙈' : '👁';
    });
  }

  /* -- Reset -- */
  function resetForm() {
    ['firstName','lastName','userEmail','userRole','userDept','userPassword'].forEach(id => {
      const el = document.getElementById(id);
      if (el) el.value = '';
    });
    const activeToggle = document.getElementById('userActive');
    if (activeToggle) activeToggle.checked = true;
    if (pwField) pwField.type = 'password';
    if (pwToggleBtn) pwToggleBtn.textContent = '👁';
    clearErrors();
  }

  /* -- Validation -- */
  function showError(inputId, message) {
    const input = document.getElementById(inputId);
    if (!input) return;
    input.style.borderColor = '#e03a3a';
    input.style.boxShadow   = '0 0 0 3px rgba(224,58,58,0.10)';
    const old = input.parentElement.querySelector('.error-msg');
    if (old) old.remove();
    const msg = document.createElement('span');
    msg.className   = 'error-msg';
    msg.textContent = message;
    input.parentElement.appendChild(msg);
  }

  function clearErrors() {
    document.querySelectorAll('.form-input').forEach(el => {
      el.style.borderColor = '';
      el.style.boxShadow   = '';
    });
    document.querySelectorAll('.error-msg').forEach(el => el.remove());
  }

  function validateForm() {
    clearErrors();
    let valid = true;
    const checks = [
      { id: 'firstName',    msg: 'First name is required.' },
      { id: 'lastName',     msg: 'Last name is required.' },
      { id: 'userEmail',    msg: 'Email is required.' },
      { id: 'userRole',     msg: 'Please select a role.' },
      { id: 'userPassword', msg: 'Password is required.' },
    ];
    checks.forEach(({ id, msg }) => {
      const el = document.getElementById(id);
      if (!el || !el.value.trim()) { showError(id, msg); valid = false; }
    });
    const email = document.getElementById('userEmail');
    if (email && email.value.trim() && !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email.value.trim())) {
      showError('userEmail', 'Enter a valid email address.');
      valid = false;
    }
    const pw = document.getElementById('userPassword');
    if (pw && pw.value && pw.value.length < 8) {
      showError('userPassword', 'Password must be at least 8 characters.');
      valid = false;
    }
    return valid;
  }

  /* -- Submit -- */
  if (submitBtn) {
    submitBtn.addEventListener('click', () => {
      if (!validateForm()) return;

      /* TODO: swap with fetch() / form POST to your PHP backend */
      const roleEl = document.getElementById('userRole');
      const newUser = {
        firstName : document.getElementById('firstName').value.trim(),
        lastName  : document.getElementById('lastName').value.trim(),
        email     : document.getElementById('userEmail').value.trim(),
        role      : roleEl.options[roleEl.selectedIndex].text,
        department: document.getElementById('userDept').value,
        active    : document.getElementById('userActive').checked,
      };
      console.log('New user payload:', newUser);
      /* -------------------------------------------------- */

      closeModal();
      showToast(`User "${newUser.firstName} ${newUser.lastName}" added successfully!`);
    });
  }

  /* ===== TOAST ===== */
  function showToast(message) {
    let toast = document.getElementById('toast-msg');
    if (!toast) {
      toast = document.createElement('div');
      toast.id = 'toast-msg';
      Object.assign(toast.style, {
        position: 'fixed', bottom: '28px', right: '28px',
        background: 'var(--navy)', color: '#fff',
        padding: '12px 20px', borderRadius: '8px',
        fontSize: '13px', fontWeight: '600',
        boxShadow: '0 6px 24px rgba(0,0,0,0.20)',
        borderLeft: '4px solid var(--gold)',
        zIndex: '999', opacity: '0',
        transform: 'translateY(8px)',
        transition: 'opacity 0.25s ease, transform 0.25s ease',
      });
      document.body.appendChild(toast);
    }
    toast.textContent = message;
    requestAnimationFrame(() => {
      toast.style.opacity   = '1';
      toast.style.transform = 'translateY(0)';
    });
    setTimeout(() => {
      toast.style.opacity   = '0';
      toast.style.transform = 'translateY(8px)';
    }, 3000);
  }

}); // end DOMContentLoaded