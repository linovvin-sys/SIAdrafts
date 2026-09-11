-- RA 10173 (Data Privacy Act) groundwork: a logged, staff-reviewable
-- request rather than an automatic hard delete. Erasure can't just mean
-- "run a DELETE" here -- academic and financial records have their own
-- legitimate recordkeeping requirements that outrank a raw erasure
-- request, so the right technical answer is to make the REQUEST itself
-- reliable and auditable, and leave the actual retention/erasure policy
-- decision to whoever ends up owning institutional compliance.
CREATE TABLE IF NOT EXISTS `data_privacy_request` (
  `id` INT NOT NULL AUTO_INCREMENT,
  `applicant_id` INT NOT NULL,
  `request_type` VARCHAR(20) NOT NULL, -- 'export' | 'erasure'
  `status` VARCHAR(20) NOT NULL DEFAULT 'pending', -- pending | reviewed | completed | denied
  `notes` TEXT DEFAULT NULL,           -- staff-facing notes on how it was resolved
  `reviewed_by` INT DEFAULT NULL,      -- users.user_id of the staff member who resolved it
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_dpr_applicant` (`applicant_id`),
  KEY `idx_dpr_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
