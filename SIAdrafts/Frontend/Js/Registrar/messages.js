// messages.js
let currentContactId = null;
let eventSource       = null;
let lastMessageId      = 0;

const chatMessages = document.getElementById('chat-messages');
const chatForm      = document.getElementById('chat-form');
const chatInput      = document.getElementById('chat-input');

function escapeHtml(str) {
  const div = document.createElement('div');
  div.textContent = str;
  return div.innerHTML;
}

function renderMessage(m) {
  const mine = m.sender_id == CURRENT_USER_ID;
  const el = document.createElement('div');
  el.className = 'chat-bubble ' + (mine ? 'mine' : 'theirs');
  el.textContent = m.body;
  chatMessages.appendChild(el);
  chatMessages.scrollTop = chatMessages.scrollHeight;
  lastMessageId = Math.max(lastMessageId, Number(m.message_id));
}

function openConversation(userId) {
  currentContactId = userId;
  chatMessages.innerHTML = '';
  lastMessageId = 0;

  if (eventSource) eventSource.close();

  fetch(`/SIAdrafts/Backend/api/get_conversations.php?with=${userId}`)
    .then(r => r.json())
    .then(d => {
      (d.messages || []).forEach(renderMessage);
      connectStream(userId);
    });
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

document.querySelectorAll('.contact-item').forEach(btn => {
  btn.addEventListener('click', () => openConversation(btn.dataset.userId));
});

chatForm.addEventListener('submit', (e) => {
  e.preventDefault();
  const body = chatInput.value.trim();
  if (!body || !currentContactId) return;

  const formData = new FormData();
  formData.append('recipient_id', currentContactId);
  formData.append('body', body);

  fetch('/SIAdrafts/Backend/api/send_message.php', { method: 'POST', body: formData })
    .then(r => r.json())
    .then(d => {
      if (d.success) {
        chatInput.value = '';
        // Own message will also arrive via the SSE stream shortly, but
        // render immediately for snappier perceived response.
        renderMessage({ message_id: d.message_id, sender_id: CURRENT_USER_ID, body, sent_at: d.sent_at });
      }
    });
});