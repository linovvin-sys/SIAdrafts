<?php
$in_view = strpos($_SERVER['PHP_SELF'], '/view/') !== false;
$root = '/SIAdrafts/Frontend/';
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

  <a href="<?= $root ?>View/Admin/admin_dashboard.php" class="nav-item<?= ($activePage ?? '') === 'dashboard' ? ' active' : '' ?>" data-page="dashboard">
    <span class="nav-icon">
      <svg xmlns="http://www.w3.org/2000/svg" width="1em" height="1em"
          viewBox="0 0 24 24" fill="currentColor">
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

    <a href="<?= $root ?>View/Admin/manage_user.php" class="nav-item<?= ($activePage ?? '') === 'manage_user' ? ' active' : '' ?>" data-page="manage_user">
      <span class="nav-icon">
        <svg xmlns="http://www.w3.org/2000/svg" width="1em" height="1em"
            viewBox="0 0 24 24" fill="currentColor">
          <path d="M0 0h24v24H0z" fill="none"/>
          <circle cx="12" cy="6" r="4" fill="currentColor"/>
          <path fill="currentColor"
                d="M20 17.5c0 2.485 0 4.5-8 4.5s-8-2.015-8-4.5S7.582 13 12 13s8 2.015 8 4.5"
                opacity=".5"/>
        </svg>
      </span>
      <span class="nav-text">Manage User</span>
    </a>

  </nav>

  <!-- User Footer -->
  <div class="sidebar-user">
    <div class="user-avatar">AD</div>
    <div class="user-info">
      <div class="user-name">Admin</div>
      <div class="user-role">System Administrator</div>
    </div>
  </div>

</aside>

<div class="main-content">
  <header class="top-header">
    <h1 class="header-title" id="page-title"><?= $pageTitle ?? '' ?></h1>
    <div class="header-actions">
      <button class="btn-notif">🔔<span class="notif-dot"></span></button>
      <div class="avatar-wrapper" id="avatarWrapper">
        <div class="header-avatar" id="avatarBtn">AD</div>
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

