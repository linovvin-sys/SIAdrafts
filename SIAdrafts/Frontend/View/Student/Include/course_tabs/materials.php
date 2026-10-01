<?php
/** Materials tab for course_detail.php. Expects $tabData['materials'] in scope. */
$materials = $tabData['materials'] ?? [];
?>
<?php if (empty($materials)): ?>
  <div class="sp-empty">
    <iconify-icon icon="mdi:folder-multiple-outline"></iconify-icon>
    <p><strong>No materials yet.</strong></p>
    <p>Materials your professor posts for this class will appear here.</p>
  </div>
<?php else: ?>
  <?php foreach ($materials as $i => $m): ?>
    <div class="sp-section" style="--row-i: <?= $i ?>;">
      <h2 class="sp-section-title" style="margin:0;"><?= htmlspecialchars($m['title'], ENT_QUOTES) ?></h2>
      <p style="color:var(--slate-300); font-size:12.5px; margin:2px 0 10px;">
        <?= date('M j, Y', strtotime($m['created_at'])) ?>
      </p>

      <?php if ($m['type'] === 'file'): ?>
        <a class="sp-btn sp-btn-secondary" href="/SIAdrafts/Backend/api/Materials/download_material.php?material_id=<?= (int)$m['material_id'] ?>" target="_blank" rel="noopener">
          <iconify-icon icon="mdi:file-outline"></iconify-icon> <?= htmlspecialchars($m['file_name'], ENT_QUOTES) ?>
        </a>
      <?php elseif ($m['type'] === 'link'): ?>
        <a class="sp-btn sp-btn-secondary" href="<?= htmlspecialchars($m['url'], ENT_QUOTES) ?>" target="_blank" rel="noopener">
          <iconify-icon icon="mdi:link-variant"></iconify-icon> Open link
        </a>
      <?php else: ?>
        <p style="font-size:14px; margin:0;"><?= nl2br(htmlspecialchars($m['body'], ENT_QUOTES)) ?></p>
      <?php endif; ?>
    </div>
  <?php endforeach; ?>
<?php endif; ?>
