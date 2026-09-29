-- Assignments, one row per class (schedule_id) -- mirrors announcement's
-- shape (same ownership model: one check on schedule_id -> professor_id)
-- since posting an assignment is conceptually the same act as posting an
-- announcement, just with due_date/max_score attached and a file coming
-- back from the student side instead of nothing.
CREATE TABLE `assignment` (
  `assignment_id` INT NOT NULL AUTO_INCREMENT,
  `schedule_id` INT NOT NULL,
  `professor_id` INT NOT NULL,
  `title` VARCHAR(150) NOT NULL,
  `instructions` TEXT NOT NULL,
  `due_date` DATE DEFAULT NULL,
  `max_score` DECIMAL(6,2) DEFAULT NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`assignment_id`),
  KEY `idx_assignment_schedule` (`schedule_id`),
  KEY `idx_assignment_professor` (`professor_id`),
  CONSTRAINT `fk_assignment_schedule` FOREIGN KEY (`schedule_id`) REFERENCES `schedule` (`schedule_id`) ON DELETE CASCADE,
  CONSTRAINT `fk_assignment_professor` FOREIGN KEY (`professor_id`) REFERENCES `professor` (`professor_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- One submission per (assignment, enrollment_subject) -- keyed on
-- enrollment_subject_id rather than student_id for the same reason
-- grade is: it's already the correctly-resolved, term-scoped, class-scoped
-- identity of "this student in this class," so re-using it here avoids
-- re-deriving the applicant_id/student_id join a third time. The unique
-- key means a re-submission overwrites the row (the app deletes the old
-- file from disk when that happens -- see submission_attachments.php) --
-- one current file per student per assignment, not a version history.
CREATE TABLE `assignment_submission` (
  `submission_id` INT NOT NULL AUTO_INCREMENT,
  `assignment_id` INT NOT NULL,
  `enrollment_subject_id` INT NOT NULL,
  `file_path` VARCHAR(255) NOT NULL,
  `file_name` VARCHAR(255) NOT NULL,
  `file_type` VARCHAR(100) NOT NULL,
  `submitted_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `score` DECIMAL(6,2) DEFAULT NULL,
  `feedback` VARCHAR(500) DEFAULT NULL,
  PRIMARY KEY (`submission_id`),
  UNIQUE KEY `uq_submission_assignment_student` (`assignment_id`, `enrollment_subject_id`),
  KEY `idx_submission_enrollment_subject` (`enrollment_subject_id`),
  CONSTRAINT `fk_submission_assignment` FOREIGN KEY (`assignment_id`) REFERENCES `assignment` (`assignment_id`) ON DELETE CASCADE,
  CONSTRAINT `fk_submission_enrollment_subject` FOREIGN KEY (`enrollment_subject_id`) REFERENCES `enrollment_subject` (`enrollment_subject_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
