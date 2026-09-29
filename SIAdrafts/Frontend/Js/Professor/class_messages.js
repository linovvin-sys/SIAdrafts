// Professor side of class-scoped messaging. Each contact is one
// (schedule_id, applicant_id) thread -- a specific student in a specific
// class -- unlike the Registrar staff chat's plain user-to-user threads.
document.addEventListener('DOMContentLoaded', function () {
  const API = '/SIAdrafts/Backend/api/ClassMessaging/';
  const csrfToken = document.body.dataset.csrf;

  let currentScheduleId = null;
  let currentApplicantId = null;
  let eventSource = null;
  let lastMessageId = 0;
  let sending = false;
  let selectedFile = null;

  const contactsEl   = document.getElementById('msgContacts');
  const chatHeader   = document.getElementById('msgChatHeader');
  const chatEmpty    = document.getElementById('msgChatEmpty');
  const messagesEl   = document.getElementById('msgMessages');
  const form         = document.getElementById('msgForm');
  const input        = document.getElementById('msgInput');
  const sendBtn      = document.getElementById('msgSendBtn');
  const attachBtn    = document.getElementById('msgAttachBtn');
  const fileInput    = document.getElementById('msgFileInput');
  const attachPreview     = document.getElementById('msgAttachmentPreview');
  const attachPreviewName = document.getElementById('msgAttachmentPreviewName');
  const attachRemoveBtn   = document.getElementById('msgAttachmentRemoveBtn');

  if (!contactsEl) return;

  function formatTime(sqlDateTime) {
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
    const url = API + 'download_attachment.php?message_id=' + m.class_message_id;
    if ((m.attachment_type || '').startsWith('image/')) {
      const link = document.createElement('a');
      link.href = url; link.target = '_blank'; link.rel = 'noopener';
      link.className = 'sp-msg-attachment-image-link';
      const img = document.createElement('img');
      img.src = url; img.alt = m.attachment_name || 'Attached image';
      img.className = 'sp-msg-attachment-image';
      link.appendChild(img);
      return link;
    }
    const link = document.createElement('a');
    link.href = url; link.target = '_blank'; link.rel = 'noopener';
    link.className = 'sp-msg-attachment-file';
    link.innerHTML = '<iconify-icon icon="mdi:file-outline"></iconify-icon> ' +
      escHtml(m.attachment_name || 'Attachment') +
      (m.attachment_size ? ' (' + formatBytes(m.attachment_size) + ')' : '');
    return link;
  }

  function renderMessage(m) {
    const mine = m.sender_role === 'professor';
    const wrap = document.createElement('div');
    wrap.className = 'sp-msg-bubble-row ' + (mine ? 'mine' : 'theirs');

    const bubble = document.createElement('div');
    bubble.className = 'sp-msg-bubble';
    if (m.attachment_name) bubble.appendChild(renderAttachment(m));
    if (m.body) {
      const text = document.createElement('div');
      text.textContent = m.body;
      bubble.appendChild(text);
    }
    wrap.appendChild(bubble);

    if (m.sent_at) {
      const time = document.createElement('div');
      time.className = 'sp-msg-bubble-time';
      time.textContent = formatTime(m.sent_at);
      wrap.appendChild(time);
    }

    messagesEl.appendChild(wrap);
    messagesEl.parentElement.scrollTop = messagesEl.parentElement.scrollHeight;
    lastMessageId = Math.max(lastMessageId, Number(m.class_message_id));
  }

  function showError(message) {
    const el = document.createElement('div');
    el.className = 'sp-msg-error';
    el.textContent = message;
    messagesEl.appendChild(el);
    messagesEl.parentElement.scrollTop = messagesEl.parentElement.scrollHeight;
  }

  function clearSelectedFile() {
    selectedFile = null;
    fileInput.value = '';
    attachPreview.classList.add('is-hidden');
    attachPreviewName.textContent = '';
  }

  function openThread(btn) {
    currentScheduleId  = Number(btn.dataset.scheduleId);
    currentApplicantId = Number(btn.dataset.applicantId);

    document.querySelectorAll('.sp-msg-contact').forEach(el => el.classList.remove('is-active'));
    btn.classList.add('is-active');
    const badge = btn.querySelector('[data-unread-badge]');
    if (badge) { badge.classList.add('is-hidden'); badge.textContent = '0'; }
    clearSelectedFile();

    chatEmpty.hidden = true;
    messagesEl.hidden = false;
    messagesEl.innerHTML = '';
    lastMessageId = 0;

    chatHeader.textContent = btn.dataset.name + ' — ' + btn.dataset.sub;

    input.disabled = false;
    sendBtn.disabled = false;
    attachBtn.disabled = false;
    input.placeholder = 'Message ' + btn.dataset.name + '…';
    input.focus();

    if (eventSource) eventSource.close();

    fetch(API + 'get_conversation.php?schedule_id=' + currentScheduleId + '&applicant_id=' + currentApplicantId)
      .then(r => r.json())
      .then(d => {
        if (d.error) { showError(d.error); return; }
        (d.messages || []).forEach(renderMessage);
        connectStream();
      })
      .catch(() => showError('Could not load this conversation. Check your connection and try again.'));
  }

  function connectStream() {
    eventSource = new EventSource(
      API + 'message_stream.php?schedule_id=' + currentScheduleId + '&applicant_id=' + currentApplicantId + '&last_id=' + lastMessageId
    );
    eventSource.onmessage = (e) => JSON.parse(e.data).forEach(renderMessage);
    eventSource.onerror = () => {
      eventSource.close();
      const sid = currentScheduleId, aid = currentApplicantId;
      setTimeout(() => { if (currentScheduleId === sid && currentApplicantId === aid) connectStream(); }, 2000);
    };
  }

  contactsEl.querySelectorAll('.sp-msg-contact').forEach(btn => {
    btn.addEventListener('click', () => openThread(btn));
  });

  attachBtn.addEventListener('click', () => { if (!attachBtn.disabled) fileInput.click(); });

  fileInput.addEventListener('change', () => {
    const file = fileInput.files[0];
    if (!file) { clearSelectedFile(); return; }
    if (file.size > 10 * 1024 * 1024) {
      showError('That file is too large (10 MB max).');
      clearSelectedFile();
      return;
    }
    selectedFile = file;
    attachPreviewName.textContent = file.name + ' (' + formatBytes(file.size) + ')';
    attachPreview.classList.remove('is-hidden');
  });

  attachRemoveBtn.addEventListener('click', clearSelectedFile);

  form.addEventListener('submit', (e) => {
    e.preventDefault();
    const body = input.value.trim();
    if ((!body && !selectedFile) || !currentScheduleId || sending) return;

    sending = true;
    sendBtn.disabled = true;

    const formData = new FormData();
    formData.append('csrf_token', csrfToken || '');
    formData.append('schedule_id', currentScheduleId);
    formData.append('applicant_id', currentApplicantId);
    formData.append('body', body);
    if (selectedFile) formData.append('attachment', selectedFile);

    fetch(API + 'send_message.php', { method: 'POST', body: formData })
      .then(r => r.json())
      .then(d => {
        if (d.success) {
          input.value = '';
          const hadFile = !!selectedFile;
          clearSelectedFile();
          renderMessage({
            class_message_id: d.message_id,
            sender_role: 'professor',
            body,
            sent_at: d.sent_at,
            attachment_name: hadFile ? d.attachment_name : null,
            attachment_type: hadFile ? d.attachment_type : null,
            attachment_size: hadFile ? d.attachment_size : null,
          });
        } else {
          showError(d.error || 'Message could not be sent.');
        }
      })
      .catch(() => showError('Message could not be sent. Check your connection and try again.'))
      .finally(() => {
        sending = false;
        sendBtn.disabled = false;
        input.focus();
      });
  });
});
