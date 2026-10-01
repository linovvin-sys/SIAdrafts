<?php
require_once __DIR__ . '/../../../Backend/require_professor.php';
require_professor();
require_once __DIR__ . '/../../../Backend/db.php';
require_once __DIR__ . '/../../../Backend/Professor/schedule_data.php';

$scheduleId = (int)($_GET['schedule_id'] ?? 0);
if (!$scheduleId) {
    header('Location: /SIAdrafts/Frontend/View/Professor/classes.php');
    exit;
}

$db   = new Database();
$conn = $db->connect();

$professorId = (int)$_SESSION['professor_id'];
$course      = get_professor_class_by_schedule($conn, $scheduleId, $professorId);

$db->close();

if (!$course) {
    header('Location: /SIAdrafts/Frontend/View/Professor/classes.php');
    exit;
}

// Only tabs that actually have a built partial show up in the subnav --
// Phase 1 ships Assignments + Materials; Quizzes/Groups/Grades/Attendance/
// Announcements light up automatically as their course_tabs/*.php files
// are added in Phase 2, with zero changes needed here.
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

$pageTitle  = $course['subject_code'] . ' — ' . $tabs[$tab]['label'];
$activePage = 'classes';
$pageScript = $tab;

include __DIR__ . '/Include/header.php';
?>

<a class="sp-course-back" href="/SIAdrafts/Frontend/View/Professor/classes.php">
  <iconify-icon icon="mdi:arrow-left"></iconify-icon> My Courses
</a>

<div class="sp-course-header">
  <div class="sp-course-header-main">
    <p class="sp-course-code"><?= htmlspecialchars($course['subject_code'], ENT_QUOTES) ?></p>
    <h1 class="sp-course-title"><?= htmlspecialchars($course['subject_name'], ENT_QUOTES) ?></h1>
  </div>
  <div class="sp-course-header-meta">
    <span class="sp-pill enrolled"><?= htmlspecialchars($course['section_name'], ENT_QUOTES) ?></span>
    <span class="sp-course-meta-item"><iconify-icon icon="mdi:calendar-week"></iconify-icon> <?= htmlspecialchars($course['day'], ENT_QUOTES) ?>, <?= date('g:ia', strtotime($course['time_start'])) ?>–<?= date('g:ia', strtotime($course['time_end'])) ?></span>
    <span class="sp-course-meta-item"><iconify-icon icon="mdi:map-marker-outline"></iconify-icon> <?= htmlspecialchars($course['room_name'], ENT_QUOTES) ?></span>
  </div>
</div>

<nav class="sp-course-tabs" aria-label="Course sections">
  <?php foreach ($tabs as $key => $t): ?>
    <a class="sp-course-tab <?= $key === $tab ? 'active' : '' ?>" href="?schedule_id=<?= $scheduleId ?>&tab=<?= $key ?>">
      <iconify-icon icon="<?= $t['icon'] ?>"></iconify-icon> <?= htmlspecialchars($t['label'], ENT_QUOTES) ?>
    </a>
  <?php endforeach; ?>
</nav>

<div class="sp-section sp-course-tab-panel">
  <?php include __DIR__ . "/Include/course_tabs/$tab.php"; ?>
</div>

<?php include __DIR__ . '/Include/footer.php'; ?>
