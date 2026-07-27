<?php
$pageTitle  = "ENROLLMENT";
$activePage = "enrollment";

require_once '../../../Backend/auth.php';
require_once '../../../Backend/roles.php';
require_once '../../../Backend/require_role.php';
require_role([ROLE_ADMISSION, ROLE_STAFF, ROLE_ADMIN]);

// Show success flash if returning from a completed enrollment
$enrolled_ref = isset($_GET['enrolled'], $_GET['ref']) ? (int)$_GET['ref'] : null;

// Quick stat strip so the search screen isn't just a lone card in empty
// space -- gives staff useful context (today's activity) while they type.
require_once __DIR__ . '/../../../Backend/db.php';
$db   = new Database();
$conn = $db->connect();
$quickStats = ['today' => 0, 'pending_payment' => 0, 'total' => 0];
$r = $conn->query("SELECT COUNT(*) AS c FROM enrollment WHERE DATE(created_at) = CURDATE()");
if ($r) $quickStats['today'] = (int)$r->fetch_assoc()['c'];
$r = $conn->query("SELECT COUNT(*) AS c FROM payment WHERE payment_status != 'Fully Paid'");
if ($r) $quickStats['pending_payment'] = (int)$r->fetch_assoc()['c'];
$r = $conn->query("SELECT COUNT(*) AS c FROM enrollment");
if ($r) $quickStats['total'] = (int)$r->fetch_assoc()['c'];
$db->close();

// This page's search-card markup (.login-card, .enroll-input,
// .search-dropdown, .btn-search, etc.) uses classes defined in the
// Admission section's own theme files, not anything in admin.css --
// but the page uses the shared Include/header.php + Include/sidebar.php
// (app-layout/page-content shell) rather than Admission's own header,
// so those files were never being loaded and the search card rendered
// completely unstyled. Loading them here via the shared header's
// existing $extraCss hook.
$extraCss = [
    '/SIAdrafts/Frontend/Css/Admission/style.css',
    '/SIAdrafts/Frontend/Css/Admission/login.css',
];
?>
<?php include '../Include/header.php' ?>

<div class="app-layout">

<?php include '../Include/sidebar.php'; ?>

<main class="page-content">

<div class="container" style="padding-top:calc(var(--nav-h) + 56px); padding-bottom:60px;">
  <div class="row justify-content-center">
    <div class="col-12 col-sm-9 col-md-7 col-lg-5">

      <?php if ($enrolled_ref): ?>
      <div class="alert-box alert-success mb-3">
        <iconify-icon icon="mdi:check-circle-outline"></iconify-icon>
        Enrollment #<?= $enrolled_ref ?> saved successfully. You can enroll another student below.
      </div>
      <?php endif; ?>

      <div class="card login-card p-4 p-sm-5">
        <div class="card-body p-0 text-center">
          <div class="login-mark d-flex align-items-center justify-content-center mb-3 mx-auto">
            <iconify-icon icon="mdi:school"></iconify-icon>
          </div>
          <h1 class="login-title h3 fw-bold mb-2">Student Enrollment</h1>
          <p class="text-ink-soft mb-4">Enter the student ID or name to pull up their record.</p>

          <div id="enroll-app" autocomplete="off">

            <!-- Input + dropdown wrapped together so dropdown anchors to input -->
            <div class="position-relative mb-1" style="z-index:100;">
              <input
                class="enroll-input w-100"
                v-model="query"
                :placeholder="nameMode ? 'Enter student name' : 'Reference ID'"
                @input="onInput"
                @keydown.enter.prevent="submitSearch"
                @keydown.esc="results = []"
                autocomplete="off">

              <!-- Autocomplete dropdown anchored below the input -->
              <div v-if="results.length" class="search-dropdown">
                <div
                  v-for="s in results"
                  :key="s.student_id"
                  class="search-result-item"
                  @click="pick(s)">
                  <div class="sri-name">{{ s.full_name }}</div>
                  <div class="sri-meta">{{ s.display_id }} &mdash; {{ s.section_name }} &mdash; {{ s.type_name }}</div>
                </div>
              </div>
            </div>

            <div class="d-flex justify-content-between align-items-center mb-3">
              <small class="text-ink-soft">{{ hint }}</small>
              <a href="#" class="link-sage small" @click.prevent="toggle">
                {{ nameMode ? 'Search by ID' : 'Search by Name' }}
              </a>
            </div>

            <div v-if="searching" class="text-center py-2">
              <small class="text-ink-soft">Searching&hellip;</small>
            </div>

            <div v-if="noResults" class="text-center py-2">
              <small class="text-ink-soft">No students found.</small>
            </div>

            <button
              type="button"
              class="btn-search d-flex align-items-center justify-content-center gap-2 mx-auto"
              @click="submitSearch">
              Search <iconify-icon icon="mdi:magnify"></iconify-icon>
            </button>
          </div>

        </div>
      </div>

      <div class="stat-grid" style="grid-template-columns:repeat(3,1fr); margin-top:20px;">
        <div class="surface-1 rd-stat-card" style="padding:16px;">
          <div class="rd-stat-icon" style="width:40px;height:40px;font-size:17px; background:var(--teal-100); color:var(--teal-600);"><i class="bi bi-check-circle-fill"></i></div>
          <div class="rd-stat-figure mono" style="font-size:20px;"><?= $quickStats['today'] ?></div>
          <div class="rd-stat-label">Enrolled Today</div>
        </div>
        <div class="surface-1 rd-stat-card" style="padding:16px;">
          <div class="rd-stat-icon" style="width:40px;height:40px;font-size:17px; background:var(--seal-100); color:var(--seal-600);"><i class="bi bi-hourglass-split"></i></div>
          <div class="rd-stat-figure mono" style="font-size:20px;"><?= $quickStats['pending_payment'] ?></div>
          <div class="rd-stat-label">Pending Payment</div>
        </div>
        <div class="surface-1 rd-stat-card" style="padding:16px;">
          <div class="rd-stat-icon" style="width:40px;height:40px;font-size:17px; background:var(--sky-100); color:var(--sky-600);"><i class="bi bi-mortarboard-fill"></i></div>
          <div class="rd-stat-figure mono" style="font-size:20px;"><?= $quickStats['total'] ?></div>
          <div class="rd-stat-label">Total Enrolled</div>
        </div>
      </div>

    </div>
  </div>
</div>

</main>

</div>

<script src="https://cdn.jsdelivr.net/npm/vue@3/dist/vue.global.prod.js"></script>
<?php
$extraScripts = ['/SIAdrafts/Frontend/Js/Admission/enrollment.js'];
include '../Include/footer.php';
?>
