function escHtml(s) {
  return String(s ?? '').replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;');
}

document.addEventListener('DOMContentLoaded', function () {

  const API = '/SIAdrafts/Backend/api/';

  // ----- Search (plain show/hide across section groups + their rows) -----
  const searchEl = document.getElementById('classesSearch');
  const list = document.getElementById('classesList');
  if (searchEl && list) {
    searchEl.addEventListener('input', () => {
      const q = searchEl.value.trim().toLowerCase();
      list.querySelectorAll('.sp-class-group').forEach(group => {
        const groupMatches = !q || (group.dataset.search || '').includes(q);
        let anyRowMatches = false;
        group.querySelectorAll('.sp-class-row').forEach(row => {
          const rowMatches = !q || groupMatches || (row.dataset.search || '').includes(q);
          row.style.display = rowMatches ? '' : 'none';
          if (rowMatches) anyRowMatches = true;
        });
        group.style.display = (groupMatches || anyRowMatches) ? '' : 'none';
      });
    });
  }

  // ----- Roster dialog (native <dialog>, same open/close mechanics as the shared spConfirm) -----
  const rosterDialog   = document.getElementById('rosterDialog');
  const rosterSubtitle = document.getElementById('rosterSubtitle');
  const rosterBody     = document.getElementById('rosterTableBody');
  let lastRosterStudents = [];

  function openRoster() {
    if (typeof rosterDialog.showModal === 'function') rosterDialog.showModal();
  }
  function closeRoster() {
    rosterDialog.close();
  }

  document.getElementById('closeRosterDialog')?.addEventListener('click', closeRoster);
  rosterDialog?.addEventListener('click', (e) => {
    if (e.target === rosterDialog) closeRoster();
  });

  function renderRoster(students) {
    lastRosterStudents = students || [];
    if (lastRosterStudents.length === 0) {
      rosterBody.innerHTML = '<tr><td colspan="2" style="text-align:center;">No students enrolled yet.</td></tr>';
      return;
    }
    rosterBody.innerHTML = lastRosterStudents.map(s => {
      const name = [s.last_name, s.first_name].filter(Boolean).join(', ') +
        (s.middle_name ? ' ' + s.middle_name.charAt(0) + '.' : '');
      return '<tr>' +
        '<td class="sp-num">' + escHtml(s.student_no) + '</td>' +
        '<td>' + escHtml(name) + '</td>' +
        '</tr>';
    }).join('');
  }

  // ----- Per-class roster -----
  document.querySelectorAll('[data-view-roster]').forEach(btn => {
    btn.addEventListener('click', async () => {
      const scheduleId = btn.getAttribute('data-view-roster');
      const row = btn.closest('tr');
      const subjectLabel = row ? row.querySelector('.sp-class-subject')?.textContent.trim() : '';

      rosterSubtitle.textContent = subjectLabel || 'Class roster';
      rosterBody.innerHTML = '<tr><td colspan="2" style="text-align:center;">Loading…</td></tr>';
      openRoster();

      try {
        const res = await fetch(API + 'Professors/get_professor_roster.php?schedule_id=' + encodeURIComponent(scheduleId));
        const data = await res.json();
        if (data.error) {
          rosterBody.innerHTML = '<tr><td colspan="2" style="text-align:center;">' + escHtml(data.error) + '</td></tr>';
          return;
        }
        renderRoster(data.students);
      } catch (err) {
        rosterBody.innerHTML = '<tr><td colspan="2" style="text-align:center;">Could not load roster.</td></tr>';
      }
    });
  });

  // ----- Section master list -----
  document.querySelectorAll('[data-view-section-roster]').forEach(btn => {
    btn.addEventListener('click', async () => {
      const sectionId   = btn.getAttribute('data-view-section-roster');
      const schoolYear  = btn.getAttribute('data-school-year');
      const semester    = btn.getAttribute('data-semester');
      const sectionName = btn.getAttribute('data-section-name');

      rosterSubtitle.textContent = sectionName + ' — Master List (' + schoolYear + ', Sem ' + semester + ')';
      rosterBody.innerHTML = '<tr><td colspan="2" style="text-align:center;">Loading…</td></tr>';
      openRoster();

      try {
        const params = new URLSearchParams({ section_id: sectionId, school_year: schoolYear, semester: semester });
        const res = await fetch(API + 'Professors/get_professor_section_roster.php?' + params.toString());
        const data = await res.json();
        if (data.error) {
          rosterBody.innerHTML = '<tr><td colspan="2" style="text-align:center;">' + escHtml(data.error) + '</td></tr>';
          return;
        }
        renderRoster(data.students);
      } catch (err) {
        rosterBody.innerHTML = '<tr><td colspan="2" style="text-align:center;">Could not load roster.</td></tr>';
      }
    });
  });

  // ----- CSV export (client-side only, from the roster already on screen) -----
  const exportBtn = document.getElementById('exportRosterCsv');
  if (exportBtn) {
    exportBtn.addEventListener('click', () => {
      if (!lastRosterStudents.length) return;

      function csvField(v) {
        const s = String(v ?? '');
        return /[",\n]/.test(s) ? '"' + s.replace(/"/g, '""') + '"' : s;
      }

      const header = ['Student No.', 'Last Name', 'First Name', 'Middle Name', 'Email', 'Contact'];
      const lines = [header.join(',')];
      lastRosterStudents.forEach(s => {
        lines.push([
          csvField(s.student_no), csvField(s.last_name), csvField(s.first_name),
          csvField(s.middle_name), csvField(s.email), csvField(s.contact_number),
        ].join(','));
      });

      const blob = new Blob([lines.join('\n')], { type: 'text/csv;charset=utf-8;' });
      const url  = URL.createObjectURL(blob);
      const a    = document.createElement('a');
      a.href = url;
      a.download = 'roster.csv';
      document.body.appendChild(a);
      a.click();
      document.body.removeChild(a);
      URL.revokeObjectURL(url);
    });
  }

  // ----- Class announcements -----
  const csrfToken       = document.body.dataset.csrf;
  const announceDialog  = document.getElementById('announceDialog');
  const announceSub     = document.getElementById('announceSubtitle');
  const announceList    = document.getElementById('announceList');
  const announceForm    = document.getElementById('announceForm');
  const announceTitleEl = document.getElementById('announceTitleInput');
  const announceBodyEl  = document.getElementById('announceBodyInput');
  const announceErrBox  = document.getElementById('announceFormError');
  const announceErrMsg  = document.getElementById('announceFormErrorMsg');
  const announcePostBtn = document.getElementById('announcePostBtn');
  let currentScheduleId = null;

  function openAnnounce() {
    if (typeof announceDialog.showModal === 'function') announceDialog.showModal();
  }
  function closeAnnounce() { announceDialog.close(); }
  document.getElementById('closeAnnounceDialog')?.addEventListener('click', closeAnnounce);
  announceDialog?.addEventListener('click', (e) => {
    if (e.target === announceDialog) closeAnnounce();
  });

  function showAnnounceError(message) {
    announceErrBox.classList.remove('is-visible');
    void announceErrBox.offsetWidth;
    announceErrMsg.textContent = message;
    announceErrBox.classList.add('is-visible');
  }

  function renderAnnouncements(items) {
    if (!items || items.length === 0) {
      announceList.innerHTML = '<li class="sp-announce-empty">No announcements posted for this class yet.</li>';
      return;
    }
    announceList.innerHTML = items.map((a) => {
      const when = new Date(a.created_at.replace(' ', 'T')).toLocaleDateString([], { month: 'short', day: 'numeric' });
      return '<li class="sp-announce-item" data-announcement-id="' + a.announcement_id + '">' +
        '<div class="sp-announce-item-head">' +
          '<strong>' + escHtml(a.title) + '</strong>' +
          '<span class="sp-announce-item-date">' + when + '</span>' +
          '<button type="button" class="sp-announce-delete" data-delete-announcement="' + a.announcement_id + '" aria-label="Delete announcement">' +
            '<iconify-icon icon="mdi:trash-can-outline"></iconify-icon>' +
          '</button>' +
        '</div>' +
        '<p>' + escHtml(a.body).replace(/\n/g, '<br>') + '</p>' +
      '</li>';
    }).join('');
  }

  async function loadAnnouncements(scheduleId) {
    announceList.innerHTML = '<li class="sp-announce-empty">Loading…</li>';
    try {
      const res = await fetch(API + 'Announcements/get_class_announcements.php?schedule_id=' + encodeURIComponent(scheduleId));
      const data = await res.json();
      if (data.error) {
        announceList.innerHTML = '<li class="sp-announce-empty">' + escHtml(data.error) + '</li>';
        return;
      }
      renderAnnouncements(data.announcements);
    } catch (err) {
      announceList.innerHTML = '<li class="sp-announce-empty">Could not load announcements.</li>';
    }
  }

  document.querySelectorAll('[data-announce]').forEach(btn => {
    btn.addEventListener('click', () => {
      currentScheduleId = btn.getAttribute('data-announce');
      const row = btn.closest('tr');
      const subjectLabel = row ? row.querySelector('.sp-class-subject')?.textContent.trim() : '';
      announceSub.textContent = subjectLabel || 'Class announcements';
      announceForm.reset();
      announceErrBox.classList.remove('is-visible');
      openAnnounce();
      loadAnnouncements(currentScheduleId);
    });
  });

  announceForm?.addEventListener('submit', async (e) => {
    e.preventDefault();
    if (!currentScheduleId) return;

    announcePostBtn.disabled = true;
    announcePostBtn.querySelector('.sp-btn-spinner').hidden = false;
    announcePostBtn.querySelector('.sp-btn-label').textContent = 'Posting…';

    const body = new FormData();
    body.append('schedule_id', currentScheduleId);
    body.append('title', announceTitleEl.value);
    body.append('body', announceBodyEl.value);
    body.append('csrf_token', csrfToken);

    try {
      const res = await fetch(API + 'Announcements/post_announcement.php', { method: 'POST', body });
      const data = await res.json();
      if (data.error) {
        showAnnounceError(data.error);
        return;
      }
      renderAnnouncements(data.announcements);
      announceForm.reset();
      if (window.spToast) window.spToast('Announcement posted.', 'mdi:bullhorn-outline');
    } catch (err) {
      showAnnounceError('Something went wrong. Please try again.');
    } finally {
      announcePostBtn.disabled = false;
      announcePostBtn.querySelector('.sp-btn-spinner').hidden = true;
      announcePostBtn.querySelector('.sp-btn-label').textContent = 'Post announcement';
    }
  });

  announceList?.addEventListener('click', async (e) => {
    const delBtn = e.target.closest('[data-delete-announcement]');
    if (!delBtn) return;
    const announcementId = delBtn.getAttribute('data-delete-announcement');

    const confirmed = window.spConfirm
      ? await window.spConfirm('This announcement will be removed for every student in this class.', { title: 'Delete this announcement?', confirmLabel: 'Delete' })
      : window.confirm('Delete this announcement?');
    if (!confirmed) return;

    const body = new FormData();
    body.append('announcement_id', announcementId);
    body.append('csrf_token', csrfToken);

    try {
      const res = await fetch(API + 'Announcements/delete_announcement.php', { method: 'POST', body });
      const data = await res.json();
      if (data.error) {
        if (window.spToast) window.spToast(data.error, 'mdi:alert-circle-outline');
        return;
      }
      loadAnnouncements(currentScheduleId);
      if (window.spToast) window.spToast('Announcement deleted.', 'mdi:trash-can-outline');
    } catch (err) {
      if (window.spToast) window.spToast('Could not delete announcement.', 'mdi:alert-circle-outline');
    }
  });

});
