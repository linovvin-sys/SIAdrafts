<?php

$pageTitle = "COURSE AND SECTIONS";
$activePage = "courses";

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
                    <button class="btn btn-primary">
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
                    <button class="btn btn-primary">
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

<?php include 'Include/footer.php'; ?>