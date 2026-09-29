<?php
/**
 * Shared "pick a class" dropdown for the Grades/Assignments/Materials/
 * Announcements pages -- each of those pages operates on exactly one
 * schedule_id at a time, same as the old Classes-page dialogs did, just
 * promoted to a real page control instead of a per-row dialog trigger.
 * Expects $classes (get_professor_active_classes() rows) and $selectId in
 * scope; groups options by section+term the same way Classes groups rows.
 */
$_bySection = [];
foreach ($classes as $c) {
    $key = $c['section_id'] . '|' . $c['school_year'] . '|' . $c['semester'];
    if (!isset($_bySection[$key])) {
        $_bySection[$key] = [
            'label'   => $c['section_name'] . ' — ' . $c['school_year'] . ', Sem ' . (int)$c['semester'],
            'classes' => [],
        ];
    }
    $_bySection[$key]['classes'][] = $c;
}
?>
<div class="sp-form-group" style="max-width:420px;">
  <label for="<?= htmlspecialchars($selectId, ENT_QUOTES) ?>">Class</label>
  <select class="sp-select" id="<?= htmlspecialchars($selectId, ENT_QUOTES) ?>">
    <option value="">Select a class…</option>
    <?php foreach ($_bySection as $group): ?>
      <optgroup label="<?= htmlspecialchars($group['label'], ENT_QUOTES) ?>">
        <?php foreach ($group['classes'] as $c): ?>
          <option value="<?= (int)$c['schedule_id'] ?>">
            <?= htmlspecialchars($c['subject_code'] . ' — ' . $c['subject_name'], ENT_QUOTES) ?>
          </option>
        <?php endforeach; ?>
      </optgroup>
    <?php endforeach; ?>
  </select>
</div>
