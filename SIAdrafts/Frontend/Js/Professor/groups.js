// escHtml() is shared globally via Frontend/Js/Student/shared.js.
document.addEventListener('DOMContentLoaded', function () {
  const API = '/SIAdrafts/Backend/api/';
  const csrfToken = document.body.dataset.csrf;

  const classSelect     = document.getElementById('groupsClassSelect');
  const emptyState      = document.getElementById('groupsEmptyState');
  const panel           = document.getElementById('groupsPanel');
  const groupCountInput = document.getElementById('groupCountInput');
  const randomizeBtn    = document.getElementById('randomizeBtn');
  const rosterCountLabel= document.getElementById('rosterCountLabel');
  const groupsGrid      = document.getElementById('groupsGrid');
  const errBox          = document.getElementById('groupsFormError');
  const errMsg          = document.getElementById('groupsFormErrorMsg');
  let currentScheduleId = null;

  if (!classSelect) return;

  function showError(message) {
    errBox.classList.remove('is-visible');
    void errBox.offsetWidth;
    errMsg.textContent = message;
    errBox.classList.add('is-visible');
  }

  function renderGroups(groups) {
    if (!groups || groups.length === 0) {
      groupsGrid.innerHTML = '<p style="color:var(--slate-300); grid-column:1/-1;">No groups yet — pick a number of groups and click Randomize.</p>';
      return;
    }
    groupsGrid.innerHTML = groups.map((g) => {
      const members = g.members.length
        ? g.members.map((m) => {
            const initials = m.name.split(' ').filter(Boolean).slice(0, 2).map(p => p[0]).join('').toUpperCase();
            return '<li class="sp-group-member"><span class="sp-group-member-avatar">' + escHtml(initials) + '</span>' + escHtml(m.name) + '</li>';
          }).join('')
        : '<li class="sp-group-member-empty">No members</li>';
      return '<div class="sp-group-card">' +
        '<p class="sp-group-card-title">' + escHtml(g.group_name) + '</p>' +
        '<ul class="sp-group-member-list">' + members + '</ul>' +
      '</div>';
    }).join('');
  }

  function loadGroups(scheduleId) {
    fetch(API + 'Groups/get_groups.php?schedule_id=' + encodeURIComponent(scheduleId))
      .then(r => r.json())
      .then(d => {
        if (d.error) { showError(d.error); return; }
        rosterCountLabel.textContent = d.roster_count + ' enrolled student' + (d.roster_count === 1 ? '' : 's');
        renderGroups(d.groups);
      })
      .catch(() => showError('Could not load groups.'));
  }

  classSelect.addEventListener('change', () => {
    window.spRememberClassSelection?.(classSelect);
    currentScheduleId = classSelect.value || null;
    if (!currentScheduleId) {
      panel.hidden = true;
      emptyState.hidden = false;
      return;
    }
    panel.hidden = false;
    emptyState.hidden = true;
    errBox.classList.remove('is-visible');
    loadGroups(currentScheduleId);
  });

  randomizeBtn.addEventListener('click', () => {
    if (!currentScheduleId) return;
    const groupCount = parseInt(groupCountInput.value, 10);
    if (!groupCount || groupCount < 1) {
      showError('Enter a valid number of groups.');
      return;
    }

    randomizeBtn.disabled = true;
    randomizeBtn.querySelector('.sp-btn-spinner').hidden = false;

    const body = new FormData();
    body.append('schedule_id', currentScheduleId);
    body.append('group_count', groupCount);
    body.append('csrf_token', csrfToken);

    fetch(API + 'Groups/generate_groups.php', { method: 'POST', body })
      .then(r => r.json())
      .then(d => {
        if (d.error) { showError(d.error); return; }
        renderGroups(d.groups);
        if (window.spToast) window.spToast('Groups randomized.', 'mdi:shuffle-variant');
      })
      .catch(() => showError('Could not generate groups. Please try again.'))
      .finally(() => {
        randomizeBtn.disabled = false;
        randomizeBtn.querySelector('.sp-btn-spinner').hidden = true;
      });
  });

  if (window.spRestoreClassSelection?.(classSelect)) {
    classSelect.dispatchEvent(new Event('change'));
  }
});
