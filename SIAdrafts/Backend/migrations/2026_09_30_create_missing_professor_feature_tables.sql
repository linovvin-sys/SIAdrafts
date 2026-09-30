-- Fills gaps left by the 2026-09-21..28 Professor-portal feature migrations:
-- six tables the shipped PHP code (attendance_data.php, group_data.php,
-- category_data.php, ClassMessaging/*, comment_data.php) queries and
-- writes to, but that no migration in this repo actually created. Found
-- by diffing every table name referenced in the new code against what
-- actually exists in the database — reconstructed here from how each
-- table is used (columns, types, uniqueness) rather than guessed.

-- ---- attendance ----
-- save_attendance_batch() in Backend/Professor/attendance_data.php relies
-- on ON DUPLICATE KEY UPDATE against (schedule_id, applicant_id,
-- session_date) -- that combination MUST be the unique key, not just an
-- index, or a re-save of the same date silently inserts duplicates instead
-- of updating.
CREATE TABLE IF NOT EXISTS `attendance` (
  `attendance_id` INT NOT NULL AUTO_INCREMENT,
  `schedule_id` INT NOT NULL,
  `applicant_id` INT NOT NULL,
  `professor_id` INT NOT NULL,
  `session_date` DATE NOT NULL,
  `status` ENUM('Present','Absent','Late','Excused') NOT NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`attendance_id`),
  UNIQUE KEY `uq_attendance_session` (`schedule_id`, `applicant_id`, `session_date`),
  KEY `idx_attendance_applicant` (`applicant_id`),
  KEY `idx_attendance_professor` (`professor_id`),
  CONSTRAINT `fk_attendance_schedule` FOREIGN KEY (`schedule_id`) REFERENCES `schedule` (`schedule_id`) ON DELETE CASCADE,
  CONSTRAINT `fk_attendance_applicant` FOREIGN KEY (`applicant_id`) REFERENCES `applicants` (`applicant_id`) ON DELETE CASCADE,
  CONSTRAINT `fk_attendance_professor` FOREIGN KEY (`professor_id`) REFERENCES `professor` (`professor_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- ---- class_group / class_group_member ----
-- generate_random_groups() in Backend/Professor/group_data.php deletes and
-- recreates both wholesale each time (not diffed), so no uniqueness beyond
-- the natural (group_id, applicant_id) pairing is needed.
CREATE TABLE IF NOT EXISTS `class_group` (
  `group_id` INT NOT NULL AUTO_INCREMENT,
  `schedule_id` INT NOT NULL,
  `group_name` VARCHAR(100) NOT NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`group_id`),
  KEY `idx_class_group_schedule` (`schedule_id`),
  CONSTRAINT `fk_class_group_schedule` FOREIGN KEY (`schedule_id`) REFERENCES `schedule` (`schedule_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

CREATE TABLE IF NOT EXISTS `class_group_member` (
  `group_member_id` INT NOT NULL AUTO_INCREMENT,
  `group_id` INT NOT NULL,
  `applicant_id` INT NOT NULL,
  PRIMARY KEY (`group_member_id`),
  UNIQUE KEY `uq_group_member` (`group_id`, `applicant_id`),
  KEY `idx_group_member_applicant` (`applicant_id`),
  CONSTRAINT `fk_group_member_group` FOREIGN KEY (`group_id`) REFERENCES `class_group` (`group_id`) ON DELETE CASCADE,
  CONSTRAINT `fk_group_member_applicant` FOREIGN KEY (`applicant_id`) REFERENCES `applicants` (`applicant_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- ---- assignment_category ----
-- Backend/Professor/category_data.php's own comment says
-- "assignment.category_id is ON DELETE SET NULL, so this alone
-- un-categorizes affected assignments rather than deleting them" -- the
-- assignment table itself has no category_id column yet either, so it's
-- added here alongside the table it references.
CREATE TABLE IF NOT EXISTS `assignment_category` (
  `category_id` INT NOT NULL AUTO_INCREMENT,
  `schedule_id` INT NOT NULL,
  `name` VARCHAR(100) NOT NULL,
  `weight_percent` DECIMAL(5,2) NOT NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`category_id`),
  KEY `idx_assignment_category_schedule` (`schedule_id`),
  CONSTRAINT `fk_assignment_category_schedule` FOREIGN KEY (`schedule_id`) REFERENCES `schedule` (`schedule_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

ALTER TABLE `assignment`
  ADD COLUMN `category_id` INT NULL AFTER `schedule_id`,
  ADD KEY `idx_assignment_category` (`category_id`),
  ADD CONSTRAINT `fk_assignment_category` FOREIGN KEY (`category_id`) REFERENCES `assignment_category` (`category_id`) ON DELETE SET NULL;

-- ---- class_message ----
-- Column list and PK name (class_message_id, not message_id) taken from
-- the actual SELECTs in ClassMessaging/get_conversation.php and
-- message_stream.php, not assumed -- message_stream.php's SSE polling
-- loop specifically needs class_message_id as a strictly increasing
-- cursor (`class_message_id > ?`).
CREATE TABLE IF NOT EXISTS `class_message` (
  `class_message_id` INT NOT NULL AUTO_INCREMENT,
  `schedule_id` INT NOT NULL,
  `applicant_id` INT NOT NULL,
  `sender_role` ENUM('professor','student') NOT NULL,
  `body` TEXT NULL,
  `attachment_path` VARCHAR(255) NULL,
  `attachment_name` VARCHAR(255) NULL,
  `attachment_type` VARCHAR(150) NULL,
  `attachment_size` INT NULL,
  `read_at` TIMESTAMP NULL DEFAULT NULL,
  `sent_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`class_message_id`),
  KEY `idx_class_message_schedule_applicant` (`schedule_id`, `applicant_id`),
  CONSTRAINT `fk_class_message_schedule` FOREIGN KEY (`schedule_id`) REFERENCES `schedule` (`schedule_id`) ON DELETE CASCADE,
  CONSTRAINT `fk_class_message_applicant` FOREIGN KEY (`applicant_id`) REFERENCES `applicants` (`applicant_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- ---- submission_comment ----
CREATE TABLE IF NOT EXISTS `submission_comment` (
  `comment_id` INT NOT NULL AUTO_INCREMENT,
  `submission_id` INT NOT NULL,
  `author_role` ENUM('professor','student') NOT NULL,
  `body` TEXT NOT NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`comment_id`),
  KEY `idx_submission_comment_submission` (`submission_id`),
  CONSTRAINT `fk_submission_comment_submission` FOREIGN KEY (`submission_id`) REFERENCES `assignment_submission` (`submission_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
