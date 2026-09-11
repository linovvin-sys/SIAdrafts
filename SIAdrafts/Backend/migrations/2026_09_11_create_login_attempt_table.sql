-- Minimal audit trail for logins, separate from rate_limit.php's file-based
-- throttle counters (which only answer "how many recently", not "who/when/
-- from where/which account"). rate_limit.php slows down brute force;
-- this table is what actually lets an incident be reconstructed afterwards.
--
-- No FK to users/professor/student_portal_account: `account_id` spans three
-- unrelated id spaces depending on `login_type`, so it's stored plain and
-- resolved by the reader against the right table using `login_type`. This
-- is a log, not a relational record — rows must survive an account being
-- edited or removed.
CREATE TABLE IF NOT EXISTS `login_attempt` (
  `id` BIGINT NOT NULL AUTO_INCREMENT,
  -- 'staff' (users table), 'professor', or 'student' (student_portal_account).
  `login_type` VARCHAR(20) NOT NULL,
  -- Exactly what was typed into the username/student-no field, whether or
  -- not it matched a real account -- that's the point of an attempt log.
  `identifier` VARCHAR(150) NOT NULL,
  `success` TINYINT(1) NOT NULL,
  -- Resolved account id on success only; NULL on failure since no account
  -- was confirmed to match.
  `account_id` INT DEFAULT NULL,
  `ip_address` VARCHAR(45) NOT NULL,
  `user_agent` VARCHAR(255) DEFAULT NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_la_identifier` (`identifier`),
  KEY `idx_la_ip_created` (`ip_address`, `created_at`),
  KEY `idx_la_created` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
