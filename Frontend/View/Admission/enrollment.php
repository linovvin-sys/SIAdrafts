<?php
require_once '../../../Backend/auth.php';

// Show success flash if returning from a completed enrollment
$enrolled_ref = isset($_GET['enrolled'], $_GET['ref']) ? (int)$_GET['ref'] : null;

$page_scripts = ['/SIAdrafts/Frontend/Js/Admission/student-search.js'];
?>
<?php include '../Admission/Include/header.php' ?>

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
                :placeholder="nameMode ? 'Enter student name' : 'Student ID (e.g. 2025-00001)'"
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

    </div>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/vue@3/dist/vue.global.prod.js"></script>
<?php include '../Admission/Include/footer.php' ?>
