<?php
/** Announcements tab for course_detail.php. Read-only — only professors post. */
$announcements = $tabData['announcements'] ?? [];
?>
<?php if (empty($announcements)): ?>
  <div class="sp-empty">
    <iconify-icon icon="mdi:bullhorn-outline"></iconify-icon>
    <p><strong>No announcements yet.</strong></p>
    <p>Updates your professor posts for this course will appear here.</p>
  </div>
<?php else: ?>
  <ul class="sp-announce-list">
    <?php foreach ($announcements as $i => $a): ?>
      <li class="sp-announce-item" style="--row-i:<?= $i ?>">
        <div class="sp-announce-item-head">
          <strong><?= htmlspecialchars($a['title'], ENT_QUOTES) ?></strong>
          <span class="sp-announce-item-date"><?= (new DateTimeImmutable($a['created_at'], new DateTimeZone('UTC')))->setTimezone(new DateTimeZone('Asia/Manila'))->format('M j, Y, g:ia') ?></span>
        </div>
        <p><?= nl2br(htmlspecialchars($a['body'], ENT_QUOTES)) ?></p>
        <p style="margin:6px 0 0; font-size:12.5px; color:var(--slate-300);">— <?= htmlspecialchars($a['professor_name'], ENT_QUOTES) ?></p>
      </li>
    <?php endforeach; ?>
  </ul>
<?php endif; ?>
