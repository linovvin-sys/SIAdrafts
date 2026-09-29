-- Course materials, one row per item posted to a class (schedule_id).
-- Same ownership model as announcement/assignment: one check on
-- schedule_id -> professor_id. Three content shapes share one table
-- rather than three, since a class's material feed is naturally a single
-- ordered list a student scrolls through, and the shapes differ only in
-- which of file_path/url/body is populated (`type` says which to read).
-- visible_from supports basic drip content: a row with a future date is
-- listed for the professor immediately but withheld from students until
-- that date -- matching the roadmap's "content and scheduling" plan.
CREATE TABLE `class_material` (
  `material_id` INT NOT NULL AUTO_INCREMENT,
  `schedule_id` INT NOT NULL,
  `professor_id` INT NOT NULL,
  `title` VARCHAR(150) NOT NULL,
  `type` ENUM('file','link','text') NOT NULL,
  `file_path` VARCHAR(255) DEFAULT NULL,
  `file_name` VARCHAR(255) DEFAULT NULL,
  `file_type` VARCHAR(100) DEFAULT NULL,
  `url` VARCHAR(500) DEFAULT NULL,
  `body` TEXT DEFAULT NULL,
  `visible_from` DATE DEFAULT NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`material_id`),
  KEY `idx_material_schedule` (`schedule_id`),
  KEY `idx_material_professor` (`professor_id`),
  CONSTRAINT `fk_material_schedule` FOREIGN KEY (`schedule_id`) REFERENCES `schedule` (`schedule_id`) ON DELETE CASCADE,
  CONSTRAINT `fk_material_professor` FOREIGN KEY (`professor_id`) REFERENCES `professor` (`professor_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
