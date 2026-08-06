<?php
$pageTitle  = "My Classes";
$activePage = "classes";
$pageScript = "classes";

require_once __DIR__ . '/../../../Backend/require_professor.php';
require_professor();
require_once __DIR__ . '/../../../Backend/db.php';
require_once __DIR__ . '/../../../Backend/Professor/schedule_data.php';

$db   = new Database();
$conn = $db->connect();

$professorId = (int)$_SESSION['professor_id'];

$stmt = $conn->prepare("
    SELECT s.schedule_id, s.section_id, s.day, s.time_start, s.time_end, s.school_year, s.semester,
           sub.subject_code, sub.subject_name, sec.section_name, r.room_name
    FROM schedule s
    JOIN subject sub ON sub.subject_id = s.subject_id
    JOIN section sec ON sec.section_id = s.section_id
    JOIN room r       ON r.room_id = s.room_id
    WHERE s.professor_id = ? AND s.is_active = 1 AND s.status = 'Approved'
    ORDER BY sec.section_name, sub.subject_code
");
$stmt->bind_param('i', $professorId);
$stmt->execute();
$classes = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// Grouped by section+term (not just section_id) so "Master List" stays
// scoped to one term -- a section taught across multiple terms shouldn't
// merge different cohorts of students into one list.
$bySection = [];
foreach ($classes as $c) {
    $key = $c['section_id'] . '|' . $c['school_year'] . '|' . $c['semester'];
    if (!isset($bySection[$key])) {
        $bySection[$key] = [
            'section_id'   => $c['section_id'],
            'section_name' => $c['section_name'],
            'school_year'  => $c['school_year'],
            'semester'     => $c['semester'],
            'classes'      => [],
        ];
    }
    $bySection[$key]['classes'][] = $c;
}

$pendingClasses = get_professor_pending($conn, $professorId);

$db->close();

include __DIR__ . '/Include/header.php';
?>

<h1 class="sp-greeting">My Classes</h1>
<p class="sp-subline">Every class offering assigned to you, grouped by section.</p>

<?php if (!empty($pendingClasses)): ?>
  <div class="sp-section sp-print-hide">
    <h2 class="sp-section-title">Pending approval</h2>
    <table class="sp-table sp-table-stagger">
      <thead><tr><th>Subject</th><th>Section</th><th>Schedule</th></tr></thead>
      <tbody>
        <?php foreach ($pendingClasses as $i => $p): ?>
          <tr style="--row-i: <?= $i ?>;">
            <td><?= htmlspecialchars($p['subject_code'], ENT_QUOTES) ?> — <?= htmlspecialchars($p['subject_name'], ENT_QUOTES) ?></td>
            <td><span class="sp-pill pending"><?= htmlspecialchars($p['section_name'], ENT_QUOTES) ?></span></td>
            <td class="sp-num"><?= htmlspecialchars($p['day'], ENT_QUOTES) ?>, <?= date('g:ia', strtotime($p['time_start'])) ?>–<?= date('g:ia', strtotime($p['time_end'])) ?></td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
<?php endif; ?>

<?php if (empty($bySection)): ?>
  <div class="sp-empty">
    <iconify-icon icon="mdi:google-classroom"></iconify-icon>
    <p><strong>No class assignments yet.</strong></p>
    <p>Classes assigned to you by the Registrar's Office will appear here.</p>
  </div>
<?php else: ?>

<div class="sp-section">
  <div style="display:flex; align-items:center; justify-content:space-between; flex-wrap:wrap; gap:12px;">
    <div class="sp-search-bar sp-print-hide" style="margin-bottom:0;">
      <input type="text" id="classesSearch" placeholder="Search subject or section…">
    </div>
    <button type="button" class="sp-btn sp-btn-secondary sp-print-hide" onclick="window.print()">
      <iconify-icon icon="mdi:printer-outline"></iconify-icon> Print / Save as PDF
    </button>
  </div>

  <div id="classesList" style="margin-top:20px;">
    <?php foreach ($bySection as $group): ?>
      <div class="sp-class-group" data-search="<?= htmlspecialchars(strtolower($group['section_name']), ENT_QUOTES) ?>">
        <div class="sp-group-header">
          <div class="sp-group-header-label">
            <span class="sp-pill enrolled"><?= htmlspecialchars($group['section_name'], ENT_QUOTES) ?></span>
            <span class="sp-group-term"><?= htmlspecialchars($group['school_year'], ENT_QUOTES) ?>, Sem <?= (int)$group['semester'] ?></span>
          </div>
          <button type="button" class="sp-table-action is-primary sp-print-hide"
            data-view-section-roster="<?= (int)$group['section_id'] ?>"
            data-school-year="<?= htmlspecialchars($group['school_year'], ENT_QUOTES) ?>"
            data-semester="<?= (int)$group['semester'] ?>"
            data-section-name="<?= htmlspecialchars($group['section_name'], ENT_QUOTES) ?>">
            <iconify-icon icon="mdi:account-group-outline"></iconify-icon> Master List
          </button>
        </div>
        <table class="sp-table">
          <thead>
            <tr><th>Subject</th><th>Schedule</th><th>Room</th><th class="sp-print-hide">Roster</th></tr>
          </thead>
          <tbody>
            <?php foreach ($group['classes'] as $c): ?>
              <tr class="sp-class-row" data-search="<?= htmlspecialchars(strtolower($c['subject_code'] . ' ' . $c['subject_name'] . ' ' . $group['section_name']), ENT_QUOTES) ?>">
                <td class="sp-class-subject"><?= htmlspecialchars($c['subject_code'], ENT_QUOTES) ?><div style="color:var(--slate-300); font-size:12.5px;"><?= htmlspecialchars($c['subject_name'], ENT_QUOTES) ?></div></td>
                <td class="sp-num"><?= htmlspecialchars($c['day'], ENT_QUOTES) ?>, <?= date('g:ia', strtotime($c['time_start'])) ?>–<?= date('g:ia', strtotime($c['time_end'])) ?></td>
                <td><?= htmlspecialchars($c['room_name'], ENT_QUOTES) ?></td>
                <td class="sp-print-hide">
                  <button type="button" class="sp-table-action" data-view-roster="<?= (int)$c['schedule_id'] ?>">
                    <iconify-icon icon="mdi:eye-outline"></iconify-icon> View
                  </button>
                </td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    <?php endforeach; ?>
  </div>
</div>

<?php endif; ?>

<!-- Roster dialog -->
<dialog class="sp-dialog sp-dialog-lg" id="rosterDialog">
  <div class="sp-dialog-body">
    <p class="sp-dialog-title" id="rosterTitle">Class Roster</p>
    <p class="sp-dialog-message" id="rosterSubtitle"></p>
    <table class="sp-table" id="rosterTable">
      <thead>
        <tr><th>Student No.</th><th>Full Name</th></tr>
      </thead>
      <tbody id="rosterTableBody"></tbody>
    </table>
  </div>
  <div class="sp-dialog-actions">
    <button type="button" class="sp-btn sp-btn-secondary" id="exportRosterCsv">Export CSV</button>
    <button type="button" class="sp-btn sp-btn-primary" id="closeRosterDialog">Close</button>
  </div>
</dialog>

<?php include __DIR__ . '/Include/footer.php'; ?>
