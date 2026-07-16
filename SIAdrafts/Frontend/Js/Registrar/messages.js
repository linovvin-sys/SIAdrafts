// messages.js
let currentContactId = null;
let eventSource       = null;
let lastMessageId     = 0;
let sending           = false;
let selectedFile      = null;

const contactList        = document.getElementById('contact-list');
const chatMessages        = document.getElementById('chat-messages');
const chatEmptyState      = document.getElementById('chat-empty-state');
const chatHeaderEmpty     = document.getElementById('chat-header-empty');
const chatHeaderActive    = document.getElementById('chat-header-active');
const chatAvatar          = document.getElementById('chat-avatar');
const chatContactName     = document.getElementById('chat-contact-name');
const chatForm             = document.getElementById('chat-form');
const chatInput            = document.getElementById('chat-input');
const chatSendBtn          = document.getElementById('chat-send-btn');
const chatAttachBtn        = document.getElementById('chat-attach-btn');
const chatFileInput        = document.getElementById('chat-file-input');
const attachmentPreview     = document.getElementById('attachment-preview');
const attachmentPreviewName = document.getElementById('attachment-preview-name');
const attachmentRemoveBtn   = document.getElementById('attachment-remove-btn');

function formatTime(sqlDateTime) {
  // sent_at comes back as "YYYY-MM-DD HH:MM:SS" — make it parseable as local time.
  const d = new Date(sqlDateTime.replace(' ', 'T'));
  if (isNaN(d.getTime())) return '';
  return d.toLocaleTimeString([], { hour: 'numeric', minute: '2-digit' });
}

function formatBytes(bytes) {
  if (!bytes && bytes !== 0) return '';
  if (bytes < 1024) return bytes + ' B';
  if (bytes < 1024 * 1024) return (bytes / 1024).toFixed(1) + ' KB';
  return (bytes / (1024 * 1024)).toFixed(1) + ' MB';
}

function renderAttachment(m) {
  const url = `/SIAdrafts/Backend/api/download_attachment.php?message_id=${m.message_id}`;

  if ((m.attachment_type || '').startsWith('image/')) {
    const link = document.createElement('a');
    link.href = url;
    link.target = '_blank';
    link.rel = 'noopener';
    link.className = 'chat-attachment-image-link';

    const img = document.createElement('img');
    img.src = url;
    img.alt = m.attachment_name || 'Attached image';
    img.className = 'chat-attachment-image';
    link.appendChild(img);
    return link;
  }

  const link = document.createElement('a');
  link.href = url;
  link.target = '_blank';
  link.rel = 'noopener';
  link.className = 'chat-attachment-file';

  const icon = document.createElement('span');
  icon.className = 'chat-attachment-file-icon';
  icon.textContent = '📄';
  link.appendChild(icon);

  const meta = document.createElement('span');
  meta.className = 'chat-attachment-file-meta';

  const name = document.createElement('span');
  name.className = 'chat-attachment-file-name';
  name.textContent = m.attachment_name || 'Attachment';
  meta.appendChild(name);

  if (m.attachment_size) {
    const size = document.createElement('span');
    size.className = 'chat-attachment-file-size';
    size.textContent = formatBytes(m.attachment_size);
    meta.appendChild(size);
  }

  link.appendChild(meta);
  return link;
}

function renderMessage(m) {
  const mine = m.sender_id == CURRENT_USER_ID;

  const wrap = document.createElement('div');
  wrap.className = 'chat-bubble-row ' + (mine ? 'mine' : 'theirs');

  const bubble = document.createElement('div');
  bubble.className = 'chat-bubble';

  if (m.attachment_name) {
    bubble.classList.add('has-attachment');
    bubble.appendChild(renderAttachment(m));
  }

  if (m.body) {
    const text = document.createElement('div');
    text.className = 'chat-bubble-text';
    text.textContent = m.body;
    bubble.appendChild(text);
  }

  wrap.appendChild(bubble);

  if (m.sent_at) {
    const time = document.createElement('div');
    time.className = 'chat-bubble-time';
    time.textContent = formatTime(m.sent_at);
    wrap.appendChild(time);
  }

  chatMessages.appendChild(wrap);
  chatMessages.scrollTop = chatMessages.scrollHeight;
  lastMessageId = Math.max(lastMessageId, Number(m.message_id));
}

function showChatError(message) {
  const el = document.createElement('div');
  el.className = 'chat-error';
  el.textContent = message;
  chatMessages.appendChild(el);
  chatMessages.scrollTop = chatMessages.scrollHeight;
}

function setActiveContact(btn) {
  contactList.querySelectorAll('.contact-item').forEach(el => el.classList.remove('is-active'));
  btn.classList.add('is-active');
}

function clearUnreadBadge(btn) {
  const badge = btn.querySelector('[data-unread-badge]');
  if (badge) {
    badge.classList.add('is-hidden');
    badge.textContent = '0';
  }
}

function clearSelectedFile() {
  selectedFile = null;
  chatFileInput.value = '';
  attachmentPreview.classList.add('is-hidden');
  attachmentPreviewName.textContent = '';
}

function openConversation(btn) {
  const userId = Number(btn.dataset.userId);
  const name   = btn.dataset.name;

  currentContactId = userId;
  setActiveContact(btn);
  clearUnreadBadge(btn);
  clearSelectedFile();

  chatEmptyState.classList.add('is-hidden');
  chatMessages.classList.remove('is-hidden');
  chatMessages.innerHTML = '';
  lastMessageId = 0;

  chatHeaderEmpty.classList.add('is-hidden');
  chatHeaderActive.classList.remove('is-hidden');
  chatAvatar.textContent = btn.querySelector('.contact-avatar').textContent;
  chatContactName.textContent = name;

  chatInput.disabled = false;
  chatSendBtn.disabled = false;
  chatAttachBtn.disabled = false;
  chatInput.placeholder = `Message ${name}…`;
  chatInput.focus();

  if (eventSource) eventSource.close();

  fetch(`/SIAdrafts/Backend/api/get_conversations.php?with=${userId}`)
    .then(r => r.json())
    .then(d => {
      if (d.error) {
        showChatError(d.error);
        return;
      }
      (d.messages || []).forEach(renderMessage);
      connectStream(userId);
    })
    .catch(() => showChatError('Could not load this conversation. Check your connection and try again.'));
}

function connectStream(userId) {
  eventSource = new EventSource(
    `/SIAdrafts/Backend/api/message_stream.php?with=${userId}&last_id=${lastMessageId}`
  );
  eventSource.onmessage = (e) => {
    const newMessages = JSON.parse(e.data);
    newMessages.forEach(renderMessage);
  };
  eventSource.onerror = () => {
    // Reconnect after a short delay if the connection drops
    eventSource.close();
    setTimeout(() => { if (currentContactId === userId) connectStream(userId); }, 2000);
  };
}

contactList.querySelectorAll('.contact-item').forEach(btn => {
  btn.addEventListener('click', () => openConversation(btn));
});

chatAttachBtn.addEventListener('click', () => {
  if (!chatAttachBtn.disabled) chatFileInput.click();
});

chatFileInput.addEventListener('change', () => {
  const file = chatFileInput.files[0];
  if (!file) {
    clearSelectedFile();
    return;
  }
  if (file.size > 10 * 1024 * 1024) {
    showChatError('That file is too large (10 MB max).');
    clearSelectedFile();
    return;
  }
  selectedFile = file;
  attachmentPreviewName.textContent = `${file.name} (${formatBytes(file.size)})`;
  attachmentPreview.classList.remove('is-hidden');
});

attachmentRemoveBtn.addEventListener('click', clearSelectedFile);

chatForm.addEventListener('submit', (e) => {
  e.preventDefault();
  const body = chatInput.value.trim();
  if ((!body && !selectedFile) || !currentContactId || sending) return;

  sending = true;
  chatSendBtn.disabled = true;

  const formData = new FormData();
  formData.append('recipient_id', currentContactId);
  formData.append('body', body);
  if (selectedFile) formData.append('attachment', selectedFile);

  fetch('/SIAdrafts/Backend/api/send_message.php', { method: 'POST', body: formData })
    .then(r => r.json())
    .then(d => {
      if (d.success) {
        chatInput.value = '';
        const hadFile = !!selectedFile;
        clearSelectedFile();
        // Own message will also arrive via the SSE stream shortly, but
        // render immediately for snappier perceived response.
        renderMessage({
          message_id: d.message_id,
          sender_id: CURRENT_USER_ID,
          body,
          sent_at: d.sent_at,
          attachment_name: hadFile ? d.attachment_name : null,
          attachment_type: hadFile ? d.attachment_type : null,
          attachment_size: hadFile ? d.attachment_size : null,
        });
      } else {
        showChatError(d.error || 'Message could not be sent.');
      }
    })
    .catch(() => showChatError('Message could not be sent. Check your connection and try again.'))
    .finally(() => {
      sending = false;
      chatSendBtn.disabled = false;
      chatInput.focus();
    });
});