<?php
$pageTitle  = "DASHBOARD";
$activePage = "dashboard";
$pageScript = "dashboard";

require_once '../../../Backend/auth.php';
require_once '../../../Backend/require_role.php';
require_once '../../../Backend/roles.php';
require_role([ROLE_PROFESSOR]);
require_once '../../../Backend/db.php';
require_once '../../../Backend/settings.php';

$db   = new Database();
$conn = $db->connect();

$profStmt = $conn->prepare("SELECT professor_id, first_name, last_name FROM professor WHERE professor_id = ? LIMIT 1");
$profStmt->bind_param('i', $_SESSION['professor_id']);
$profStmt->execute();
$professor = $profStmt->get_result()->fetch_assoc();
$profStmt->close();

$todayClasses   = [];
$totalClasses   = 0;
$totalStudents  = 0;
$pendingClasses = [];

if ($professor) {
    $professor_id = $professor['professor_id'];
    $today        = date('l'); // e.g. "Monday"
    $school_year  = get_setting('current_school_year') ?? '';
    $semester     = (int)(get_setting('current_semester') ?? 0);

    $todayStmt = $conn->prepare("
        SELECT sub.subject_code, sub.subject_name, sec.section_name, r.room_name,
               s.time_start, s.time_end
        FROM schedule s
        JOIN subject sub ON sub.subject_id = s.subject_id
        JOIN section sec ON sec.section_id = s.section_id
        JOIN room r       ON r.room_id = s.room_id
        WHERE s.professor_id = ? AND s.is_active = 1 AND s.status = 'Approved' AND s.day = ?
        ORDER BY s.time_start
    ");
    $todayStmt->bind_param('is', $professor_id, $today);
    $todayStmt->execute();
    $todayClasses = $todayStmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $todayStmt->close();

    $countStmt = $conn->prepare("SELECT COUNT(*) AS cnt FROM schedule WHERE professor_id = ? AND is_active = 1 AND status = 'Approved'");
    $countStmt->bind_param('i', $professor_id);
    $countStmt->execute();
    $totalClasses = (int)($countStmt->get_result()->fetch_assoc()['cnt'] ?? 0);
    $countStmt->close();

    // Distinct students across all of this professor's approved offerings
    // for the current term — same applicant_id join fix as the roster
    // endpoints (enrollment.student_id is really applicants.applicant_id).
    $headcountStmt = $conn->prepare("
        SELECT COUNT(DISTINCT st.student_id) AS cnt
        FROM schedule s
        JOIN enrollment e ON e.section_id = s.section_id AND e.school_year = s.school_year AND e.semester = s.semester
        JOIN enrollment_subject es ON es.enrollment_id = e.enrollment_id AND es.subject_id = s.subject_id AND es.status = 'Enrolled'
        JOIN student st ON st.applicant_id = e.student_id
        WHERE s.professor_id = ? AND s.is_active = 1 AND s.status = 'Approved'
          AND s.school_year = ? AND s.semester = ?
    ");
    $headcountStmt->bind_param('isi', $professor_id, $school_year, $semester);
    $headcountStmt->execute();
    $totalStudents = (int)($headcountStmt->get_result()->fetch_assoc()['cnt'] ?? 0);
    $headcountStmt->close();

    $pendingStmt = $conn->prepare("
        SELECT sub.subject_code, sub.subject_name, sec.section_name, s.day, s.time_start, s.time_end
        FROM schedule s
        JOIN subject sub ON sub.subject_id = s.subject_id
        JOIN section sec ON sec.section_id = s.section_id
        WHERE s.professor_id = ? AND s.status = 'Pending'
        ORDER BY s.day, s.time_start
    ");
    $pendingStmt->bind_param('i', $professor_id);
    $pendingStmt->execute();
    $pendingClasses = $pendingStmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $pendingStmt->close();
}

$db->close();

include '../Include/header.php';
?>

<div class="app-layout">

  <?php include '../Include/sidebar.php'; ?>

  <main class="page-content">

    <?php if (!$professor): ?>
      <div class="panel">
        <div class="panel-body" style="padding:24px;">
          <p>Your account is not yet linked to a professor record. Please contact the Registrar's Office.</p>
        </div>
      </div>
    <?php else: ?>

      <div class="panel" style="margin-bottom:20px;">
        <div class="panel-body" style="padding:20px 24px;">
          <div style="font-size:14px;color:var(--text-muted);">Welcome back,</div>
          <div style="font-size:22px;font-weight:700;color:var(--navy);"><?= htmlspecialchars($professor['first_name'] . ' ' . $professor['last_name']) ?></div>
        </div>
      </div>

      <div style="display:flex; gap:20px; margin-bottom:20px; flex-wrap:wrap;">
        <div class="panel" style="flex:1; min-width:200px;">
          <div class="panel-body" style="padding:20px 24px;">
            <div style="font-size:13px;color:var(--text-muted);">Active class offerings</div>
            <div style="font-size:28px;font-weight:700;color:var(--navy);"><?= $totalClasses ?></div>
          </div>
        </div>
        <div class="panel" style="flex:1; min-width:200px;">
          <div class="panel-body" style="padding:20px 24px;">
            <div style="font-size:13px;color:var(--text-muted);">Students this term</div>
            <div style="font-size:28px;font-weight:700;color:var(--navy);"><?= $totalStudents ?></div>
          </div>
        </div>
      </div>

      <?php if (!empty($pendingClasses)): ?>
        <div class="panel" style="margin-bottom:20px; border-left:3px solid var(--gold);">
          <div class="panel-header">
            <span class="panel-title"><?= count($pendingClasses) ?> class<?= count($pendingClasses) === 1 ? '' : 'es' ?> pending Registrar approval</span>
          </div>
          <div class="panel-body" style="padding:0;">
            <div class="table-responsive">
              <table class="data-table">
                <thead>
                  <tr>
                    <th>Subject</th>
                    <th>Section</th>
                    <th>Day / Time</th>
                  </tr>
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
          <span class="panel-title">Today's Classes (<?= htmlspecialchars(date('l')) ?>)</span>
        </div>
        <div class="panel-body" style="padding:0;">
          <div class="table-responsive">
            <table class="data-table">
              <thead>
                <tr>
                  <th>Subject</th>
                  <th>Section</th>
                  <th>Room</th>
                  <th>Time</th>
                </tr>
              </thead>
              <tbody>
                <?php if (!empty($todayClasses)): ?>
                  <?php foreach ($todayClasses as $c): ?>
                    <tr>
                      <td><?= htmlspecialchars($c['subject_code']) ?><div class="text-muted"><?= htmlspecialchars($c['subject_name']) ?></div></td>
                      <td><?= htmlspecialchars($c['section_name']) ?></td>
                      <td><?= htmlspecialchars($c['room_name']) ?></td>
                      <td><?= htmlspecialchars(date('g:i A', strtotime($c['time_start']))) ?> – <?= htmlspecialchars(date('g:i A', strtotime($c['time_end']))) ?></td>
                    </tr>
                  <?php endforeach; ?>
                <?php else: ?>
                  <tr><td colspan="4" style="text-align:center;">No classes scheduled today.</td></tr>
                <?php endif; ?>
              </tbody>
            </table>
          </div>
        </div>
      </div>

    <?php endif; ?>

  </main>
</div>

<?php
include '../Include/footer.php';
?>
