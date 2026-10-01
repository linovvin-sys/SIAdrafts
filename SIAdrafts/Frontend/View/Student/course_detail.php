<?php
require_once __DIR__ . '/../../../Backend/require_student.php';
require_student();
require_once __DIR__ . '/../../../Backend/db.php';
require_once __DIR__ . '/../../../Backend/Student/course_data.php';

$subjectId = (int)($_GET['subject_id'] ?? 0);
if (!$subjectId) {
    header('Location: /SIAdrafts/Frontend/View/Student/my_courses.php');
    exit;
}

$db   = new Database();
$conn = $db->connect();

$applicantId = (int)$_SESSION['student_id'];
$course      = get_my_course_by_subject($conn, $subjectId, $applicantId);

if (!$course) {
    $db->close();
    header('Location: /SIAdrafts/Frontend/View/Student/my_courses.php');
    exit;
}

// Same auto-discovery as the Professor shell -- only tabs with a built
// partial show up.
$allTabs = [
    'assignments'   => ['label' => 'Assignments',   'icon' => 'mdi:file-document-edit-outline'],
    'materials'     => ['label' => 'Materials',     'icon' => 'mdi:folder-multiple-outline'],
    'quizzes'       => ['label' => 'Quizzes',       'icon' => 'mdi:clipboard-text-clock-outline'],
    'groups'        => ['label' => 'Groups',        'icon' => 'mdi:account-group-outline'],
    'grades'        => ['label' => 'Grades',        'icon' => 'mdi:school-outline'],
    'attendance'    => ['label' => 'Attendance',    'icon' => 'mdi:clipboard-check-outline'],
    'announcements' => ['label' => 'Announcements', 'icon' => 'mdi:bullhorn-outline'],
];
$tabs = array_filter($allTabs, fn($key) => is_file(__DIR__ . "/Include/course_tabs/$key.php"), ARRAY_FILTER_USE_KEY);

$tab = (string)($_GET['tab'] ?? 'assignments');
if (!isset($tabs[$tab])) {
    $tab = array_key_first($tabs);
}

// Student tabs are server-rendered PHP (same convention as the existing
// Assignments/Materials pages -- confirmed neither uses a JS fetch), so
// the tab's data is loaded here, before header.php, same as any other
// Student page. Every loader below is scoped by subject_id, not
// schedule_id -- a subject can have more than one schedule block
// (lecture/lab), and each _for_subject() function merges across all of
// them so the student sees one unified feed per course.
$tabData = [];
if ($tab === 'assignments') {
    require_once __DIR__ . '/../../../Backend/Student/assignment_data.php';
    $tabData['assignments'] = get_my_assignments_for_subject($conn, $applicantId, $subjectId);
} elseif ($tab === 'materials') {
    require_once __DIR__ . '/../../../Backend/Student/material_data.php';
    $tabData['materials'] = get_my_materials_for_subject($conn, $applicantId, $subjectId);
} elseif ($tab === 'groups') {
    require_once __DIR__ . '/../../../Backend/Student/group_data.php';
    $tabData['groups'] = get_my_groups_for_subject($conn, $subjectId, $applicantId);
} elseif ($tab === 'attendance') {
    require_once __DIR__ . '/../../../Backend/Student/attendance_data.php';
    $tabData['attendance'] = get_my_attendance_for_subject($conn, $applicantId, $subjectId);
} elseif ($tab === 'grades') {
    require_once __DIR__ . '/../../../Backend/Student/grade_data.php';
    $tabData['grades'] = get_my_grades_for_subject($conn, $applicantId, $subjectId);
} elseif ($tab === 'quizzes') {
    require_once __DIR__ . '/../../../Backend/Student/quiz_data.php';
    $tabData['quizzes'] = get_my_quizzes_for_subject($conn, $applicantId, $subjectId);
} elseif ($tab === 'announcements') {
    require_once __DIR__ . '/../../../Backend/Student/announcement_data.php';
    $tabData['announcements'] = get_my_announcements_for_subject($conn, $applicantId, $subjectId);
}

$db->close();

$pageTitle  = $course['subject_code'] . ' — ' . $tabs[$tab]['label'];
$activePage = 'my_courses';
// Only Assignments needs its own script (the submission-form handler);
// Materials is pure links/text, no JS. Mirrors $pageScript per-tab the
// same way Professor/course_detail.php does, just PHP-rendered instead
// of fetch-driven.
if ($tab === 'assignments') {
    $pageScript = 'assignments';
} elseif ($tab === 'quizzes') {
    $pageScript = 'quizzes';
}

include __DIR__ . '/Include/header.php';
?>

<a class="sp-course-back" href="/SIAdrafts/Frontend/View/Student/my_courses.php">
  <iconify-icon icon="mdi:arrow-left"></iconify-icon> My Courses
</a>

<div class="sp-course-header">
  <div class="sp-course-header-main">
    <p class="sp-course-code"><?= htmlspecialchars($course['subject_code'], ENT_QUOTES) ?></p>
    <h1 class="sp-course-title"><?= htmlspecialchars($course['subject_name'], ENT_QUOTES) ?></h1>
  </div>
  <div class="sp-course-header-meta">
    <?php foreach ($course['professors'] as $prof): ?>
      <span class="sp-pill enrolled"><iconify-icon icon="mdi:account-tie-outline"></iconify-icon> <?= htmlspecialchars($prof, ENT_QUOTES) ?></span>
    <?php endforeach; ?>
  </div>
</div>

<nav class="sp-course-tabs" aria-label="Course sections">
  <?php foreach ($tabs as $key => $t): ?>
    <a class="sp-course-tab <?= $key === $tab ? 'active' : '' ?>" href="?subject_id=<?= $subjectId ?>&tab=<?= $key ?>">
      <iconify-icon icon="<?= $t['icon'] ?>"></iconify-icon> <?= htmlspecialchars($t['label'], ENT_QUOTES) ?>
    </a>
  <?php endforeach; ?>
</nav>

<div class="sp-course-tab-panel">
  <?php include __DIR__ . "/Include/course_tabs/$tab.php"; ?>
</div>

<?php include __DIR__ . '/Include/footer.php'; ?>
