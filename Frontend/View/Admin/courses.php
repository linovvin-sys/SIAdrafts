<?php

$pageTitle = "COURSE AND SECTIONS";
$activePage = "courses";

require_once '../../../Backend/auth.php';
require_once __DIR__ . '/../../../Backend/admin/course_section.php';


include 'Include/header.php';

?>

<div class="app-layout">

    <?php include 'Include/sidebar.php'; ?>

    <main class="page-content">

        <div class="grid-2">

            <!-- Courses -->
            <div class="panel">

                <div class="panel-header">
                    <span class="panel-title">Courses</span>
                    <button type="button" class="btn btn-primary" id="btnAddCourse">
                        + Add Course
                    </button>
                </div>

                <div class="panel-body" style="padding:0;">

                    <table class="data-table">

                        <thead>
                            <tr>
                                <th>Code</th>
                                <th>Course Name</th>
                                <th>Units</th>
                            </tr>
                        </thead>

                        <tbody>

                        <?php if (!empty($courses)): ?>

                            <?php foreach ($courses as $row): ?>

                                <tr>

                                    <td><?= htmlspecialchars($row['course_code']); ?></td>

                                    <td><?= htmlspecialchars($row['course_name']); ?></td>

                                    <td><?= htmlspecialchars($row['total_units']); ?></td>

                                </tr>

                            <?php endforeach; ?>

                        <?php else: ?>

                            <tr>
                                <td colspan="3" style="text-align:center;">
                                    No courses found.
                                </td>
                            </tr>

                        <?php endif; ?>

                        </tbody>

                    </table>

                </div>

            </div>

            <!-- Sections -->
            <div class="panel">

                <div class="panel-header">
                    <span class="panel-title">Sections</span>
                    <button type="button" class="btn btn-primary" id="btnAddSection">
                        + Add Section
                    </button>
                </div>

                <div class="panel-body" style="padding:0;">

                    <table class="data-table">

                        <thead>
                            <tr>
                                <th>Section</th>
                                <th>Course</th>
                                <th>Capacity</th>
                                <th>Enrolled</th>
                            </tr>
                        </thead>

                        <tbody>

                        <?php if (!empty($sections)): ?>

                            <?php foreach ($sections as $row): ?>

                                <tr>

                                    <td><?= htmlspecialchars($row['section_name']); ?></td>

                                    <td><?= htmlspecialchars($row['course_code']); ?></td>

                                    <td><?= htmlspecialchars($row['capacity']); ?></td>

                                    <td><?= htmlspecialchars($row['enrolled']); ?></td>

                                </tr>

                            <?php endforeach; ?>

                        <?php else: ?>

                            <tr>
                                <td colspan="4" style="text-align:center;">
                                    No sections found.
                                </td>
                            </tr>

                        <?php endif; ?>

                        </tbody>

                    </table>

                </div>

            </div>

        </div>

    </main>

</div>

<!-- Add Course Modal -->
<div id="addCourseModal" class="modal-overlay">
    <div class="modal-box">

        <div class="modal-header">
            <div class="modal-header-left">
                <div class="modal-icon">📘</div>
                <div>
                    <div class="modal-title">Add Course</div>
                    <div class="modal-subtitle">Create a new course record</div>
                </div>
            </div>
            <button type="button" class="modal-close" data-close="addCourseModal">✕</button>
        </div>

        <form method="POST" action="">
            <input type="hidden" name="action" value="add_course">

            <div class="modal-body">

                <?php if (!empty($courseError)): ?>
                    <span class="error-msg"><?= htmlspecialchars($courseError) ?></span>
                <?php endif; ?>

                <div class="form-group">
                    <label class="form-label">Course Code<span class="required">*</span></label>
                    <input type="text" name="course_code" class="form-input" required>
                </div>

                <div class="form-group">
                    <label class="form-label">Course Name<span class="required">*</span></label>
                    <input type="text" name="course_name" class="form-input" required>
                </div>

                <div class="form-group">
                    <label class="form-label">Total Units</label>
                    <input type="number" name="total_units" class="form-input" value="0" min="0" required>
                </div>

            </div>

            <div class="modal-footer">
                <button type="button" class="btn btn-outline" data-close="addCourseModal">Cancel</button>
                <button type="submit" class="btn btn-primary">Save Course</button>
            </div>
        </form>
    </div>
</div>

<!-- Add Section Modal -->
<div id="addSectionModal" class="modal-overlay">
    <div class="modal-box">

        <div class="modal-header">
            <div class="modal-header-left">
                <div class="modal-icon">🏫</div>
                <div>
                    <div class="modal-title">Add Section</div>
                    <div class="modal-subtitle">Create a new section</div>
                </div>
            </div>
            <button type="button" class="modal-close" data-close="addSectionModal">✕</button>
        </div>

        <form method="POST" action="">
            <input type="hidden" name="action" value="add_section">

            <div class="modal-body">

                <?php if (!empty($sectionError)): ?>
                    <span class="error-msg"><?= htmlspecialchars($sectionError) ?></span>
                <?php endif; ?>

                <div class="form-group">
                    <label class="form-label">Section Name<span class="required">*</span></label>
                    <input type="text" name="section_name" class="form-input" required>
                </div>

                <div class="form-group">
                    <label class="form-label">Capacity</label>
                    <input type="number" name="capacity" class="form-input" value="40" min="1" required>
                </div>

                <div class="form-group">
                    <label class="form-label">Course<span class="required">*</span></label>
                    <div class="select-wrapper">
                        <select name="course_id" class="form-input form-select" required>
                            <option value="">-- Select Course --</option>
                            <?php foreach ($courses as $c): ?>
                                <option value="<?= $c['course_id'] ?>">
                                    <?= htmlspecialchars($c['course_code']) ?> - <?= htmlspecialchars($c['course_name']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>

            </div>

            <div class="modal-footer">
                <button type="button" class="btn btn-outline" data-close="addSectionModal">Cancel</button>
                <button type="submit" class="btn btn-primary">Save Section</button>
            </div>
        </form>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {

    function openModal(modal) {
        modal.classList.add('active');
        document.body.style.overflow = 'hidden';
    }
    function closeModal(modal) {
        modal.classList.remove('active');
        document.body.style.overflow = '';
    }

    const btnAddCourse    = document.getElementById('btnAddCourse');
    const btnAddSection   = document.getElementById('btnAddSection');
    const addCourseModal  = document.getElementById('addCourseModal');
    const addSectionModal = document.getElementById('addSectionModal');

    if (btnAddCourse && addCourseModal) {
        btnAddCourse.addEventListener('click', () => openModal(addCourseModal));
    }
    if (btnAddSection && addSectionModal) {
        btnAddSection.addEventListener('click', () => openModal(addSectionModal));
    }

    document.querySelectorAll('[data-close]').forEach(btn => {
        btn.addEventListener('click', () => {
            const target = document.getElementById(btn.getAttribute('data-close'));
            if (target) closeModal(target);
        });
    });

    document.querySelectorAll('.modal-overlay').forEach(overlay => {
        overlay.addEventListener('click', (e) => {
            if (e.target === overlay) closeModal(overlay);
        });
    });

    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape') {
            document.querySelectorAll('.modal-overlay.active').forEach(closeModal);
        }
    });

});
</script>

<?php include 'Include/footer.php'; ?>