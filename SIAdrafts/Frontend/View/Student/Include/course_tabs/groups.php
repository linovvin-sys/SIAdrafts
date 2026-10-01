<?php
/**
 * Groups tab for course_detail.php. Expects $tabData['groups'] (array,
 * usually 0 or 1 entries -- more than one only happens when a subject's
 * lecture and lab blocks were grouped separately) and $applicantId.
 */
$groups = $tabData['groups'] ?? [];
?>
<?php if (empty($groups)): ?>
  <div class="sp-group-card">
    <p class="sp-group-card-title">No group yet</p>
    <p class="sp-group-member-empty">Your professor hasn't created groups for this class yet.</p>
  </div>
<?php else: ?>
  <?php foreach ($groups as $group): ?>
    <div class="sp-group-card" style="margin-bottom:16px;">
      <?php if (!empty($group['members'])): ?>
        <?php $clusterShown = array_slice($group['members'], 0, 5); $overflowCount = count($group['members']) - count($clusterShown); ?>
        <div class="sp-group-avatars">
          <?php foreach ($clusterShown as $m): ?>
            <span class="sp-group-member-avatar"><?= htmlspecialchars(sp_group_initials($m['name']), ENT_QUOTES) ?></span>
          <?php endforeach; ?>
          <?php if ($overflowCount > 0): ?>
            <span class="sp-group-avatar-overflow">+<?= $overflowCount ?></span>
          <?php endif; ?>
        </div>
      <?php endif; ?>

      <p class="sp-group-card-title"><?= htmlspecialchars($group['group_name'], ENT_QUOTES) ?></p>
      <ul class="sp-group-member-list">
        <?php foreach ($group['members'] as $m): ?>
          <li class="sp-group-member">
            <span class="sp-group-member-avatar"><?= htmlspecialchars(sp_group_initials($m['name']), ENT_QUOTES) ?></span>
            <?= htmlspecialchars($m['name'], ENT_QUOTES) ?><?= (int)$m['applicant_id'] === $applicantId ? ' (you)' : '' ?>
          </li>
        <?php endforeach; ?>
      </ul>
    </div>
  <?php endforeach; ?>
<?php endif; ?>
