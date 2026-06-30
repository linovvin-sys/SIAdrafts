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


  <a href="<?= $root ?>View/Admin/admin_admission.php" class="nav-item<?= ($activePage ?? '') === 'admission' ? ' active' : '' ?>" data-page="admission">
    <span class="nav-icon">
      <svg xmlns="http://www.w3.org/2000/svg" width="1em" height="1em"
          viewBox="0 0 24 24" fill="currentColor">
        <path d="M0 0h24v24H0z" fill="none"/>
        <path d="M15.154 18.789H16.5v-1.347h-1.346zm2.173 0h1.346v-1.347h-1.346zm2.173 0h1.346v-1.347H19.5zM4 20V4h16v7.566q-.263-.091-.504-.148q-.24-.056-.496-.112V5H5v14h6.28q.037.28.094.521q.057.24.147.479zm1-2v1V5v6.306v-.075zm2.5-1.73h3.96q.055-.257.15-.497l.2-.504H7.5zm0-3.77h6.58q.493-.346.971-.586q.478-.241 1.026-.378V11.5H7.5zm0-3.77h9v-1h-9zM18 22.117q-1.671 0-2.835-1.165Q14 19.787 14 18.116t1.165-2.836T18 14.116t2.836 1.164T22 18.116q0 1.67-1.164 2.835Q19.67 22.116 18 22.116"/>
      </svg>
    </span>
    <span class="nav-text">Admission</span>
  </a>


  <a href="<?= $root ?>View/Admin/admin_enrollment.php" class="nav-item<?= ($activePage ?? '') === 'enrollment' ? ' active' : '' ?>" data-page="enrollment">
    <span class="nav-icon">
      <svg xmlns="http://www.w3.org/2000/svg" width="1em" height="1em"
          viewBox="0 0 2048 2048" fill="currentColor">
        <path d="M0 0h2048v2048H0z" fill="none"/>
        <path d="M1848 896q42 0 78 15t64 42t42 63t16 78q0 39-15 76t-43 65l-717 719l-377 94l94-377l717-718q28-28 65-42t76-15m51 249q21-21 21-51q0-31-20-50t-52-20q-14 0-27 4t-23 15l-692 694l-34 135l135-34zM640 896H512V768h128zm896 0H768V768h768zM512 1152h128v128H512zm128-640H512V384h128zm896 0H768V384h768zM384 1664h443l-32 128H256V0h1536v743q-67 10-128 44V128H384zm384-512h514l-128 128H768z"/>
      </svg>
    </span>
    <span class="nav-text">Enrollment</span>
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


    <a href="<?= $root ?>View/Admin/courses.php" class="nav-item<?= ($activePage ?? '') === 'courses' ? ' active' : '' ?>" data-page="courses">
      <span class="nav-icon">
        <svg xmlns="http://www.w3.org/2000/svg" width="1em" height="1em"
            viewBox="0 0 24 24" fill="currentColor">
          <path d="M0 0h24v24H0z" fill="none"/>
          <g fill="none">
            <path d="M20 2v20H4V2z"/>
            <path d="M16 2h-4v5.5L14 6l2 1.5z"/>
            <path stroke="currentColor" stroke-width="2" d="M16 2h-4v5.5L14 6l2 1.5z"/>
            <path stroke="currentColor" stroke-width="2" d="M20 2v20H4V2z"/>
          </g>
        </svg>
      </span>
      <span class="nav-text">Course and Sections</span>
    </a>


    <a href="<?= $root ?>View/Admin/schedule.php" class="nav-item<?= ($activePage ?? '') === 'schedule' ? ' active' : '' ?>" data-page="schedule">
      <span class="nav-icon">
        <svg xmlns="http://www.w3.org/2000/svg" width="1em" height="1em"
            viewBox="0 0 24 24" fill="currentColor">
          <path d="M0 0h24v24H0z" fill="none"/>
          <path d="M12 14a1 1 0 1 0-1-1a1 1 0 0 0 1 1m5 0a1 1 0 1 0-1-1a1 1 0 0 0 1 1m-5 4a1 1 0 1 0-1-1a1 1 0 0 0 1 1m5 0a1 1 0 1 0-1-1a1 1 0 0 0 1 1M7 14a1 1 0 1 0-1-1a1 1 0 0 0 1 1M19 4h-1V3a1 1 0 0 0-2 0v1H8V3a1 1 0 0 0-2 0v1H5a3 3 0 0 0-3 3v12a3 3 0 0 0 3 3h14a3 3 0 0 0 3-3V7a3 3 0 0 0-3-3m1 15a1 1 0 0 1-1 1H5a1 1 0 0 1-1-1v-9h16Zm0-11H4V7a1 1 0 0 1 1-1h14a1 1 0 0 1 1 1ZM7 18a1 1 0 1 0-1-1a1 1 0 0 0 1 1"/>
        </svg>
      </span>
      <span class="nav-text">Schedule</span>
    </a>

  </nav>

  <!-- REPORTS -->
  <nav class="nav-section">
    <div class="nav-label">Reports</div>

    <a href="<?= $root ?>View/Admin/revenue.php" class="nav-item<?= ($activePage ?? '') === 'revenue' ? ' active' : '' ?>" data-page="revenue">
      <span class="nav-icon">
        <svg xmlns="http://www.w3.org/2000/svg" width="1em" height="1em" viewBox="0 0 512 512">
          <path d="M0 0h512v512H0z" fill="none" />
          <path fill="currentColor" d="M18.78 19.5v79.656c44.684 5.582 81.517 24.966 116.657 47.156l-24.75 20.063L212.47 218.28L184.53 106.5l-25.905 21c-20.225-40.01-42.778-77.73-72.75-108zm277.376 0c-15.624 28.765-29.207 58.126-41.78 88.156l-30.19-6.406l25.94 112.25l67.06-92.5l-29.592-6.28c33.29-34.747 67.597-67.793 108.062-95.22zm197.5 93.844c-37.988 2.482-72.04 19.677-105.03 40.906l-12.47-32.53l-80.062 82.843l114.094 5.937l-13.25-34.563c32.24-.934 64.478 1.827 96.718 21.375zm-194.03 128.03c-5.28.12-10.21 2.416-16.938 9.595l-6.563 6.968l-6.813-6.72c-7.387-7.28-13.216-9.29-19.125-9.03c-5.908.26-12.855 3.367-20.625 9.656l-6.218 5.03l-5.906-5.374c-8.9-8.052-16.485-10.438-23.75-10.063c-5.288.274-10.775 2.266-16.25 5.75l40.968 73.688c15.454 9.452 47.033 13.007 68.75 2.063l39.594-73.344c-7.51-3.062-14.26-6.202-20.094-7.406c-2.112-.437-4.072-.756-5.97-.813a21 21 0 0 0-1.06 0m-89.97 96.19c-18.035 12.742-32.516 34.718-38.125 66.905c-5.435 31.196 3.128 52.265 18.282 66.624c15.155 14.36 37.902 21.737 61 21.437c23.1-.3 46.136-8.31 61.625-22.936c15.49-14.627 24.25-35.426 19.282-65.188c-5.137-30.757-18.4-52.148-35.19-65.094c-28.482 15.056-64.094 11.856-86.874-1.75z" />
        </svg>
      </span>
      <span class="nav-text">Revenue</span>
    </a>


    <a href="<?= $root ?>View/Admin/total_enrolees.php" 
      class="nav-item<?= ($activePage ?? '') === 'total_enrolees' ? ' active' : '' ?>" 
      data-page="total_enrolees">
      <span class="nav-icon">
        <svg xmlns="http://www.w3.org/2000/svg" width="1em" height="1em" viewBox="0 0 24 24">
          <path d="M0 0h24v24H0z" fill="none" />
          <g fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5">
            <path d="M2.5 6L8 4l5.5 2L11 7.5V9s-.667-.5-3-.5S5 9 5 9V7.5zm0 0v4" />
            <path d="M11 8.5v.889c0 1.718-1.343 3.111-3 3.111s-3-1.393-3-3.111V8.5m10.318 2.53s.485-.353 2.182-.353s2.182.352 2.182.352m-4.364 0V10L13.5 9l4-1.5l4 1.5l-1.818 1v1.03m-4.364 0v.288a2.182 2.182 0 1 0 4.364 0v-.289M4.385 15.926c-.943.527-3.416 1.602-1.91 2.947C3.211 19.53 4.03 20 5.061 20h5.878c1.03 0 1.85-.47 2.586-1.127c1.506-1.345-.967-2.42-1.91-2.947c-2.212-1.235-5.018-1.235-7.23 0M16 20h3.705c.773 0 1.387-.376 1.939-.902c1.13-1.076-.725-1.936-1.432-2.357A5.34 5.34 0 0 0 16 16.214" />
          </g>
        </svg>
      </span>
      <span class="nav-text">Total Enrolees</span>
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
          <a href="#" class="dropdown-item dropdown-item--danger">
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

