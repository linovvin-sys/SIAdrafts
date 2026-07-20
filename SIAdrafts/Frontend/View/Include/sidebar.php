<?php
require_once __DIR__ . '/../../../Backend/require_role.php';
require_once __DIR__ . '/../../../Backend/db.php';

$navConfig   = require __DIR__ . '/../../../Backend/nav_config.php';
$currentRole = strtolower(trim($_SESSION['role_name'] ?? ''));
$navItems    = $navConfig[$currentRole] ?? [];

$nameParts = preg_split('/\s+/', trim($_SESSION['full_name'] ?? ''));
$initials  = '';
foreach ($nameParts as $part) {
    if ($part !== '') $initials .= strtoupper($part[0]);
}
$initials  = substr($initials, 0, 2) ?: 'US';
$fullName  = htmlspecialchars($_SESSION['full_name'] ?? '', ENT_QUOTES);
$roleLabel = ucwords($currentRole);

$pendingCount = 0;
if ($currentRole === 'head registrar') {
    $db   = new Database();
    $conn = $db->connect();
    $res  = $conn->query("SELECT COUNT(*) AS cnt FROM schedule WHERE status = 'Pending'");
    $pendingCount = (int)($res->fetch_assoc()['cnt'] ?? 0);
    $db->close();
}

$unreadCount = 0;
if (!empty($_SESSION['user_id']) && in_array($currentRole, ['registrar staff', 'head registrar'], true)) {
    $db      = new Database();
    $conn    = $db->connect();
    $msgStmt = $conn->prepare("SELECT COUNT(*) AS cnt FROM messages WHERE recipient_id = ? AND read_at IS NULL");
    $msgStmt->bind_param('i', $_SESSION['user_id']);
    $msgStmt->execute();
    $unreadCount = (int)($msgStmt->get_result()->fetch_assoc()['cnt'] ?? 0);
    $msgStmt->close();
    $db->close();
}

$iconMap = [
    'dashboard'   => 'bi-grid-1x2-fill',
    'users'       => 'bi-people-fill',
    'settings'    => 'bi-gear-fill',
    'admission'   => 'bi-person-check-fill',
    'enrollment'  => 'bi-journal-check',
    'enrolees'    => 'bi-mortarboard-fill',
    'course'      => 'bi-book-half',
    'schedule'    => 'bi-calendar3',
    'addDrop'     => 'bi-arrow-left-right',
    'approval'    => 'bi-check2-square',
    'readmission' => 'bi-arrow-repeat',
    'messages'    => 'bi-chat-dots-fill',
];
?>
<!-- ===== SIDEBAR ===== -->
<aside class="sidebar">

  <div class="sidebar-brand" id="sidebar-toggle" title="Toggle sidebar">
    <div class="brand-icon">🎓</div>
    <div class="brand-name">Edu<span>School</span></div>
  </div>

  <nav class="nav-section">
    <div class="nav-label">Menu</div>
    <?php foreach ($navItems as $item): ?>
      <a href="<?= htmlspecialchars($item['url'], ENT_QUOTES) ?>"
         class="nav-item<?= ($activePage ?? '') === $item['page'] ? ' active' : '' ?>"
         data-page="<?= htmlspecialchars($item['page'], ENT_QUOTES) ?>">
        <span class="nav-icon"><i class="bi <?= $iconMap[$item['icon']] ?? 'bi-dot' ?>"></i></span>
        <span class="nav-text"><?= htmlspecialchars($item['label'], ENT_QUOTES) ?></span>
        <?php if (($item['badge'] ?? null) === 'pending' && $pendingCount > 0): ?>
          <span class="nav-badge"><?= $pendingCount ?></span>
        <?php elseif (($item['badge'] ?? null) === 'unread' && $unreadCount > 0): ?>
          <span class="nav-badge"><?= $unreadCount ?></span>
        <?php endif; ?>
      </a>
    <?php endforeach; ?>
  </nav>

  <div class="sidebar-user">
    <div class="user-avatar"><?= $initials ?></div>
    <div class="user-info">
      <div class="user-name"><?= $fullName ?: $roleLabel ?></div>
      <div class="user-role"><?= $roleLabel ?></div>
    </div>
  </div>

</aside>

<div class="main-content">
  <header class="top-header">
    <h1 class="header-title" id="page-title"><?= htmlspecialchars($pageTitle ?? '', ENT_QUOTES) ?></h1>
    <div class="header-actions">
      <button class="btn-notif">🔔<?php if ($pendingCount > 0): ?><span class="notif-dot"></span><?php endif; ?></button>
      <div class="avatar-wrapper" id="avatarWrapper">
        <div class="header-avatar" id="avatarBtn"><?= $initials ?></div>
        <div class="avatar-dropdown" id="avatarDropdown">
          <a href="/SIAdrafts/Frontend/View/profile.php" class="dropdown-item">
            <i class="bi bi-person-circle"></i> My profile
          </a>
          <a href="/SIAdrafts/Backend/api/logout.php" class="dropdown-item dropdown-item--danger">
            <i class="bi bi-box-arrow-right"></i> Log out
          </a>
        </div>
      </div>
    </div>
  </header>
