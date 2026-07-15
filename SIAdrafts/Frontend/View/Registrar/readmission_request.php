<?php
$pageTitle  = "READMISSION REQUEST";
$activePage = "readmission";
$pageScript = "readmission_request";

require_once '../../../Backend/auth.php';
require_once '../../../Backend/require_role.php';
require_role(['Head Registrar', 'Registrar Staff']);
require_once '../../../Backend/db.php';

$db   = new Database();
$conn = $db->connect();

$courses = $conn->query("SELECT course_id, course_name FROM course ORDER BY course_name")->fetch_all(MYSQLI_ASSOC);
$db->close();

include 'Include/header.php';
?>

<div class="app-layout">

  <?php include 'Include/sidebar.php'; ?>

  <main class="page-content">

    <div class="panel" style="max-width:640px; margin:0 auto;">
      <div class="panel-header">
        <span class="panel-title">File a Readmission Request</span>
      </div>
      <div class="panel-body" id="readmit-app">

        <p style="color:var(--text-muted, #666); font-size:0.9rem; margin-bottom:1.25rem;">
          For a student who previously stopped out (LOA, dropped, etc.) and wants to return.
        </p>

        <div class="form-group">
          <label class="form-label">Student ID<span class="required">*</span></label>
          <div style="display:flex; gap:8px;">
            <input type="text" class="form-input" v-model="studentNo" placeholder="e.g. 2025-00012" @keydown.enter.prevent="lookup" style="flex:1;">
            <button type="button" class="btn btn-outline" @click="lookup" :disabled="looking">
              {{ looking ? 'Searching…' : 'Find' }}
            </button>
          </div>
          <p v-if="lookupError" style="color:#c0392b; font-size:0.85rem; margin-top:6px;">{{ lookupError }}</p>
        </div>

        <div v-if="student" class="status-pill status-pill--approved" style="display:inline-block; margin-bottom:1rem;">
          {{ student.first_name }} {{ student.last_name }} &mdash; currently {{ student.course_name }}
        </div>

        <template v-if="student">
          <div class="form-group">
            <label class="form-label">Reason for leaving / requesting readmission<span class="required">*</span></label>
            <textarea class="form-input" rows="3" v-model="reason"></textarea>
          </div>

          <div class="form-group">
            <label class="form-label">Requested School Year<span class="required">*</span></label>
            <input type="text" class="form-input" v-model="schoolYear" placeholder="2026-2027" pattern="\d{4}-\d{4}">
          </div>

          <div class="form-group">
            <label class="form-label">Semester<span class="required">*</span></label>
            <div class="select-wrapper">
              <select class="form-input form-select" v-model="semester">
                <option value="1">1st Semester</option>
                <option value="2">2nd Semester</option>
              </select>
            </div>
          </div>

          <div class="form-group">
            <label class="form-label" style="display:flex; align-items:center; gap:8px; font-weight:normal;">
              <input type="checkbox" v-model="isShifting">
              Also shifting to a different program
            </label>
          </div>

          <div class="form-group" v-if="isShifting">
            <label class="form-label">New Program<span class="required">*</span></label>
            <div class="select-wrapper">
              <select class="form-input form-select" v-model="newCourseId">
                <option value="">-- Select Program --</option>
                <?php foreach ($courses as $c): ?>
                  <option value="<?= (int)$c['course_id'] ?>"><?= htmlspecialchars($c['course_name']) ?></option>
                <?php endforeach; ?>
              </select>
            </div>
          </div>

          <p v-if="submitError" style="color:#c0392b; font-size:0.9rem;">{{ submitError }}</p>
          <p v-if="submitted" style="color:#2f8f4e; font-size:0.9rem;">Readmission processed successfully.</p>

          <button type="button" class="btn btn-primary" style="width:100%;" @click="submit" :disabled="submitting || submitted">
            {{ submitting ? 'Submitting…' : 'Submit Request' }}
          </button>
        </template>

      </div>
    </div>

  </main>
</div>

<?php include 'Include/footer.php'; ?>