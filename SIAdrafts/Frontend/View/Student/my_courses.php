<?php
$pageTitle  = "My Courses";
$activePage = "my_courses";

require_once __DIR__ . '/../../../Backend/require_student.php';
require_student();
require_once __DIR__ . '/../../../Backend/db.php';
require_once __DIR__ . '/../../../Backend/Student/course_data.php';

$db   = new Database();
$conn = $db->connect();

$applicantId = (int)$_SESSION['student_id'];
$courses     = get_my_courses($conn, $applicantId);

$db->close();

include __DIR__ . '/Include/header.php';
?>

<h1 class="sp-greeting">My Courses</h1>
<p class="sp-subline">Open a course to see its assignments, materials, quizzes, and groups all in one place.</p>

<?php if (empty($courses)): ?>
  <div class="sp-empty">
    <iconify-icon icon="mdi:bookshelf"></iconify-icon>
    <p><strong>No courses yet.</strong></p>
    <p>Classes you're enrolled in will appear here once the Registrar's Office processes your enrollment.</p>
  </div>
<?php else: ?>

<div style="display:grid; grid-template-columns: repeat(auto-fill, minmax(260px, 1fr)); gap:16px;">
  <?php foreach ($courses as $i => $c): ?>
    <a class="sp-course-card" style="--row-i:<?= $i ?>" href="/SIAdrafts/Frontend/View/Student/course_detail.php?subject_id=<?= (int)$c['subject_id'] ?>">
      <p class="sp-course-card-code"><?= htmlspecialchars($c['subject_code'], ENT_QUOTES) ?></p>
      <p class="sp-course-card-title"><?= htmlspecialchars($c['subject_name'], ENT_QUOTES) ?></p>
      <div class="sp-course-card-meta">
        <?php foreach ($c['professors'] as $prof): ?>
          <span><iconify-icon icon="mdi:account-tie-outline"></iconify-icon> <?= htmlspecialchars($prof, ENT_QUOTES) ?></span>
        <?php endforeach; ?>
      </div>
    </a>
  <?php endforeach; ?>
</div>

<?php endif; ?>

<?php include __DIR__ . '/Include/footer.php'; ?>
