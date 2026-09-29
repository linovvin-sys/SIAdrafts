-- Splits a single grade per class into one grade per grading period
-- (Prelim/Midterm/Prefinal/Final), matching how PH college terms are
-- actually graded piece by piece rather than with one end-of-term mark.
-- The old single-grade-per-class row becomes this term's Final entry --
-- DEFAULT 'Final' on the new column preserves that meaning for any row
-- already on file before this migration, without needing a data backfill
-- statement. The unique key widens to (enrollment_subject_id, period) so
-- a student can now hold up to 4 grade rows per class instead of 1.

ALTER TABLE `grade`
  ADD COLUMN `period` ENUM('Prelim','Midterm','Prefinal','Final') NOT NULL DEFAULT 'Final' AFTER `professor_id`,
  DROP INDEX `uq_grade_enrollment_subject`,
  ADD UNIQUE KEY `uq_grade_enrollment_subject_period` (`enrollment_subject_id`, `period`);
