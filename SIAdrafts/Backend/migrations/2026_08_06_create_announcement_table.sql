-- Class announcements: a professor posts a note tied to one specific class
-- (schedule_id), not a whole section or a blanket broadcast -- this keeps
-- authorization simple (one ownership check: does this schedule_id belong
-- to the posting professor) and means term scoping comes for free, since
-- `schedule` already carries school_year/semester. No read-tracking table
-- and no edit support by design -- delete-only, matching how little else
-- in this app needs a full audit trail for a professor's own notes.

CREATE TABLE `announcement` (
  `announcement_id` INT NOT NULL AUTO_INCREMENT,
  `schedule_id` INT NOT NULL,
  `professor_id` INT NOT NULL,
  `title` VARCHAR(150) NOT NULL,
  `body` TEXT NOT NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`announcement_id`),
  KEY `idx_announcement_schedule` (`schedule_id`),
  KEY `idx_announcement_professor` (`professor_id`),
  CONSTRAINT `fk_announcement_schedule` FOREIGN KEY (`schedule_id`) REFERENCES `schedule` (`schedule_id`) ON DELETE CASCADE,
  CONSTRAINT `fk_announcement_professor` FOREIGN KEY (`professor_id`) REFERENCES `professor` (`professor_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
