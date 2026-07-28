<?php
session_start();
require_once '../db.php';
require_once '../roles.php';
require_once '../require_role.php';
require_once '../prereq.php';

header('Content-Type: application/json');

if (empty($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized.']);
    exit;
}

require_role([ROLE_STAFF, ROLE_ADMIN], true);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed.']);
    exit;
}

$db   = new Database();
$conn = $db->connect();

$raw  = file_get_contents('php://input');
$data = json_decode($raw, true);


if (!is_array($data)) {
    echo json_encode(['error' => 'Invalid request body.']);
    exit;
}

$student_id  = (int)($data['student_id']  ?? 0);
$school_year = trim($data['school_year']  ?? '');
$semester    = (int)($data['semester']    ?? 0);
$year_level  = (int)($data['year_level']  ?? 0);
$type_id     = (int)($data['type_id']     ?? 1);
$section_id  = !empty($data['section_id']) ? (int)$data['section_id'] : null;

const TYPE_IRREGULAR = 2;
$is_irregular = ($type_id === TYPE_IRREGULAR);

$subject_ids = array_values(array_unique(
    array_filter(array_map('intval', $data['subject_ids'] ?? []))
));

// Irregular: subject_ids and schedule_ids arrive as two parallel arrays

$subject_schedule = [];
if ($is_irregular) {
    $raw_subject_ids  = $data['subject_ids']  ?? [];
    $raw_schedule_ids = $data['schedule_ids'] ?? [];
    if (is_array($raw_subject_ids) && is_array($raw_schedule_ids)
        && count($raw_subject_ids) === count($raw_schedule_ids)) {
        foreach ($raw_subject_ids as $i => $rawSid) {
            $sid = (int)$rawSid;
            $chd = (int)($raw_schedule_ids[$i] ?? 0);
            if ($sid && $chd) $subject_schedule[] = ['subject_id' => $sid, 'schedule_id' => $chd];
        }
    }
}

$missing_common    = !$student_id || !$school_year || !$semester || !$year_level || !$type_id || empty($subject_ids);
$missing_regular   = !$is_irregular && !$section_id;
$missing_irregular = $is_irregular && empty($subject_schedule);

if ($missing_common || $missing_regular || $missing_irregular) {
    echo json_encode(['error' => 'Missing required fields.']);
    exit;
}

if (!preg_match('/^\d{4}-\d{4}$/', $school_year)) {
    echo json_encode(['error' => 'Invalid school year format. Use YYYY-YYYY.']);
    exit;
}

// Payment is now fully automated: amount_due comes from fee_schedule
// (keyed by year_level + school_year), the breakdown is copied from
// fee_schedule_item into payment_breakdown as a point-in-time snapshot,
// and due_date is fixed at "today + PAYMENT_DUE_DAYS". There is no
// manual treasury setup step anymore — setup_payment.php is kept only
// as a fallback for legacy enrollments created before this change.
const PAYMENT_DUE_DAYS = 3;

// The official, human-readable student ID. Generated here — at enrollment
// confirmation — not at admission, since not every applicant who gets a
// reference ID actually enrolls. Stored in student.student_no; the
// auto-increment student.student_id stays untouched as the internal PK.
function generate_student_no(mysqli $conn): string {
    $year = date('Y');

    $stmt = $conn->prepare(
        "SELECT student_no FROM student
         WHERE student_no LIKE ?
         ORDER BY student_no DESC
         LIMIT 1"
    );
    $like = $year . '-%';
    $stmt->bind_param('s', $like);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if ($row && preg_match('/^(\d{4})-(\d{5})$/', $row['student_no'], $m)) {
        $next_number = (int)$m[2] + 1;
    } else {
        $next_number = 1;
    }

    $padded = str_pad((string)$next_number, 5, '0', STR_PAD_LEFT);
    return "{$year}-{$padded}";
}

// Resolve the applicant's course — needed to scope subject/section
// validation. Fetched here, not trusted from the client payload.
$courseStmt = $conn->prepare("SELECT course_id, admission_status FROM applicants WHERE applicant_id = ? LIMIT 1");
if (!$courseStmt) {
    throw new RuntimeException('Database error: ' . $conn->error);
}
$courseStmt->bind_param('i', $student_id);
$courseStmt->execute();
$courseRow = $courseStmt->get_result()->fetch_assoc();
$courseStmt->close();

if (!$courseRow || !$courseRow['course_id']) {
    throw new RuntimeException('This applicant has no program assigned.');
}

// Enrollment can only be finalized for applicants who already cleared
// walk-in document verification. This mirrors the gate in get_student.php
// and enrollment_profile.php — enforced here too since this endpoint can
// be called directly, bypassing the UI wizard.
if (($courseRow['admission_status'] ?? '') !== 'verified') {
    echo json_encode(['error' => 'This applicant hasn\'t completed document verification yet. Verify them via Admission first, then come back to enroll.']);
    exit;
}

$course_id = (int)$courseRow['course_id'];

$conn->begin_transaction();

try {
    $credited_subject_ids = [];

    if ($is_irregular) {
        // Irregular: no single section to validate against. Each picked
        // (subject_id, schedule_id) pair is independently re-verified —
        // real, active, correct course/year/semester — and the whole set
        // is re-checked for time overlaps server-side (never trust the
        // client's conflict check alone).
        $slotStmt = $conn->prepare(
            "SELECT sch.schedule_id, sch.subject_id, sch.day, sch.time_start, sch.time_end
             FROM schedule sch
             JOIN subject sub ON sub.subject_id = sch.subject_id
             JOIN section sec ON sec.section_id = sch.section_id
             WHERE sch.schedule_id = ? AND sch.subject_id = ?
               AND sec.course_id = ? AND sub.year_level = ? AND sub.semester = ?
               AND sch.semester = ? AND sch.school_year = ? AND sch.is_active = 1
             LIMIT 1"
        );
        if (!$slotStmt) {
            throw new RuntimeException('Database error: ' . $conn->error);
        }

        $validatedSlots = [];
        foreach ($subject_schedule as $pick) {
            $slotStmt->bind_param(
                'iiiiiis',
                $pick['schedule_id'], $pick['subject_id'], $course_id, $year_level, $semester, $semester, $school_year
            );
            $slotStmt->execute();
            $row = $slotStmt->get_result()->fetch_assoc();
            if (!$row) {
                $slotStmt->close();
                throw new RuntimeException('One or more selected subjects are no longer available. Please go back and reselect.');
            }
            if (!subject_prereq_met($conn, $student_id, $pick['subject_id'])) {
                $slotStmt->close();
                $prereqLabel = subject_prereq_label($conn, $pick['subject_id']);
                throw new RuntimeException('Prerequisite not yet completed' . ($prereqLabel ? ": $prereqLabel" : '.') . '.');
            }
            foreach ($validatedSlots as $v) {
                if ($v['day'] === $row['day'] &&
                    $row['time_start'] < $v['time_end'] &&
                    $v['time_start'] < $row['time_end']) {
                    $slotStmt->close();
                    throw new RuntimeException('Two selected subjects have overlapping schedules. Please go back and reselect.');
                }
            }
            $validatedSlots[] = $row;
        }
        $slotStmt->close();

        $validatedSubjectIds = array_column($validatedSlots, 'subject_id');
        sort($validatedSubjectIds);
        $sentSubjectIds = $subject_ids;
        sort($sentSubjectIds);
        if ($validatedSubjectIds !== $sentSubjectIds) {
            throw new RuntimeException('Subject selection does not match validated schedule picks. Please go back and reselect.');
        }

        // Subject credits don't apply to the irregular path — credited
        // subjects are only relevant to Transferee/Regular's section-package
        // model, where a whole fixed load minus credits needs reconciling.

    } else {
        // Section must exist and actually be offering this exact subject load
        // for this year level / semester / school year. Re-validating here
        // (rather than trusting the client) matches the all-or-nothing section
        // package model from the subject-selection step.
        $secCheck = $conn->prepare(
            "SELECT COUNT(DISTINCT sch.subject_id)
            FROM schedule sch
            JOIN subject sub ON sub.subject_id = sch.subject_id
            JOIN section sec ON sec.section_id = sch.section_id
            WHERE sch.section_id = ? AND sec.course_id = ?
            AND sch.school_year = ? AND sch.semester = ?
            AND sub.year_level = ? AND sub.semester = ?"
        );
        if (!$secCheck) {
            throw new RuntimeException('Database error: ' . $conn->error);
        }
        $secCheck->bind_param('iisiii', $section_id, $course_id, $school_year, $semester, $year_level, $semester);
        $secCheck->execute();
        $offered_count = (int)$secCheck->get_result()->fetch_row()[0];
        $secCheck->close();

        if ($offered_count === 0) {
            throw new RuntimeException('Selected section is not offered for this year level / semester.');
        }

        // Enforce section capacity server-side — the client's picker can go
        // stale between load and submit if another staff member enrolls a
        // student into the same section in the meantime.
        $capStmt = $conn->prepare(
            "SELECT sec.capacity,
                    (SELECT COUNT(*) FROM enrollment e
                     JOIN student st ON st.student_id = e.student_id
                     WHERE e.section_id = sec.section_id
                       AND e.school_year = ? AND e.semester = ?
                       AND st.applicant_id != ?) AS taken
             FROM section sec WHERE sec.section_id = ?"
        );
        if (!$capStmt) {
            throw new RuntimeException('Database error: ' . $conn->error);
        }
        $capStmt->bind_param('siii', $school_year, $semester, $student_id, $section_id);
        $capStmt->execute();
        $capRow = $capStmt->get_result()->fetch_assoc();
        $capStmt->close();

        if ($capRow && $capRow['taken'] >= (int)$capRow['capacity']) {
            throw new RuntimeException('Selected section is already full. Please choose another section.');
        }

        // Re-derive credited subjects server-side — the session count is a
        // convenience, not a trust boundary.
        $creditedStmt = $conn->prepare(
            "SELECT sch.subject_id
            FROM schedule sch
            JOIN applicant_subject_credit c ON c.subject_id = sch.subject_id AND c.applicant_id = ?
            WHERE sch.section_id = ? AND sch.school_year = ? AND sch.semester = ?"
        );
        $creditedStmt->bind_param('iisi', $student_id, $section_id, $school_year, $semester);
        $creditedStmt->execute();
        $credited_subject_ids = array_map('intval', array_column($creditedStmt->get_result()->fetch_all(MYSQLI_ASSOC), 'subject_id'));
        $creditedStmt->close();

        $expected_billable = $offered_count - count($credited_subject_ids);
        if ($expected_billable !== count($subject_ids)) {
            throw new RuntimeException('Subject selection does not match the section\'s current offering. Please go back and reselect the section.');
        }
    }

    // Duplicate guard
    $dup = $conn->prepare(
        "SELECT enrollment_id FROM enrollment
         WHERE student_id = ? AND school_year = ? AND semester = ?
         LIMIT 1"
    );
    if (!$dup) {
        throw new RuntimeException('Database error: ' . $conn->error);
    }
    $dup->bind_param('isi', $student_id, $school_year, $semester);
    $dup->execute();
    $dup_result = $dup->get_result();
    if ($dup_result->fetch_assoc()) {
        $dup->close();
        throw new RuntimeException('Student is already enrolled for this term.');
    }
    $dup->close();

    // Look up the fee schedule for this year level / school year BEFORE
    // creating the enrollment, so we fail fast (and roll back nothing)
    // if treasury hasn't configured fees for this year level yet.
    $feeStmt = $conn->prepare(
        "SELECT fee_schedule_id, total_amount FROM fee_schedule
         WHERE year_level = ? AND school_year = ? AND is_active = 1
         LIMIT 1"
    );
    if (!$feeStmt) {
        throw new RuntimeException('Database error: ' . $conn->error);
    }
    $feeStmt->bind_param('is', $year_level, $school_year);
    $feeStmt->execute();
    $feeSchedule = $feeStmt->get_result()->fetch_assoc();
    $feeStmt->close();

    if (!$feeSchedule) {
        throw new RuntimeException(
            "No fee schedule has been set up for Year $year_level, SY $school_year. " .
            "Please ask Treasury to configure it before this student can be enrolled."
        );
    }

    $itemsStmt = $conn->prepare(
        "SELECT label, amount, sort_order, is_per_unit FROM fee_schedule_item
        WHERE fee_schedule_id = ? ORDER BY sort_order"
    );
    if (!$itemsStmt) {
        throw new RuntimeException('Database error: ' . $conn->error);
    }
    $itemsStmt->bind_param('i', $feeSchedule['fee_schedule_id']);
    $itemsStmt->execute();
    $feeItems = $itemsStmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $itemsStmt->close();

    if (empty($feeItems)) {
        throw new RuntimeException(
            "The fee schedule for Year $year_level, SY $school_year has no breakdown items configured."
        );
    }
    // Total units being enrolled — needed to price per-unit fee items
    // (e.g. Tuition Fee is a rate, not a flat amount). Credited subjects
    // are already excluded from $subject_ids by this point, so they're
    // correctly excluded from billed units too.
    $unitsPh    = implode(',', array_fill(0, count($subject_ids), '?'));
    $unitsTypes = str_repeat('i', count($subject_ids));
    $unitsStmt  = $conn->prepare(
        "SELECT COALESCE(SUM(units), 0) FROM subject WHERE subject_id IN ($unitsPh)"
    );
    if (!$unitsStmt) {
        throw new RuntimeException('Database error: ' . $conn->error);
    }
    $unitsStmt->bind_param($unitsTypes, ...$subject_ids);
    $unitsStmt->execute();
    $total_units = (float)$unitsStmt->get_result()->fetch_row()[0];
    $unitsStmt->close();

    if ($total_units <= 0) {
        throw new RuntimeException('Could not determine total units for the selected subjects.');
    }

    // Resolve each fee item's actual charged amount: per-unit rates get
    // multiplied by total_units, flat fees pass through unchanged.
    foreach ($feeItems as &$item) {
        if (!empty($item['is_per_unit'])) {
            $item['amount'] = round((float)$item['amount'] * $total_units, 2);
        } else {
            $item['amount'] = round((float)$item['amount'], 2);
        }
    }
    unset($item);

    // Promote the applicant into the `student` table. `student` is a
    // separate table from `applicants` (its own auto-increment PK),
    // linked back via student.applicant_id. Nothing else in the app
    // ever wrote this row before, which is why enrolled applicants
    // never showed up in the admin enrollment list / dashboard / section
    // rosters — those all join against `student`, not `applicants`.
    // We upsert on applicant_id since re-enrolling in a later term
    // should just update the existing student's section/type, not
    // create a duplicate student row.
    $studentCheck = $conn->prepare(
        "SELECT student_id, student_no FROM student WHERE applicant_id = ? LIMIT 1"
    );
    if (!$studentCheck) {
        throw new RuntimeException('Database error: ' . $conn->error);
    }
    $studentCheck->bind_param('i', $student_id);
    $studentCheck->execute();
    $existingStudent = $studentCheck->get_result()->fetch_assoc();
    $studentCheck->close();

    $applicantStmt = $conn->prepare(
        "SELECT last_name, first_name, middle_name, birth_date, sex,
                contact_number, email, home_address
         FROM applicants WHERE applicant_id = ? LIMIT 1"
    );
    if (!$applicantStmt) {
        throw new RuntimeException('Database error: ' . $conn->error);
    }
    $applicantStmt->bind_param('i', $student_id);
    $applicantStmt->execute();
    $applicant = $applicantStmt->get_result()->fetch_assoc();
    $applicantStmt->close();

    if (!$applicant) {
        throw new RuntimeException('Applicant record not found.');
    }

    $student_name = trim($applicant['first_name'] . ' ' . $applicant['last_name']);

    // Re-enrolling students keep the student number they already have.
    // First-time enrollees get a fresh one — this is the one and only
    // place in the system where an official student ID is minted.
    $student_no = (!empty($existingStudent['student_no']))
        ? $existingStudent['student_no']
        : generate_student_no($conn);

    if ($existingStudent) {
        $upd = $conn->prepare(
            "UPDATE student SET
                student_name = ?, last_name = ?, first_name = ?, middle_name = ?,
                birth_date = ?, sex = ?, contact_number = ?, email = ?, address = ?,
                student_no = ?, section_id = ?, type_id = ?
             WHERE student_id = ?"
        );
        if (!$upd) {
            throw new RuntimeException('Database error: ' . $conn->error);
        }
        $upd->bind_param(
            'ssssssssssiii',
            $student_name, $applicant['last_name'], $applicant['first_name'], $applicant['middle_name'],
            $applicant['birth_date'], $applicant['sex'], $applicant['contact_number'],
            $applicant['email'], $applicant['home_address'], $student_no,
            $section_id, $type_id, $existingStudent['student_id']
        );
        if (!$upd->execute()) {
            $upd->close();
            throw new RuntimeException('Database error: ' . $conn->error);
        }
        $upd->close();
    } else {
        $insStudent = $conn->prepare(
            "INSERT INTO student
                (student_name, last_name, first_name, middle_name, birth_date, sex,
                 contact_number, email, address, student_no, applicant_id, section_id, type_id)
             VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?)"
        );
        if (!$insStudent) {
            throw new RuntimeException('Database error: ' . $conn->error);
        }
        $insStudent->bind_param(
            'ssssssssssiii',
            $student_name, $applicant['last_name'], $applicant['first_name'], $applicant['middle_name'],
            $applicant['birth_date'], $applicant['sex'], $applicant['contact_number'],
            $applicant['email'], $applicant['home_address'], $student_no,
            $student_id, $section_id, $type_id
        );
        if (!$insStudent->execute()) {
            $insStudent->close();
            throw new RuntimeException('Database error: ' . $conn->error);
        }
        $insStudent->close();
    }

    // Insert enrollment. Status starts as "Pending Payment" — the payment
    // row below is auto-created with amount owed, but the student still
    // needs to actually pay; record_payment.php is what moves this to
    // "Enrolled" once treasury records the payment. section_id is NULL
    // for irregular enrollments — their schedule lives per-subject instead.
    $ins = $conn->prepare(
        "INSERT INTO enrollment (student_id, school_year, semester, year_level, section_id, status, type_id)
         VALUES (?, ?, ?, ?, ?, 'Pending Payment', ?)"
    );
    if (!$ins) {
        throw new RuntimeException('Database error: ' . $conn->error);
    }
    $ins->bind_param('isiiii', $student_id, $school_year, $semester, $year_level, $section_id, $type_id);
    if (!$ins->execute()) {
        $ins->close();
        throw new RuntimeException('Database error: ' . $conn->error);
    }
    $enrollment_id = (int)$conn->insert_id;
    $ins->close();

    // Insert subjects. Irregular enrollments carry a per-subject schedule_id
    // (their subjects can come from different sections); Regular/Transferee
    // leave it NULL since their schedule is implied by enrollment.section_id.
    $sub_stmt = $conn->prepare(
        "INSERT INTO enrollment_subject (enrollment_id, subject_id, schedule_id, status) VALUES (?, ?, ?, 'Enrolled')"
    );
    if (!$sub_stmt) {
        throw new RuntimeException('Database error: ' . $conn->error);
    }

    $scheduleBySubject = [];
    if ($is_irregular) {
        foreach ($subject_schedule as $pick) {
            $scheduleBySubject[$pick['subject_id']] = $pick['schedule_id'];
        }
    }

    foreach ($subject_ids as $sid) {
        $schedule_id = $scheduleBySubject[$sid] ?? null;
        $sub_stmt->bind_param('iii', $enrollment_id, $sid, $schedule_id);
        if (!$sub_stmt->execute()) {
            $sub_stmt->close();
            throw new RuntimeException('Database error: ' . $conn->error);
        }
    }
    $sub_stmt->close();

    // Credited subjects also get recorded — not billed, no schedule, but
    // kept on the academic record so it's clear the full curriculum is
    // accounted for, not just what's being paid this term.
    if (!empty($credited_subject_ids)) {
        $credit_sub_stmt = $conn->prepare(
            "INSERT INTO enrollment_subject (enrollment_id, subject_id, schedule_id, status)
             VALUES (?, ?, NULL, 'Credited')"
        );
        if (!$credit_sub_stmt) {
            throw new RuntimeException('Database error: ' . $conn->error);
        }
        foreach ($credited_subject_ids as $sid) {
            $credit_sub_stmt->bind_param('ii', $enrollment_id, $sid);
            if (!$credit_sub_stmt->execute()) {
                $credit_sub_stmt->close();
                throw new RuntimeException('Database error: ' . $conn->error);
            }
        }
        $credit_sub_stmt->close();
    }

    // Auto-create the payment row from the (now per-unit-resolved) fee items
    $amount_due = round(array_sum(array_column($feeItems, 'amount')), 2);
    $due_date   = date('Y-m-d', strtotime('+' . PAYMENT_DUE_DAYS . ' days'));

    $payStmt = $conn->prepare(
        "INSERT INTO payment (enrollment_id, amount_due, downpayment, due_date, payment_status)
         VALUES (?, ?, 0, ?, 'Unpaid')"
    );
    if (!$payStmt) {
        throw new RuntimeException('Database error: ' . $conn->error);
    }
    $payStmt->bind_param('ids', $enrollment_id, $amount_due, $due_date);
    if (!$payStmt->execute()) {
        $payStmt->close();
        throw new RuntimeException('Database error: ' . $conn->error);
    }
    $payment_id = (int)$conn->insert_id;
    $payStmt->close();

    // Snapshot the fee breakdown onto this payment, so later changes to
    // fee_schedule don't retroactively change what this student owes.
    $bdStmt = $conn->prepare(
        "INSERT INTO payment_breakdown (payment_id, label, amount, sort_order) VALUES (?, ?, ?, ?)"
    );
    if (!$bdStmt) {
        throw new RuntimeException('Database error: ' . $conn->error);
    }
    foreach ($feeItems as $item) {
        $bdStmt->bind_param('isdi', $payment_id, $item['label'], $item['amount'], $item['sort_order']);
        if (!$bdStmt->execute()) {
            $bdStmt->close();
            throw new RuntimeException('Database error: ' . $conn->error);
        }
    }
    $bdStmt->close();

    $conn->commit();
    unset($_SESSION['enroll']);

    echo json_encode([
        'success'       => true,
        'enrollment_id' => $enrollment_id,
        'student_no'    => $student_no,
        'payment'       => [
            'payment_id' => $payment_id,
            'amount_due' => $amount_due,
            'due_date'   => $due_date,
            'breakdown'  => $feeItems,
        ],
    ]);

} catch (RuntimeException $e) {
    $conn->rollback();
    $msg = $conn->errno === 1062
        ? 'Student is already enrolled for this term.'
        : $e->getMessage();
    echo json_encode(['error' => $msg]);
} catch (mysqli_sql_exception $e) {
    $conn->rollback();
    $msg = $e->getCode() === 1062
        ? 'Student is already enrolled for this term.'
        : 'A database error occurred. Please try again.';
    echo json_encode(['error' => $msg]);
}

$db->close();