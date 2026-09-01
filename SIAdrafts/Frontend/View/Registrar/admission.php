<?php
$pageTitle = "ADMISSION";
$activePage = "admission";
$pageScript = "admission";

require_once '../../../Backend/auth.php';
require_once '../../../Backend/require_role.php';
require_role(['Registrar Staff', 'Head Registrar', 'Admin']);
require_once __DIR__ . '/../../../Backend/admin/admission.php';

include '../Include/header.php';
?>

<div class="app-layout">

    <?php include '../Include/sidebar.php'; ?>

    <main class="page-content">
        <?php include '../Include/readonly_banner.php'; ?>

        <?php
            $admTotal      = count($admissions);
            $admFullyPaid  = count(array_filter($admissions, fn($a) => $a['status'] === 'Fully Paid'));
            $admPending    = count(array_filter($admissions, fn($a) => $a['status'] === 'Pending'));
        ?>
        <div class="clay-stat-grid clay-stat-grid--compact">
          <div class="clay-stat-card">
            <div class="clay-icon"><i class="bi bi-person-check-fill"></i></div>
            <div>
              <div class="stat-value"><?= $admTotal ?></div>
              <div class="stat-label">Total Records</div>
            </div>
          </div>
          <div class="clay-stat-card">
            <div class="seal"><i class="bi bi-check-lg"></i></div>
            <div>
              <div class="stat-value"><?= $admFullyPaid ?></div>
              <div class="stat-label">Fully Paid</div>
            </div>
          </div>
          <div class="clay-stat-card">
            <div class="clay-icon"><i class="bi bi-hourglass-split"></i></div>
            <div>
              <div class="stat-value"><?= $admPending ?></div>
              <div class="stat-label">Pending</div>
            </div>
          </div>
        </div>

        <div class="panel">

            <div class="panel-header">
                <span class="panel-title">All Admission Records</span>

            </div>

            <?php
                // Build the "Date Applied" dropdown from whatever months actually
                // appear in the data, newest first — no fixed/guessed list.
                $admissionDateOptions = [];
                foreach ($admissions as $row) {
                    $ym = date('Y-m', strtotime($row['created_at']));
                    $admissionDateOptions[$ym] = date('F Y', strtotime($row['created_at']));
                }
                krsort($admissionDateOptions);
            ?>

            <div class="panel-body clay-filter-bar" style="padding:16px 24px 0;">
                <div class="filter-bar">
                    <div class="select-wrapper">
                        <select class="form-input form-select" id="admissionStatusFilter">
                            <option value="">All Statuses</option>
                            <option value="Pending">Pending</option>
                            <option value="Downpayment Paid">Downpayment Paid</option>
                            <option value="Fully Paid">Fully Paid</option>
                        </select>
                    </div>
                    <div class="select-wrapper">
                        <select class="form-input form-select" id="admissionDateFilter">
                            <option value="">All Dates</option>
                            <?php foreach ($admissionDateOptions as $value => $label): ?>
                                <option value="<?= htmlspecialchars($value) ?>"><?= htmlspecialchars($label) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
            </div>

            <div class="panel-body" style="padding:0;">
                <div class="table-responsive">
                <table class="data-table rd-table-stagger" id="admissionTable">

                    <thead>
                        <tr>
                            <th>REFERENCE ID</th>
                            <th>Applicant Name</th>
                            <th>Program</th>
                            <th>Application Date</th>
                            <th>Status</th>
                            <th>Action</th>
                        </tr>
                    </thead>

                    <tbody id="admissionBody">

                    <?php if (!empty($admissions)): ?>

                        <?php foreach ($admissions as $rowIndex => $row): ?>

                            <?php
                                $status = strtolower($row['status']);

                                switch ($status) {
                                    case 'approved':
                                    case 'fully paid':
                                        $badge = 'success';
                                        break;

                                    case 'pending':
                                        $badge = 'pending';
                                        break;

                                    case 'downpayment paid':
                                        $badge = 'info';
                                        break;

                                    case 'rejected':
                                        $badge = 'danger';
                                        break;

                                    default:
                                        $badge = 'secondary';
                                }

                                $admissionSearchKey = strtolower($row['reference_id'] . ' ' . $row['applicant_name'] . ' ' . $row['program'] . ' ' . ($row['course_code'] ?? ''));
                                $admissionDateKey = date('Y-m', strtotime($row['created_at']));
                            ?>

                            <tr style="--row-i: <?= min((int)$rowIndex, 12) ?>;" data-status="<?= htmlspecialchars($row['status']) ?>" data-date="<?= htmlspecialchars($admissionDateKey) ?>" data-search="<?= htmlspecialchars($admissionSearchKey) ?>">

                                <td class="mono"><?= $row['reference_id']; ?></td>

                                <td><?= htmlspecialchars($row['applicant_name']); ?></td>

                                <td><?= htmlspecialchars($row['program']); ?></td>

                                <td class="mono"><?= date('M d, Y', strtotime($row['created_at'])); ?></td>

                                <td>
                                    <span class="stamp <?= $badge === 'success' ? 'approved' : ($badge === 'pending' ? 'pending' : ($badge === 'danger' ? 'rejected' : '')) ?>">
                                        <?= htmlspecialchars($row['status']); ?>
                                    </span>
                                </td>

                                <td>
                                    <a href="admission_view.php?id=<?= $row['reference_id']; ?>"
                                       class="btn btn-outline"
                                       style="padding:4px 10px;font-size:12px">
                                        View
                                    </a>
                                </td>

                            </tr>

                        <?php endforeach; ?>

                    <?php else: ?>

                        <tr>
                            <td colspan="6" style="text-align:center;">
                                No admission records found.
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