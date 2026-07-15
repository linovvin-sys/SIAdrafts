<?php
require_once __DIR__ . '/../../../../Backend/require_role.php';

$root       = '/SIAdrafts/Frontend/';
$isHead     = current_user_is(['Head Registrar']);
$roleLabel  = $isHead ? 'Head Registrar' : 'Registrar Staff';
$initials   = $isHead ? 'HR' : 'RS';

// Small badge on "Pending Approvals" showing how many are waiting.
$pendingCount = 0;
if ($isHead) {
    require_once __DIR__ . '/../../../../Backend/db.php';
    $db   = new Database();
    $conn = $db->connect();
    $res  = $conn->query("SELECT COUNT(*) AS cnt FROM schedule WHERE status = 'Pending'");
    $pendingCount = (int)($res->fetch_assoc()['cnt'] ?? 0);
    $db->close();
}

// Pending readmission requests — Head Registrar only, same pattern as
// the schedule-approval badge above.
$pendingReadmissionCount = 0;
if ($isHead) {
    require_once __DIR__ . '/../../../../Backend/db.php';
    $db   = new Database();
    $conn = $db->connect();
    $res  = $conn->query("SELECT COUNT(*) AS cnt FROM readmission_request WHERE status = 'Pending'");
    $pendingReadmissionCount = (int)($res->fetch_assoc()['cnt'] ?? 0);
    $db->close();
}

// Unread message count for whoever's logged in — both roles can receive.
$unreadCount = 0;
if (!empty($_SESSION['user_id'])) {
    require_once __DIR__ . '/../../../../Backend/db.php';
    $db   = new Database();
    $conn = $db->connect();
    $msgStmt = $conn->prepare("SELECT COUNT(*) AS cnt FROM messages WHERE recipient_id = ? AND read_at IS NULL");
    $msgStmt->bind_param('i', $_SESSION['user_id']);
    $msgStmt->execute();
    $unreadCount = (int)($msgStmt->get_result()->fetch_assoc()['cnt'] ?? 0);
    $msgStmt->close();
    $db->close();
}
?>
<!-- ===== SIDEBAR ===== -->
<aside class="sidebar">

  <!-- Brand -->
  <div class="sidebar-brand" id="sidebar-toggle" title="Toggle sidebar">
    <div class="brand-icon">🎓</div>
    <div class="brand-name">Edu<span>School</span></div>
  </div>

  <!-- MAIN -->
  <nav class="nav-section">
    <div class="nav-label">Main</div>

    <a href="<?= $root ?>View/Registrar/registrar_dashboard.php" class="nav-item<?= ($activePage ?? '') === 'dashboard' ? ' active' : '' ?>" data-page="dashboard">
      <span class="nav-icon">
        <svg xmlns="http://www.w3.org/2000/svg" width="1em" height="1em" viewBox="0 0 24 24" fill="currentColor">
          <path d="M0 0h24v24H0z" fill="none"/>
          <path d="M13 9V3h8v6zM3 13V3h8v10zm10 8V11h8v10zM3 21v-6h8v6z"/>
        </svg>
      </span>
      <span class="nav-text">Dashboard</span>
    </a>

  </nav>

  <!-- MANAGEMENT -->
  <nav class="nav-section">
    <div class="nav-label">Management</div>

    <a href="<?= $root ?>View/Registrar/courses.php" class="nav-item<?= ($activePage ?? '') === 'courses' ? ' active' : '' ?>" data-page="courses">
      <span class="nav-icon">
        <svg xmlns="http://www.w3.org/2000/svg" width="1em" height="1em" viewBox="0 0 24 24" fill="none">
          <path stroke="currentColor" stroke-width="2" d="M16 2h-4v5.5L14 6l2 1.5z"/>
          <path stroke="currentColor" stroke-width="2" d="M20 2v20H4V2z"/>
        </svg>
      </span>
      <span class="nav-text">Course &amp; Section</span>
    </a>

    <a href="<?= $root ?>View/Registrar/schedule.php" class="nav-item<?= ($activePage ?? '') === 'schedule' ? ' active' : '' ?>" data-page="schedule">
      <span class="nav-icon">
        <svg xmlns="http://www.w3.org/2000/svg" width="1em" height="1em" viewBox="0 0 24 24" fill="currentColor">
          <path d="M0 0h24v24H0z" fill="none"/>
          <path d="M12 14a1 1 0 1 0-1-1a1 1 0 0 0 1 1m5 0a1 1 0 1 0-1-1a1 1 0 0 0 1 1m-5 4a1 1 0 1 0-1-1a1 1 0 0 0 1 1m5 0a1 1 0 1 0-1-1a1 1 0 0 0 1 1M7 14a1 1 0 1 0-1-1a1 1 0 0 0 1 1M19 4h-1V3a1 1 0 0 0-2 0v1H8V3a1 1 0 0 0-2 0v1H5a3 3 0 0 0-3 3v12a3 3 0 0 0 3 3h14a3 3 0 0 0 3-3V7a3 3 0 0 0-3-3m1 15a1 1 0 0 1-1 1H5a1 1 0 0 1-1-1v-9h16Zm0-11H4V7a1 1 0 0 1 1-1h14a1 1 0 0 1 1 1ZM7 18a1 1 0 1 0-1-1a1 1 0 0 0 1 1"/>
        </svg>
      </span>
      <span class="nav-text">Schedule</span>
    </a>

    <a href="<?= $root ?>View/Registrar/add_drop_subject.php" class="nav-item<?= ($activePage ?? '') === 'addDrop' ? ' active' : '' ?>" data-page="addDrop">
      <span class="nav-icon">
        <svg xmlns="http://www.w3.org/2000/svg" width="1em" height="1em" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
          <path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/>
          <circle cx="9" cy="7" r="4"/>
          <line x1="19" y1="8" x2="19" y2="14"/>
          <line x1="22" y1="11" x2="16" y2="11"/>
        </svg>
      </span>
      <span class="nav-text">Add/Drop Subject</span>
    </a>



    <?php if ($isHead): ?>
    <a href="<?= $root ?>View/Registrar/pending_readmissions.php" class="nav-item<?= ($activePage ?? '') === 'readmission' ? ' active' : '' ?>" data-page="readmission">
      <span class="nav-icon">
        <svg xmlns="http://www.w3.org/2000/svg" width="1em" height="1em" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
          <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/>
          <circle cx="9" cy="7" r="4"/>
          <path d="M23 21v-2a4 4 0 0 0-3-3.87"/>
          <path d="M16 3.13a4 4 0 0 1 0 7.75"/>
        </svg>
      </span>
      <span class="nav-text">Readmissions</span>
      <?php if ($pendingReadmissionCount > 0): ?>
        <span class="nav-badge"><?= $pendingReadmissionCount ?></span>
      <?php endif; ?>
    </a>
    <?php else: ?>
    <a href="<?= $root ?>View/Registrar/readmission_request.php" class="nav-item<?= ($activePage ?? '') === 'readmission' ? ' active' : '' ?>" data-page="readmission">
      <span class="nav-icon">
        <svg xmlns="http://www.w3.org/2000/svg" width="1em" height="1em" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
          <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/>
          <circle cx="9" cy="7" r="4"/>
          <path d="M23 21v-2a4 4 0 0 0-3-3.87"/>
          <path d="M16 3.13a4 4 0 0 1 0 7.75"/>
        </svg>
      </span>
      <span class="nav-text">Readmission Request</span>
    </a>
    <a href="<?= $root ?>View/Registrar/pending_approval.php" class="nav-item<?= ($activePage ?? '') === 'pending' ? ' active' : '' ?>" data-page="pending">
      <span class="nav-icon">
        <svg xmlns="http://www.w3.org/2000/svg" width="1em" height="1em" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
          <circle cx="12" cy="12" r="10"/>
          <polyline points="12 6 12 12 16 14"/>
        </svg>
      </span>
      <span class="nav-text">Pending Approvals</span>
      <?php if ($pendingCount > 0): ?>
        <span class="nav-badge"><?= $pendingCount ?></span>
      <?php endif; ?>
    </a>
    <?php endif; ?>

  </nav>

  <!-- COMMUNICATION -->
  <nav class="nav-section">
    <div class="nav-label">Communication</div>

    <a href="<?= $root ?>View/Registrar/messages.php" class="nav-item<?= ($activePage ?? '') === 'messages' ? ' active' : '' ?>" data-page="messages">
      <span class="nav-icon">
        <svg xmlns="http://www.w3.org/2000/svg" width="1em" height="1em" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
          <path d="M4 4h16v12H7l-3 3z"/>
        </svg>
      </span>
      <span class="nav-text">Messages</span>
      <?php if ($unreadCount > 0): ?>
        <span class="nav-badge"><?= $unreadCount ?></span>
      <?php endif; ?>
    </a>

  </nav>

  <!-- User Footer -->
  <div class="sidebar-user">
    <div class="user-avatar"><?= $initials ?></div>
    <div class="user-info">
      <div class="user-name"><?= htmlspecialchars($_SESSION['full_name'] ?? $roleLabel) ?></div>
      <div class="user-role"><?= $roleLabel ?></div>
    </div>
  </div>

</aside>

<div class="main-content">
  <header class="top-header">
    <h1 class="header-title" id="page-title"><?= $pageTitle ?? '' ?></h1>
    <div class="header-actions">
      <button class="btn-notif">🔔<?php if ($pendingCount > 0): ?><span class="notif-dot"></span><?php endif; ?></button>
      <div class="avatar-wrapper" id="avatarWrapper">
        <div class="header-avatar" id="avatarBtn"><?= $initials ?></div>
        <div class="avatar-dropdown" id="avatarDropdown">
          <a href="<?= $root ?>view/profile.php" class="dropdown-item">
            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="currentColor">
              <circle cx="12" cy="6" r="4"/>
              <path d="M20 17.5c0 2.485 0 4.5-8 4.5s-8-2.015-8-4.5S7.582 13 12 13s8 2.015 8 4.5" opacity=".5"/>
            </svg>
            My profile
          </a>
          <a href="/SIAdrafts/Backend/api/logout.php" class="dropdown-item dropdown-item--danger">
            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
              <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/>
              <polyline points="16 17 21 12 16 7"/>
              <line x1="21" y1="12" x2="9" y2="12"/>
            </svg>
            Log out
          </a>
        </div>
      </div>
    </div>
  </header>