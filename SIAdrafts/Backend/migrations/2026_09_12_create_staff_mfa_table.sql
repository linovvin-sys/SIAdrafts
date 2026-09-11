-- TOTP-based MFA for staff (`users` table) and professor accounts, kept as
-- its own table rather than columns added to `users`/`professor` because
-- one row shape needs to serve two otherwise-unrelated id spaces (see
-- login_attempt's migration for the same reasoning) -- `login_type` says
-- which table `account_id` refers to.
--
-- Opt-in: a row only exists once an account has actually enrolled via
-- mfa_setup_confirm.php. `enabled = 0` rows are a secret that's been
-- generated but not yet confirmed with a real code -- treated as "MFA off"
-- everywhere except the confirm step itself, so an abandoned setup attempt
-- can never accidentally start being enforced.
CREATE TABLE IF NOT EXISTS `staff_mfa` (
  `id` INT NOT NULL AUTO_INCREMENT,
  `login_type` VARCHAR(20) NOT NULL, -- 'staff' or 'professor'
  `account_id` INT NOT NULL,
  -- Base32 TOTP secret. Not encrypted at rest (matches how this app
  -- already treats other sensitive columns, e.g. password hashes are the
  -- only field given that treatment) -- flagged as a follow-up, not
  -- blocking MFA shipping.
  `secret` VARCHAR(64) NOT NULL,
  -- JSON array of bcrypt-hashed single-use recovery codes. Hashed the
  -- same way passwords are, since a recovery code IS a password-
  -- equivalent credential.
  `recovery_codes` TEXT DEFAULT NULL,
  `enabled` TINYINT(1) NOT NULL DEFAULT 0,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_staff_mfa_account` (`login_type`, `account_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
