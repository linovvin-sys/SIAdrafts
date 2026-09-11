-- Tracks every PayMongo Checkout Session we start for a tuition payment.
-- Purpose: idempotency. The return handler (paymongo_return.php) can be hit
-- multiple times (browser refresh, back button), so it looks the row up by
-- local_ref, checks PayMongo once for the real status, and only posts to the
-- payment ledger if this row is not already 'recorded'.
CREATE TABLE IF NOT EXISTS `paymongo_checkout` (
  `id` INT NOT NULL AUTO_INCREMENT,
  -- Opaque ref we put in the success_url instead of the raw cs_ id.
  `local_ref` CHAR(32) NOT NULL,
  -- PayMongo's checkout session id (cs_...).
  `checkout_session_id` VARCHAR(100) NOT NULL,
  `payment_id` INT NOT NULL,
  `amount` DECIMAL(10,2) NOT NULL,
  -- pending  : session created, student not back yet
  -- paid     : PayMongo says paid, but ledger post failed/pending reconciliation
  -- recorded : posted to payment_transactions successfully (terminal)
  -- failed   : PayMongo says failed/expired/cancelled (terminal)
  `status` VARCHAR(20) NOT NULL DEFAULT 'pending',
  `transaction_id` INT DEFAULT NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_pmc_local_ref` (`local_ref`),
  UNIQUE KEY `uq_pmc_session` (`checkout_session_id`),
  KEY `idx_pmc_payment` (`payment_id`),
  CONSTRAINT `fk_pmc_payment` FOREIGN KEY (`payment_id`)
    REFERENCES `payment` (`payment_id`) ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
