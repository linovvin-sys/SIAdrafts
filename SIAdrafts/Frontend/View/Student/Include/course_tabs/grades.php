<?php
$grade = $tabData['grades'] ?? null;
$periods = [
  'prelim'   => 'Prelim',
  'midterm'  => 'Midterm',
  'prefinal' => 'Prefinal',
  'final'    => 'Final',
];
?>
<?php if (!$grade): ?>
  <div class="sp-empty">
    <iconify-icon icon="mdi:school-outline"></iconify-icon>
    <p>No grades encoded yet for this course.</p>
  </div>
<?php else: ?>
  <div class="sp-grade-periods">
    <?php foreach ($periods as $key => $label): $val = $grade[$key] ?? null; ?>
      <div class="sp-grade-period-card">
        <p class="sp-field-label"><?= $label ?></p>
        <?php if ($val === null): ?>
          <span class="sp-grade-value sp-grade-pending">—</span>
        <?php else: ?>
          <span class="sp-grade-value <?= (float)$val >= 75 ? 'is-pass' : 'is-fail' ?>"><?= htmlspecialchars($val, ENT_QUOTES) ?></span>
        <?php endif; ?>
      </div>
    <?php endforeach; ?>
  </div>
  <?php if (!empty($grade['final_remarks'])): ?>
    <div class="sp-grade-remarks">
      <p class="sp-field-label">Remarks</p>
      <p><?= htmlspecialchars($grade['final_remarks'], ENT_QUOTES) ?></p>
    </div>
  <?php endif; ?>
<?php endif; ?>
