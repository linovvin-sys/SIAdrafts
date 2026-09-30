-- Quiz feature (MVP): multiple-choice, class-scoped (schedule_id), timed,
-- with a strike-based anti-cheat model. Shape mirrors `assignment` closely
-- (schedule_id/professor_id ownership pair, ON DELETE CASCADE on both)
-- since posting a quiz is the same kind of act as posting an assignment.
CREATE TABLE `quiz` (
  `quiz_id` INT NOT NULL AUTO_INCREMENT,
  `schedule_id` INT NOT NULL,
  `professor_id` INT NOT NULL,
  `title` VARCHAR(150) NOT NULL,
  `instructions` TEXT NULL,
  -- No "untimed quiz" concept in MVP -- the whole anti-cheat model assumes
  -- a per-attempt deadline exists.
  `time_limit_minutes` SMALLINT UNSIGNED NOT NULL,
  -- The professor's scheduling window (separate from the per-attempt
  -- countdown above): when the quiz can be started at all. NULL on either
  -- end means no restriction on that side.
  `available_from` DATETIME NULL,
  `available_until` DATETIME NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`quiz_id`),
  KEY `idx_quiz_schedule` (`schedule_id`),
  KEY `idx_quiz_professor` (`professor_id`),
  CONSTRAINT `fk_quiz_schedule` FOREIGN KEY (`schedule_id`) REFERENCES `schedule` (`schedule_id`) ON DELETE CASCADE,
  CONSTRAINT `fk_quiz_professor` FOREIGN KEY (`professor_id`) REFERENCES `professor` (`professor_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- sort_order is app-managed (not derived from the AUTO_INCREMENT id) so the
-- professor can reorder questions without renumbering ids. points defaults
-- to 1.00 so an all-equal-weight quiz needs no per-question input.
CREATE TABLE `quiz_question` (
  `question_id` INT NOT NULL AUTO_INCREMENT,
  `quiz_id` INT NOT NULL,
  `question_text` TEXT NOT NULL,
  `points` DECIMAL(5,2) NOT NULL DEFAULT 1.00,
  `sort_order` SMALLINT NOT NULL DEFAULT 0,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`question_id`),
  KEY `idx_quiz_question_quiz` (`quiz_id`),
  CONSTRAINT `fk_quiz_question_quiz` FOREIGN KEY (`quiz_id`) REFERENCES `quiz` (`quiz_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- "Exactly one correct choice" is enforced in the data layer
-- (post_question/update_question require exactly one is_correct=1 among
-- the submitted choices), not by a DB constraint -- MySQL has no clean
-- "exactly one TRUE per group" constraint.
CREATE TABLE `quiz_choice` (
  `choice_id` INT NOT NULL AUTO_INCREMENT,
  `question_id` INT NOT NULL,
  `choice_text` VARCHAR(500) NOT NULL,
  `is_correct` TINYINT(1) NOT NULL DEFAULT 0,
  `sort_order` SMALLINT NOT NULL DEFAULT 0,
  PRIMARY KEY (`choice_id`),
  KEY `idx_quiz_choice_question` (`question_id`),
  CONSTRAINT `fk_quiz_choice_question` FOREIGN KEY (`question_id`) REFERENCES `quiz_question` (`question_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- One attempt per (quiz, enrollment_subject) in MVP -- same
-- enrollment_subject_id identity assignment_submission/grade already use
-- (the correctly resolved, term/class-scoped student identity). deadline_at
-- is written once at start_attempt as DATE_ADD(NOW(), INTERVAL ? MINUTE)
-- (capped at the quiz's available_until, if any) and is the sole timer
-- authority -- every subsequent request re-checks the DB's own NOW()
-- against this stored value, never a client-supplied "time remaining."
-- max_score is snapshotted at finalize time (sum of quiz_question.points at
-- that moment) so a professor editing points after a student has already
-- taken the quiz doesn't retroactively change a graded attempt's denominator.
CREATE TABLE `quiz_attempt` (
  `attempt_id` INT NOT NULL AUTO_INCREMENT,
  `quiz_id` INT NOT NULL,
  `enrollment_subject_id` INT NOT NULL,
  `started_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `deadline_at` DATETIME NOT NULL,
  `submitted_at` DATETIME NULL,
  `status` ENUM('in_progress','submitted','auto_submitted_time','auto_submitted_violations') NOT NULL DEFAULT 'in_progress',
  `violation_count` TINYINT UNSIGNED NOT NULL DEFAULT 0,
  `score` DECIMAL(6,2) NULL,
  `max_score` DECIMAL(6,2) NULL,
  PRIMARY KEY (`attempt_id`),
  UNIQUE KEY `uq_attempt_quiz_student` (`quiz_id`, `enrollment_subject_id`),
  KEY `idx_attempt_enrollment_subject` (`enrollment_subject_id`),
  CONSTRAINT `fk_attempt_quiz` FOREIGN KEY (`quiz_id`) REFERENCES `quiz` (`quiz_id`) ON DELETE CASCADE,
  CONSTRAINT `fk_attempt_enrollment_subject` FOREIGN KEY (`enrollment_subject_id`) REFERENCES `enrollment_subject` (`enrollment_subject_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- One row per (attempt, question); UNIQUE key makes every answer
-- selection/change an ON DUPLICATE KEY UPDATE, exactly like
-- assignment_submission's resubmit-overwrites pattern -- no answer version
-- history, just the current selection. is_correct is filled in only at
-- finalize time (never revealed mid-quiz). choice_id is nullable with
-- ON DELETE SET NULL rather than CASCADE so that if a professor edits a
-- question's choices after an attempt exists, the attempt row survives
-- (as an unanswered/needs-review row) instead of silently vanishing.
CREATE TABLE `quiz_attempt_answer` (
  `answer_id` INT NOT NULL AUTO_INCREMENT,
  `attempt_id` INT NOT NULL,
  `question_id` INT NOT NULL,
  `choice_id` INT NULL,
  `is_correct` TINYINT(1) NULL,
  `answered_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`answer_id`),
  UNIQUE KEY `uq_attempt_answer` (`attempt_id`, `question_id`),
  KEY `idx_attempt_answer_attempt` (`attempt_id`),
  CONSTRAINT `fk_attempt_answer_attempt` FOREIGN KEY (`attempt_id`) REFERENCES `quiz_attempt` (`attempt_id`) ON DELETE CASCADE,
  CONSTRAINT `fk_attempt_answer_question` FOREIGN KEY (`question_id`) REFERENCES `quiz_question` (`question_id`) ON DELETE CASCADE,
  CONSTRAINT `fk_attempt_answer_choice` FOREIGN KEY (`choice_id`) REFERENCES `quiz_choice` (`choice_id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Modeled directly on login_attempt (the repo's only existing log table):
-- deliberately NO foreign key on attempt_id, so this audit trail survives
-- the app deleting a quiz/attempt (e.g. professor deletes the quiz after
-- reviewing violations) instead of being silently destroyed by a cascade.
CREATE TABLE `quiz_violation_log` (
  `id` BIGINT NOT NULL AUTO_INCREMENT,
  `violation_type` VARCHAR(20) NOT NULL,
  `attempt_id` INT NOT NULL,
  `ip_address` VARCHAR(45) NOT NULL,
  `user_agent` VARCHAR(255) DEFAULT NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_violation_attempt_created` (`attempt_id`, `created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
