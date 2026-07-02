<?php
require_once '../../../Backend/auth.php';
require_once '../../../Backend/db.php';

$db   = new Database();
$conn = $db->connect();

$enroll = $_SESSION['enroll'] ?? null;
if (!$enroll || !isset($enroll['student_id'])) {
    header('Location: enrollment.php');
    exit;
}

// Handle POST: validate chosen section server-side, resolve its subject load
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $section_id = (int)($_POST['section_id'] ?? 0);

    if (!$section_id) {
        $post_error = 'Please select a section.';
    } else {
        $year_level  = (int)$enroll['year_level'];
        $semester    = (int)$enroll['semester'];
        $school_year = $enroll['school_year'];

        $course_id = (int)$enroll['course_id'];

        $stmt = $conn->prepare(
            "SELECT sec.section_name
            FROM section sec
            JOIN schedule sch ON sch.section_id = sec.section_id
            JOIN subject sub  ON sub.subject_id = sch.subject_id
            WHERE sec.section_id = ? AND sec.course_id = ?
              AND sub.year_level = ? AND sub.semester = ?
              AND sch.semester = ? AND sch.school_year = ?
            LIMIT 1"
        );
        $stmt->bind_param('iiiiis', $section_id, $course_id, $year_level, $semester, $semester, $school_year);
        $stmt->execute();
        $secRow = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if (!$secRow) {
            $post_error = 'That section is not offered for this year level / semester.';
        } else {
            $stmt2 = $conn->prepare(
                "SELECT sub.subject_id
                FROM subject sub
                JOIN schedule sch ON sch.subject_id = sub.subject_id
                JOIN section sec  ON sec.section_id = sch.section_id
                WHERE sec.course_id = ?
                  AND sub.year_level = ? AND sub.semester = ?
                  AND sch.semester = ? AND sch.school_year = ? AND sch.section_id = ?"
            );
            $stmt2->bind_param('iiiisi', $course_id, $year_level, $semester, $semester, $school_year, $section_id);
            $stmt2->execute();
            $subject_ids = array_map(fn($r) => (int)$r['subject_id'], $stmt2->get_result()->fetch_all(MYSQLI_ASSOC));
            $stmt2->close();

            if (empty($subject_ids)) {
                $post_error = 'That section has no subjects configured yet.';
            } else {
                $_SESSION['enroll']['section_id']   = $section_id;
                $_SESSION['enroll']['section_name'] = $secRow['section_name'];
                $_SESSION['enroll']['subject_ids']  = $subject_ids;
                header('Location: enrollment_confirm.php');
                exit;
            }
        }
    }
}

$sem_label    = $enroll['semester'] == 1 ? '1st Semester' : '2nd Semester';
$page_scripts = ['/SIAdrafts/Frontend/Js/Admission/section-picker.js'];

function fmt_id(int $id): string {
    $s = (string)$id;
    return strlen($s) >= 5 ? substr($s, 0, 4) . '-' . substr($s, 4) : $s;
}
?>
<?php include '../Admission/Include/header.php'; ?>


<div class="container" style="padding-top: calc(var(--nav-h) + 40px); padding-bottom: 60px;">
  <div class="row justify-content-center">
    <div class="col-12 col-lg-9">

      <!-- Stepper -->
      <div class="wizard-steps mb-4">
        <div class="ws-step done"><span>1</span> Search</div>
        <div class="ws-line done"></div>
        <div class="ws-step done"><span>2</span> Profile</div>
        <div class="ws-line done"></div>
        <div class="ws-step active"><span>3</span> Section</div>
        <div class="ws-line"></div>
        <div class="ws-step"><span>4</span> Confirm</div>
      </div>

      <!-- Context bar -->
      <div class="context-bar mb-3">
        <span><strong><?= htmlspecialchars($enroll['student_name']) ?></strong></span>
        <span class="cb-sep">|</span>
        <span><?= htmlspecialchars(fmt_id((int)$enroll['student_id'])) ?></span>
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

      <div id="section-app" v-cloak>

        <!-- Loading -->
        <div v-if="loading" class="text-center py-5">
          <iconify-icon icon="mdi:loading" class="spin" style="font-size:2rem;color:var(--sage);"></iconify-icon>
          <p class="text-ink-soft mt-2">Loading sections&hellip;</p>
        </div>

        <!-- Error -->
        <div v-else-if="loadError" class="alert-box alert-error mb-3">
          <iconify-icon icon="mdi:alert-circle-outline"></iconify-icon>
          {{ loadError }}
        </div>

        <!-- Empty -->
        <div v-else-if="sections.length === 0" class="sections-empty">
          <div><iconify-icon icon="mdi:folder-open-outline"></iconify-icon></div>
          <p><strong>No sections available</strong><br>
          No sections are currently offered for this year level and semester.</p>
        </div>

        <!-- Section cards -->
        <template v-else>

          <div class="section-intro mb-4">
            <iconify-icon icon="mdi:information-outline"></iconify-icon>
            <span>Select the section this student will join. Each section comes with a fixed subject load and schedule — all subjects will be enrolled together.</span>
          </div>

          <div v-if="submitError" class="alert-box alert-error mb-3">
            <iconify-icon icon="mdi:alert-circle-outline"></iconify-icon>
            {{ submitError }}
          </div>

          <div
            v-for="sec in sections"
            :key="sec.section_id"
            class="section-card mb-3"
            :class="{ selected: selectedId === sec.section_id }"
            @click="select(sec.section_id)">

            <!-- Card header -->
            <div class="section-card-head">
              <label class="sec-check-label" @click.stop>
                <input
                  type="radio"
                  name="section_pick"
                  class="sec-radio"
                  :value="sec.section_id"
                  v-model="selectedId">
                <span class="sec-name">{{ sec.section_name }}</span>
              </label>

              <div class="d-flex align-items-center gap-2 flex-wrap">
                <span class="units-badge">{{ sec.total_units }} Units &middot; {{ sec.subjects.length }} subjects</span>
                <span v-if="selectedId === sec.section_id" class="sec-selected-badge">
                  <iconify-icon icon="mdi:check"></iconify-icon> Selected
                </span>
              </div>
            </div>

            <!-- Subject rows -->
            <div class="subject-list">
              <div
                v-for="sub in sec.subjects"
                :key="sub.subject_id"
                class="subject-row-static">
                <div class="sub-info">
                  <span class="sub-code">{{ sub.subject_code }}</span>
                  <span class="sub-name">{{ sub.subject_name }}</span>
                </div>
                <span class="sub-units">{{ parseFloat(sub.units).toFixed(0) }} Units</span>
                <span class="sub-sched" :class="{ tba: !sub.day }">
                  <template v-if="sub.day">
                    {{ sub.day }}<br>
                    <small>{{ fmtTime(sub.time_start) }}–{{ fmtTime(sub.time_end) }}</small><br>
                    <small>{{ sub.room_name }} &middot; {{ sub.professor_name }}</small>
                  </template>
                  <template v-else>TBA</template>
                </span>
              </div>
            </div>

          </div>

          <!-- Hidden form -->
          <form id="sec-form" method="POST" action="enrollment_subjects.php" style="display:none">
            <input type="hidden" name="section_id" :value="selectedId">
          </form>

          <div class="d-flex justify-content-between align-items-center mt-4">
            <a href="enrollment_profile.php?student_id=<?=$enroll['student_id'] ?>" class="btn-back-link">
              <iconify-icon icon="mdi:arrow-left"></iconify-icon> Back
            </a>
            <button
              type="button"
              class="btn-primary-action"
              @click="proceed"
              :disabled="!selectedId">
              Confirm Section
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
  type_id:     <?= (int)$enroll['type_id'] ?>,
  course_id:   <?= (int)$enroll['course_id'] ?>,
};
</script>
<script src="https://cdn.jsdelivr.net/npm/vue@3/dist/vue.global.prod.js"></script>
<?php include '../Admission/Include/footer.php' ?>