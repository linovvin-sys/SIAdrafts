<?php
$pageTitle  = "STUDENT PROFILE";
$activePage = "enrollment";

require_once '../../../Backend/auth.php';
require_once '../../../Backend/require_role.php';
require_role(['Registrar Staff', 'Head Registrar']);
require_once '../../../Backend/db.php';

$db   = new Database();
$conn = $db->connect();

$studentNo = trim($_GET['student_no'] ?? '');
$student = null;

if ($studentNo !== '') {
    $stmt = $conn->prepare("
        SELECT s.student_id, s.student_no, s.first_name, s.last_name, s.middle_name,
               a.applicant_id, c.course_code, c.course_name, sec.section_name
        FROM student s
        LEFT JOIN applicants a ON a.applicant_id = s.applicant_id
        LEFT JOIN course c ON c.course_id = a.course_id
        LEFT JOIN section sec ON sec.section_id = s.section_id
        WHERE s.student_no = ?
    ");
    $stmt->bind_param('s', $studentNo);
    $stmt->execute();
    $student = $stmt->get_result()->fetch_assoc() ?: null;
    $stmt->close();
}

$latestEnrollment = null;
$balance = 0.0;
$activity = [];

if ($student) {
    $stmt = $conn->prepare("
        SELECT e.enrollment_id, e.school_year, e.semester, e.year_level, e.status, e.created_at
        FROM enrollment e
        WHERE e.student_id = ?
        ORDER BY e.created_at DESC
        LIMIT 1
    ");
    $stmt->bind_param('i', $student['applicant_id']);
    $stmt->execute();
    $latestEnrollment = $stmt->get_result()->fetch_assoc() ?: null;
    $stmt->close();

    // Activity ledger: every enrollment + every payment for this student,
    // merged chronologically -- real records, not placeholders.
    $stmt = $conn->prepare("
        SELECT CONCAT('Enrolled — A.Y. ', e.school_year, ', ', e.year_level, CASE e.year_level WHEN 1 THEN 'st' WHEN 2 THEN 'nd' WHEN 3 THEN 'rd' ELSE 'th' END, ' Year') AS label, e.created_at AS ts
        FROM enrollment e WHERE e.student_id = ?
    ");
    $stmt->bind_param('i', $student['applicant_id']);
    $stmt->execute();
    $res = $stmt->get_result();
    while ($row = $res->fetch_assoc()) $activity[] = $row;
    $stmt->close();

    $stmt = $conn->prepare("
        SELECT CONCAT('Payment received — ₱', FORMAT(p.downpayment, 2)) AS label, p.paid_at AS ts
        FROM payment p
        JOIN enrollment e ON e.enrollment_id = p.enrollment_id
        WHERE e.student_id = ? AND p.paid_at IS NOT NULL
    ");
    $stmt->bind_param('i', $student['applicant_id']);
    $stmt->execute();
    $res = $stmt->get_result();
    while ($row = $res->fetch_assoc()) {
        $activity[] = ['label' => $row['label'], 'ts' => $row['ts']];
    }
    $stmt->close();

    // Balance shown on the card is for the CURRENT term only, and must show
    // the full amount due even before any payment has been recorded — not
    // 0.0 (which the old paid_at-filtered query would silently default to
    // for a freshly-enrolled, not-yet-paid student).
    if ($latestEnrollment) {
        $stmt = $conn->prepare("SELECT balance FROM payment WHERE enrollment_id = ? LIMIT 1");
        $stmt->bind_param('i', $latestEnrollment['enrollment_id']);
        $stmt->execute();
        $balRow = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        if ($balRow) $balance = (float)$balRow['balance'];
    }

    usort($activity, fn($a, $b) => strtotime($b['ts']) <=> strtotime($a['ts']));
}

$db->close();

function sp_initials(string $first, string $last): string {
    $i = '';
    if ($first !== '') $i .= strtoupper($first[0]);
    if ($last !== '') $i .= strtoupper($last[0]);
    return $i ?: '?';
}

include '../Include/header.php';
?>

<div class="app-layout">

    <?php include '../Include/sidebar.php'; ?>

    <main class="page-content">

        <?php if (!$student): ?>
            <div class="surface-2 rd-empty-state">
                <div class="rd-empty-icon">?</div>
                <div class="rd-empty-title">Student not found</div>
                <div class="rd-empty-sub">Check the student number and try again, or look them up from the Enrollment list.</div>
                <a href="enrollment.php" class="btn-secondary" style="margin-top:8px;">&larr; Back to Enrollment</a>
            </div>
        <?php else: ?>
            <div class="grid-2">
                <div class="surface-2" style="padding:24px; text-align:center;">
                    <div class="rd-avatar seal" style="width:64px;height:64px;font-size:20px; margin:0 auto 14px;">
                        <?= htmlspecialchars(sp_initials($student['first_name'], $student['last_name'])) ?>
                    </div>
                    <div style="font-weight:600; font-size:16px;"><?= htmlspecialchars($student['last_name'] . ', ' . $student['first_name']) ?></div>
                    <div class="row-secondary mono" style="margin:4px 0 12px;"><?= htmlspecialchars($student['student_no']) ?></div>
                    <?php if ($latestEnrollment): ?>
                        <span class="stamp <?= strtolower($latestEnrollment['status']) === 'enrolled' ? 'approved' : 'pending' ?>" style="justify-content:center;">
                            <?= htmlspecialchars($latestEnrollment['status']) ?>
                        </span>
                    <?php endif; ?>
                    <div class="rd-divider-tick"></div>
                    <div style="text-align:left; font-size:13px;">
                        <div class="flex" style="justify-content:space-between; padding:8px 0;">
                            <span class="row-secondary">Program</span>
                            <span><?= htmlspecialchars($student['course_code'] ?? '—') ?><?= $latestEnrollment ? ' · ' . (int)$latestEnrollment['year_level'] . htmlspecialchars(match((int)$latestEnrollment['year_level']){1=>'st',2=>'nd',3=>'rd',default=>'th'}) . ' Year' : '' ?></span>
                        </div>
                        <div class="flex" style="justify-content:space-between; padding:8px 0;">
                            <span class="row-secondary">Section</span>
                            <span><?= htmlspecialchars($student['section_name'] ?? '—') ?></span>
                        </div>
                        <div class="flex" style="justify-content:space-between; padding:8px 0;">
                            <span class="row-secondary">Balance</span>
                            <span class="mono" style="color:<?= $balance > 0 ? 'var(--seal-600)' : 'var(--teal-600)' ?>;">₱<?= number_format($balance, 2) ?></span>
                        </div>
                    </div>
                </div>
                <div class="surface-2" style="padding:20px 4px;">
                    <div class="rd-section-title" style="padding:0 18px;">Activity ledger</div>
                    <?php if (empty($activity)): ?>
                        <div class="rd-empty-state">
                            <div class="rd-empty-icon">✓</div>
                            <div class="rd-empty-title">No activity yet</div>
                            <div class="rd-empty-sub">Enrollment and payment history will appear here.</div>
                        </div>
                    <?php else: ?>
                        <div class="ledger">
                            <?php foreach ($activity as $a): ?>
                                <div class="ledger-row">
                                    <div style="flex:1;"><?= htmlspecialchars($a['label']) ?></div>
                                    <div class="mono row-secondary"><?= date('M d, Y', strtotime($a['ts'])) ?></div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        <?php endif; ?>

    </main>

</div>

<?php include '../Include/footer.php'; ?>
