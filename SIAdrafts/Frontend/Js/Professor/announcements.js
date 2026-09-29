// escHtml() is shared globally via Frontend/Js/Student/shared.js, loaded
// on every Professor page before this file (see Include/footer.php).
document.addEventListener('DOMContentLoaded', function () {

  const API = '/SIAdrafts/Backend/api/';
  const csrfToken = document.body.dataset.csrf;

  const classSelect   = document.getElementById('announceClassSelect');
  const emptyState    = document.getElementById('announceEmptyState');
  const panel         = document.getElementById('announcePanel');
  const announceList  = document.getElementById('announceList');
  const announceForm  = document.getElementById('announceForm');
  const titleEl       = document.getElementById('announceTitleInput');
  const bodyEl        = document.getElementById('announceBodyInput');
  const errBox        = document.getElementById('announceFormError');
  const errMsg        = document.getElementById('announceFormErrorMsg');
  const postBtn       = document.getElementById('announcePostBtn');
  let currentScheduleId = null;
  let editingAnnouncementId = null;

  if (!classSelect) return;

  function showError(message) {
    errBox.classList.remove('is-visible');
    void errBox.offsetWidth;
    errMsg.textContent = message;
    errBox.classList.add('is-visible');
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
          '<button type="button" class="sp-announce-edit" data-edit-announcement="' + a.announcement_id + '" data-title="' + escHtml(a.title) + '" data-body="' + escHtml(a.body) + '" aria-label="Edit announcement">' +
            '<iconify-icon icon="mdi:pencil-outline"></iconify-icon>' +
          '</button>' +
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
    cancelEdit();
    errBox.classList.remove('is-visible');
    loadAnnouncements(currentScheduleId);
  });

  function cancelEdit() {
    editingAnnouncementId = null;
    announceForm.reset();
    postBtn.querySelector('.sp-btn-label').textContent = 'Post announcement';
  }

  announceForm?.addEventListener('submit', async (e) => {
    e.preventDefault();
    if (!currentScheduleId) return;

    const isEdit = editingAnnouncementId !== null;
    postBtn.disabled = true;
    postBtn.querySelector('.sp-btn-spinner').hidden = false;
    postBtn.querySelector('.sp-btn-label').textContent = isEdit ? 'Saving…' : 'Posting…';

    const body = new FormData();
    if (isEdit) {
      body.append('announcement_id', editingAnnouncementId);
    } else {
      body.append('schedule_id', currentScheduleId);
    }
    body.append('title', titleEl.value);
    body.append('body', bodyEl.value);
    body.append('csrf_token', csrfToken);

    try {
      const endpoint = isEdit ? 'Announcements/update_announcement.php' : 'Announcements/post_announcement.php';
      const res = await fetch(API + endpoint, { method: 'POST', body });
      const data = await res.json();
      if (data.error) {
        showError(data.error);
        return;
      }
      renderAnnouncements(data.announcements);
      cancelEdit();
      if (window.spToast) window.spToast(isEdit ? 'Announcement updated.' : 'Announcement posted.', 'mdi:bullhorn-outline');
    } catch (err) {
      showError('Something went wrong. Please try again.');
    } finally {
      postBtn.disabled = false;
      postBtn.querySelector('.sp-btn-spinner').hidden = true;
      postBtn.querySelector('.sp-btn-label').textContent = editingAnnouncementId !== null ? 'Save changes' : 'Post announcement';
    }
  });

  announceList?.addEventListener('click', async (e) => {
    const editBtn = e.target.closest('[data-edit-announcement]');
    if (editBtn) {
      editingAnnouncementId = editBtn.getAttribute('data-edit-announcement');
      titleEl.value = editBtn.getAttribute('data-title') || '';
      bodyEl.value = editBtn.getAttribute('data-body') || '';
      postBtn.querySelector('.sp-btn-label').textContent = 'Save changes';
      errBox.classList.remove('is-visible');
      titleEl.focus();
      return;
    }

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
      if (String(editingAnnouncementId) === String(announcementId)) cancelEdit();
      loadAnnouncements(currentScheduleId);
      if (window.spToast) window.spToast('Announcement deleted.', 'mdi:trash-can-outline');
    } catch (err) {
      if (window.spToast) window.spToast('Could not delete announcement.', 'mdi:alert-circle-outline');
    }
  });

  if (window.spRestoreClassSelection?.(classSelect)) {
    classSelect.dispatchEvent(new Event('change'));
  }

});
