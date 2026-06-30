(function () {
  const sidEl   = document.querySelector('[name="student_id"]');
  const syEl    = document.getElementById('field-sy');
  const semEl   = document.getElementById('field-sem');
  const warn    = document.getElementById('dup-warning');
  const warnMsg = document.getElementById('dup-msg');
  if (!sidEl || !syEl || !semEl) return;

  let timer;
  function check() {
    clearTimeout(timer);
    const sid = sidEl.value;
    const sy  = syEl.value.trim();
    const sem = semEl.value;
    if (!sid || !/^\d{4}-\d{4}$/.test(sy) || !sem) { warn.hidden = true; return; }
    timer = setTimeout(async () => {
      try {
        const r = await fetch(
          `/SIAdrafts/Backend/api/check_enrollment.php??student_id=${encodeURIComponent(sid)}&school_year=${encodeURIComponent(sy)}&semester=${encodeURIComponent(sem)}`
        );
        const d = await r.json();
        if (d.exists) {
          warnMsg.textContent = `This student is already enrolled for ${sy} — Semester ${sem} (Enrollment #${d.enrollment_id}).`;
          warn.hidden = false;
        } else {
          warn.hidden = true;
        }
      } catch (_) { warn.hidden = true; }
    }, 450);
  }

  syEl.addEventListener('input',  check);
  semEl.addEventListener('change', check);
  check();
})();
