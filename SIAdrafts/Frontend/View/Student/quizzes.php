<?php
$pageTitle  = "Quizzes";
$activePage = "quizzes";
$pageScript = "quizzes";

require_once __DIR__ . '/../../../Backend/require_student.php';
require_student();
require_once __DIR__ . '/../../../Backend/db.php';
require_once __DIR__ . '/../../../Backend/Student/quiz_data.php';

$db   = new Database();
$conn = $db->connect();

$applicantId = (int)$_SESSION['student_id'];
$quizzes     = get_my_quizzes($conn, $applicantId);

$db->close();

include __DIR__ . '/Include/header.php';
?>

<h1 class="sp-greeting">Quizzes</h1>
<p class="sp-subline">Timed quizzes posted by your professors. Once started, stay on this tab and in fullscreen — switching away is logged and repeated switches will auto-submit your attempt.</p>

<?php if (empty($quizzes)): ?>
  <div class="sp-empty">
    <iconify-icon icon="mdi:clipboard-text-clock-outline"></iconify-icon>
    <p><strong>No quizzes yet.</strong></p>
    <p>Quizzes your professors post will appear here.</p>
  </div>
<?php else: ?>

  <?php foreach ($quizzes as $i => $q): ?>
    <?php
      $now = new DateTime();
      $opensAt  = $q['available_from']  ? new DateTime($q['available_from'])  : null;
      $closesAt = $q['available_until'] ? new DateTime($q['available_until']) : null;
      $notYetOpen = $opensAt && $now < $opensAt;
      $windowClosed = $closesAt && $now > $closesAt && !$q['attempt_id'];
    ?>
    <div class="sp-section" style="--row-i: <?= $i ?>;">
      <div style="display:flex; align-items:flex-start; justify-content:space-between; gap:12px; flex-wrap:wrap;">
        <div>
          <span class="sp-pill enrolled"><?= htmlspecialchars($q['subject_code'], ENT_QUOTES) ?></span>
          <h2 class="sp-section-title" style="margin-top:8px; margin-bottom:2px;"><?= htmlspecialchars($q['title'], ENT_QUOTES) ?></h2>
          <p style="color:var(--slate-300); font-size:12.5px; margin:0;"><?= htmlspecialchars($q['subject_name'], ENT_QUOTES) ?> · <?= (int)$q['time_limit_minutes'] ?> min time limit</p>
        </div>
        <div style="text-align:right; font-size:13px; color:var(--slate-500);">
          <?php if ($opensAt): ?><div>Opens <?= $opensAt->format('M j, g:ia') ?></div><?php endif; ?>
          <?php if ($closesAt): ?><div>Closes <?= $closesAt->format('M j, g:ia') ?></div><?php endif; ?>
        </div>
      </div>

      <?php if ($q['instructions']): ?>
        <p style="margin:12px 0; font-size:14px;"><?= nl2br(htmlspecialchars($q['instructions'], ENT_QUOTES)) ?></p>
      <?php endif; ?>

      <div style="margin-top:12px;">
        <?php if ($q['attempt_id'] && $q['status'] === 'in_progress'): ?>
          <span class="sp-pill pending">In progress</span>
          <a class="sp-btn sp-btn-primary" style="margin-left:10px;" href="/SIAdrafts/Frontend/View/Student/take_quiz.php?attempt_id=<?= (int)$q['attempt_id'] ?>">Resume</a>
        <?php elseif ($q['attempt_id']): ?>
          <span class="sp-pill enrolled">Submitted — <?= rtrim(rtrim(number_format((float)$q['score'], 2), '0'), '.') ?>/<?= rtrim(rtrim(number_format((float)$q['max_score'], 2), '0'), '.') ?></span>
        <?php elseif ($notYetOpen): ?>
          <span class="sp-pill attention">Not open yet</span>
        <?php elseif ($windowClosed): ?>
          <span class="sp-pill attention">Closed</span>
        <?php else: ?>
          <button type="button" class="sp-btn sp-btn-primary" data-start-quiz="<?= (int)$q['quiz_id'] ?>">Start</button>
        <?php endif; ?>
      </div>
    </div>
  <?php endforeach; ?>

<?php endif; ?>

<?php include __DIR__ . '/Include/footer.php'; ?>
