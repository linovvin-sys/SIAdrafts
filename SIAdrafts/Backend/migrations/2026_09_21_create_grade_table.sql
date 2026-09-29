-- Grades, one row per (enrollment_subject_id) -- a student's grade for one
-- specific subject in one specific term. Keyed on enrollment_subject_id
-- rather than student_id/subject_id directly so it automatically inherits
-- the correct term scoping and the professor/schedule ownership already
-- resolved by enrollment_subject, the same join every roster endpoint uses
-- (see Backend/api/Professors/get_professor_roster.php). UNIQUE on
-- enrollment_subject_id means encoding again overwrites, not duplicates --
-- matches the "one grade per class enrollment" mental model.
-- professor_id is kept (not just derived via joins) so a grade still shows
-- who encoded it even if the schedule is later reassigned.

CREATE TABLE `grade` (
  `grade_id` INT NOT NULL AUTO_INCREMENT,
  `enrollment_subject_id` INT NOT NULL,
  `professor_id` INT NOT NULL,
  `grade_value` VARCHAR(10) NOT NULL,
  `remarks` VARCHAR(255) DEFAULT NULL,
  `encoded_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`grade_id`),
  UNIQUE KEY `uq_grade_enrollment_subject` (`enrollment_subject_id`),
  KEY `idx_grade_professor` (`professor_id`),
  CONSTRAINT `fk_grade_enrollment_subject` FOREIGN KEY (`enrollment_subject_id`) REFERENCES `enrollment_subject` (`enrollment_subject_id`) ON DELETE CASCADE,
  CONSTRAINT `fk_grade_professor` FOREIGN KEY (`professor_id`) REFERENCES `professor` (`professor_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
