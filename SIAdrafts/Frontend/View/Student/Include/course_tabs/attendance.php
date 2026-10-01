<?php
/**
 * Attendance tab for course_detail.php. Expects $tabData['attendance']
 * (get_my_attendance()'s ['records','percent','total_sessions'] shape) in
 * scope. Scoped to one course, this can show the actual per-session log
 * instead of just a single summary row across classes like the old
 * cross-class Attendance page did -- the records were already being
 * fetched and computed, just unused by that page's summary-only view.
 */
$attendance = $tabData['attendance'] ?? ['records' => [], 'percent' => null, 'total_sessions' => 0];
?>
<?php if ($attendance['total_sessions'] === 0): ?>
  <div class="sp-empty">
    <iconify-icon icon="mdi:clipboard-check-outline"></iconify-icon>
    <p><strong>No attendance recorded yet.</strong></p>
    <p>Sessions your professor marks for this class will appear here.</p>
  </div>
<?php else: ?>
  <div class="sp-balance-hero" style="padding:14px 18px; margin-bottom:16px;">
    <div>
      <p class="sp-field-label">Overall attendance</p>
      <p class="sp-field-value">
        <span class="sp-pill <?= $attendance['percent'] >= 90 ? 'enrolled' : ($attendance['percent'] >= 75 ? 'pending' : 'attention') ?>" style="font-size:16px; padding:6px 16px;">
          <?= (int)$attendance['percent'] ?>%
        </span>
      </p>
    </div>
    <div class="sp-balance-meta">
      <div class="sp-field">
        <p class="sp-field-label">Sessions recorded</p>
        <p class="sp-field-value"><?= (int)$attendance['total_sessions'] ?></p>
      </div>
    </div>
  </div>

  <table class="sp-table sp-table-stagger">
    <thead><tr><th>Date</th><th>Status</th></tr></thead>
    <tbody>
      <?php foreach ($attendance['records'] as $i => $r): ?>
        <tr style="--row-i:<?= $i ?>">
          <td><?= date('M j, Y (D)', strtotime($r['session_date'])) ?></td>
          <td>
            <span class="sp-pill <?= in_array($r['status'], ['Present', 'Late'], true) ? 'enrolled' : 'attention' ?>">
              <?= htmlspecialchars($r['status'], ENT_QUOTES) ?>
            </span>
          </td>
        </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
<?php endif; ?>
