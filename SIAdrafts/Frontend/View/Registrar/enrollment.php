<?php

$pageTitle = "ENROLLMENT";
$activePage = "enrollment";
$pageScript = "enrollment";

require_once '../../../Backend/auth.php';
require_once '../../../Backend/require_role.php';
require_role(['Registrar Staff']);
require_once __DIR__ . '/../../../Backend/admin/enrollment.php';


include '../Include/header.php';

?>

<div class="app-layout">

    <?php include '../Include/sidebar.php'; ?>

    <main class="page-content">

        <!-- Statistics -->
        <div class="stats-grid" style="grid-template-columns:repeat(3,1fr);margin-bottom:24px;">

            <div class="stat-card">
                <div class="stat-icon gold">📝</div>
                <div>
                    <div class="stat-value"><?= $dashboard['enrolled']; ?></div>
                    <div class="stat-label">Total Enrolled</div>
                </div>
            </div>

            <div class="stat-card">
                <div class="stat-icon blue">⏳</div>
                <div>
                    <div class="stat-value"><?= $dashboard['pending_payment']; ?></div>
                    <div class="stat-label">Awaiting Payment</div>
                </div>
            </div>

            <div class="stat-card">
                <div class="stat-icon green">✅</div>
                <div>
                    <div class="stat-value"><?= $dashboard['fully_enrolled']; ?></div>
                    <div class="stat-label">Fully Enrolled</div>
                </div>
            </div>

        </div>

        <!-- Enrollment List -->
        <div class="panel">

            <div class="panel-header">
                <span class="panel-title">Enrollment List</span>
            </div>

            <?php
                // Dropdown options built from whatever's actually in the data —
                // no hardcoded year/section list that could drift from reality.
                $yearOptions = [];
                $semOptions  = [];
                $sectionOptions = [];
                foreach ($enrollmentList as $row) {
                    if (!empty($row['year_level'])) $yearOptions[(int)$row['year_level']] = true;
                    if (!empty($row['semester']))   $semOptions[(int)$row['semester']] = true;
                    if (!empty($row['section_name'])) $sectionOptions[$row['section_name']] = true;
                }
                ksort($yearOptions);
                ksort($semOptions);
                ksort($sectionOptions);

                function ordinal_year(int $n): string {
                    $suffix = $n === 1 ? 'st' : ($n === 2 ? 'nd' : ($n === 3 ? 'rd' : 'th'));
                    return $n . $suffix . ' Year';
                }
            ?>

            <div class="panel-body" style="padding:16px 24px 0;">
                <div class="filter-bar">
                    <div class="select-wrapper">
                        <select class="form-input form-select" id="enrollmentStatusFilter">
                            <option value="">All Statuses</option>
                            <option value="Enrolled">Enrolled</option>
                            <option value="Pending Payment">Pending Payment</option>
                        </select>
                    </div>
                    <div class="select-wrapper">
                        <select class="form-input form-select" id="enrollmentYearFilter">
                            <option value="">All Year Levels</option>
                            <?php foreach (array_keys($yearOptions) as $year): ?>
                                <option value="<?= $year ?>"><?= htmlspecialchars(ordinal_year($year)) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="select-wrapper">
                        <select class="form-input form-select" id="enrollmentSemFilter">
                            <option value="">All Semesters</option>
                            <?php foreach (array_keys($semOptions) as $sem): ?>
                                <option value="<?= $sem ?>"><?= $sem == 1 ? '1st Semester' : ($sem == 2 ? '2nd Semester' : htmlspecialchars("Sem $sem")) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="select-wrapper">
                        <select class="form-input form-select" id="enrollmentSectionFilter">
                            <option value="">All Sections</option>
                            <?php foreach (array_keys($sectionOptions) as $section): ?>
                                <option value="<?= htmlspecialchars($section) ?>"><?= htmlspecialchars($section) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
            </div>

            <div class="panel-body" style="padding:0;">
                <div class="table-responsive">
                <table class="data-table" id="enrollmentTable">

                    <thead>
                        <tr>
                            <th>Student ID</th>
                            <th>Name</th>
                            <th>Course</th>
                            <th>Section</th>
                            <th>Year / Sem</th>
                            <th>Payment</th>
                            <th>Status</th>
                        </tr>
                    </thead>

                    <tbody id="enrollmentBody">

                    <?php if (!empty($enrollmentList)): ?>

                        <?php foreach ($enrollmentList as $row): ?>

                            <?php

                            $status = strtolower($row['status']);

                            switch ($status) {

                                case 'active':
                                case 'enrolled':
                                    $badge = 'success';
                                    break;

                                case 'pending':
                                case 'pending payment':
                                    $badge = 'pending';
                                    break;

                                default:
                                    $badge = 'secondary';
                            }

                            $enrollmentSearchKey = strtolower($row['student_no'] . ' ' . $row['student_name'] . ' ' . $row['course_name'] . ' ' . $row['section_name']);

                            ?>

                            <tr data-status="<?= htmlspecialchars($row['status']) ?>"
                                data-year="<?= htmlspecialchars($row['year_level'] ?? '') ?>"
                                data-sem="<?= htmlspecialchars($row['semester'] ?? '') ?>"
                                data-section="<?= htmlspecialchars($row['section_name'] ?? '') ?>"
                                data-search="<?= htmlspecialchars($enrollmentSearchKey) ?>">

                                <td><?= htmlspecialchars($row['student_no']); ?></td>

                                <td><?= htmlspecialchars($row['student_name']); ?></td>

                                <td><?= htmlspecialchars($row['course_name']); ?></td>

                                <td><?= htmlspecialchars($row['section_name']); ?></td>

                                <td><?= !empty($row['year_level']) ? htmlspecialchars(ordinal_year((int)$row['year_level'])) : '—' ?><?= !empty($row['semester']) ? ' · Sem ' . (int)$row['semester'] : '' ?></td>

                                <td><?= htmlspecialchars($row['payment_status']); ?></td>

                                <td>
                                    <span class="badge badge-<?= $badge; ?>">
                                        <?= htmlspecialchars($row['status']); ?>
                                    </span>
                                </td>

                            </tr>

                        <?php endforeach; ?>

                    <?php else: ?>

                        <tr>
                            <td colspan="7" style="text-align:center;">
                                No enrolled students found.
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

<?php
$extraScripts = [
    'https://cdn.jsdelivr.net/npm/vue@3/dist/vue.global.prod.js',
    '/SIAdrafts/Frontend/Js/Registrar/' . ($pageScript ?? 'registrar') . '.js',
];
include '../Include/footer.php';
?>