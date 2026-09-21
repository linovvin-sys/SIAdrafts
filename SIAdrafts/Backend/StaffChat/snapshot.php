<?php
/**
 * Builds the ONLY data an internal-Dotty request is ever grounded on. The
 * role scoping happens entirely here, server-side, before Gemini is ever
 * called — not by asking the model to police itself. A Treasury account
 * gets the Treasury block; nothing else exists in that request at all, so
 * there's no prompt-injection path that can surface Admission or Registrar
 * figures to someone who shouldn't see them — the data was simply never
 * sent.
 *
 * Every query here is a fixed, hardcoded read — no user input ever reaches
 * SQL through this file.
 */

function staff_chat_snapshot(mysqli $conn, string $roleName): string
{
    $role = strtolower(trim($roleName));
    $blocks = [];

    if (in_array($role, ['treasury', 'admin'], true)) {
        $blocks[] = treasury_snapshot_block($conn);
    }
    if (in_array($role, ['admission', 'admin'], true)) {
        $blocks[] = admission_snapshot_block($conn);
    }
    if (in_array($role, ['registrar staff', 'head registrar', 'admin'], true)) {
        $blocks[] = registrar_snapshot_block($conn);
    }
    if (in_array($role, ['staff', 'admin'], true)) {
        $blocks[] = enrollment_snapshot_block($conn);
    }

    if (empty($blocks)) {
        return "No live figures are available for this account's role.";
    }

    return implode("\n\n", $blocks);
}

function treasury_snapshot_block(mysqli $conn): string
{
    $row = $conn->query("
        SELECT
            COUNT(*) AS unpaid_count,
            COALESCE(SUM(balance), 0) AS unpaid_balance,
            COALESCE(SUM(downpayment), 0) AS collected_total,
            SUM(payment_status = 'Fully Paid') AS fully_paid_count
        FROM payment
    ")->fetch_assoc();

    // The actual "who" behind the unpaid_count above — students already
    // flagged overdue (same unpaid_students table transfer_overdue_unpaid()
    // in treasury.php populates) and still unresolved. Ordered by due date
    // so the most overdue lead the list; capped at 10 so a large backlog
    // doesn't blow out the prompt size.
    $critical = $conn->query("
        SELECT s.student_name, s.student_no, u.balance, u.due_date,
               DATEDIFF(CURDATE(), u.due_date) AS days_overdue
        FROM unpaid_students u
        JOIN student s ON s.student_id = u.student_id
        WHERE u.status = 'Pending'
        ORDER BY u.due_date ASC
        LIMIT 10
    ")->fetch_all(MYSQLI_ASSOC);

    $criticalText = 'None currently flagged overdue.';
    if ($critical) {
        $lines = array_map(function ($c) {
            return "  - {$c['student_name']} ({$c['student_no']}) — due {$c['due_date']}, {$c['days_overdue']} days overdue, balance \u{20B1}" . number_format((float)$c['balance'], 2);
        }, $critical);
        $criticalText = implode("\n", $lines);
    }

    // Same "who", this time for fully_paid_count above — most recently
    // settled first, capped at 10 for the same prompt-size reason.
    // enrollment.student_id is actually applicants.applicant_id (an odd but
    // established naming in this schema — see Backend/unpaid_transfer.php,
    // which resolves the same way) — student.applicant_id is the real link
    // to the student table, not student.student_id.
    $fullyPaid = $conn->query("
        SELECT s.student_name, s.student_no, p.amount_due, p.paid_at
        FROM payment p
        JOIN enrollment e ON e.enrollment_id = p.enrollment_id
        JOIN student s ON s.applicant_id = e.student_id
        WHERE p.payment_status = 'Fully Paid'
        ORDER BY p.paid_at DESC
        LIMIT 10
    ")->fetch_all(MYSQLI_ASSOC);

    $fullyPaidText = 'No fully paid accounts yet.';
    if ($fullyPaid) {
        $lines = array_map(function ($f) {
            $paidAt = $f['paid_at'] ? date('M j, Y', strtotime($f['paid_at'])) : 'date not recorded';
            return "  - {$f['student_name']} ({$f['student_no']}) — paid {$paidAt}, total \u{20B1}" . number_format((float)$f['amount_due'], 2);
        }, $fullyPaid);
        $fullyPaidText = implode("\n", $lines);
    }

    return "TREASURY\n"
        . "- Payments still owing a balance: {$row['unpaid_count']} (total outstanding: \u{20B1}" . number_format((float)$row['unpaid_balance'], 2) . ")\n"
        . "- Fully paid accounts: {$row['fully_paid_count']}\n"
        . "- Total collected to date (all downpayments/payments recorded): \u{20B1}" . number_format((float)$row['collected_total'], 2) . "\n"
        . "- Students flagged overdue and still unpaid (most overdue first, up to 10):\n{$criticalText}\n"
        . "- Fully paid students (most recently settled first, up to 10):\n{$fullyPaidText}";
}

function admission_snapshot_block(mysqli $conn): string
{
    $row = $conn->query("
        SELECT
            SUM(admission_status = 'pending_verification') AS pending,
            SUM(admission_status = 'verified') AS verified
        FROM applicants
    ")->fetch_assoc();

    $docs = $conn->query("
        SELECT COUNT(*) AS pending_docs FROM applicant_documents WHERE status = 'will_submit_later'
    ")->fetch_assoc();

    $pendingList = $conn->query("
        SELECT reference_id, first_name, last_name, program, created_at
        FROM applicants
        WHERE admission_status = 'pending_verification'
        ORDER BY created_at ASC
        LIMIT 10
    ")->fetch_all(MYSQLI_ASSOC);

    $pendingText = 'None currently pending.';
    if ($pendingList) {
        $lines = array_map(function ($a) {
            $waitingSince = date('M j, Y', strtotime($a['created_at']));
            return "  - {$a['first_name']} {$a['last_name']} ({$a['reference_id']}) — {$a['program']}, applied {$waitingSince}";
        }, $pendingList);
        $pendingText = implode("\n", $lines);
    }

    $docList = $conn->query("
        SELECT a.reference_id, a.first_name, a.last_name, ad.document_name
        FROM applicant_documents ad
        JOIN applicants a ON a.applicant_id = ad.applicant_id
        WHERE ad.status = 'will_submit_later'
        ORDER BY ad.uploaded_at ASC
        LIMIT 10
    ")->fetch_all(MYSQLI_ASSOC);

    $docText = 'None outstanding.';
    if ($docList) {
        $lines = array_map(function ($d) {
            return "  - {$d['first_name']} {$d['last_name']} ({$d['reference_id']}) — still owes: {$d['document_name']}";
        }, $docList);
        $docText = implode("\n", $lines);
    }

    return "ADMISSION\n"
        . "- Applicants pending document verification: {$row['pending']}\n"
        . "- Applicants already verified: {$row['verified']}\n"
        . "- Applicants awaiting verification (oldest first, up to 10):\n{$pendingText}\n"
        . "- Outstanding \"will submit later\" documents ({$docs['pending_docs']} total, up to 10 shown):\n{$docText}";
}

function registrar_snapshot_block(mysqli $conn): string
{
    $row = $conn->query("
        SELECT
            SUM(status = 'Pending') AS pending_sections,
            COUNT(*) AS total_sections
        FROM section
    ")->fetch_assoc();

    $sched = $conn->query("
        SELECT SUM(status = 'Pending') AS pending_schedules FROM schedule
    ")->fetch_assoc();

    $enr = $conn->query("
        SELECT
            SUM(status = 'Enrolled') AS enrolled,
            SUM(status = 'Pending Payment') AS pending_payment
        FROM enrollment
    ")->fetch_assoc();

    $pendingSections = $conn->query("
        SELECT s.section_name, s.capacity, c.course_code
        FROM section s
        LEFT JOIN course c ON c.course_id = s.course_id
        WHERE s.status = 'Pending'
        ORDER BY s.section_id ASC
        LIMIT 10
    ")->fetch_all(MYSQLI_ASSOC);

    $sectionsText = 'None currently pending.';
    if ($pendingSections) {
        $lines = array_map(function ($s) {
            $course = $s['course_code'] ?? 'no course set';
            return "  - {$s['section_name']} ({$course}), capacity {$s['capacity']}";
        }, $pendingSections);
        $sectionsText = implode("\n", $lines);
    }

    $pendingSchedules = $conn->query("
        SELECT sub.subject_code, sub.subject_name, sec.section_name, sc.day, sc.time_start, sc.time_end
        FROM schedule sc
        LEFT JOIN subject sub ON sub.subject_id = sc.subject_id
        LEFT JOIN section sec ON sec.section_id = sc.section_id
        WHERE sc.status = 'Pending'
        ORDER BY sc.schedule_id ASC
        LIMIT 10
    ")->fetch_all(MYSQLI_ASSOC);

    $schedulesText = 'None currently pending.';
    if ($pendingSchedules) {
        $lines = array_map(function ($s) {
            $start = date('g:ia', strtotime($s['time_start']));
            $end = date('g:ia', strtotime($s['time_end']));
            return "  - {$s['subject_code']} ({$s['section_name']}) — {$s['day']} {$start}-{$end}";
        }, $pendingSchedules);
        $schedulesText = implode("\n", $lines);
    }

    return "REGISTRAR\n"
        . "- Sections awaiting approval: {$row['pending_sections']} (of {$row['total_sections']} total sections):\n{$sectionsText}\n"
        . "- Schedules awaiting approval: {$sched['pending_schedules']}:\n{$schedulesText}\n"
        . "- Enrolled students this term: {$enr['enrolled']}\n"
        . "- Enrollments still waiting on payment: {$enr['pending_payment']}";
}

function enrollment_snapshot_block(mysqli $conn): string
{
    $row = $conn->query("
        SELECT
            SUM(status = 'Enrolled') AS enrolled,
            SUM(status = 'Pending Payment') AS pending_payment
        FROM enrollment
    ")->fetch_assoc();

    return "ENROLLMENT\n"
        . "- Enrolled students this term: {$row['enrolled']}\n"
        . "- Enrollments still waiting on payment: {$row['pending_payment']}";
}
