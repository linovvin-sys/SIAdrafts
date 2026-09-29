<?php
$pageTitle  = "Materials";
$activePage = "materials";

require_once __DIR__ . '/../../../Backend/require_student.php';
require_student();
require_once __DIR__ . '/../../../Backend/db.php';
require_once __DIR__ . '/../../../Backend/Student/material_data.php';

$db   = new Database();
$conn = $db->connect();

$applicantId = (int)$_SESSION['student_id'];
$materials   = get_my_materials($conn, $applicantId);
$db->close();

$typeIcon = ['file' => 'mdi:file-outline', 'link' => 'mdi:link-variant', 'text' => 'mdi:text-box-outline'];

include __DIR__ . '/Include/header.php';
?>

<h1 class="sp-greeting">Materials</h1>
<p class="sp-subline">Course materials posted by your professors for your current classes.</p>

<?php if (empty($materials)): ?>
  <div class="sp-empty">
    <iconify-icon icon="mdi:folder-multiple-outline"></iconify-icon>
    <p><strong>No materials yet.</strong></p>
    <p>Materials your professors post will appear here.</p>
  </div>
<?php else: ?>

  <?php foreach ($materials as $i => $m): ?>
    <div class="sp-section" style="--row-i: <?= $i ?>;">
      <div style="display:flex; align-items:baseline; gap:10px; flex-wrap:wrap;">
        <span class="sp-pill enrolled"><?= htmlspecialchars($m['subject_code'], ENT_QUOTES) ?></span>
        <h2 class="sp-section-title" style="margin:0;"><?= htmlspecialchars($m['title'], ENT_QUOTES) ?></h2>
      </div>
      <p style="color:var(--slate-300); font-size:12.5px; margin:2px 0 10px;">
        <?= htmlspecialchars($m['subject_name'], ENT_QUOTES) ?> · <?= date('M j, Y', strtotime($m['created_at'])) ?>
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

<?php include __DIR__ . '/Include/footer.php'; ?>
