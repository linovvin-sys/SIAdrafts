<?php

// Moves enrollments that missed their payment window into `unpaid_students`.
// "Missed" means: due_date has passed AND the student never made even a
// downpayment (downpayment = 0). A partial downpayment already paid keeps
// a student in the normal Process queue, past due or not — only a
// completely unpaid enrollment gets flagged here.
//
// There's no cron/scheduler in this app, so this runs lazily: every
// Treasury-area page calls it once on load, which is enough to keep the
// table current since Treasury staff visit these pages daily.
function transfer_overdue_unpaid(mysqli $conn): void
{
    $conn->query("
        INSERT INTO unpaid_students
            (enrollment_id, student_id, payment_id, school_year, semester, amount_due, downpayment, balance, due_date)
        SELECT e.enrollment_id, s.student_id, p.payment_id, e.school_year, e.semester,
               p.amount_due, p.downpayment, p.balance, p.due_date
        FROM payment p
        JOIN enrollment e ON e.enrollment_id = p.enrollment_id
        JOIN student s    ON s.applicant_id  = e.student_id
        WHERE p.downpayment = 0
          AND p.balance > 0
          AND p.due_date < CURDATE()
          AND NOT EXISTS (
              SELECT 1 FROM unpaid_students u WHERE u.payment_id = p.payment_id
          )
    ");
}
