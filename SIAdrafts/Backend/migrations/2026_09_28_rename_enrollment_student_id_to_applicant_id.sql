-- `enrollment.student_id` has always been a foreign key into `applicants`
-- (fk_enroll_applicant -> applicants.applicant_id), never into `student`.
-- The name was misleading: two other tables (`readmission_request`,
-- `unpaid_students`) also have a column literally named `student_id`, but
-- theirs correctly references `student.student_id` -- same name, two
-- different tables, a silent trap for any future join. Renaming this one
-- to match what it actually points to. The FK and the uq_enrollment unique
-- index follow the column automatically; no drop/recreate needed.
ALTER TABLE enrollment CHANGE COLUMN student_id applicant_id INT(11) NOT NULL;
