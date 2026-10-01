document.addEventListener('DOMContentLoaded', function () {
  const API = '/SIAdrafts/Backend/api/Attendance/';
  const csrfToken = document.body.dataset.csrf;

  const classSelect  = document.getElementById('attendanceClassSelect');
  const emptyState   = document.getElementById('attendanceEmptyState');
  const panel        = document.getElementById('attendancePanel');
  const fixedScheduleId = document.body.dataset.scheduleId || null;
  const dateInput    = document.getElementById('attendanceDateInput');
  const rosterBody   = document.getElementById('attendanceRosterBody');
  const saveBtn      = document.getElementById('saveAttendanceBtn');
  const markAllBtn   = document.getElementById('markAllPresentBtn');
  const errBox       = document.getElementById('attendanceFormError');
  const errMsg       = document.getElementById('attendanceFormErrorMsg');
  let currentScheduleId = null;

  if (!classSelect && !fixedScheduleId) return;

  const STATUSES = ['Present', 'Absent', 'Late', 'Excused'];

  function todayLocal() {
    const d = new Date();
    const off = d.getTimezoneOffset();
    return new Date(d.getTime() - off * 60000).toISOString().slice(0, 10);
  }
  dateInput.value = todayLocal();

  function showError(message) {
    errBox.classList.remove('is-visible');
    void errBox.offsetWidth;
    errMsg.textContent = message;
    errBox.classList.add('is-visible');
  }

  function renderRoster(roster) {
    if (!roster || roster.length === 0) {
      rosterBody.innerHTML = '<tr><td colspan="2">No enrolled students for this class.</td></tr>';
      return;
    }
    rosterBody.innerHTML = roster.map((r, i) => {
      const name = escHtml(r.first_name + ' ' + r.last_name);
      const current = r.status || 'Present';
      const pills = STATUSES.map(s =>
        '<button type="button" class="sp-status-pill" data-status="' + s + '" aria-pressed="' + (current === s ? 'true' : 'false') + '">' + s + '</button>'
      ).join('');
      return '<tr data-applicant-id="' + r.applicant_id + '" style="--row-i:' + i + '">' +
        '<td>' + name + '</td>' +
        '<td><div class="sp-status-pills" data-status-group>' + pills + '</div></td>' +
      '</tr>';
    }).join('');
  }

  rosterBody.addEventListener('click', (e) => {
    const btn = e.target.closest('.sp-status-pill');
    if (!btn) return;
    btn.parentElement.querySelectorAll('.sp-status-pill').forEach(p => p.setAttribute('aria-pressed', 'false'));
    btn.setAttribute('aria-pressed', 'true');
  });

  function loadAttendance() {
    if (!currentScheduleId || !dateInput.value) return;
    fetch(API + 'get_attendance.php?schedule_id=' + encodeURIComponent(currentScheduleId) + '&session_date=' + encodeURIComponent(dateInput.value))
      .then(r => r.json())
      .then(d => {
        if (d.error) { showError(d.error); return; }
        renderRoster(d.roster);
      })
      .catch(() => showError('Could not load attendance.'));
  }

  function initForClass(scheduleId) {
    currentScheduleId = scheduleId;
    if (panel) panel.hidden = false;
    if (emptyState) emptyState.hidden = true;
    errBox.classList.remove('is-visible');
    loadAttendance();
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

  dateInput.addEventListener('change', loadAttendance);

  markAllBtn.addEventListener('click', () => {
    rosterBody.querySelectorAll('[data-status-group]').forEach(group => {
      group.querySelectorAll('.sp-status-pill').forEach(p => p.setAttribute('aria-pressed', p.dataset.status === 'Present' ? 'true' : 'false'));
    });
  });

  saveBtn.addEventListener('click', () => {
    if (!currentScheduleId || !dateInput.value) return;

    const statuses = {};
    rosterBody.querySelectorAll('tr[data-applicant-id]').forEach(tr => {
      const id = tr.dataset.applicantId;
      const pressed = tr.querySelector('.sp-status-pill[aria-pressed="true"]');
      if (pressed) statuses[id] = pressed.dataset.status;
    });
    if (Object.keys(statuses).length === 0) return;

    saveBtn.disabled = true;
    saveBtn.querySelector('.sp-btn-spinner').hidden = false;

    const body = new FormData();
    body.append('schedule_id', currentScheduleId);
    body.append('session_date', dateInput.value);
    body.append('statuses', JSON.stringify(statuses));
    body.append('csrf_token', csrfToken);

    fetch(API + 'save_attendance.php', { method: 'POST', body })
      .then(r => r.json())
      .then(d => {
        if (d.error) { showError(d.error); return; }
        renderRoster(d.roster);
        if (window.spToast) window.spToast('Attendance saved.', 'mdi:clipboard-check-outline');
      })
      .catch(() => showError('Could not save attendance. Please try again.'))
      .finally(() => {
        saveBtn.disabled = false;
        saveBtn.querySelector('.sp-btn-spinner').hidden = true;
      });
  });

  if (classSelect) {
    if (window.spRestoreClassSelection?.(classSelect)) {
      classSelect.dispatchEvent(new Event('change'));
    }
  } else if (fixedScheduleId) {
    initForClass(fixedScheduleId);
  }
});
