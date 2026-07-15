<?php
$pageTitle  = "MESSAGES";
$activePage = "messages";
$pageScript = "messages";

require_once '../../../Backend/auth.php';
require_once '../../../Backend/require_role.php';
require_role(['Head Registrar', 'Registrar Staff']);
require_once '../../../Backend/db.php';
require_once '../../../Backend/api/can_message.php';

$db   = new Database();
$conn = $db->connect();

$contacts = get_allowed_contacts($conn, (int)$_SESSION['user_id']);
$db->close();

include 'Include/header.php';
?>

<div class="app-layout">

  <?php include 'Include/sidebar.php'; ?>

  <main class="page-content">

    <div class="grid-2">

      <div class="panel">
        <div class="panel-header"><span class="panel-title">Contacts</span></div>
        <div class="panel-body" style="padding:0;">
          <?php if (empty($contacts)): ?>
            <p style="padding:16px; color:var(--text-muted, #666);">No one is available to message with your current role.</p>
          <?php else: ?>
            <?php foreach ($contacts as $c): ?>
              <button type="button" class="contact-item" data-user-id="<?= (int)$c['user_id'] ?>" style="width:100%; text-align:left; padding:12px 16px; border:none; background:none; border-bottom:1px solid var(--border-color, #eee); cursor:pointer;">
                <?= htmlspecialchars($c['first_name'] . ' ' . $c['last_name']) ?>
              </button>
            <?php endforeach; ?>
          <?php endif; ?>
        </div>
      </div>

      <div class="panel">
        <div class="panel-body" id="chat-window" style="min-height:420px; display:flex; flex-direction:column; padding:16px;">
          <div id="chat-messages" style="flex:1; overflow-y:auto; margin-bottom:12px;"></div>
          <form id="chat-form" style="display:flex; gap:8px;">
            <input type="text" id="chat-input" class="form-input" placeholder="Type a message…" autocomplete="off" style="flex:1;">
            <button type="submit" class="btn btn-primary">Send</button>
          </form>
        </div>
      </div>

    </div>

  </main>
</div>

<script>
  const CURRENT_USER_ID = <?= (int)$_SESSION['user_id'] ?>;
</script>

<?php include 'Include/footer.php' ?>