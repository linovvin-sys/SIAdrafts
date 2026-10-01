<?php
$pageTitle  = "Quiz";
$activePage = "quizzes";
$pageScript = "take_quiz";
$hideNav    = true;

require_once __DIR__ . '/../../../Backend/require_student.php';
require_student();
require_once __DIR__ . '/../../../Backend/db.php';
require_once __DIR__ . '/../../../Backend/Student/quiz_data.php';

$attemptId = (int)($_GET['attempt_id'] ?? 0);
if (!$attemptId) {
    header('Location: /SIAdrafts/Frontend/View/Student/my_courses.php');
    exit;
}

// Lightweight ownership peek only, so an attempt that isn't this
// student's 404s early -- the real, fresh state (timer/answers/questions)
// always comes from get_attempt_state.php on load, never baked in here.
$db   = new Database();
$conn = $db->connect();
$owned = load_owned_attempt($conn, $attemptId, (int)$_SESSION['student_id']);
$db->close();

if (!$owned) {
    header('Location: /SIAdrafts/Frontend/View/Student/my_courses.php');
    exit;
}

include __DIR__ . '/Include/header.php';
?>

<div class="sp-quiz-attempt-shell" id="quizAttemptShell" data-attempt-id="<?= $attemptId ?>">
  <div class="sp-empty">
    <span class="sp-loading-dots" style="margin-bottom:12px;"><span></span><span></span><span></span></span>
    <p>Loading your quiz…</p>
  </div>
</div>

<?php include __DIR__ . '/Include/footer.php'; ?>
