<?php
$pageTitle  = "MESSAGES";
$activePage = "messages";
$pageScript = "messages";

require_once '../../../Backend/auth.php';
require_once '../../../Backend/require_role.php';
require_role(['Registrar Staff', 'Head Registrar', 'Admin']);
require_once '../../../Backend/db.php';
require_once '../../../Backend/api/Messaging/can_message.php';

$isAdminViewer = current_user_is(['Admin']);

$db   = new Database();
$conn = $db->connect();

if (!$isAdminViewer) {
    $myId         = (int)$_SESSION['user_id'];
    $contacts     = get_allowed_contacts($conn, $myId);
    $unreadCounts = get_unread_counts($conn, $myId);
} else {
    // Admin gets activity metadata only — who's talking to whom, how much,
    // and how recently — never message bodies. This is a deliberate privacy
    // boundary, not an oversight: staff messages are correspondence, and
    // Admin's oversight need is "is this being used / responsive", not
    // "what did they say." Grouped by unordered participant pair so an
    // A->B and B->A thread count as one conversation.
    $activityStmt = $conn->prepare(
        "SELECT
            LEAST(m.sender_id, m.recipient_id)    AS user_a,
            GREATEST(m.sender_id, m.recipient_id) AS user_b,
            COUNT(*)                              AS message_count,
            SUM(m.read_at IS NULL)                AS unread_count,
            MAX(m.sent_at)                        AS last_activity
         FROM messages m
         GROUP BY user_a, user_b
         ORDER BY last_activity DESC"
    );
    $activityStmt->execute();
    $activityRows = $activityStmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $activityStmt->close();

    $userIds = [];
    foreach ($activityRows as $row) {
        $userIds[(int)$row['user_a']] = true;
        $userIds[(int)$row['user_b']] = true;
    }
    $userNames = [];
    if (!empty($userIds)) {
        $ids = array_keys($userIds);
        $ph  = implode(',', array_fill(0, count($ids), '?'));
        $nameStmt = $conn->prepare("SELECT user_id, first_name, last_name FROM users WHERE user_id IN ($ph)");
        $nameStmt->bind_param(str_repeat('i', count($ids)), ...$ids);
        $nameStmt->execute();
        foreach ($nameStmt->get_result()->fetch_all(MYSQLI_ASSOC) as $u) {
            $userNames[(int)$u['user_id']] = trim($u['first_name'] . ' ' . $u['last_name']);
        }
        $nameStmt->close();
    }
}
$db->close();

function initials(string $first, string $last): string {
    $f = mb_substr(trim($first), 0, 1);
    $l = mb_substr(trim($last), 0, 1);
    return mb_strtoupper($f . $l) ?: '?';
}

include '../Include/header.php';
?>

<?php if ($isAdminViewer): ?>

<div class="app-layout">

  <?php include '../Include/sidebar.php'; ?>

  <main class="page-content">

    <div class="panel">
      <div class="panel-header">
        <span class="panel-title">Messaging Activity</span>
        <span class="text-muted" style="font-size:12px;">Metadata only — message content is private to participants</span>
      </div>
      <div class="panel-body" style="padding:0;">
        <div class="table-responsive">
        <table class="data-table">
          <thead>
            <tr>
              <th>Participants</th>
              <th>Messages</th>
              <th>Unread</th>
              <th>Last activity</th>
            </tr>
          </thead>
          <tbody>
            <?php if (empty($activityRows)): ?>
              <tr><td colspan="4" style="text-align:center;">No messaging activity yet.</td></tr>
            <?php else: ?>
              <?php foreach ($activityRows as $row): ?>
                <?php
                  $nameA = $userNames[(int)$row['user_a']] ?? 'Unknown';
                  $nameB = $userNames[(int)$row['user_b']] ?? 'Unknown';
                ?>
                <tr>
                  <td><?= htmlspecialchars($nameA) ?> &harr; <?= htmlspecialchars($nameB) ?></td>
                  <td class="mono"><?= (int)$row['message_count'] ?></td>
                  <td class="mono"><?= (int)$row['unread_count'] ?></td>
                  <td class="mono"><?= htmlspecialchars(date('M j, Y g:i A', strtotime($row['last_activity']))) ?></td>
                </tr>
              <?php endforeach; ?>
            <?php endif; ?>
          </tbody>
        </table>
        </div>
      </div>
    </div>

  </main>
</div>

<?php else: ?>

<div class="app-layout">

  <?php include '../Include/sidebar.php'; ?>

  <main class="page-content">

    <div class="messenger-layout">

      <div class="panel messenger-contacts">
        <div class="panel-header"><span class="panel-title">Contacts</span></div>
        <div class="contact-list" id="contact-list">
          <?php if (empty($contacts)): ?>
            <p class="contact-list-empty">No one is available to message with your current role.</p>
          <?php else: ?>
            <?php foreach ($contacts as $c): ?>
              <?php
                $name    = trim($c['first_name'] . ' ' . $c['last_name']);
                $unread  = $unreadCounts[(int)$c['user_id']] ?? 0;
              ?>
              <button type="button"
                      class="contact-item"
                      data-user-id="<?= (int)$c['user_id'] ?>"
                      data-name="<?= htmlspecialchars($name) ?>">
                <span class="contact-avatar"><?= htmlspecialchars(initials($c['first_name'], $c['last_name'])) ?></span>
                <span class="contact-details">
                  <span class="contact-name"><?= htmlspecialchars($name) ?></span>
                </span>
                <span class="contact-unread<?= $unread > 0 ? '' : ' is-hidden' ?>" data-unread-badge>
                  <?= $unread > 99 ? '99+' : $unread ?>
                </span>
              </button>
            <?php endforeach; ?>
          <?php endif; ?>
        </div>
      </div>

      <div class="panel messenger-chat">
        <div class="panel-header chat-header" id="chat-header">
          <span class="chat-header-empty" id="chat-header-empty">Select a contact to start messaging</span>
          <span class="chat-header-active is-hidden" id="chat-header-active">
            <span class="contact-avatar" id="chat-avatar"></span>
            <span class="panel-title" id="chat-contact-name"></span>
          </span>
        </div>

        <div class="chat-body" id="chat-window">
          <div class="chat-empty-state" id="chat-empty-state">
            <p>Select a contact from the left to view your conversation.</p>
          </div>
          <div id="chat-messages" class="chat-messages is-hidden"></div>
        </div>

        <form id="chat-form" class="chat-input-row" enctype="multipart/form-data">
          <div id="attachment-preview" class="attachment-preview is-hidden">
            <span class="attachment-preview-name" id="attachment-preview-name"></span>
            <button type="button" id="attachment-remove-btn" class="attachment-remove-btn" aria-label="Remove attachment">&times;</button>
          </div>
          <div class="chat-input-controls">
            <input type="file" id="chat-file-input" hidden accept=".jpg,.jpeg,.png,.gif,.webp,.pdf,.doc,.docx,.xls,.xlsx,.txt">
            <button type="button" class="btn btn-outline chat-attach-btn" id="chat-attach-btn" disabled title="Attach a file">📎</button>
            <input type="text" id="chat-input" class="form-input" placeholder="Select a contact to start messaging…" autocomplete="off" disabled>
            <button type="submit" class="btn btn-primary" id="chat-send-btn" disabled>Send</button>
          </div>
        </form>
      </div>

    </div>

  </main>
</div>

<script>
  const CURRENT_USER_ID = <?= (int)$_SESSION['user_id'] ?>;
</script>

<?php $extraScripts = ['https://cdn.jsdelivr.net/npm/vue@3/dist/vue.global.prod.js', '/SIAdrafts/Frontend/Js/Registrar/messages.js']; ?>

<?php endif; ?>

<?php include '../Include/footer.php'; ?>