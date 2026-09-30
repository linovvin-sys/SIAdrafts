<?php
$pageTitle  = "Groups";
$activePage = "groups";

require_once __DIR__ . '/../../../Backend/require_student.php';
require_student();
require_once __DIR__ . '/../../../Backend/db.php';
require_once __DIR__ . '/../../../Backend/Student/group_data.php';

$db   = new Database();
$conn = $db->connect();

$applicantId = (int)$_SESSION['student_id'];
$classes     = get_my_groups_all_classes($conn, $applicantId);

$db->close();

function sp_group_initials(string $name): string {
    $parts = array_filter(explode(' ', $name));
    $initials = array_map(fn($p) => mb_strtoupper(mb_substr($p, 0, 1)), array_slice($parts, 0, 2));
    return implode('', $initials) ?: '?';
}

include __DIR__ . '/Include/header.php';
?>

<h1 class="sp-greeting">Groups</h1>
<p class="sp-subline">Your group assignment for each class, when your professor has created one.</p>

<?php if (empty($classes)): ?>
  <div class="sp-empty">
    <iconify-icon icon="mdi:account-group-outline"></iconify-icon>
    <p><strong>No classes yet.</strong></p>
  </div>
<?php else: ?>

<div class="sp-section" style="display:grid; grid-template-columns: repeat(auto-fill, minmax(240px, 1fr)); gap:16px;">
  <?php foreach ($classes as $i => $c): ?>
    <div class="sp-group-card" style="--row-i:<?= $i ?>">
      <p class="sp-group-card-title"><?= htmlspecialchars($c['subject_code'], ENT_QUOTES) ?> — <?= htmlspecialchars($c['subject_name'], ENT_QUOTES) ?></p>
      <?php if (!$c['group']): ?>
        <p class="sp-group-member-empty">No group yet for this class.</p>
      <?php else: ?>
        <p style="font-size:12.5px; color:var(--slate-300); margin:0 0 8px;">You're in <strong><?= htmlspecialchars($c['group']['group_name'], ENT_QUOTES) ?></strong></p>
        <ul class="sp-group-member-list">
          <?php foreach ($c['group']['members'] as $m): ?>
            <li class="sp-group-member">
              <span class="sp-group-member-avatar"><?= htmlspecialchars(sp_group_initials($m['name']), ENT_QUOTES) ?></span>
              <?= htmlspecialchars($m['name'], ENT_QUOTES) ?><?= (int)$m['applicant_id'] === $applicantId ? ' (you)' : '' ?>
            </li>
          <?php endforeach; ?>
        </ul>
      <?php endif; ?>
    </div>
  <?php endforeach; ?>
</div>

<?php endif; ?>

<?php include __DIR__ . '/Include/footer.php'; ?>
