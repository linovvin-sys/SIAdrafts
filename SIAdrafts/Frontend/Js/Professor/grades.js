// escHtml() is shared globally via Frontend/Js/Student/shared.js, loaded
// on every Professor page before this file (see Include/footer.php).
document.addEventListener('DOMContentLoaded', function () {

  const API = '/SIAdrafts/Backend/api/';
  const csrfToken = document.body.dataset.csrf;

  const classSelect     = document.getElementById('gradesClassSelect');
  const periodSelect    = document.getElementById('gradesPeriodSelect');
  const emptyState      = document.getElementById('gradesEmptyState');
  const panel           = document.getElementById('gradesPanel');
  const fixedScheduleId = document.body.dataset.scheduleId || null;
  const gradesBody      = document.getElementById('gradesTableBody');
  const errBox          = document.getElementById('gradesFormError');
  const errMsg          = document.getElementById('gradesFormErrorMsg');
  const successBox      = document.getElementById('gradesFormSuccess');
  const successMsg      = document.getElementById('gradesFormSuccessMsg');
  const saveGradesBtn   = document.getElementById('saveGradesBtn');

  if (!classSelect && !fixedScheduleId) return;

  let currentScheduleId = null;

  function hideNotices() {
    errBox.classList.remove('is-visible');
    successBox.classList.remove('is-visible');
  }

  function showError(message) {
    successBox.classList.remove('is-visible');
    errBox.classList.remove('is-visible');
    void errBox.offsetWidth;
    errMsg.textContent = message;
    errBox.classList.add('is-visible');
  }

  function showSuccess(message) {
    errBox.classList.remove('is-visible');
    successBox.classList.remove('is-visible');
    void successBox.offsetWidth;
    successMsg.textContent = message;
    successBox.classList.add('is-visible');
  }

  function renderGrades(students) {
    if (!students || students.length === 0) {
      gradesBody.innerHTML = '<tr><td colspan="4" style="text-align:center;">No students enrolled yet.</td></tr>';
      return;
    }
    gradesBody.innerHTML = students.map((s, i) => {
      const name = [s.last_name, s.first_name].filter(Boolean).join(', ') +
        (s.middle_name ? ' ' + s.middle_name.charAt(0) + '.' : '');
      return '<tr data-enrollment-subject-id="' + s.enrollment_subject_id + '" style="--row-i:' + i + '">' +
        '<td class="sp-num">' + escHtml(s.student_no) + '</td>' +
        '<td>' + escHtml(name) + '</td>' +
        '<td><input type="text" class="sp-table-input sp-grade-input" data-grade-input maxlength="10" placeholder="e.g. 95" value="' + escHtml(s.grade_value || '') + '"><span class="sp-remarks-hint" data-pass-hint hidden></span></td>' +
        '<td><input type="text" class="sp-table-input" data-remarks-input maxlength="255" placeholder="Optional remarks…" value="' + escHtml(s.remarks || '') + '"></td>' +
        '</tr>';
    }).join('');
    gradesBody.querySelectorAll('[data-grade-input]').forEach(updatePassHint);
  }

  // 75 is the standard CHED/DepEd passing cutoff on a 100-point scale --
  // no pass/fail convention existed anywhere in this codebase before, so
  // this hint is purely a visual aid for the professor and never touches
  // what actually gets saved (save_grades.php stores grade_value as-is).
  function updatePassHint(input) {
    const hint = input.nextElementSibling;
    if (!hint || !hint.hasAttribute('data-pass-hint')) return;
    const val = parseFloat(input.value);
    if (isNaN(val)) { hint.hidden = true; return; }
    hint.hidden = false;
    hint.textContent = val >= 75 ? 'Pass' : 'Fail';
    hint.classList.toggle('is-pass', val >= 75);
    hint.classList.toggle('is-fail', val < 75);
  }

  gradesBody.addEventListener('input', (e) => {
    if (e.target.matches('[data-grade-input]')) updatePassHint(e.target);
  });

  async function loadGrades(scheduleId, period) {
    gradesBody.innerHTML = '<tr><td colspan="4" style="text-align:center;"><span class="sp-loading-dots"><span></span><span></span><span></span></span></td></tr>';
    try {
      const params = new URLSearchParams({ schedule_id: scheduleId, period: period });
      const res = await fetch(API + 'Professors/get_class_grades.php?' + params.toString());
      const data = await res.json();
      if (data.error) {
        gradesBody.innerHTML = '<tr><td colspan="4" style="text-align:center;">' + escHtml(data.error) + '</td></tr>';
        return;
      }
      renderGrades(data.students);
    } catch (err) {
      gradesBody.innerHTML = '<tr><td colspan="4" style="text-align:center;">Could not load grades.</td></tr>';
    }
  }

  // Grades is the one per-class tool page with a second dimension of state
  // (grading period) beyond just the class -- remembered the same way
  // spRestoreClassSelection/spRememberClassSelection persist the class
  // dropdown, so returning to this page doesn't reset it to Prelim.
  const LAST_PERIOD_KEY = 'sp_professor_last_grading_period';
  try {
    const lastPeriod = localStorage.getItem(LAST_PERIOD_KEY);
    if (lastPeriod && periodSelect?.querySelector('option[value="' + lastPeriod + '"]')) {
      periodSelect.value = lastPeriod;
    }
  } catch (e) {}

  function initForClass(scheduleId) {
    currentScheduleId = scheduleId;
    if (panel) panel.hidden = false;
    if (emptyState) emptyState.hidden = true;
    hideNotices();
    loadGrades(currentScheduleId, periodSelect.value);
  }

  classSelect?.addEventListener('change', () => {
    window.spRememberClassSelection?.(classSelect);
    const scheduleId = classSelect.value || null;
    if (!scheduleId) {
      if (panel) panel.hidden = true;
      if (emptyState) emptyState.hidden = false;
      currentScheduleId = null;
      return;
    }
    initForClass(scheduleId);
  });

  periodSelect?.addEventListener('change', () => {
    try { localStorage.setItem(LAST_PERIOD_KEY, periodSelect.value); } catch (e) {}
    if (!currentScheduleId) return;
    hideNotices();
    loadGrades(currentScheduleId, periodSelect.value);
  });

  saveGradesBtn?.addEventListener('click', async () => {
    if (!currentScheduleId) return;

    const rows = Array.from(gradesBody.querySelectorAll('tr[data-enrollment-subject-id]'));
    const grades = rows.map(row => ({
      enrollment_subject_id: row.getAttribute('data-enrollment-subject-id'),
      grade_value: row.querySelector('[data-grade-input]')?.value.trim() || '',
      remarks: row.querySelector('[data-remarks-input]')?.value.trim() || '',
    })).filter(g => g.grade_value !== '');

    if (grades.length === 0) {
      showError('Enter at least one grade before saving.');
      return;
    }

    saveGradesBtn.disabled = true;
    saveGradesBtn.querySelector('.sp-btn-spinner').hidden = false;
    saveGradesBtn.querySelector('.sp-btn-label').textContent = 'Saving…';

    const body = new FormData();
    body.append('schedule_id', currentScheduleId);
    body.append('period', periodSelect.value);
    body.append('grades', JSON.stringify(grades));
    body.append('csrf_token', csrfToken);

    try {
      const res = await fetch(API + 'Professors/save_grades.php', { method: 'POST', body });
      const data = await res.json();
      if (data.students) renderGrades(data.students);
      if (data.error) {
        showError(data.error);
        return;
      }
      showSuccess(periodSelect.value + ' grades saved.');
    } catch (err) {
      showError('Something went wrong. Please try again.');
    } finally {
      saveGradesBtn.disabled = false;
      saveGradesBtn.querySelector('.sp-btn-spinner').hidden = true;
      saveGradesBtn.querySelector('.sp-btn-label').textContent = 'Save grades';
    }
  });

  if (classSelect) {
    if (window.spRestoreClassSelection?.(classSelect)) {
      classSelect.dispatchEvent(new Event('change'));
    }
  } else if (fixedScheduleId) {
    initForClass(fixedScheduleId);
  }

});
