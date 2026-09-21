// Internal Dotty — staff portal. Direct port of index.php's FAQ chat
// script (same mascot reactions, same panel behavior) pointed at the
// authenticated, role-scoped endpoint instead of the public FAQ one.
(function () {
  var toggle = document.getElementById('chatToggle');
  var panel  = document.getElementById('chatPanel');
  if (!toggle || !panel) return;

  var log    = document.getElementById('chatLog');
  var form   = document.getElementById('chatForm');
  var input  = document.getElementById('chatInput');
  var send   = document.getElementById('chatSend');
  var avatar = document.getElementById('chatAvatar');
  var scrim  = document.getElementById('chatScrim');
  var opened = false;
  var sending = false;
  var history = []; // {role: 'user'|'model', text: string} — errors are never added, only real turns

  // Eyes glance down while typing, quick double-blink the instant a
  // message sends — same reactive-not-looping approach as the public widget.
  input.addEventListener('input', function () {
    avatar.classList.toggle('e-chat-avatar--attentive', input.value.length > 0);
  });
  input.addEventListener('blur', function () {
    avatar.classList.remove('e-chat-avatar--attentive');
  });
  function avatarBlink(afterCb) {
    avatar.classList.remove('e-chat-avatar--blink');
    void avatar.offsetWidth;
    avatar.classList.add('e-chat-avatar--blink');
    setTimeout(function () {
      avatar.classList.remove('e-chat-avatar--blink');
      if (afterCb) afterCb();
    }, 400);
  }

  function avatarUnsure() {
    avatar.classList.remove('e-chat-avatar--unsure');
    void avatar.offsetWidth;
    avatar.classList.add('e-chat-avatar--unsure');
    setTimeout(function () { avatar.classList.remove('e-chat-avatar--unsure'); }, 700);
  }
  // Matches the internal system prompt's own decline phrasing ("I don't
  // have access", "contact/reach out to <office>") — broad on purpose,
  // same reasoning as the public widget's pattern.
  var DECLINE_PATTERN = /not sure|don'?t (know|have access)|do not (know|have access)|doesn'?t (mention|cover|say|list)|reach out|contact.{0,30}(admissions|treasury|registrar)/i;

  var idleGlanceTimer = null;
  function scheduleIdleGlance() {
    clearTimeout(idleGlanceTimer);
    idleGlanceTimer = setTimeout(function () {
      if (opened && !sending && input.value === '') {
        var dir = Math.random() < 0.5 ? 'e-chat-avatar--glance-left' : 'e-chat-avatar--glance-right';
        avatar.classList.add(dir);
        setTimeout(function () { avatar.classList.remove(dir); }, 900);
      }
      scheduleIdleGlance();
    }, 9000 + Math.random() * 6000);
  }

  var MINI_AVATAR_HTML =
    '<span class="e-chat-avatar-mini" aria-hidden="true">' +
      '<span class="e-chat-socket"><span class="e-chat-eye"></span></span>' +
      '<span class="e-chat-socket"><span class="e-chat-eye"></span></span>' +
    '</span>';

  function escapeHtml(str) {
    return String(str).replace(/[&<>"']/g, function (c) {
      return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c];
    });
  }

  // The model answers in plain text with a "- item" convention for lists
  // (see the system prompt), not markdown — this turns that convention
  // into real <p>/<ul><li> structure instead of relying on CSS white-space
  // to fake it with raw newlines. Every line still goes through
  // escapeHtml before becoming HTML, same safety as textContent had.
  function renderMessageBody(el, text) {
    var lines = String(text).split('\n').map(function (l) { return l.trim(); }).filter(function (l) { return l !== ''; });
    var html = '';
    var i = 0;
    while (i < lines.length) {
      if (/^-\s+/.test(lines[i])) {
        var items = [];
        while (i < lines.length && /^-\s+/.test(lines[i])) {
          items.push(lines[i].replace(/^-\s+/, ''));
          i++;
        }
        html += '<ul class="e-chat-list">' + items.map(function (t) { return '<li>' + escapeHtml(t) + '</li>'; }).join('') + '</ul>';
      } else {
        var para = [];
        while (i < lines.length && !/^-\s+/.test(lines[i])) {
          para.push(lines[i]);
          i++;
        }
        html += '<p class="e-chat-line">' + escapeHtml(para.join(' ')) + '</p>';
      }
    }
    el.innerHTML = html || escapeHtml(text);
  }

  function addMessage(text, kind) {
    var msg = document.createElement('div');
    msg.className = 'e-chat-msg e-chat-msg--' + kind;
    if (kind === 'user') {
      msg.textContent = text;
    } else {
      renderMessageBody(msg, text);
    }

    if (kind === 'user') {
      msg.classList.add('e-chat-msg-in');
      log.appendChild(msg);
      log.scrollTop = log.scrollHeight;
      return msg;
    }

    var row = document.createElement('div');
    row.className = 'e-chat-row e-chat-msg-in';
    row.innerHTML = MINI_AVATAR_HTML;
    row.appendChild(msg);
    log.appendChild(row);
    log.scrollTop = log.scrollHeight;
    return row;
  }

  function addTyping() {
    var bubble = document.createElement('div');
    bubble.className = 'e-chat-typing';
    bubble.innerHTML = '<span></span><span></span><span></span>';

    var row = document.createElement('div');
    row.className = 'e-chat-row e-chat-msg-in';
    row.innerHTML = MINI_AVATAR_HTML;
    row.appendChild(bubble);
    log.appendChild(row);
    log.scrollTop = log.scrollHeight;
    return row;
  }

  toggle.addEventListener('click', function () {
    opened = !opened;
    toggle.classList.toggle('open', opened);
    toggle.setAttribute('aria-expanded', opened ? 'true' : 'false');
    panel.classList.toggle('open', opened);
    scrim.classList.toggle('open', opened);
    if (opened) {
      if (!log.children.length) {
        addMessage("Hi, I'm Dotty. Ask me about your own role's figures — I only see what your account is allowed to.", 'bot');
      }
      input.focus();
      scheduleIdleGlance();
    } else {
      clearTimeout(idleGlanceTimer);
    }
  });

  scrim.addEventListener('click', function () {
    if (opened) toggle.click();
  });

  form.addEventListener('submit', function (e) {
    e.preventDefault();
    var message = input.value.trim();
    if (!message || sending) return;

    addMessage(message, 'user');
    input.value = '';
    input.classList.remove('e-chat-sent');
    void input.offsetWidth;
    input.classList.add('e-chat-sent');
    avatar.classList.remove('e-chat-avatar--attentive');
    sending = true;
    avatarBlink(function () {
      if (sending) avatar.classList.add('e-chat-avatar--thinking');
    });
    send.disabled = true;
    var typing = addTyping();

    fetch('/SIAdrafts/Backend/api/Chat/ask_staff.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ message: message, history: history }),
    })
      .then(function (res) { return res.json(); })
      .then(function (data) {
        typing.remove();
        if (data.error) {
          addMessage(data.error, 'error');
          avatarUnsure();
        } else {
          addMessage(data.reply, 'bot');
          history.push({ role: 'user', text: message });
          history.push({ role: 'model', text: data.reply });
          if (DECLINE_PATTERN.test(data.reply)) avatarUnsure();
          else avatarBlink();
        }
      })
      .catch(function () {
        typing.remove();
        addMessage("Hmm, I lost my train of thought there. Mind trying that again?", 'error');
        avatarUnsure();
      })
      .finally(function () {
        sending = false;
        send.disabled = false;
        avatar.classList.remove('e-chat-avatar--thinking');
        scheduleIdleGlance();
      });
  });
})();
