<?php
$pageTitle  = "MY CLASSES";
$activePage = "classes";
$pageScript = "classes";

require_once '../../../Backend/auth.php';
require_once '../../../Backend/require_role.php';
require_once '../../../Backend/roles.php';
require_role([ROLE_PROFESSOR]);
require_once '../../../Backend/db.php';

$db   = new Database();
$conn = $db->connect();

$profStmt = $conn->prepare("SELECT professor_id FROM professor WHERE professor_id = ? LIMIT 1");
$profStmt->bind_param('i', $_SESSION['professor_id']);
$profStmt->execute();
$professor = $profStmt->get_result()->fetch_assoc();
$profStmt->close();

$bySection      = [];
$pendingClasses = [];

if ($professor) {
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
    $stmt->bind_param('i', $professor['professor_id']);
    $stmt->execute();
    $classes = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();

    // Group by section+term (not just section_id) so the "Master List" stays
    // scoped to one term — a section taught across multiple terms shouldn't
    // merge different cohorts of students into one list.
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

    $pendingStmt = $conn->prepare("
        SELECT sub.subject_code, sub.subject_name, sec.section_name, s.day, s.time_start, s.time_end
        FROM schedule s
        JOIN subject sub ON sub.subject_id = s.subject_id
        JOIN section sec ON sec.section_id = s.section_id
        WHERE s.professor_id = ? AND s.status = 'Pending'
        ORDER BY s.day, s.time_start
    ");
    $pendingStmt->bind_param('i', $professor['professor_id']);
    $pendingStmt->execute();
    $pendingClasses = $pendingStmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $pendingStmt->close();
}

$db->close();

$extraCss = ['/SIAdrafts/Frontend/Css/Professor/professor.css'];
include '../Include/header.php';
?>

<div class="app-layout">

  <?php include '../Include/sidebar.php'; ?>

  <main class="page-content">

    <?php if (!empty($pendingClasses)): ?>
      <div class="panel no-print" style="margin-bottom:16px; border-left:3px solid var(--gold);">
        <div class="panel-header">
          <span class="panel-title"><?= count($pendingClasses) ?> class<?= count($pendingClasses) === 1 ? '' : 'es' ?> pending Registrar approval</span>
        </div>
        <div class="panel-body" style="padding:0;">
          <div class="table-responsive">
            <table class="data-table">
              <thead>
                <tr><th>Subject</th><th>Section</th><th>Day / Time</th></tr>
              </thead>
              <tbody>
                <?php foreach ($pendingClasses as $p): ?>
                  <tr>
                    <td><?= htmlspecialchars($p['subject_code']) ?><div class="text-muted"><?= htmlspecialchars($p['subject_name']) ?></div></td>
                    <td><?= htmlspecialchars($p['section_name']) ?></td>
                    <td><?= htmlspecialchars($p['day']) ?>, <?= htmlspecialchars(date('g:i A', strtotime($p['time_start']))) ?> – <?= htmlspecialchars(date('g:i A', strtotime($p['time_end']))) ?></td>
                  </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          </div>
        </div>
      </div>
    <?php endif; ?>

    <div class="panel">
      <div class="panel-header">
        <span class="panel-title">My Classes</span>
        <button type="button" class="btn btn-outline no-print" onclick="window.print()">Print / Save as PDF</button>
      </div>

      <?php if (!$professor): ?>
        <div class="panel-body" style="padding:24px;">
          <p>Your account is not yet linked to a professor record. Please contact the Registrar's Office.</p>
        </div>
      <?php else: ?>
        <div class="panel-body no-print" style="padding:16px 24px 0;">
          <div class="filter-bar">
            <input type="text" class="form-input" id="classesSearch" placeholder="Search subject or section…">
          </div>
        </div>

        <div class="panel-body" style="padding:0;">
          <div class="table-responsive">
            <table class="sched-table" id="classesTable">
              <thead>
                <tr>
                  <th style="width:44px"></th>
                  <th>Section / Term</th>
                  <th>Subject</th>
                  <th>Schedule</th>
                  <th>Room</th>
                  <th style="width:140px">Roster</th>
                </tr>
              </thead>
              <tbody>
                <?php if (!empty($bySection)): ?>
                  <?php foreach ($bySection as $group): ?>
                    <tr class="section-group-row"
                        data-search="<?= htmlspecialchars(strtolower($group['section_name'])) ?>">
                      <td class="expand-cell"></td>
                      <td colspan="3">
                        <div class="group-label">
                          <span class="sec-tag"><?= htmlspecialchars($group['section_name']) ?></span>
                          <span class="group-meta"><?= htmlspecialchars($group['school_year']) ?>, Sem <?= (int)$group['semester'] ?></span>
                        </div>
                      </td>
                      <td></td>
                      <td>
                        <button type="button" class="btn btn-primary" style="padding:4px 10px;font-size:12px"
                          data-view-section-roster="<?= (int)$group['section_id'] ?>"
                          data-school-year="<?= htmlspecialchars($group['school_year']) ?>"
                          data-semester="<?= (int)$group['semester'] ?>"
                          data-section-name="<?= htmlspecialchars($group['section_name']) ?>">Master List</button>
                      </td>
                    </tr>
                    <?php foreach ($group['classes'] as $c): ?>
                      <tr class="subject-row"
                          data-search="<?= htmlspecialchars(strtolower($c['subject_code'] . ' ' . $c['subject_name'] . ' ' . $group['section_name'])) ?>">
                        <td class="child-indent-cell"><span class="child-arrow">↳</span></td>
                        <td></td>
                        <td class="subject-name"><?= htmlspecialchars($c['subject_code']) ?><div class="text-muted"><?= htmlspecialchars($c['subject_name']) ?></div></td>
                        <td class="time-cell"><?= htmlspecialchars($c['day']) ?><br><span><?= htmlspecialchars(date('g:i A', strtotime($c['time_start']))) ?> – <?= htmlspecialchars(date('g:i A', strtotime($c['time_end']))) ?></span></td>
                        <td class="room-cell"><?= htmlspecialchars($c['room_name']) ?></td>
                        <td>
                          <button type="button" class="btn btn-outline" style="padding:4px 10px;font-size:12px" data-view-roster="<?= (int)$c['schedule_id'] ?>">View</button>
                        </td>
                      </tr>
                    <?php endforeach; ?>
                  <?php endforeach; ?>
                <?php else: ?>
                  <tr><td colspan="6" style="text-align:center;">No class assignments yet.</td></tr>
                <?php endif; ?>
              </tbody>
            </table>
          </div>
        </div>
      <?php endif; ?>
    </div>

    <!-- Roster Modal -->
    <div id="rosterModal" class="modal-overlay">
      <div class="modal-box">
        <div class="modal-header">
          <div class="modal-header-left">
            <div class="modal-icon">🧑‍🎓</div>
            <div>
              <div class="modal-title">Class Roster</div>
              <div class="modal-subtitle" id="rosterSubtitle"></div>
            </div>
          </div>
          <button type="button" class="modal-close" data-close="rosterModal">✕</button>
        </div>
        <div class="modal-body">
          <table class="data-table" id="rosterTable">
            <thead>
              <tr>
                <th>Student No.</th>
                <th>Full Name</th>
              </tr>
            </thead>
            <tbody id="rosterTableBody"></tbody>
          </table>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-outline" id="exportRosterCsv">Export CSV</button>
          <button type="button" class="btn btn-outline" data-close="rosterModal">Close</button>
        </div>
      </div>
    </div>

  </main>
</div>

<?php
$extraScripts = [
    '/SIAdrafts/Frontend/Js/Professor/' . ($pageScript ?? 'professor') . '.js',
];
include '../Include/footer.php';
?>
