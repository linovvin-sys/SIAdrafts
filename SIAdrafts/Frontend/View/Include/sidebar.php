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

/* mdi: (Material Design Icons via Iconify) — same icon system Student's
   own nav already uses (see Student/Include/header.php's $_tabs), swapped
   in for the Bootstrap Icons this sidebar used before. Bootstrap Icons
   stay in use elsewhere in the app (buttons, inline glyphs); this map is
   scoped to the nav rail, the single most-repeated icon surface since it
   renders on every page. */
$iconMap = [
    'dashboard'   => 'mdi:view-grid',
    'users'       => 'mdi:account-group',
    'settings'    => 'mdi:cog',
    'admission'   => 'mdi:account-check',
    'enrollment'  => 'mdi:notebook-check',
    'enrolees'    => 'mdi:school',
    'course'      => 'mdi:book-open-page-variant',
    'sections'    => 'mdi:account-group',
    'subjects'    => 'mdi:bookmark-multiple',
    'professors'  => 'mdi:account-tie',
    'schedule'    => 'mdi:calendar-week',
    'addDrop'     => 'mdi:swap-horizontal',
    'approval'    => 'mdi:checkbox-marked-outline',
    'readmission' => 'mdi:autorenew',
    'messages'    => 'mdi:message-text',
    'notifications' => 'mdi:bell',
    'treasury'    => 'mdi:cash-multiple',
    'revenue'     => 'mdi:chart-line',
    'paid'        => 'mdi:check-circle',
    'process'     => 'mdi:progress-clock',
    'unpaid'      => 'mdi:alert-circle',
];
?>
<!-- ===== SIDEBAR ===== -->
<aside class="sidebar" id="appSidebar">

  <div class="sidebar-brand" id="sidebar-toggle" title="Toggle sidebar">
    <div class="brand-icon"><iconify-icon icon="mdi:school"></iconify-icon></div>
    <div class="brand-name">Edu<span>School</span></div>
  </div>

  <nav class="nav-section">
    <div class="nav-label">Menu</div>
    <?php foreach ($navItems as $item): ?>
      <?php if (isset($item['group'])): ?>
        <?php
          $groupOpen = false;
          foreach ($item['items'] as $child) {
              if (($activePage ?? '') === $child['page']) { $groupOpen = true; break; }
          }
        ?>
        <div class="nav-group<?= $groupOpen ? ' open' : '' ?>">
          <button type="button" class="nav-item nav-group-toggle" aria-expanded="<?= $groupOpen ? 'true' : 'false' ?>">
            <span class="nav-icon"><iconify-icon icon="<?= $iconMap[$item['icon']] ?? 'mdi:circle-small' ?>"></iconify-icon></span>
            <span class="nav-text"><?= htmlspecialchars($item['group'], ENT_QUOTES) ?></span>
            <span class="nav-group-chevron"><iconify-icon icon="mdi:chevron-down"></iconify-icon></span>
          </button>
          <div class="nav-group-items">
            <?php foreach ($item['items'] as $child): ?>
              <a href="<?= htmlspecialchars($child['url'], ENT_QUOTES) ?>"
                 class="nav-item nav-subitem<?= ($activePage ?? '') === $child['page'] ? ' active' : '' ?>"
                 data-page="<?= htmlspecialchars($child['page'], ENT_QUOTES) ?>">
                <span class="nav-icon"><iconify-icon icon="<?= $iconMap[$child['icon']] ?? 'mdi:circle-small' ?>"></iconify-icon></span>
                <span class="nav-text"><?= htmlspecialchars($child['label'], ENT_QUOTES) ?></span>
                <?php if (($child['badge'] ?? null) === 'pending' && $pendingCount > 0): ?>
                  <span class="nav-badge"><?= $pendingCount ?></span>
                <?php elseif (($child['badge'] ?? null) === 'unread' && $unreadCount > 0): ?>
                  <span class="nav-badge"><?= $unreadCount ?></span>
                <?php endif; ?>
              </a>
            <?php endforeach; ?>
          </div>
        </div>
      <?php else: ?>
        <a href="<?= htmlspecialchars($item['url'], ENT_QUOTES) ?>"
           class="nav-item<?= ($activePage ?? '') === $item['page'] ? ' active' : '' ?>"
           data-page="<?= htmlspecialchars($item['page'], ENT_QUOTES) ?>">
          <span class="nav-icon"><iconify-icon icon="<?= $iconMap[$item['icon']] ?? 'mdi:circle-small' ?>"></iconify-icon></span>
          <span class="nav-text"><?= htmlspecialchars($item['label'], ENT_QUOTES) ?></span>
          <?php if (($item['badge'] ?? null) === 'pending' && $pendingCount > 0): ?>
            <span class="nav-badge"><?= $pendingCount ?></span>
          <?php elseif (($item['badge'] ?? null) === 'unread' && $unreadCount > 0): ?>
            <span class="nav-badge"><?= $unreadCount ?></span>
          <?php endif; ?>
        </a>
      <?php endif; ?>
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

<style>
  /* ---- entrance: nav items fade + slide in, staggered ---- */
  .nav-section > .nav-item,
  .nav-section > .nav-group {
    opacity: 0;
    transform: translateX(-8px);
    animation: navItemIn 0.35s ease forwards;
  }
  .nav-section > *:nth-child(1)  { animation-delay: 0.02s; }
  .nav-section > *:nth-child(2)  { animation-delay: 0.06s; }
  .nav-section > *:nth-child(3)  { animation-delay: 0.10s; }
  .nav-section > *:nth-child(4)  { animation-delay: 0.14s; }
  .nav-section > *:nth-child(5)  { animation-delay: 0.18s; }
  .nav-section > *:nth-child(6)  { animation-delay: 0.22s; }
  .nav-section > *:nth-child(7)  { animation-delay: 0.26s; }
  @keyframes navItemIn {
    to { opacity: 1; transform: translateX(0); }
  }
  @media (prefers-reduced-motion: reduce) {
    .nav-section > .nav-item,
    .nav-section > .nav-group { animation: none; opacity: 1; transform: none; }
  }

  /* ---- nav-item hover/press polish ---- */
  .nav-item {
    transition: background var(--transition, 0.15s), color var(--transition, 0.15s),
                transform 0.12s ease, border-left-color 0.2s ease;
  }
  .nav-item:hover {
    transform: translateX(3px);
  }
  .nav-item:active {
    transform: translateX(3px) scale(0.98);
  }
  .nav-icon i {
    transition: transform 0.2s ease;
  }
  .nav-item:hover .nav-icon i {
    transform: scale(1.15);
  }

  /* ---- collapsible group ---- */
  .nav-group-toggle {
    width: 100%;
    background: none;
    border: none;
    border-left: 3px solid transparent;
    font: inherit;
    text-align: left;
    cursor: pointer;
  }
  .nav-group-chevron {
    margin-left: auto;
    display: inline-flex;
    transition: transform 0.25s cubic-bezier(0.4, 0, 0.2, 1);
  }
  .nav-group.open > .nav-group-toggle .nav-group-chevron {
    transform: rotate(180deg);
  }
  .nav-group-items {
    display: flex;
    flex-direction: column;
    max-height: 0;
    overflow: hidden;
    opacity: 0;
    transition: max-height 0.3s cubic-bezier(0.4, 0, 0.2, 1), opacity 0.25s ease;
  }
  .nav-group.open > .nav-group-items {
    opacity: 1;
  }
  .nav-subitem {
    padding-left: 44px;
    font-size: 13px;
  }

  /* ---- badge pulse for anything needing attention ---- */
  .nav-badge {
    animation: badgePop 0.4s cubic-bezier(0.34, 1.56, 0.64, 1);
  }
  @keyframes badgePop {
    0% { transform: scale(0); }
    100% { transform: scale(1); }
  }

  /* ---- page content fade-in ---- */
  .main-content {
    animation: contentFadeIn 0.3s ease;
  }
  @keyframes contentFadeIn {
    from { opacity: 0; transform: translateY(6px); }
    to { opacity: 1; transform: translateY(0); }
  }
</style>
<script>
  function setGroupHeight(group, animate) {
    var items = group.querySelector('.nav-group-items');
    if (group.classList.contains('open')) {
      items.style.maxHeight = items.scrollHeight + 'px';
    } else {
      items.style.maxHeight = '0px';
    }
  }

  document.querySelectorAll('.nav-group').forEach(function (group) {
    setGroupHeight(group);
  });

  document.querySelectorAll('.nav-group-toggle').forEach(function (btn) {
    btn.addEventListener('click', function () {
      var group = btn.closest('.nav-group');
      var isOpen = group.classList.toggle('open');
      btn.setAttribute('aria-expanded', isOpen ? 'true' : 'false');
      setGroupHeight(group);
    });
  });

  // Re-measure open groups after the entrance animation finishes, in case
  // font loading (icon font) shifted layout height.
  window.addEventListener('load', function () {
    document.querySelectorAll('.nav-group.open').forEach(function (group) {
      setGroupHeight(group);
    });
  });

  // Mobile drawer: hamburger opens the sidebar as an off-canvas panel;
  // overlay click or Escape closes it. Desktop behavior is untouched.
  document.addEventListener('DOMContentLoaded', function () {
    var toggle = document.getElementById('menuToggle');
    var sidebar = document.getElementById('appSidebar');
    var overlay = document.getElementById('sidebarOverlay');
    if (!toggle || !sidebar || !overlay) return;

    function setOpen(open) {
      sidebar.classList.toggle('mobile-open', open);
      overlay.classList.toggle('visible', open);
      toggle.setAttribute('aria-expanded', open ? 'true' : 'false');
      if (open) sidebar.querySelector('.nav-item')?.focus?.();
    }
    toggle.addEventListener('click', function () {
      setOpen(!sidebar.classList.contains('mobile-open'));
    });
    overlay.addEventListener('click', function () { setOpen(false); });
    document.addEventListener('keydown', function (e) {
      if (e.key === 'Escape' && sidebar.classList.contains('mobile-open')) {
        setOpen(false);
        toggle.focus();
      }
    });
  });

  // Dark mode toggle. The initial theme is already applied before first
  // paint by the inline script in Include/header.php (reads the same
  // localStorage key) — this just handles the click: flip explicitly
  // between light/dark and persist the choice, overriding whatever the
  // OS preference was. Needs DOMContentLoaded same as the drawer above —
  // #themeToggle lives in .main-content, further down the page than
  // this <script> tag.
  document.addEventListener('DOMContentLoaded', function () {
    var themeToggle = document.getElementById('themeToggle');
    if (!themeToggle) return;
    themeToggle.addEventListener('click', function () {
      var isDark = document.documentElement.getAttribute('data-theme') === 'dark'
        || (document.documentElement.getAttribute('data-theme') !== 'light'
            && window.matchMedia('(prefers-color-scheme: dark)').matches);
      var next = isDark ? 'light' : 'dark';
      document.documentElement.setAttribute('data-theme', next);
      try { localStorage.setItem('sia_theme', next); } catch (e) {}
    });
  });
</script>

<div class="sidebar-overlay" id="sidebarOverlay"></div>

<div class="main-content">
  <header class="top-header">
    <div style="display:flex; align-items:center; gap:10px; min-width:0;">
      <button class="menu-toggle" id="menuToggle" aria-label="Open navigation menu" aria-expanded="false" aria-controls="appSidebar">
        <iconify-icon icon="mdi:menu"></iconify-icon>
      </button>
      <h1 class="header-title" id="page-title"><?= htmlspecialchars($pageTitle ?? '', ENT_QUOTES) ?></h1>
    </div>
    <div class="header-actions">
      <button class="btn-notif" id="themeToggle" aria-label="Toggle dark mode" title="Toggle dark mode">
        <iconify-icon icon="mdi:white-balance-sunny" class="theme-icon theme-icon-sun"></iconify-icon>
        <iconify-icon icon="mdi:moon-waning-crescent" class="theme-icon theme-icon-moon"></iconify-icon>
      </button>
      <button class="btn-notif" aria-label="Notifications"><iconify-icon icon="mdi:bell-outline"></iconify-icon><?php if ($pendingCount > 0): ?><span class="notif-dot"></span><?php endif; ?></button>
      <div class="avatar-wrapper" id="avatarWrapper">
        <div class="header-avatar" id="avatarBtn"><?= $initials ?></div>
        <div class="avatar-dropdown" id="avatarDropdown">
          <a href="/SIAdrafts/Frontend/View/profile.php" class="dropdown-item">
            <iconify-icon icon="mdi:account-circle-outline"></iconify-icon> My profile
          </a>
          <a href="/SIAdrafts/Backend/api/Auth/logout.php" class="dropdown-item dropdown-item--danger">
            <iconify-icon icon="mdi:logout"></iconify-icon> Log out
          </a>
        </div>
      </div>
    </div>
  </header>
