<?php
$pageTitle  = "Assignments";
$activePage = "assignments";
$pageScript = "assignments";

require_once __DIR__ . '/../../../Backend/require_student.php';
require_student();
require_once __DIR__ . '/../../../Backend/db.php';
require_once __DIR__ . '/../../../Backend/Student/assignment_data.php';

$db   = new Database();
$conn = $db->connect();

$applicantId = (int)$_SESSION['student_id'];
$assignments = get_my_assignments($conn, $applicantId);
$db->close();

include __DIR__ . '/Include/header.php';
?>

<h1 class="sp-greeting">Assignments</h1>
<p class="sp-subline">Assignments posted by your professors for your current classes.</p>

<?php if (empty($assignments)): ?>
  <div class="sp-empty">
    <iconify-icon icon="mdi:file-document-edit-outline"></iconify-icon>
    <p><strong>No assignments yet.</strong></p>
    <p>Assignments your professors post will appear here.</p>
  </div>
<?php else: ?>

  <?php foreach ($assignments as $i => $a): ?>
    <div class="sp-section" style="--row-i: <?= $i ?>;">
      <div style="display:flex; align-items:flex-start; justify-content:space-between; gap:12px; flex-wrap:wrap;">
        <div>
          <span class="sp-pill enrolled"><?= htmlspecialchars($a['subject_code'], ENT_QUOTES) ?></span>
          <h2 class="sp-section-title" style="margin-top:8px; margin-bottom:2px;"><?= htmlspecialchars($a['title'], ENT_QUOTES) ?></h2>
          <p style="color:var(--slate-300); font-size:12.5px; margin:0;"><?= htmlspecialchars($a['subject_name'], ENT_QUOTES) ?></p>
        </div>
        <div style="text-align:right; font-size:13px; color:var(--slate-500);">
          <?php if ($a['due_date']): ?>
            <div>Due <?= date('M j, Y', strtotime($a['due_date'])) ?></div>
          <?php else: ?>
            <div>No due date</div>
          <?php endif; ?>
          <?php if ($a['max_score']): ?>
            <div><?= rtrim(rtrim(number_format((float)$a['max_score'], 2), '0'), '.') ?> pts</div>
          <?php endif; ?>
        </div>
      </div>

      <p style="margin:12px 0; font-size:14px;"><?= nl2br(htmlspecialchars($a['instructions'], ENT_QUOTES)) ?></p>

      <?php if ($a['submission_id']): ?>
        <div class="sp-balance-hero" style="padding:14px 18px;">
          <div>
            <p class="sp-field-label">Your submission</p>
            <p class="sp-field-value">
              <a href="/SIAdrafts/Backend/api/Assignments/download_submission.php?submission_id=<?= (int)$a['submission_id'] ?>" target="_blank" rel="noopener">
                <?= htmlspecialchars($a['file_name'], ENT_QUOTES) ?>
              </a>
            </p>
            <p style="color:var(--slate-300); font-size:12px; margin:2px 0 0;">
              Submitted <?= date('M j, Y g:ia', strtotime($a['submitted_at'])) ?>
              <?php if ($a['is_late']): ?>
                <span class="sp-pill" style="background:var(--danger-100,#fee2e2); color:var(--danger-600,#dc2626);">Late</span>
              <?php endif; ?>
            </p>
          </div>
          <div class="sp-balance-meta">
            <div class="sp-field">
              <p class="sp-field-label">Status</p>
              <p class="sp-field-value">
                <?php if ($a['score'] !== null): ?>
                  <span class="sp-pill enrolled">Graded — <?= rtrim(rtrim(number_format((float)$a['score'], 2), '0'), '.') ?><?= $a['max_score'] ? '/' . rtrim(rtrim(number_format((float)$a['max_score'], 2), '0'), '.') : '' ?></span>
                <?php else: ?>
                  <span class="sp-pill pending">Submitted — awaiting grade</span>
                <?php endif; ?>
              </p>
            </div>
          </div>
        </div>
        <?php if ($a['feedback']): ?>
          <p style="margin:10px 0 0; font-size:13px; color:var(--slate-500);"><strong>Feedback:</strong> <?= htmlspecialchars($a['feedback'], ENT_QUOTES) ?></p>
        <?php endif; ?>
        <p class="sp-form-error-hint" style="margin-top:12px;">Submitting a new file below will replace your current submission and clear any existing score.</p>
      <?php endif; ?>

      <form class="sp-assignment-submit-form" data-assignment-id="<?= (int)$a['assignment_id'] ?>" style="margin-top:12px; display:flex; gap:10px; align-items:center; flex-wrap:wrap;">
        <input type="file" data-submission-file required>
        <button type="submit" class="sp-btn sp-btn-primary">
          <span class="sp-btn-spinner" hidden></span>
          <span class="sp-btn-label"><?= $a['submission_id'] ? 'Resubmit' : 'Submit' ?></span>
        </button>
        <span class="sp-form-error-msg" data-submit-error style="display:none; color:var(--danger-600); font-size:13px;"></span>
      </form>
    </div>
  <?php endforeach; ?>

<?php endif; ?>

<?php include __DIR__ . '/Include/footer.php'; ?>
