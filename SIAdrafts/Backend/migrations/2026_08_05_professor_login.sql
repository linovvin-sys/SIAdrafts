-- Gives professors their own portal login credentials, stored directly on
-- the professor record (mirrors the applicant/student side, which already
-- keeps its own login separate from the staff `users` table rather than
-- routing professors through `users`/`roles`).

ALTER TABLE `professor`
  ADD COLUMN `username` VARCHAR(50) NULL AFTER `middle_name`,
  ADD COLUMN `password` VARCHAR(255) NULL AFTER `username`,
  ADD COLUMN `email` VARCHAR(255) NULL AFTER `password`,
  ADD COLUMN `last_login` DATETIME NULL AFTER `email`,
  ADD UNIQUE KEY `uq_professor_username` (`username`),
  ADD UNIQUE KEY `uq_professor_email` (`email`);
