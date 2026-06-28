<?php
require_once 'includes/auth.php';
require_once 'includes/db.php';

$enroll = $_SESSION['enroll'] ?? null;
if (!$enroll || !isset($enroll['student_id'])) {
    header('Location: enrollment.php');
    exit;
}

// Handle POST: save selected subjects to session, redirect to confirm
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $subject_ids = array_values(array_unique(
        array_filter(array_map('intval', $_POST['subject_ids'] ?? []))
    ));
    if (empty($subject_ids)) {
        $post_error = 'Please select at least one subject.';
    } else {
        $_SESSION['enroll']['subject_ids'] = $subject_ids;
        header('Location: enrollment_confirm.php');
        exit;
    }
}

// Build semester label
$sem_label = $enroll['semester'] == 1 ? '1st Semester' : '2nd Semester';

// Student type determines default selection mode
$type_ids_auto = [1]; // type_id 1 = Regular → auto-select all
$page_scripts  = ['js/subject-picker.js'];

function fmt_id(int $id): string {
    $s = (string)$id;
    return strlen($s) >= 5 ? substr($s, 0, 4) . '-' . substr($s, 4) : $s;
}
?>
<?php include 'header.php' ?>

<div class="container" style="padding-top: calc(var(--nav-h) + 40px); padding-bottom: 60px;">
  <div class="row justify-content-center">
    <div class="col-12 col-lg-9">

      <!-- Stepper -->
      <div class="wizard-steps mb-4">
        <div class="ws-step done"><span>1</span> Search</div>
        <div class="ws-line done"></div>
        <div class="ws-step done"><span>2</span> Profile</div>
        <div class="ws-line done"></div>
        <div class="ws-step active"><span>3</span> Subjects</div>
        <div class="ws-line"></div>
        <div class="ws-step"><span>4</span> Confirm</div>
      </div>

      <!-- Context bar -->
      <div class="context-bar mb-3">
        <span><strong><?= htmlspecialchars($enroll['student_name']) ?></strong></span>
        <span class="cb-sep">|</span>
        <span><?= htmlspecialchars(fmt_id((int)$enroll['student_id'])) ?></span>
        <span class="cb-sep">|</span>
        <span><?= htmlspecialchars($enroll['section_name']) ?></span>
        <span class="cb-sep">|</span>
        <span>SY <?= htmlspecialchars($enroll['school_year']) ?></span>
        <span class="cb-sep">|</span>
        <span><?= htmlspecialchars($sem_label) ?></span>
      </div>

      <?php if (!empty($post_error)): ?>
        <div class="alert-box alert-error mb-3">
          <iconify-icon icon="mdi:alert-circle-outline"></iconify-icon>
          <?= htmlspecialchars($post_error) ?>
        </div>
      <?php endif; ?>

      <div id="subject-app" v-cloak>
        <!-- Loading state -->
        <div v-if="loading" class="text-center py-5">
          <iconify-icon icon="mdi:loading" class="spin" style="font-size:2rem; color:var(--sage);"></iconify-icon>
          <p class="text-ink-soft mt-2">Loading subjects&hellip;</p>
        </div>

        <!-- Error state -->
        <div v-else-if="loadError" class="alert-box alert-error mb-3">
          <iconify-icon icon="mdi:alert-circle-outline"></iconify-icon>
          {{ loadError }}
        </div>

        <!-- Subject list -->
        <template v-else>
          <div class="d-flex align-items-center justify-content-between mb-3 flex-wrap gap-2">
            <div class="d-flex gap-2">
              <button type="button" class="btn-sm-outline" @click="selectAll">Select All</button>
              <button type="button" class="btn-sm-outline" @click="clearAll">Clear All</button>
            </div>
            <div class="units-badge">
              Total Units: <strong>{{ totalUnits }}</strong>
            </div>
          </div>

          <div v-if="submitError" class="alert-box alert-error mb-3">
            <iconify-icon icon="mdi:alert-circle-outline"></iconify-icon>
            {{ submitError }}
          </div>

          <div v-for="cat in categories" :key="cat.category_name" class="subject-category mb-3">
            <!-- Category header with "select all" checkbox -->
            <div class="cat-header">
              <label class="cat-check-label">
                <input
                  type="checkbox"
                  class="cat-checkbox"
                  :checked="catAllChecked(cat)"
                  :indeterminate.prop="catSomeChecked(cat)"
                  @change="toggleCategory(cat)">
                <span class="cat-name">{{ cat.category_name }}</span>
              </label>
              <span class="cat-count">{{ cat.subjects.length }} subject(s)</span>
            </div>

            <!-- Subject rows -->
            <div class="subject-list">
              <label
                v-for="sub in cat.subjects"
                :key="sub.subject_id"
                class="subject-row"
                :class="{ checked: selected.includes(sub.subject_id) }">
                <input
                  type="checkbox"
                  class="sub-checkbox"
                  :value="sub.subject_id"
                  v-model="selected">
                <div class="sub-info">
                  <span class="sub-code">{{ sub.subject_code }}</span>
                  <span class="sub-name">{{ sub.subject_name }}</span>
                </div>
                <span class="sub-units">{{ parseFloat(sub.units).toFixed(0) }} Units</span>
                <span class="sub-sched" :class="{ tba: !sub.day }">
                  <template v-if="sub.day">
                    {{ sub.day }}<br>
                    <small>{{ fmtTime(sub.time_start) }}–{{ fmtTime(sub.time_end) }}</small><br>
                    <small>{{ sub.room_name }}</small>
                  </template>
                  <template v-else>TBA</template>
                </span>
              </label>
            </div>
          </div>

          <!-- Hidden form for POST submit -->
          <form id="subj-form" method="POST" action="enrollment_subjects.php" style="display:none">
            <input v-for="id in selected" :key="id" type="hidden" name="subject_ids[]" :value="id">
          </form>

          <div class="d-flex justify-content-between align-items-center mt-4">
            <a href="enrollment_profile.php?student_id=<?= (int)$enroll['student_id'] ?>" class="btn-back-link">
              <iconify-icon icon="mdi:arrow-left"></iconify-icon> Back
            </a>
            <button type="button" class="btn-primary-action" @click="proceed" :disabled="selected.length === 0">
              Confirm Selection ({{ selected.length }})
              <iconify-icon icon="mdi:arrow-right"></iconify-icon>
            </button>
          </div>
        </template>
      </div>

    </div>
  </div>
</div>

<script>
const ENROLL_META = {
  year_level:  <?= (int)$enroll['year_level'] ?>,
  semester:    <?= (int)$enroll['semester'] ?>,
  school_year: <?= json_encode($enroll['school_year']) ?>,
  section_id:  <?= (int)$enroll['section_id'] ?>,
  type_id:     <?= (int)$enroll['type_id'] ?>,
  auto_types:  <?= json_encode($type_ids_auto) ?>,
};
</script>
<script src="https://cdn.jsdelivr.net/npm/vue@3/dist/vue.global.prod.js"></script>
<?php include 'footer.php' ?>
