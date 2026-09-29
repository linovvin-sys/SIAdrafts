<?php
$pageTitle  = "Messages";
$activePage = "messages";

require_once __DIR__ . '/../../../Backend/require_professor.php';
require_professor();
require_once __DIR__ . '/../../../Backend/db.php';
require_once __DIR__ . '/../../../Backend/api/ClassMessaging/can_message.php';

$db   = new Database();
$conn = $db->connect();

$professorId = (int)$_SESSION['professor_id'];
$threads     = get_professor_class_threads($conn, $professorId);

$db->close();

function class_msg_initials(string $first, string $last): string {
    $f = mb_substr(trim($first), 0, 1);
    $l = mb_substr(trim($last), 0, 1);
    return mb_strtoupper($f . $l) ?: '?';
}

include __DIR__ . '/Include/header.php';
?>

<h1 class="sp-greeting">Messages</h1>
<p class="sp-subline">Message a student directly about one of your classes.</p>

<div class="sp-msg-layout">
  <div class="sp-msg-contacts" id="msgContacts">
    <?php if (empty($threads)): ?>
      <p class="sp-msg-empty-contacts">No students to message yet — this fills in once you have active classes with enrolled students.</p>
    <?php else: ?>
      <?php foreach ($threads as $t): ?>
        <?php
          $name = trim($t['first_name'] . ' ' . $t['last_name']);
          $sub  = htmlspecialchars($t['subject_code'] . ' — ' . $t['section_name'], ENT_QUOTES);
        ?>
        <button type="button" class="sp-msg-contact"
                data-schedule-id="<?= (int)$t['schedule_id'] ?>"
                data-applicant-id="<?= (int)$t['applicant_id'] ?>"
                data-name="<?= htmlspecialchars($name, ENT_QUOTES) ?>"
                data-sub="<?= $sub ?>">
          <span class="sp-msg-contact-avatar"><?= htmlspecialchars(class_msg_initials($t['first_name'], $t['last_name']), ENT_QUOTES) ?></span>
          <span class="sp-msg-contact-text">
            <span class="sp-msg-contact-name"><?= htmlspecialchars($name, ENT_QUOTES) ?></span><br>
            <span class="sp-msg-contact-sub"><?= $sub ?></span>
          </span>
          <span class="sp-msg-contact-unread<?= $t['unread'] > 0 ? '' : ' is-hidden' ?>" data-unread-badge>
            <?= $t['unread'] > 99 ? '99+' : (int)$t['unread'] ?>
          </span>
        </button>
      <?php endforeach; ?>
    <?php endif; ?>
  </div>

  <div class="sp-msg-chat">
    <div class="sp-msg-chat-header" id="msgChatHeader">Select a student to start messaging</div>
    <div class="sp-msg-chat-body" id="msgChatBody">
      <p class="sp-msg-chat-empty" id="msgChatEmpty">Select a student from the left to view your conversation.</p>
      <div id="msgMessages" hidden></div>
    </div>
    <div class="sp-msg-attachment-preview is-hidden" id="msgAttachmentPreview">
      <span id="msgAttachmentPreviewName"></span>
      <button type="button" class="sp-msg-attachment-remove" id="msgAttachmentRemoveBtn" aria-label="Remove attachment">&times;</button>
    </div>
    <form class="sp-msg-input-row" id="msgForm" enctype="multipart/form-data">
      <input type="file" id="msgFileInput" hidden accept=".jpg,.jpeg,.png,.gif,.webp,.pdf,.doc,.docx,.xls,.xlsx,.txt">
      <button type="button" class="sp-msg-attach-btn" id="msgAttachBtn" disabled title="Attach a file">
        <iconify-icon icon="mdi:paperclip"></iconify-icon>
      </button>
      <input type="text" id="msgInput" placeholder="Select a student to start messaging…" autocomplete="off" disabled>
      <button type="submit" class="sp-msg-send-btn" id="msgSendBtn" disabled>Send</button>
    </form>
  </div>
</div>

<script>
  const CLASS_MSG_ROLE = 'professor';
</script>
<?php $pageScript = 'class_messages'; ?>

<?php include __DIR__ . '/Include/footer.php'; ?>
