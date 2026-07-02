<?php
$pageTitle = "ADMISSION";
$activePage = "admission";

require_once '../../../Backend/auth.php';
require_once __DIR__ . '/../../../Backend/admin/admission_view.php';

include 'Include/header.php';
?>

<div class="app-layout">

    <?php include 'Include/sidebar.php'; ?>

    <main class="page-content">

        <?php if (!$applicant): ?>

            <div class="panel">
                <div class="panel-body" style="text-align:center;">
                    <p class="text-muted">No admission record found for this ID.</p>
                    <a href="admin_admission.php" class="btn btn-outline mt-24">&larr; Back to Admission Records</a>
                </div>
            </div>

        <?php else: ?>

            <?php
                $status = strtolower($applicant['status']);

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

                $fullName = trim($applicant['first_name'] . ' ' . $applicant['middle_name'] . ' ' . $applicant['last_name']);
            ?>

            <div class="panel" id="printArea">

                <div class="panel-header no-print">
                    <span class="panel-title">Admission Registration Form</span>
                    <div style="display:flex; gap:10px;">
                        <a href="admin_admission.php" class="btn btn-outline">&larr; Back</a>
                        <button type="button" class="btn btn-primary" onclick="window.print()">🖨 Print</button>
                    </div>
                </div>

                <div class="panel-body">

                    <div class="print-header">
                        <h2 style="font-size:20px; font-weight:800; color:var(--navy); text-transform:uppercase;">
                            Student Registration / Admission Form
                        </h2>
                        <p class="text-muted">
                            Application ID: <?= htmlspecialchars($applicant['student_id']); ?>
                            &nbsp;|&nbsp;
                            Date Filed: <?= date('M d, Y', strtotime($applicant['created_at'])); ?>
                        </p>
                    </div>

                    <div class="form-section">
                        <div class="section-title">Applicant Status</div>
                        <div class="grid-2">
                            <div class="form-group">
                                <span class="form-label">Application ID</span>
                                <span class="form-value"><?= htmlspecialchars($applicant['student_id']); ?></span>
                            </div>
                            <div class="form-group">
                                <span class="form-label">Status</span>
                                <span class="form-value">
                                    <span class="badge badge-<?= $badge; ?>"><?= htmlspecialchars($applicant['status']); ?></span>
                                </span>
                            </div>
                        </div>
                    </div>

                    <div class="modal-divider"></div>

                    <div class="form-section">
                        <div class="section-title">Personal Information</div>
                        <div class="grid-2">
                            <div class="form-group">
                                <span class="form-label">Full Name</span>
                                <span class="form-value"><?= htmlspecialchars($fullName); ?></span>
                            </div>
                            <div class="form-group">
                                <span class="form-label">Birth Date</span>
                                <span class="form-value"><?= date('M d, Y', strtotime($applicant['birth_date'])); ?></span>
                            </div>
                            <div class="form-group">
                                <span class="form-label">Sex</span>
                                <span class="form-value"><?= htmlspecialchars($applicant['sex']); ?></span>
                            </div>
                            <div class="form-group">
                                <span class="form-label">Civil Status</span>
                                <span class="form-value"><?= htmlspecialchars($applicant['civil_status']); ?></span>
                            </div>
                        </div>
                    </div>

                    <div class="modal-divider"></div>

                    <div class="form-section">
                        <div class="section-title">Contact & Address</div>
                        <div class="grid-2">
                            <div class="form-group">
                                <span class="form-label">Contact Number</span>
                                <span class="form-value"><?= htmlspecialchars($applicant['contact_number'] ?: '—'); ?></span>
                            </div>
                            <div class="form-group">
                                <span class="form-label">Email</span>
                                <span class="form-value"><?= htmlspecialchars($applicant['email'] ?: '—'); ?></span>
                            </div>
                            <div class="form-group" style="grid-column: 1 / -1;">
                                <span class="form-label">Home Address</span>
                                <span class="form-value"><?= htmlspecialchars($applicant['home_address']); ?></span>
                            </div>
                        </div>
                    </div>

                    <div class="modal-divider"></div>

                    <div class="form-section">
                        <div class="section-title">Guardian Information</div>
                        <div class="grid-2">
                            <div class="form-group">
                                <span class="form-label">Guardian Name</span>
                                <span class="form-value"><?= htmlspecialchars($applicant['guardian_name']); ?></span>
                            </div>
                            <div class="form-group">
                                <span class="form-label">Relationship</span>
                                <span class="form-value"><?= htmlspecialchars($applicant['guardian_relationship']); ?></span>
                            </div>
                            <div class="form-group">
                                <span class="form-label">Guardian Contact</span>
                                <span class="form-value"><?= htmlspecialchars($applicant['guardian_contact']); ?></span>
                            </div>
                            <div class="form-group">
                                <span class="form-label">Guardian Valid ID</span>
                                <span class="form-value">
                                    <?= htmlspecialchars($applicant['guardian_id_type'] ?: '—'); ?>
                                    <?= $applicant['guardian_id_number'] ? ' – ' . htmlspecialchars($applicant['guardian_id_number']) : ''; ?>
                                </span>
                            </div>
                        </div>
                    </div>

                    <div class="modal-divider"></div>

                    <div class="form-section">
                        <div class="section-title">Program & Admission Details</div>
                        <div class="grid-2">
                            <div class="form-group">
                                <span class="form-label">Program</span>
                                <span class="form-value">
                                    <?= htmlspecialchars($applicant['program']); ?>
                                    <?= $applicant['course_code'] ? ' (' . htmlspecialchars($applicant['course_code']) . ')' : ''; ?>
                                </span>
                            </div>
                            <div class="form-group">
                                <span class="form-label">Year Level</span>
                                <span class="form-value"><?= htmlspecialchars($applicant['year_level']); ?></span>
                            </div>
                            <div class="form-group">
                                <span class="form-label">Applicant Type</span>
                                <span class="form-value"><?= htmlspecialchars($applicant['applicant_type'] ?: '—'); ?></span>
                            </div>
                            <div class="form-group">
                                <span class="form-label">Start Term</span>
                                <span class="form-value"><?= htmlspecialchars($applicant['start_term']); ?></span>
                            </div>
                        </div>
                    </div>

                    <?php if (!empty($schoolHistory)): ?>
                        <div class="modal-divider"></div>

                        <div class="form-section">
                            <div class="section-title">Educational Background</div>

                            <?php foreach ($schoolHistory as $school): ?>
                                <div class="grid-2 mt-24" style="margin-top: 12px;">
                                    <div class="form-group">
                                        <span class="form-label">School Name</span>
                                        <span class="form-value"><?= htmlspecialchars($school['school_name']); ?></span>
                                    </div>
                                    <div class="form-group">
                                        <span class="form-label">School Year</span>
                                        <span class="form-value"><?= htmlspecialchars($school['school_year']); ?></span>
                                    </div>
                                    <div class="form-group" style="grid-column: 1 / -1;">
                                        <span class="form-label">School Address</span>
                                        <span class="form-value"><?= htmlspecialchars($school['school_address']); ?></span>
                                    </div>
                                    <div class="form-group">
                                        <span class="form-label">Strand</span>
                                        <span class="form-value"><?= htmlspecialchars($school['school_strand'] ?: '—'); ?></span>
                                    </div>
                                    <div class="form-group">
                                        <span class="form-label">GPA</span>
                                        <span class="form-value"><?= $school['school_gpa'] !== null ? htmlspecialchars($school['school_gpa']) : '—'; ?></span>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>

                    <?php if (!empty($documents)): ?>
                        <div class="modal-divider"></div>

                        <div class="form-section">
                            <div class="section-title">Documents Submitted</div>

                            <table class="data-table">
                                <thead>
                                    <tr>
                                        <th>Document</th>
                                        <th>Status</th>
                                        <th>Uploaded</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($documents as $doc): ?>
                                        <?php
                                            $docStatus = strtolower($doc['status']);
                                            $docBadge = match ($docStatus) {
                                                'submitted', 'verified' => 'success',
                                                'pending' => 'pending',
                                                'rejected' => 'danger',
                                                default => 'secondary',
                                            };
                                        ?>
                                        <tr>
                                            <td><?= htmlspecialchars($doc['document_name']); ?></td>
                                            <td><span class="badge badge-<?= $docBadge; ?>"><?= htmlspecialchars($doc['status']); ?></span></td>
                                            <td><?= date('M d, Y', strtotime($doc['uploaded_at'])); ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>

                    <div class="signature-block no-print-hide">
                        <div class="signature-line">
                            <div class="sig-box">
                                <div class="sig-underline"></div>
                                <span class="text-muted">Applicant Signature</span>
                            </div>
                            <div class="sig-box">
                                <div class="sig-underline"></div>
                                <span class="text-muted">Registrar / Admission Officer</span>
                            </div>
                        </div>
                    </div>

                </div>

            </div>

        <?php endif; ?>

    </main>

</div>

<style>
    .form-section {
        padding: 8px 0;
    }

    .section-title {
        font-size: 12px;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.8px;
        color: var(--gold-dark);
        margin-bottom: 14px;
    }

    .form-value {
        font-size: 14px;
        font-weight: 500;
        color: var(--navy);
        padding-top: 2px;
    }

    .print-header {
        text-align: center;
        padding-bottom: 20px;
        margin-bottom: 20px;
        border-bottom: 2px solid var(--navy);
    }

    .signature-block {
        margin-top: 48px;
    }

    .signature-line {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 40px;
    }

    .sig-box {
        text-align: center;
        font-size: 12px;
    }

    .sig-underline {
        border-bottom: 1.5px solid var(--navy);
        height: 40px;
        margin-bottom: 6px;
    }

    @media print {
        body * {
            visibility: hidden;
        }

        #printArea, #printArea * {
            visibility: visible;
        }

        #printArea {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            box-shadow: none;
            border: none;
        }

        .no-print {
            display: none !important;
        }

        .page-content {
            padding: 0 !important;
        }
    }
</style>

<?php include 'Include/footer.php'; ?>