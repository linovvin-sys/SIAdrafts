// Shared across every student AND professor page (loaded via both portals'
// Include/footer.php) so any page can call spToast(...)/spConfirm(...)/
// escHtml(...) from a click handler without its own copy. Only ever
// invoked from event listeners, which run after this parses, so load
// order relative to page-specific scripts doesn't matter.
window.escHtml = function (s) {
  return String(s == null ? '' : s).replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;');
};

// Persists which class is selected in the Professor portal's per-class
// tool pages (Announcements/Assignments/Materials/Grades, each its own
// page since the classes.php split -- see Include/class_picker.php).
// Without this, switching between tools for the same class means
// re-picking it from the dropdown on every single page.
window.spRestoreClassSelection = function (selectEl) {
  if (!selectEl) return false;
  try {
    var last = localStorage.getItem('sp_professor_last_schedule_id');
    if (last && selectEl.querySelector('option[value="' + last + '"]')) {
      selectEl.value = last;
      return true;
    }
  } catch (e) {}
  return false;
};

window.spRememberClassSelection = function (selectEl) {
  if (!selectEl) return;
  try {
    if (selectEl.value) localStorage.setItem('sp_professor_last_schedule_id', selectEl.value);
  } catch (e) {}
};

window.spToast = function (message, icon) {
  var host = document.getElementById('spToastHost');
  if (!host) return;
  var el = document.createElement('div');
  el.className = 'sp-toast';
  var iconEl = document.createElement('iconify-icon');
  iconEl.setAttribute('icon', icon || 'mdi:check-circle-outline');
  var textEl = document.createElement('span');
  textEl.textContent = message;
  el.appendChild(iconEl);
  el.appendChild(textEl);
  host.appendChild(el);
  requestAnimationFrame(function () { el.classList.add('is-visible'); });
  setTimeout(function () {
    el.classList.remove('is-visible');
    setTimeout(function () { el.remove(); }, 300);
  }, 3200);
};

// Delegated so any current or future "download a file" link just needs
// this class -- no per-page wiring required.
document.addEventListener('click', function (e) {
  var t = e.target.closest('.js-ics-download');
  if (t) window.spToast('Calendar file downloading…', 'mdi:calendar-check');
});

// A native <dialog>-based confirm, styled to match the rest of the app,
// instead of a themed alert library (SweetAlert etc.) that ships its own
// visual language you'd then have to fight to make match. Returns a
// Promise<boolean> so callers can `await` the user's choice.
window.spConfirm = function (message, opts) {
  opts = opts || {};
  var dialog = document.getElementById('spConfirmDialog');
  if (!dialog || typeof dialog.showModal !== 'function') {
    return Promise.resolve(window.confirm(message)); // very old browser fallback
  }
  document.getElementById('spConfirmTitle').textContent = opts.title || 'Are you sure?';
  document.getElementById('spConfirmMessage').textContent = message;
  var okBtn = document.getElementById('spConfirmOk');
  var cancelBtn = document.getElementById('spConfirmCancel');
  okBtn.textContent = opts.confirmLabel || 'Confirm';

  return new Promise(function (resolve) {
    function cleanup(result) {
      dialog.close();
      okBtn.removeEventListener('click', onOk);
      cancelBtn.removeEventListener('click', onCancel);
      dialog.removeEventListener('cancel', onCancel);
      dialog.removeEventListener('click', onBackdrop);
      resolve(result);
    }
    function onOk() { cleanup(true); }
    function onCancel() { cleanup(false); }
    function onBackdrop(e) { if (e.target === dialog) cleanup(false); }

    okBtn.addEventListener('click', onOk);
    cancelBtn.addEventListener('click', onCancel);
    dialog.addEventListener('cancel', onCancel); // Esc key
    dialog.addEventListener('click', onBackdrop);
    dialog.showModal();
  });
};

// Confirm before logging out -- previously an instant GET with no chance
// to back out of an accidental click.
document.addEventListener('click', function (e) {
  var logoutLink = e.target.closest('.sp-logout');
  if (!logoutLink) return;
  e.preventDefault();
  var href = logoutLink.getAttribute('href');
  window.spConfirm("You'll need to sign in again to get back into your portal.", {
    title: 'Log out?',
    confirmLabel: 'Log out'
  }).then(function (confirmed) {
    if (confirmed) window.location.href = href;
  });
});

// ----- Notification bell (fixed, top-right) -----
// "Dynamic" here means two things: it polls for genuinely new items
// without a page reload, and the unread badge is real state, not a fake
// always-on dot. There's no server-side per-student read-tracking table
// (a broader announcements feature earlier in this project deliberately
// skipped that) -- unread is tracked client-side via localStorage.
//
// The feed merges four sources (announcement/quiz/assignment/material),
// each with its own id space -- an assignment_id and a quiz_id aren't
// comparable, so "seen" is tracked as a per-type highest-id map rather
// than one global max id (which is all the previous announcements-only
// version needed).
(function () {
  var bell  = document.getElementById('notifBell');
  var badge = document.getElementById('notifBadge');
  var panel = document.getElementById('notifPanel');
  var list  = document.getElementById('notifList');
  if (!bell || !panel || !list) return;

  var STORAGE_KEY = 'sp_notif_last_seen_id';
  var POLL_MS = 60000;
  var items = [];
  var lastKnownUnread = 0;

  var TYPE_LABEL = { announcement: 'Announcement', quiz: 'Quiz', assignment: 'Assignment', material: 'Material' };

  function lastSeenMap() {
    var raw = localStorage.getItem(STORAGE_KEY);
    if (!raw) return {};
    try {
      var parsed = JSON.parse(raw);
      // Migrates the old schema (a bare number, meaning "highest
      // announcement_id seen") into the new per-type map so existing
      // users don't see every past announcement as newly unread.
      if (typeof parsed === 'number') return { announcement: parsed };
      return parsed && typeof parsed === 'object' ? parsed : {};
    } catch (e) {
      return {};
    }
  }

  function timeAgo(mysqlDatetime) {
    var then = new Date(mysqlDatetime.replace(' ', 'T')).getTime();
    var diff = Math.floor((Date.now() - then) / 1000);
    if (diff < 60) return 'just now';
    if (diff < 3600) return Math.floor(diff / 60) + 'm ago';
    if (diff < 86400) return Math.floor(diff / 3600) + 'h ago';
    var days = Math.floor(diff / 86400);
    if (days < 7) return days + 'd ago';
    return new Date(then).toLocaleDateString([], { month: 'short', day: 'numeric' });
  }

  function render() {
    if (items.length === 0) {
      list.innerHTML = '<p class="sp-card-hint">No notifications yet.</p>';
      return;
    }
    list.innerHTML = items.map(function (n) {
      return '<a class="sp-notif-item ' + n.tint + '" href="' + n.link + '">' +
        '<div class="sp-notif-item-head">' +
          '<span class="sp-notif-item-subject">' + escHtml(n.subject_code) + ' · ' + (TYPE_LABEL[n.type] || n.type) + '</span>' +
          '<span class="sp-notif-item-time">' + timeAgo(n.created_at) + '</span>' +
        '</div>' +
        '<p class="sp-notif-item-title">' + escHtml(n.title) + '</p>' +
        '<p class="sp-notif-item-body">' + escHtml(n.body).replace(/\n/g, '<br>') + '</p>' +
      '</a>';
    }).join('');
  }

  function updateBadge() {
    var seen = lastSeenMap();
    var unread = items.filter(function (n) { return n.item_id > (seen[n.type] || 0); }).length;
    if (unread > 0) {
      badge.textContent = unread > 9 ? '9+' : String(unread);
      badge.hidden = false;
    } else {
      badge.hidden = true;
    }
    if (unread > lastKnownUnread) {
      bell.classList.remove('is-ringing');
      void bell.offsetWidth;
      bell.classList.add('is-ringing');
    }
    lastKnownUnread = unread;
  }

  function fetchNotifications() {
    fetch('/SIAdrafts/Backend/api/Announcements/get_student_notifications.php')
      .then(function (res) { return res.json(); })
      .then(function (data) {
        items = data.notifications || [];
        render();
        updateBadge();
      })
      .catch(function () { /* silent -- a failed background poll shouldn't interrupt the page */ });
  }

  function openPanel() {
    panel.classList.add('is-open');
    bell.setAttribute('aria-expanded', 'true');
    if (items.length > 0) {
      // The true max per type, not items[0] -- items posted in the same
      // second sort as ties, so the first item of a given type in the
      // list isn't guaranteed to be that type's highest id.
      var seen = lastSeenMap();
      items.forEach(function (n) {
        seen[n.type] = Math.max(seen[n.type] || 0, n.item_id);
      });
      localStorage.setItem(STORAGE_KEY, JSON.stringify(seen));
      updateBadge();
    }
  }
  function closePanel() {
    panel.classList.remove('is-open');
    bell.setAttribute('aria-expanded', 'false');
  }

  bell.addEventListener('click', function (e) {
    e.stopPropagation();
    if (panel.classList.contains('is-open')) closePanel();
    else openPanel();
  });
  document.addEventListener('click', function (e) {
    if (!panel.contains(e.target) && e.target !== bell) closePanel();
  });
  document.addEventListener('keydown', function (e) {
    if (e.key === 'Escape') closePanel();
  });

  fetchNotifications();
  setInterval(fetchNotifications, POLL_MS);
})();
