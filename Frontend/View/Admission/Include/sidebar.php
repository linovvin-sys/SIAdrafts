<?php
$root = '/SIAdrafts/Frontend/';
$_tFullName = htmlspecialchars($_SESSION['full_name'] ?? 'Treasury', ENT_QUOTES);
$_tInitials = strtoupper(substr($_tFullName, 0, 2));
?>
<!-- ===== TREASURY SIDEBAR ===== -->
<aside class="sidebar">

  <!-- Brand -->
  <div class="sidebar-brand" id="sidebar-toggle" title="Toggle sidebar">
    <div class="brand-icon">🎓</div>
    <div class="brand-name">Edu<span>School</span></div>
  </div>

  <!-- MAIN -->
  <nav class="nav-section">
    <div class="nav-label">Main</div>

    <a href="<?= $root ?>View/Admission/treasury.php" class="nav-item<?= ($activePage ?? '') === 'treasury' ? ' active' : '' ?>" data-page="treasury">
      <span class="nav-icon">
        <svg xmlns="http://www.w3.org/2000/svg" width="1em" height="1em" viewBox="0 0 24 24" fill="currentColor">
          <path d="M0 0h24v24H0z" fill="none"/>
          <path d="M12 1a11 11 0 1 0 11 11A11.013 11.013 0 0 0 12 1m1 16.93V19h-2v-1.07a4 4 0 0 1-3-3.87h2a2 2 0 0 0 2 2h.5a1.5 1.5 0 0 0 0-3H11.5a3.5 3.5 0 0 1 0-7H12V5h2v1.07a4 4 0 0 1 3 3.87h-2a2 2 0 0 0-2-2h-.5a1.5 1.5 0 0 0 0 3h1a3.5 3.5 0 0 1 0 7z"/>
        </svg>
      </span>
      <span class="nav-text">Treasury</span>
    </a>

  </nav>

  <!-- REVENUE -->
  <nav class="nav-section">
    <div class="nav-label">Revenue</div>

    <a href="<?= $root ?>View/Admission/revenue_paid.php" class="nav-item<?= ($activePage ?? '') === 'revenue_paid' ? ' active' : '' ?>" data-page="revenue_paid">
      <span class="nav-icon">
        <svg xmlns="http://www.w3.org/2000/svg" width="1em" height="1em" viewBox="0 0 24 24" fill="currentColor">
          <path d="M0 0h24v24H0z" fill="none"/>
          <path d="M9 16.17L4.83 12l-1.42 1.41L9 19 21 7l-1.41-1.41z"/>
        </svg>
      </span>
      <span class="nav-text">Paid</span>
    </a>

    <a href="<?= $root ?>View/Admission/revenue_process.php" class="nav-item<?= ($activePage ?? '') === 'revenue_process' ? ' active' : '' ?>" data-page="revenue_process">
      <span class="nav-icon">
        <svg xmlns="http://www.w3.org/2000/svg" width="1em" height="1em" viewBox="0 0 24 24" fill="currentColor">
          <path d="M0 0h24v24H0z" fill="none"/>
          <path d="M12 2a10 10 0 1 0 10 10A10 10 0 0 0 12 2m1 15h-2v-2h2zm0-4h-2V7h2z"/>
        </svg>
      </span>
      <span class="nav-text">Process</span>
    </a>

  </nav>

  <!-- User Footer -->
  <div class="sidebar-user">
    <div class="user-avatar"><?= $_tInitials ?></div>
    <div class="user-info">
      <div class="user-name"><?= $_tFullName ?></div>
      <div class="user-role">Treasury</div>
    </div>
  </div>

</aside>


<div class="main-content">
  <header class="top-header">
    <h1 class="header-title" id="page-title"><?= $pageTitle ?? '' ?></h1>
    <div class="header-actions">
      <button class="btn-notif">🔔<span class="notif-dot"></span></button>
      <div class="avatar-wrapper" id="avatarWrapper">
        <div class="header-avatar" id="avatarBtn"><?= $_tInitials ?></div>
        <div class="avatar-dropdown" id="avatarDropdown">
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