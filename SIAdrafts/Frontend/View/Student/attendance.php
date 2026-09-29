<?php
$pageTitle  = "Attendance";
$activePage = "attendance";

require_once __DIR__ . '/../../../Backend/require_student.php';
require_student();
require_once __DIR__ . '/../../../Backend/db.php';
require_once __DIR__ . '/../../../Backend/Student/attendance_data.php';

$db   = new Database();
$conn = $db->connect();

$classes = get_my_attendance_all_classes($conn, (int)$_SESSION['student_id']);

$db->close();

include __DIR__ . '/Include/header.php';
?>

<h1 class="sp-greeting">Attendance</h1>
<p class="sp-subline">Your attendance record for each class.</p>

<?php if (empty($classes)): ?>
  <div class="sp-empty">
    <iconify-icon icon="mdi:clipboard-check-outline"></iconify-icon>
    <p><strong>No classes yet.</strong></p>
  </div>
<?php else: ?>

<div class="sp-section">
  <table class="sp-table">
    <thead><tr><th>Class</th><th>Sessions recorded</th><th>Attendance %</th></tr></thead>
    <tbody>
      <?php foreach ($classes as $c): ?>
        <tr>
          <td><?= htmlspecialchars($c['subject_code'], ENT_QUOTES) ?> — <?= htmlspecialchars($c['subject_name'], ENT_QUOTES) ?></td>
          <td class="sp-num"><?= (int)$c['total_sessions'] ?></td>
          <td class="sp-num"><?= $c['percent'] === null ? '—' : (int)$c['percent'] . '%' ?></td>
        </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
</div>

<?php endif; ?>

<?php include __DIR__ . '/Include/footer.php'; ?>
