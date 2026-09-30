-- Supports auto-generating quiz questions from an uploaded lesson file
-- (cloze/fill-in-the-blank, no AI) and dealing each attempt a random
-- subset of a larger generated pool.

ALTER TABLE `quiz`
  ADD COLUMN `status` ENUM('draft','published') NOT NULL DEFAULT 'draft' AFTER `instructions`,
  ADD COLUMN `questions_per_attempt` SMALLINT UNSIGNED NULL AFTER `time_limit_minutes`;

-- Don't retroactively hide a quiz already in use just because this gate
-- is new -- anything that already has questions was already reachable by
-- students under the old "visible once it has >=1 question" rule.
UPDATE `quiz` SET `status` = 'published'
WHERE `quiz_id` IN (SELECT DISTINCT `quiz_id` FROM `quiz_question`);

-- Records exactly which questions, in what order, were dealt to one
-- attempt. Populated for EVERY attempt going forward (not only when a
-- random subset is used) -- this also closes a latent correctness gap:
-- previously finalize_attempt() summed ALL of a quiz's question points as
-- max_score, so a professor editing the question list mid-attempt could
-- silently shift an in-progress student's denominator. Locking the set at
-- start_attempt() time fixes that for every quiz, not just generated ones.
CREATE TABLE `quiz_attempt_question` (
  `id` INT NOT NULL AUTO_INCREMENT,
  `attempt_id` INT NOT NULL,
  `question_id` INT NOT NULL,
  `sort_order` SMALLINT NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_attempt_question` (`attempt_id`, `question_id`),
  KEY `idx_attempt_question_attempt` (`attempt_id`),
  CONSTRAINT `fk_attempt_question_attempt` FOREIGN KEY (`attempt_id`) REFERENCES `quiz_attempt` (`attempt_id`) ON DELETE CASCADE,
  CONSTRAINT `fk_attempt_question_question` FOREIGN KEY (`question_id`) REFERENCES `quiz_question` (`question_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Backfill so already-existing attempts (submitted or in-progress) keep
-- working once the read path switches to this table.
INSERT INTO `quiz_attempt_question` (`attempt_id`, `question_id`, `sort_order`)
SELECT qa.`attempt_id`, qq.`question_id`, qq.`sort_order`
FROM `quiz_attempt` qa
JOIN `quiz_question` qq ON qq.`quiz_id` = qa.`quiz_id`;
